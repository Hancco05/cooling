<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\DispositivoModel;
use App\Models\LecturaModel;
use App\Models\AlertaModel;

class Dispositivos extends ResourceController
{
    use ResponseTrait;
    
    protected $dispositivoModel;
    protected $lecturaModel;
    protected $alertaModel;
    protected $usuarioId;
    
    public function __construct()
    {
        $this->dispositivoModel = new DispositivoModel();
        $this->lecturaModel = new LecturaModel();
        $this->alertaModel = new AlertaModel();
        $this->usuarioId = $this->obtenerUsuarioId();
    }
    
    /**
     * GET /api/dispositivos
     * Listar todos los dispositivos del usuario
     */
    public function index()
    {
        $filtros = [
            'tipo' => $this->request->getGet('tipo'),
            'estado' => $this->request->getGet('estado'),
            'busqueda' => $this->request->getGet('q')
        ];
        
        $dispositivos = $this->dispositivoModel->getPorUsuario($this->usuarioId, $filtros);
        
        // Agregar última lectura a cada dispositivo
        foreach ($dispositivos as &$dispositivo) {
            $dispositivo->ultima_lectura = $this->lecturaModel->getUltimaLectura($dispositivo->id);
        }
        
        return $this->respond([
            'success' => true,
            'data' => $dispositivos,
            'total' => count($dispositivos),
            'estadisticas' => $this->dispositivoModel->getEstadisticas($this->usuarioId)
        ]);
    }
    
    /**
     * GET /api/dispositivos/:id
     * Obtener un dispositivo específico
     */
    public function show($id = null)
    {
        $dispositivo = $this->dispositivoModel->find($id);
        
        if (!$dispositivo || $dispositivo->usuario_id != $this->usuarioId) {
            return $this->failNotFound('Dispositivo no encontrado');
        }
        
        $dispositivo->ultimas_lecturas = $this->lecturaModel->getHistorial($id, 10);
        $dispositivo->alertas_activas = $this->alertaModel->getAlertasActivas($id);
        
        return $this->respond([
            'success' => true,
            'data' => $dispositivo
        ]);
    }
    
    /**
     * POST /api/dispositivos
     * Crear un nuevo dispositivo
     */
    public function create()
    {
        $rules = [
            'nombre' => 'required|min_length[3]|max_length[100]',
            'tipo' => 'required|in_list[aire_acondicionado,ventilador,refrigerador,chiller,torre_enfriamiento,otro]',
            'temperatura_min' => 'decimal',
            'temperatura_max' => 'decimal'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        $data['usuario_id'] = $this->usuarioId;
        
        // Configuración por defecto según tipo
        $configuracion = [
            'intervalo_lectura' => 300, // 5 minutos
            'alertas' => [
                'temperatura_alta' => true,
                'temperatura_baja' => true,
                'humedad_alta' => false
            ]
        ];
        
        if ($data['tipo'] === 'refrigerador') {
            $configuracion['temperatura_minima'] = 2;
            $configuracion['temperatura_maxima'] = 8;
        } elseif ($data['tipo'] === 'aire_acondicionado') {
            $configuracion['temperatura_minima'] = 16;
            $configuracion['temperatura_maxima'] = 26;
        }
        
        $data['configuracion'] = json_encode($configuracion);
        
        try {
            $id = $this->dispositivoModel->insert($data);
            
            return $this->respondCreated([
                'success' => true,
                'message' => 'Dispositivo creado exitosamente',
                'data' => [
                    'id' => $id,
                    'uuid' => $this->dispositivoModel->find($id)->uuid
                ]
            ]);
            
        } catch (\Exception $e) {
            return $this->failServerError('Error al crear dispositivo: ' . $e->getMessage());
        }
    }
    
    /**
     * PUT /api/dispositivos/:id
     * Actualizar dispositivo
     */
    public function update($id = null)
    {
        $dispositivo = $this->dispositivoModel->find($id);
        
        if (!$dispositivo || $dispositivo->usuario_id != $this->usuarioId) {
            return $this->failNotFound('Dispositivo no encontrado');
        }
        
        $rules = [
            'nombre' => 'min_length[3]|max_length[100]',
            'temperatura_min' => 'decimal',
            'temperatura_max' => 'decimal'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        if (isset($data['configuracion']) && is_array($data['configuracion'])) {
            $data['configuracion'] = json_encode($data['configuracion']);
        }
        
        try {
            $this->dispositivoModel->update($id, $data);
            
            return $this->respond([
                'success' => true,
                'message' => 'Dispositivo actualizado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return $this->failServerError('Error al actualizar dispositivo: ' . $e->getMessage());
        }
    }
    
    /**
     * DELETE /api/dispositivos/:id
     * Eliminar dispositivo
     */
    public function delete($id = null)
    {
        $dispositivo = $this->dispositivoModel->find($id);
        
        if (!$dispositivo || $dispositivo->usuario_id != $this->usuarioId) {
            return $this->failNotFound('Dispositivo no encontrado');
        }
        
        try {
            $this->dispositivoModel->delete($id);
            
            return $this->respond([
                'success' => true,
                'message' => 'Dispositivo eliminado exitosamente'
            ]);
            
        } catch (\Exception $e) {
            return $this->failServerError('Error al eliminar dispositivo: ' . $e->getMessage());
        }
    }
    
    /**
     * POST /api/dispositivos/:id/encender
     * Encender dispositivo
     */
    public function encender($id = null)
    {
        return $this->cambiarEstado($id, 'encendido');
    }
    
    /**
     * POST /api/dispositivos/:id/apagar
     * Apagar dispositivo
     */
    public function apagar($id = null)
    {
        return $this->cambiarEstado($id, 'apagado');
    }
    
    /**
     * POST /api/dispositivos/:id/standby
     * Poner en standby
     */
    public function standby($id = null)
    {
        return $this->cambiarEstado($id, 'standby');
    }
    
    /**
     * GET /api/dispositivos/:id/temperaturas
     * Obtener historial de temperaturas
     */
    public function temperaturas($id = null)
    {
        $dispositivo = $this->dispositivoModel->find($id);
        
        if (!$dispositivo || $dispositivo->usuario_id != $this->usuarioId) {
            return $this->failNotFound('Dispositivo no encontrado');
        }
        
        $rango = $this->request->getGet('rango') ?? 'dia';
        $limite = $this->request->getGet('limite') ?? 50;
        
        if ($rango === 'hora') {
            $data = $this->lecturaModel->getPromedios($id, 'hora');
        } elseif ($rango === 'dia') {
            $data = $this->lecturaModel->getPromedios($id, 'dia');
        } else {
            $data = $this->lecturaModel->getHistorial($id, $limite);
        }
        
        return $this->respond([
            'success' => true,
            'data' => $data,
            'dispositivo' => [
                'nombre' => $dispositivo->nombre,
                'tipo' => $dispositivo->tipo,
                'temperatura_actual' => $dispositivo->temperatura_actual
            ]
        ]);
    }
    
    /**
     * POST /api/dispositivos/:id/lectura
     * Registrar nueva lectura de temperatura
     */
    public function lectura($id = null)
    {
        $dispositivo = $this->dispositivoModel->find($id);
        
        if (!$dispositivo || $dispositivo->usuario_id != $this->usuarioId) {
            return $this->failNotFound('Dispositivo no encontrado');
        }
        
        $rules = [
            'temperatura' => 'required|decimal',
            'humedad' => 'decimal',
            'consumo' => 'decimal'
        ];
        
        if (!$this->validate($rules)) {
            return $this->failValidationErrors($this->validator->getErrors());
        }
        
        $data = $this->request->getJSON(true);
        
        // Registrar lectura
        $lecturaId = $this->lecturaModel->registrarLectura($id, $data);
        
        // Actualizar temperatura actual del dispositivo
        $this->dispositivoModel->actualizarTemperatura(
            $id, 
            $data['temperatura'], 
            $data['humedad'] ?? null
        );
        
        // Verificar alertas
        $this->verificarAlertas($dispositivo, $data);
        
        return $this->respondCreated([
            'success' => true,
            'message' => 'Lectura registrada exitosamente',
            'data' => [
                'lectura_id' => $lecturaId,
                'temperatura' => $data['temperatura'],
                'fecha_hora' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
    /**
     * GET /api/dispositivos/estadisticas
     * Obtener estadísticas generales
     */
    public function estadisticas()
    {
        $estadisticas = $this->dispositivoModel->getEstadisticas($this->usuarioId);
        
        // Obtener consumo por tipo
        $consumoPorTipo = $this->dispositivoModel
            ->select('tipo, SUM(consumo_energia) as consumo_total')
            ->where('usuario_id', $this->usuarioId)
            ->groupBy('tipo')
            ->findAll();
        
        return $this->respond([
            'success' => true,
            'data' => [
                'estadisticas' => $estadisticas,
                'consumo_por_tipo' => $consumoPorTipo,
                'dispositivos_por_estado' => [
                    'encendidos' => $estadisticas['encendidos'],
                    'apagados' => $estadisticas['apagados'],
                    'mantenimiento' => $estadisticas['en_mantenimiento']
                ]
            ]
        ]);
    }
    
    /**
     * Métodos privados auxiliares
     */
    private function cambiarEstado($id, $estado)
    {
        $dispositivo = $this->dispositivoModel->find($id);
        
        if (!$dispositivo || $dispositivo->usuario_id != $this->usuarioId) {
            return $this->failNotFound('Dispositivo no encontrado');
        }
        
        $this->dispositivoModel->cambiarEstado($id, $estado);
        
        return $this->respond([
            'success' => true,
            'message' => "Dispositivo {$estado} exitosamente",
            'data' => [
                'id' => $id,
                'estado' => $estado,
                'fecha' => date('Y-m-d H:i:s')
            ]
        ]);
    }
    
    private function verificarAlertas($dispositivo, $lectura)
    {
        $config = json_decode($dispositivo->configuracion, true) ?? [];
        
        // Verificar temperatura alta
        if (!empty($config['temperatura_maxima']) && 
            $lectura['temperatura'] > $config['temperatura_maxima']) {
            
            $this->alertaModel->crearAlerta([
                'dispositivo_id' => $dispositivo->id,
                'tipo' => 'temperatura_alta',
                'nivel' => 'critico',
                'mensaje' => "Temperatura alta: {$lectura['temperatura']}°C",
                'valor_actual' => $lectura['temperatura'],
                'valor_limite' => $config['temperatura_maxima']
            ]);
        }
        
        // Verificar temperatura baja
        if (!empty($config['temperatura_minima']) && 
            $lectura['temperatura'] < $config['temperatura_minima']) {
            
            $this->alertaModel->crearAlerta([
                'dispositivo_id' => $dispositivo->id,
                'tipo' => 'temperatura_baja',
                'nivel' => 'advertencia',
                'mensaje' => "Temperatura baja: {$lectura['temperatura']}°C",
                'valor_actual' => $lectura['temperatura'],
                'valor_limite' => $config['temperatura_minima']
            ]);
        }
    }
    
    private function obtenerUsuarioId()
    {
        // Obtener usuario desde el token JWT
        $token = $this->request->getHeaderLine('Authorization');
        
        if (empty($token)) {
            return null;
        }
        
        $token = str_replace('Bearer ', '', $token);
        
        // Decodificar JWT (implementar según tu método)
        try {
            $decoded = verifyJWT($token);
            return $decoded->id ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}