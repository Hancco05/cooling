<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;
use App\Models\DispositivoModel;
use App\Models\LecturaModel;
use App\Models\AlertaModel;
use App\Models\UsuarioModel;

class Dashboard extends Controller
{
    protected $dispositivoModel;
    protected $lecturaModel;
    protected $alertaModel;
    protected $usuarioModel;
    
    public function __construct()
    {
        $this->dispositivoModel = new DispositivoModel();
        $this->lecturaModel = new LecturaModel();
        $this->alertaModel = new AlertaModel();
        $this->usuarioModel = new UsuarioModel();
        
        // Verificar que sea administrador
        $this->checkAdmin();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Dashboard Administrativo',
            'totalDevices' => $this->dispositivoModel->countAll(),
            'totalUsers' => $this->usuarioModel->countAll(),
            'activeAlerts' => $this->alertaModel->where('resuelta', 0)->countAllResults(),
            'totalReadings' => $this->lecturaModel->countAll(),
            'devicesByType' => $this->getDevicesByType(),
            'alertsByType' => $this->getAlertsByType(),
            'consumptionStats' => $this->getConsumptionStats(),
            'recentAlerts' => $this->getRecentAlerts(10),
            'systemHealth' => $this->getSystemHealth(),
        ];
        
        return view('admin/dashboard', $data);
    }
    
    public function devicesReport()
    {
        $startDate = $this->request->getGet('start_date') ?? date('Y-m-01');
        $endDate = $this->request->getGet('end_date') ?? date('Y-m-d');
        
        $data = [
            'title' => 'Reporte de Dispositivos',
            'devices' => $this->dispositivoModel->findAll(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'consumptionData' => $this->getConsumptionData($startDate, $endDate),
            'temperatureData' => $this->getTemperatureData($startDate, $endDate),
        ];
        
        return view('admin/devices_report', $data);
    }
    
    public function exportPDF()
    {
        $mpdf = new \Mpdf\Mpdf();
        
        $html = view('admin/reports/pdf_template', [
            'title' => 'Reporte del Sistema Cooling',
            'date' => date('d/m/Y'),
            'data' => $this->getExportData()
        ]);
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('cooling_report_' . date('Ymd_His') . '.pdf', 'D');
    }
    
    public function systemMetrics()
    {
        $metrics = [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'database_size' => $this->getDatabaseSize(),
            'api_requests' => $this->getApiRequests(),
            'websocket_connections' => $this->getWebSocketConnections(),
        ];
        
        return $this->response->setJSON($metrics);
    }
    
    private function getDevicesByType()
    {
        return $this->dispositivoModel
            ->select('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->findAll();
    }
    
    private function getAlertsByType()
    {
        return $this->alertaModel
            ->select('tipo, COUNT(*) as total')
            ->groupBy('tipo')
            ->findAll();
    }
    
    private function getConsumptionStats()
    {
        return $this->dispositivoModel
            ->select('
                DATE(creado_en) as fecha,
                SUM(consumo_energia) as consumo_total,
                AVG(temperatura_actual) as temp_promedio
            ')
            ->where('creado_en >=', date('Y-m-d', strtotime('-30 days')))
            ->groupBy('DATE(creado_en)')
            ->orderBy('fecha', 'ASC')
            ->findAll();
    }
    
    private function getRecentAlerts($limit = 10)
    {
        return $this->alertaModel
            ->select('alertas.*, dispositivos.nombre as dispositivo_nombre')
            ->join('dispositivos', 'dispositivos.id = alertas.dispositivo_id')
            ->where('resuelta', 0)
            ->orderBy('fecha_alerta', 'DESC')
            ->limit($limit)
            ->findAll();
    }
    
    private function getSystemHealth()
    {
        $totalDevices = $this->dispositivoModel->countAll();
        $activeDevices = $this->dispositivoModel
            ->where('estado', 'encendido')
            ->countAllResults();
        
        $healthPercentage = ($totalDevices > 0) 
            ? ($activeDevices / $totalDevices) * 100 
            : 100;
            
        $status = $healthPercentage >= 80 ? 'excelente' 
                : ($healthPercentage >= 60 ? 'bueno' 
                : ($healthPercentage >= 40 ? 'regular' : 'critico'));
        
        return [
            'percentage' => round($healthPercentage, 2),
            'status' => $status,
            'active' => $activeDevices,
            'total' => $totalDevices
        ];
    }
    
    private function checkAdmin()
    {
        // Implementar lógica de verificación de administrador
        // Por ahora, siempre permite el acceso
        return true;
    }
    
    private function getCpuUsage()
    {
        // En producción, usar sys_getloadavg() o comando del sistema
        $load = sys_getloadavg();
        return [
            '1min' => $load[0],
            '5min' => $load[1],
            '15min' => $load[2],
        ];
    }
    
    private function getMemoryUsage()
    {
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        
        $memory_used = $mem[2];
        $memory_total = $mem[1];
        $memory_percent = ($memory_used / $memory_total) * 100;
        
        return [
            'used' => $this->formatBytes($memory_used * 1024),
            'total' => $this->formatBytes($memory_total * 1024),
            'percent' => round($memory_percent, 2)
        ];
    }
    
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}