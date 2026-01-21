<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CoolingSeeder extends Seeder
{
    public function run()
    {
        // Usuarios de prueba
        $usuarios = [
            [
                'nombre' => 'Admin Cooling',
                'email' => 'admin@cooling.com',
                'password' => password_hash('Cooling@2024', PASSWORD_DEFAULT),
                'rol' => 'admin',
                'api_token' => bin2hex(random_bytes(32))
            ],
            [
                'nombre' => 'Juan Pérez',
                'email' => 'juan@empresa.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'rol' => 'user'
            ],
            [
                'nombre' => 'María López',
                'email' => 'maria@empresa.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'rol' => 'tech'
            ]
        ];
        
        $this->db->table('usuarios')->insertBatch($usuarios);
        
        // Dispositivos de prueba
        $dispositivos = [
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440001',
                'nombre' => 'Aire Acondicionado Principal',
                'tipo' => 'aire_acondicionado',
                'marca' => 'LG',
                'modelo' => 'AC-5000',
                'temperatura_actual' => 22.5,
                'temperatura_min' => 18,
                'temperatura_max' => 26,
                'humedad_actual' => 45,
                'estado' => 'encendido',
                'consumo_energia' => 1500,
                'ubicacion' => 'Oficina Principal',
                'latitud' => 19.432608,
                'longitud' => -99.133209,
                'usuario_id' => 1,
                'configuracion' => json_encode([
                    'intervalo_lectura' => 300,
                    'temperatura_minima' => 18,
                    'temperatura_maxima' => 26,
                    'modo' => 'enfriamiento',
                    'velocidad_ventilador' => 'media'
                ])
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440002',
                'nombre' => 'Refrigerador Laboratorio',
                'tipo' => 'refrigerador',
                'marca' => 'Mabe',
                'modelo' => 'RF-350',
                'temperatura_actual' => 4.2,
                'temperatura_min' => 2,
                'temperatura_max' => 8,
                'estado' => 'encendido',
                'consumo_energia' => 300,
                'ubicacion' => 'Laboratorio A',
                'usuario_id' => 1,
                'configuracion' => json_encode([
                    'intervalo_lectura' => 600,
                    'temperatura_minima' => 2,
                    'temperatura_maxima' => 8,
                    'alarma_temperatura' => true
                ])
            ],
            [
                'uuid' => '550e8400-e29b-41d4-a716-446655440003',
                'nombre' => 'Ventilador Sala Servidores',
                'tipo' => 'ventilador',
                'marca' => 'Delta',
                'modelo' => 'VF-2000',
                'temperatura_actual' => 18.5,
                'estado' => 'encendido',
                'consumo_energia' => 250,
                'ubicacion' => 'Sala de Servidores',
                'usuario_id' => 1,
                'configuracion' => json_encode([
                    'intervalo_lectura' => 300,
                    'rpm_max' => 2000,
                    'control_temperatura' => true
                ])
            ]
        ];
        
        $this->db->table('dispositivos')->insertBatch($dispositivos);
        
        echo "✅ Datos de prueba para Cooling insertados correctamente\n";
    }
}