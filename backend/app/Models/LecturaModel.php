<?php

namespace App\Models;

use CodeIgniter\Model;

class LecturaModel extends Model
{
    protected $table = 'lecturas';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    
    protected $returnType = 'object';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'dispositivo_id', 'temperatura', 'humedad', 'presion',
        'consumo', 'voltaje', 'corriente', 'fecha_hora'
    ];
    
    protected $useTimestamps = false;
    
    protected $validationRules = [
        'dispositivo_id' => 'required|integer',
        'temperatura' => 'required|decimal',
    ];
    
    // Registrar nueva lectura
    public function registrarLectura($dispositivoId, $datos)
    {
        $datos['dispositivo_id'] = $dispositivoId;
        $datos['fecha_hora'] = date('Y-m-d H:i:s');
        
        return $this->insert($datos);
    }
    
    // Obtener historial
    public function getHistorial($dispositivoId, $limite = 100, $fechaInicio = null, $fechaFin = null)
    {
        $builder = $this->where('dispositivo_id', $dispositivoId)
                       ->orderBy('fecha_hora', 'DESC');
        
        if ($fechaInicio) {
            $builder->where('fecha_hora >=', $fechaInicio);
        }
        
        if ($fechaFin) {
            $builder->where('fecha_hora <=', $fechaFin);
        }
        
        return $builder->limit($limite)->findAll();
    }
    
    // Obtener promedios por hora/día
    public function getPromedios($dispositivoId, $rango = 'dia')
    {
        $format = $rango === 'hora' ? '%Y-%m-%d %H:00:00' : '%Y-%m-%d';
        $groupBy = $rango === 'hora' ? 'DATE_FORMAT(fecha_hora, "%Y-%m-%d %H")' : 'DATE(fecha_hora)';
        
        return $this->select("
                DATE_FORMAT(fecha_hora, '{$format}') as periodo,
                AVG(temperatura) as temp_promedio,
                AVG(humedad) as humedad_promedio,
                AVG(consumo) as consumo_promedio,
                COUNT(*) as total_lecturas
            ")
            ->where('dispositivo_id', $dispositivoId)
            ->where('fecha_hora >=', date('Y-m-d', strtotime('-7 days')))
            ->groupBy($groupBy)
            ->orderBy('periodo', 'ASC')
            ->findAll();
    }
    
    // Obtener última lectura
    public function getUltimaLectura($dispositivoId)
    {
        return $this->where('dispositivo_id', $dispositivoId)
                   ->orderBy('fecha_hora', 'DESC')
                   ->first();
    }
}