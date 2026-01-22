<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Cooling System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #4facfe;
            --secondary: #00f2fe;
            --success: #00b894;
            --danger: #ff6b6b;
            --warning: #feca57;
            --info: #54a0ff;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--secondary) 100%);
            min-height: 100vh;
            color: white;
            position: fixed;
            width: 250px;
        }
        
        .sidebar .logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 10px;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border-left: 5px solid var(--primary);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card .stat-title {
            color: #666;
            font-size: 0.9rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .alert-card {
            border-left: 5px solid var(--danger);
        }
        
        .alert-card .badge {
            font-size: 0.7rem;
            padding: 5px 10px;
        }
        
        .health-indicator {
            height: 10px;
            background: #e9ecef;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .health-bar {
            height: 100%;
            border-radius: 5px;
        }
        
        .health-excellent { background: var(--success); }
        .health-good { background: var(--info); }
        .health-regular { background: var(--warning); }
        .health-critico { background: var(--danger); }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                min-height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <h3><i class="bi bi-snow"></i> Cooling System</h3>
            <small>Panel de Administración</small>
        </div>
        
        <nav class="nav flex-column mt-4">
            <a class="nav-link active" href="/admin/dashboard">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <a class="nav-link" href="/admin/devices">
                <i class="bi bi-device-ssd"></i> Dispositivos
            </a>
            <a class="nav-link" href="/admin/users">
                <i class="bi bi-people"></i> Usuarios
            </a>
            <a class="nav-link" href="/admin/alerts">
                <i class="bi bi-bell"></i> Alertas
            </a>
            <a class="nav-link" href="/admin/reports">
                <i class="bi bi-bar-chart"></i> Reportes
            </a>
            <a class="nav-link" href="/admin/settings">
                <i class="bi bi-gear"></i> Configuración
            </a>
            <a class="nav-link" href="/admin/logs">
                <i class="bi bi-journal-text"></i> Logs del Sistema
            </a>
        </nav>
        
        <div class="mt-auto p-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <h6><i class="bi bi-shield-check"></i> Estado del Sistema</h6>
                    <div class="health-indicator">
                        <div class="health-bar health-<?= $systemHealth['status'] ?>" 
                             style="width: <?= $systemHealth['percentage'] ?>%"></div>
                    </div>
                    <small class="d-block mt-2">
                        <?= $systemHealth['active'] ?> / <?= $systemHealth['total'] ?> dispositivos activos
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Dashboard Administrativo</h1>
            <div>
                <button class="btn btn-primary me-2">
                    <i class="bi bi-download"></i> Exportar PDF
                </button>
                <button class="btn btn-outline-primary">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(79, 172, 254, 0.1); color: var(--primary);">
                        <i class="bi bi-device-ssd"></i>
                    </div>
                    <div class="stat-value"><?= number_format($totalDevices) ?></div>
                    <div class="stat-title">Dispositivos Totales</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(0, 184, 148, 0.1); color: var(--success);">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value"><?= number_format($totalUsers) ?></div>
                    <div class="stat-title">Usuarios Registrados</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card alert-card">
                    <div class="stat-icon" style="background: rgba(255, 107, 107, 0.1); color: var(--danger);">
                        <i class="bi bi-exclamation-triangle"></i>
                    </div>
                    <div class="stat-value"><?= $activeAlerts ?></div>
                    <div class="stat-title">Alertas Activas</div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background: rgba(84, 160, 255, 0.1); color: var(--info);">
                        <i class="bi bi-thermometer-half"></i>
                    </div>
                    <div class="stat-value"><?= number_format($totalReadings) ?></div>
                    <div class="stat-title">Lecturas Totales</div>
                </div>
            </div>
        </div>
        
        <!-- Charts -->
        <div class="row">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5>Consumo de Energía (Últimos 30 días)</h5>
                    <canvas id="consumptionChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="chart-container">
                    <h5>Dispositivos por Tipo</h5>
                    <canvas id="devicesByTypeChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Recent Alerts -->
        <div class="row">
            <div class="col-12">
                <div class="chart-container">
                    <h5>Alertas Recientes</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Dispositivo</th>
                                    <th>Tipo</th>
                                    <th>Mensaje</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAlerts as $alert): ?>
                                <tr>
                                    <td><?= esc($alert->dispositivo_nombre) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $alert->tipo == 'critico' ? 'danger' : 
                                            ($alert->tipo == 'advertencia' ? 'warning' : 'info') 
                                        ?>">
                                            <?= $alert->tipo ?>
                                        </span>
                                    </td>
                                    <td><?= esc($alert->mensaje) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($alert->fecha_alerta)) ?></td>
                                    <td>
                                        <?php if ($alert->resuelta): ?>
                                            <span class="badge bg-success">Resuelta</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Consumption Chart
        const consumptionCtx = document.getElementById('consumptionChart').getContext('2d');
        const consumptionChart = new Chart(consumptionCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($consumptionStats, 'fecha')) ?>,
                datasets: [{
                    label: 'Consumo (kWh)',
                    data: <?= json_encode(array_column($consumptionStats, 'consumo_total')) ?>,
                    borderColor: '#4facfe',
                    backgroundColor: 'rgba(79, 172, 254, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Temperatura Promedio (°C)',
                    data: <?= json_encode(array_column($consumptionStats, 'temp_promedio')) ?>,
                    borderColor: '#ff6b6b',
                    backgroundColor: 'rgba(255, 107, 107, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Consumo (kWh)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Temperatura (°C)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    }
                }
            }
        });
        
        // Devices by Type Chart
        const devicesCtx = document.getElementById('devicesByTypeChart').getContext('2d');
        const devicesChart = new Chart(devicesCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($devicesByType, 'tipo')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($devicesByType, 'total')) ?>,
                    backgroundColor: [
                        '#4facfe', '#00f2fe', '#ff6b6b', '#feca57', '#00b894', '#54a0ff'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            fetch('/admin/dashboard/metrics')
                .then(response => response.json())
                .then(data => {
                    // Update stats
                    document.querySelector('.stat-card:nth-child(1) .stat-value').textContent = 
                        data.totalDevices.toLocaleString();
                    document.querySelector('.stat-card:nth-child(3) .stat-value').textContent = 
                        data.activeAlerts;
                    
                    // Update system health
                    const healthBar = document.querySelector('.health-bar');
                    healthBar.style.width = data.systemHealth.percentage + '%';
                    healthBar.className = 'health-bar health-' + data.systemHealth.status;
                });
        }, 30000);
    </script>
</body>
</html>