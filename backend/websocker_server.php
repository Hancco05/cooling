<?php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSockets\CoolingServer;

// Configurar servidor WebSocket
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new CoolingServer()
        )
    ),
    8082 // Puerto para WebSockets
);

echo "✅ Servidor WebSocket Cooling iniciado en puerto 8082\n";
echo "📡 Escuchando conexiones...\n";

$server->run();