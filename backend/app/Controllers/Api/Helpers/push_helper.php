<?php

use GuzzleHttp\Client;

if (!function_exists('sendPushNotification')) {
    function sendPushNotification($playerIds, $title, $message, $data = [])
    {
        $client = new Client();
        
        // Configuración para OneSignal
        $appId = getenv('ONESIGNAL_APP_ID');
        $apiKey = getenv('ONESIGNAL_API_KEY');
        
        if (!$appId || !$apiKey) {
            log_message('error', 'OneSignal credentials not configured');
            return false;
        }
        
        $notificationData = [
            'app_id' => $appId,
            'contents' => ['en' => $message],
            'headings' => ['en' => $title],
            'data' => $data,
            'url' => $data['url'] ?? null,
        ];
        
        // Si se especifican players específicos
        if (!empty($playerIds)) {
            $notificationData['include_player_ids'] = is_array($playerIds) ? $playerIds : [$playerIds];
        } else {
            // Enviar a todos los suscriptores
            $notificationData['included_segments'] = ['Subscribed Users'];
        }
        
        try {
            $response = $client->post('https://onesignal.com/api/v1/notifications', [
                'headers' => [
                    'Authorization' => 'Basic ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $notificationData
            ]);
            
            $result = json_decode($response->getBody(), true);
            
            if (isset($result['id'])) {
                log_message('info', 'Push notification sent: ' . $result['id']);
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            log_message('error', 'Error sending push notification: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendTemperaturePushAlert')) {
    function sendTemperaturePushAlert($device, $temperature, $limit, $type = 'high')
    {
        $title = $type === 'high' 
            ? "🌡️ Temperatura ALTA: {$device->nombre}" 
            : "❄️ Temperatura BAJA: {$device->nombre}";
        
        $message = $type === 'high'
            ? "Temperatura: {$temperature}°C (Límite: {$limit}°C)"
            : "Temperatura: {$temperature}°C (Límite: {$limit}°C)";
        
        $data = [
            'type' => 'temperature_alert',
            'device_id' => $device->id,
            'device_name' => $device->nombre,
            'temperature' => $temperature,
            'limit' => $limit,
            'alert_type' => $type,
            'timestamp' => time(),
            'url' => getenv('app.baseURL') . 'devices/' . $device->id
        ];
        
        // En producción, obtendrías los player_ids de la base de datos
        return sendPushNotification([], $title, $message, $data);
    }
}