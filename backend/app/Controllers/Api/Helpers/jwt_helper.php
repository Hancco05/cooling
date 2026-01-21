<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('generateJWT')) {
    function generateJWT($payload, $expireHours = 24)
    {
        $key = getenv('JWT_SECRET_KEY') ?: 'cooling_system_secret_key_2024';
        
        $payload['iat'] = time();
        $payload['exp'] = time() + ($expireHours * 3600);
        
        return JWT::encode($payload, $key, 'HS256');
    }
}

if (!function_exists('verifyJWT')) {
    function verifyJWT($token)
    {
        try {
            $key = getenv('JWT_SECRET_KEY') ?: 'cooling_system_secret_key_2024';
            
            return JWT::decode($token, new Key($key, 'HS256'));
        } catch (\Exception $e) {
            throw new \Exception('Token inválido: ' . $e->getMessage());
        }
    }
}

if (!function_exists('validateApiKey')) {
    function validateApiKey($apiKey)
    {
        $validKey = getenv('COOLING_API_KEY') ?: 'cooling_default_api_key';
        
        return hash_equals($validKey, $apiKey);
    }
}