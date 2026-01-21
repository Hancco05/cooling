<?php

namespace App\Models;

use CodeIgniter\Model;

class DispositivoModel extends Model
{
    protected $table = 'dispositivos';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'uuid', 'nombre', 'tipo', 'marca', 'modelo',
        'temperatura_actual', 'temperatura_min', 'temperatura_max',
        'humedad_actual', 'estado', 'consumo_energia',
        'ubicacion', 'latitud', 'longitud', 'usuario_id', 'configuracion'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'creado_en';
    protected $updatedField = 'actualizado_en';
    
    protected $validationRules = [
        'nombre' => 'required|min_length[3]|max_length[100]',
        'tipo' => 'required|in_list[aire_acondicionado,ventilador,refrigerador,chiller,torre_enfriamiento,otro]',
        'uuid' => 'required|is_unique[dispositivos.uuid,id,{id}]',
    ];
    
    // Generar UUID automáticamente
    protected $beforeInsert = ['generarUUID'];
    
    protected function generarUUID(array $data)
    {
        if (!isset($data['data']['uuid'])) {
            $data['data']['uuid'] = $this->generarUUIDUnico();
        }
        return $data;
    }
    
    private function generarUUIDUnico()
    {
        do {
            $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        } while ($this->where('uuid', $uuid)->countAllResults() > 0);
        
        return $uuid;
    }
    
    // Obtener dispositivos por usuario
    public function getPorUsuario($usuarioId, $filtros = [])
    {
        $builder = $this->where('usuario_id', $usuarioId);
        
        if (!empty($filtros['tipo'])) {
            $builder->where('tipo', $filtros['tipo']);
        }
        
        if (!empty($filtros['estado'])) {
            $builder->where('estado', $filtros['estado']);
        }
        
        if (!empty($filtros['busqueda'])) {
            $builder->groupStart()
                   ->like('nombre', $filtros['busqueda'])
                   ->orLike('ubicacion', $filtros['busqueda'])
                   ->groupEnd();
        }
        
        return $builder->orderBy('nombre')->findAll();
    }
    
    // Actualizar temperatura
    public function actualizarTemperatura($id, $temperatura, $humedad = null)
    {
        $data = [
            'temperatura_actual' => $temperatura,
            'actualizado_en' => date('Y-m-d H:i:s')
        ];
        
        if ($humedad !== null) {
            $data['humedad_actual'] = $humedad;
        }
        
        return $this->update($id, $data);
    }
    
    // Cambiar estado
    public function cambiarEstado($id, $estado)
    {
        return $this->update($id, [
            'estado' => $estado,
            'actualizado_en' => date('Y-m-d H:i:s')
        ]);
    }
    
    // Obtener estadísticas
    public function getEstadisticas($usuarioId)
    {
        $total = $this->where('usuario_id', $usuarioId)->countAllResults();
        $encendidos = $this->where('usuario_id', $usuarioId)
                          ->where('estado', 'encendido')
                          ->countAllResults();
        $enMantenimiento = $this->where('usuario_id', $usuarioId)
                               ->where('estado', 'mantenimiento')
                               ->countAllResults();
        
        $consumoTotal = $this->where('usuario_id', $usuarioId)
                           ->selectSum('consumo_energia')
                           ->get()
                           ->getRow();
        
        return [
            'total' => $total,
            'encendidos' => $encendidos,
            'en_mantenimiento' => $enMantenimiento,
            'apagados' => $total - $encendidos - $enMantenimiento,
            'consumo_total' => $consumoTotal->consumo_energia ?? 0
        ];
    }
}