public $aliases = [
    'csrf'     => \CodeIgniter\Filters\CSRF::class,
    'toolbar'  => \CodeIgniter\Filters\DebugToolbar::class,
    'honeypot' => \CodeIgniter\Filters\Honeypot::class,
    'auth'     => \App\Filters\AuthFilter::class, // Agregar este
    'cors'     => \App\Filters\CorsFilter::class, // Para CORS
];

public $globals = [
    'before' => [
        'cors', // Aplicar CORS a todas las rutas
        // 'csrf', // Deshabilitar CSRF para API
    ],
    'after' => [
        'toolbar',
        // 'honeypot',
    ],
];

public $methods = [];

public $filters = [
    'auth' => [
        'before' => [
            'api/dispositivos/*',
            'api/temperaturas/*',
            'api/alertas/*',
            'api/auth/me',
            'api/auth/logout'
        ]
    ]
];