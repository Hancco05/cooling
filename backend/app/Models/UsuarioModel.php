<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'nombre', 'email', 'password', 'rol', 'activo', 
        'api_token', 'token_expira'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'creado_en';
    protected $updatedField = 'actualizado_en';
    
    protected $validationRules = [
        'nombre' => 'required|min_length[3]|max_length[100]',
        'email' => 'required|valid_email|is_unique[usuarios.email,id,{id}]',
        'password' => 'required|min_length[6]',
        'rol' => 'required|in_list[admin,user,tech]',
    ];
    
    protected $validationMessages = [];
    protected $skipValidation = false;
    
    // Encriptar password antes de insertar
    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];
    
    protected function hashPassword(array $data)
    {
        if (isset($data['data']['password'])) {
            $data['data']['password'] = password_hash($data['data']['password'], PASSWORD_DEFAULT);
        }
        return $data;
    }
    
    // Verificar credenciales
    public function verificarCredenciales($email, $password)
    {
        $usuario = $this->where('email', $email)
                       ->where('activo', 1)
                       ->first();
        
        if (!$usuario) {
            return false;
        }
        
        if (password_verify($password, $usuario->password)) {
            return $usuario;
        }
        
        return false;
    }
    
    // Generar token API
    public function generarToken($usuarioId)
    {
        $token = bin2hex(random_bytes(32));
        $expira = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        $this->update($usuarioId, [
            'api_token' => $token,
            'token_expira' => $expira
        ]);
        
        return [
            'token' => $token,
            'expira' => $expira
        ];
    }
    
    // Validar token
    public function validarToken($token)
    {
        return $this->where('api_token', $token)
                   ->where('token_expira >', date('Y-m-d H:i:s'))
                   ->where('activo', 1)
                   ->first();
    }
}