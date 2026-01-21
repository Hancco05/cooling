<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCoolingTables extends Migration
{
    public function up()
    {
        // Tabla de usuarios
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => '150',
                'unique' => true,
                'null' => false,
            ],
            'password' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => false,
            ],
            'rol' => [
                'type' => 'ENUM',
                'constraint' => ['admin', 'user', 'tech'],
                'default' => 'user',
            ],
            'activo' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'api_token' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
            ],
            'token_expira' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'actualizado_en' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'on update' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('usuarios');

        // Tabla de dispositivos
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type' => 'VARCHAR',
                'constraint' => '36',
                'unique' => true,
                'null' => false,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => false,
            ],
            'tipo' => [
                'type' => 'ENUM',
                'constraint' => ['aire_acondicionado', 'ventilador', 'refrigerador', 'chiller', 'torre_enfriamiento', 'otro'],
                'default' => 'aire_acondicionado',
            ],
            'marca' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'modelo' => [
                'type' => 'VARCHAR',
                'constraint' => '50',
                'null' => true,
            ],
            'temperatura_actual' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'temperatura_min' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'temperatura_max' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'humedad_actual' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['encendido', 'apagado', 'standby', 'mantenimiento', 'falla'],
                'default' => 'apagado',
            ],
            'consumo_energia' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Consumo en kWh',
            ],
            'ubicacion' => [
                'type' => 'VARCHAR',
                'constraint' => '200',
                'null' => true,
            ],
            'latitud' => [
                'type' => 'DECIMAL',
                'constraint' => '10,8',
                'null' => true,
            ],
            'longitud' => [
                'type' => 'DECIMAL',
                'constraint' => '11,8',
                'null' => true,
            ],
            'usuario_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
            ],
            'configuracion' => [
                'type' => 'JSON',
                'null' => true,
                'comment' => 'Configuración específica del dispositivo',
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'actualizado_en' => [
                'type' => 'TIMESTAMP',
                'null' => true,
                'on update' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('usuario_id');
        $this->forge->addKey('estado');
        $this->forge->addForeignKey('usuario_id', 'usuarios', 'id', 'SET NULL', 'SET NULL');
        $this->forge->createTable('dispositivos');

        // Tabla de lecturas
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'dispositivo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'temperatura' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => false,
            ],
            'humedad' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'presion' => [
                'type' => 'DECIMAL',
                'constraint' => '7,2',
                'null' => true,
                'comment' => 'Presión en kPa',
            ],
            'consumo' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'comment' => 'Consumo instantáneo en Watts',
            ],
            'voltaje' => [
                'type' => 'DECIMAL',
                'constraint' => '7,2',
                'null' => true,
            ],
            'corriente' => [
                'type' => 'DECIMAL',
                'constraint' => '7,2',
                'null' => true,
            ],
            'fecha_hora' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dispositivo_id', 'fecha_hora']);
        $this->forge->addForeignKey('dispositivo_id', 'dispositivos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('lecturas');

        // Tabla de alertas
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'dispositivo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tipo' => [
                'type' => 'ENUM',
                'constraint' => ['temperatura_alta', 'temperatura_baja', 'humedad_alta', 'falla_equipo', 'consumo_excesivo', 'mantenimiento'],
                'null' => false,
            ],
            'nivel' => [
                'type' => 'ENUM',
                'constraint' => ['info', 'advertencia', 'critico'],
                'default' => 'advertencia',
            ],
            'mensaje' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'valor_actual' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'valor_limite' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
            ],
            'resuelta' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'fecha_alerta' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
            'fecha_resolucion' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dispositivo_id', 'resuelta']);
        $this->forge->addForeignKey('dispositivo_id', 'dispositivos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('alertas');

        // Tabla de mantenimientos
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'dispositivo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => false,
            ],
            'tipo_mantenimiento' => [
                'type' => 'ENUM',
                'constraint' => ['preventivo', 'correctivo', 'limpieza', 'calibracion'],
                'null' => false,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tecnico' => [
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => true,
            ],
            'fecha_programada' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'fecha_realizacion' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'estado' => [
                'type' => 'ENUM',
                'constraint' => ['programado', 'en_proceso', 'completado', 'cancelado'],
                'default' => 'programado',
            ],
            'observaciones' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'creado_en' => [
                'type' => 'TIMESTAMP',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['dispositivo_id', 'estado']);
        $this->forge->addForeignKey('dispositivo_id', 'dispositivos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('mantenimientos');
    }

    public function down()
    {
        $this->forge->dropTable('mantenimientos', true);
        $this->forge->dropTable('alertas', true);
        $this->forge->dropTable('lecturas', true);
        $this->forge->dropTable('dispositivos', true);
        $this->forge->dropTable('usuarios', true);
    }
}