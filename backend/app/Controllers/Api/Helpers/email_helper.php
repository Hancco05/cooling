<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendCoolingEmail')) {
    function sendCoolingEmail($to, $subject, $body, $altBody = '', $attachments = [])
    {
        $mail = new PHPMailer(true);
        
        try {
            // Configuración del servidor
            $mail->isSMTP();
            $mail->Host       = getenv('EMAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = getenv('EMAIL_USER') ?: 'cooling.system@example.com';
            $mail->Password   = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = getenv('EMAIL_PORT') ?: 587;
            $mail->CharSet    = 'UTF-8';
            
            // Remitente y destinatario
            $mail->setFrom(
                getenv('EMAIL_FROM') ?: 'cooling.system@example.com',
                getenv('EMAIL_FROM_NAME') ?: 'Cooling System'
            );
            $mail->addAddress($to);
            
            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody ?: strip_tags($body);
            
            // Adjuntos
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment['path'], $attachment['name']);
            }
            
            return $mail->send();
            
        } catch (Exception $e) {
            log_message('error', 'Error enviando email: ' . $mail->ErrorInfo);
            return false;
        }
    }
}

if (!function_exists('sendTemperatureAlert')) {
    function sendTemperatureAlert($device, $temperature, $limit, $type = 'high')
    {
        $subject = $type === 'high' 
            ? "⚠️ ALERTA: Temperatura ALTA en {$device->nombre}" 
            : "⚠️ ALERTA: Temperatura BAJA en {$device->nombre}";
        
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
                .container { max-width: 600px; background: white; border-radius: 10px; padding: 30px; margin: 0 auto; }
                .header { background: linear-gradient(90deg, #ff6b6b, #ff8e53); color: white; padding: 20px; border-radius: 10px; text-align: center; }
                .content { padding: 20px; }
                .device-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .temperature { font-size: 2em; font-weight: bold; color: #ff6b6b; text-align: center; margin: 20px 0; }
                .footer { text-align: center; margin-top: 30px; color: #666; font-size: 0.9em; }
                .btn { display: inline-block; background: #4facfe; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>🚨 ALERTA DEL SISTEMA COOLING</h2>
                </div>
                <div class="content">
                    <h3>' . ($type === 'high' ? '🌡️ TEMPERATURA ALTA DETECTADA' : '❄️ TEMPERATURA BAJA DETECTADA') . '</h3>
                    
                    <div class="device-info">
                        <strong>Dispositivo:</strong> ' . $device->nombre . '<br>
                        <strong>Ubicación:</strong> ' . ($device->ubicacion ?? 'No especificada') . '<br>
                        <strong>Tipo:</strong> ' . $device->tipo . '<br>
                        <strong>ID:</strong> ' . $device->uuid . '
                    </div>
                    
                    <div class="temperature">
                        ' . $temperature . '°C
                    </div>
                    
                    <p><strong>Límite configurado:</strong> ' . $limit . '°C</p>
                    <p><strong>Tipo de alerta:</strong> ' . ($type === 'high' ? 'Temperatura sobre el límite máximo' : 'Temperatura bajo el límite mínimo') . '</p>
                    <p><strong>Fecha y hora:</strong> ' . date('d/m/Y H:i:s') . '</p>
                    
                    <div style="text-align: center; margin: 25px 0;">
                        <a href="' . getenv('app.baseURL') . 'devices/' . $device->id . '" class="btn">
                            👁️ VER DISPOSITIVO
                        </a>
                    </div>
                    
                    <p><small>Esta alerta fue generada automáticamente por el sistema Cooling.</small></p>
                </div>
                <div class="footer">
                    Cooling System © ' . date('Y') . ' - Monitoreo de temperatura inteligente
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Obtener usuarios a notificar
        $userModel = new \App\Models\UsuarioModel();
        $users = $userModel->where('activo', 1)->findAll();
        
        $sentCount = 0;
        foreach ($users as $user) {
            if (sendCoolingEmail($user->email, $subject, $body)) {
                $sentCount++;
            }
        }
        
        return $sentCount;
    }
}