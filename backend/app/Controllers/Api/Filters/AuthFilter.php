<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\UsuarioModel;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Rutas que no requieren autenticación
        $publicRoutes = [
            'api/auth/login',
            'api/auth/register',
            'api/docs',
            'api/status'
        ];
        
        $uri = $request->uri->getPath();
        
        // Verificar si es ruta pública
        foreach ($publicRoutes as $route) {
            if (strpos($uri, $route) === 0) {
                return;
            }
        }
        
        // Verificar token
        $token = $request->getHeaderLine('Authorization');
        
        if (empty($token)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Token de autenticación requerido'
                ]);
        }
        
        $token = str_replace('Bearer ', '', $token);
        
        $usuarioModel = new UsuarioModel();
        $usuario = $usuarioModel->validarToken($token);
        
        if (!$usuario) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'success' => false,
                    'message' => 'Token inválido o expirado'
                ]);
        }
        
        // Agregar usuario a la request
        $request->usuario = $usuario;
    }
    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Loggear respuesta si es necesario
        if ($response->getStatusCode() >= 400) {
            log_message('error', 'Error API: ' . $response->getBody());
        }
        
        return $response;
    }
}