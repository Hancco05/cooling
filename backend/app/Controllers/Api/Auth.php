<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UsuarioModel;

class Auth extends ResourceController
{
    use ResponseTrait;
    
    protected $usuarioModel;
    
    public function __construct()
    {
        $this->usuarioModel = new UsuarioModel();
        helper('jwt'); // Helper personalizado para JWT
    }
    
    /**
     * POST /api/auth/login
     * Login de usuario
     */
    public function login()
    {
        // Validar entrada
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $email = $this->request->getVar('email');
        $password = $this->request->getVar('password');
        
        // Verificar credenciales
        $usuario = $this->usuarioModel->verificarCredenciales($email, $password);
        
        if (!$usuario) {
            return $this->failUnauthorized('Credenciales incorrectas');
        }
        
        // Generar token JWT
        $token = generateJWT([
            'id' => $usuario->id,
            'email' => $usuario->email,
            'rol' => $usuario->rol,
            'nombre' => $usuario->nombre
        ]);
        
        // Actualizar último login
        $this->usuarioModel->update($usuario->id, [
            'ultimo_login' => date('Y-m-d H:i:s')
        ]);
        
        return $this->respond([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'token' => $token,
                'usuario' => [
                    'id' => $usuario->id,
                    'nombre' => $usuario->nombre,
                    'email' => $usuario->email,
                    'rol' => $usuario->rol
                ]
            ]
        ]);
    }
    
    /**
     * POST /api/auth/register
     * Registrar nuevo usuario
     */
    public function register()
    {
        // Validar entrada
        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[usuarios.email]',
            'password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        // Crear usuario
        $data = [
            'nombre' => $this->request->getVar('nombre'),
            'email' => $this->request->getVar('email'),
            'password' => $this->request->getVar('password'),
            'rol' => 'user' // Rol por defecto
        ];
        
        try {
            $usuarioId = $this->usuarioModel->insert($data);
            
            // Generar token para el nuevo usuario
            $tokenData = $this->usuarioModel->generarToken($usuarioId);
            
            return $this->respondCreated([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'data' => [
                    'token' => $tokenData['token'],
                    'token_expira' => $tokenData['expira'],
                    'usuario' => [
                        'id' => $usuarioId,
                        'nombre' => $data['nombre'],
                        'email' => $data['email'],
                        'rol' => $data['rol']
                    ]
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->failServerError('Error al registrar usuario: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/auth/logout
     * Cerrar sesión
     */
    public function logout()
    {
        $token = $this->request->getHeaderLine('Authorization');
        
        if (!empty($token)) {
            // Invalidar token en base de datos
            $usuario = $this->usuarioModel->validarToken(str_replace('Bearer ', '', $token));
            if ($usuario) {
                $this->usuarioModel->update($usuario->id, [
                    'api_token' => null,
                    'token_expira' => null
                ]);
            }
        }
        
        return $this->respond([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }
    
    /**
     * GET /api/auth/me
     * Obtener información del usuario actual
     */
    public function me()
    {
        $usuario = $this->getUsuarioAutenticado();
        
        return $this->respond([
            'success' => true,
            'data' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'email' => $usuario->email,
                'rol' => $usuario->rol,
                'creado_en' => $usuario->creado_en
            ]
        ]);
    }
    
    /**
     * Helper para obtener usuario autenticado
     */
    private function getUsuarioAutenticado()
    {
        $token = $this->request->getHeaderLine('Authorization');
        
        if (empty($token)) {
            return $this->failUnauthorized('Token no proporcionado');
        }
        
        $token = str_replace('Bearer ', '', $token);
        $usuario = $this->usuarioModel->validarToken($token);
        
        if (!$usuario) {
            return $this->failUnauthorized('Token inválido o expirado');
        }
        
        return $usuario;
    }
}