<?php

namespace App\WebSockets;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use App\Models\DispositivoModel;

class CoolingServer implements MessageComponentInterface
{
    protected $clients;
    protected $dispositivoModel;
    private $subscriptions = [];

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->dispositivoModel = new DispositivoModel();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "Nueva conexión: {$conn->resourceId}\n";
        
        $conn->send(json_encode([
            'type' => 'connection',
            'message' => 'Conectado al servidor Cooling',
            'clients' => count($this->clients)
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        
        if (!$data) {
            $from->send(json_encode(['error' => 'Mensaje JSON inválido']));
            return;
        }

        switch ($data['action']) {
            case 'subscribe':
                $this->handleSubscribe($from, $data);
                break;
                
            case 'unsubscribe':
                $this->handleUnsubscribe($from, $data);
                break;
                
            case 'update_temperature':
                $this->handleTemperatureUpdate($from, $data);
                break;
                
            case 'command':
                $this->handleCommand($from, $data);
                break;
                
            default:
                $from->send(json_encode(['error' => 'Acción no reconocida']));
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Conexión cerrada: {$conn->resourceId}\n";
        
        // Limpiar suscripciones
        foreach ($this->subscriptions as $channel => &$subscribers) {
            $subscribers = array_filter($subscribers, function($client) use ($conn) {
                return $client !== $conn;
            });
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    private function handleSubscribe(ConnectionInterface $client, $data)
    {
        if (!isset($data['channel'])) {
            $client->send(json_encode(['error' => 'Canal no especificado']));
            return;
        }

        $channel = $data['channel'];
        
        if (!isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = [];
        }

        $this->subscriptions[$channel][] = $client;
        
        $client->send(json_encode([
            'type' => 'subscribed',
            'channel' => $channel,
            'message' => 'Suscrito al canal: ' . $channel
        ]));
    }

    private function handleUnsubscribe(ConnectionInterface $client, $data)
    {
        if (!isset($data['channel'])) {
            return;
        }

        $channel = $data['channel'];
        
        if (isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = array_filter(
                $this->subscriptions[$channel],
                function($c) use ($client) {
                    return $c !== $client;
                }
            );
        }
    }

    private function handleTemperatureUpdate($from, $data)
    {
        if (!isset($data['device_id']) || !isset($data['temperature'])) {
            $from->send(json_encode(['error' => 'Datos incompletos']));
            return;
        }

        // Actualizar en base de datos
        try {
            $this->dispositivoModel->actualizarTemperatura(
                $data['device_id'],
                $data['temperature'],
                $data['humidity'] ?? null
            );

            // Notificar a todos los suscritos al dispositivo
            $channel = 'device_' . $data['device_id'];
            $this->broadcastToChannel($channel, [
                'type' => 'temperature_update',
                'device_id' => $data['device_id'],
                'temperature' => $data['temperature'],
                'humidity' => $data['humidity'] ?? null,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // Verificar alertas
            $this->checkAlerts($data['device_id'], $data['temperature']);

        } catch (\Exception $e) {
            $from->send(json_encode(['error' => $e->getMessage()]));
        }
    }

    private function handleCommand($from, $data)
    {
        if (!isset($data['device_id']) || !isset($data['command'])) {
            $from->send(json_encode(['error' => 'Comando inválido']));
            return;
        }

        $deviceId = $data['device_id'];
        $command = $data['command'];
        
        // Ejecutar comando (simulado - en producción se enviaría al dispositivo real)
        $response = [
            'type' => 'command_response',
            'device_id' => $deviceId,
            'command' => $command,
            'status' => 'success',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $from->send(json_encode($response));

        // Notificar a otros clientes
        $this->broadcastToChannel('device_' . $deviceId, [
            'type' => 'device_status',
            'device_id' => $deviceId,
            'status' => $command === 'encender' ? 'encendido' : 'apagado',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    private function checkAlerts($deviceId, $temperature)
    {
        // Obtener configuración del dispositivo
        $device = $this->dispositivoModel->find($deviceId);
        
        if (!$device) return;

        $config = json_decode($device->configuracion, true) ?? [];
        
        // Verificar límites de temperatura
        if (isset($config['temperature_max']) && $temperature > $config['temperature_max']) {
            $this->broadcastToChannel('alerts', [
                'type' => 'alert',
                'level' => 'warning',
                'device_id' => $deviceId,
                'device_name' => $device->nombre,
                'message' => "Temperatura ALTA: {$temperature}°C",
                'temperature' => $temperature,
                'limit' => $config['temperature_max'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }

        if (isset($config['temperature_min']) && $temperature < $config['temperature_min']) {
            $this->broadcastToChannel('alerts', [
                'type' => 'alert',
                'level' => 'warning',
                'device_id' => $deviceId,
                'device_name' => $device->nombre,
                'message' => "Temperatura BAJA: {$temperature}°C",
                'temperature' => $temperature,
                'limit' => $config['temperature_min'],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    private function broadcastToChannel($channel, $message)
    {
        if (!isset($this->subscriptions[$channel])) {
            return;
        }

        $jsonMessage = json_encode($message);
        
        foreach ($this->subscriptions[$channel] as $client) {
            $client->send($jsonMessage);
        }
    }

    // Método para enviar notificaciones desde la API
    public static function broadcast($channel, $message)
    {
        // Esta función sería llamada desde los controladores
        // Para una implementación completa, necesitarías un sistema de mensajería
    }
}