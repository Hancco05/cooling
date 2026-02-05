<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Configuración de base de datos
$host = 'mysql';
$dbname = 'cooling_db';
$username = 'cooling_user';
$password = 'cooling123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión a BD']);
    exit();
}

// Crear tabla usuarios si no existe
$pdo->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        rol ENUM('user', 'admin') DEFAULT 'user',
        activo BOOLEAN DEFAULT TRUE,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Endpoint: Registrar usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'register') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['nombre']) || empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son requeridos']);
        exit();
    }
    
    // Verificar si el email ya existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$data['email']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'El email ya está registrado']);
        exit();
    }
    
    // Hash de la contraseña
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $rol = isset($data['rol']) ? $data['rol'] : 'user';
    
    // Insertar usuario
    $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
    $stmt->execute([$data['nombre'], $data['email'], $hashedPassword, $rol]);
    
    $userId = $pdo->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'user' => [
            'id' => $userId,
            'nombre' => $data['nombre'],
            'email' => $data['email'],
            'rol' => $rol
        ]
    ]);
    exit();
}

// Endpoint: Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email']) || empty($data['password'])) {
        echo json_encode(['success' => false, 'message' => 'Email y contraseña son requeridos']);
        exit();
    }
    
    // Buscar usuario
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND activo = TRUE");
    $stmt->execute([$data['email']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($data['password'], $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Credenciales incorrectas']);
        exit();
    }
    
    // Crear token simple (en producción usar JWT)
    $token = bin2hex(random_bytes(32));
    
    // Guardar token en sesión (en BD en producción)
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_rol'] = $user['rol'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Login exitoso',
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'nombre' => $user['nombre'],
            'email' => $user['email'],
            'rol' => $user['rol']
        ]
    ]);
    exit();
}

// Endpoint: Verificar token
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verify') {
    session_start();
    
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'rol' => $_SESSION['user_rol']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No autenticado']);
    }
    exit();
}

// Endpoint por defecto
echo json_encode([
    'success' => true,
    'message' => 'Cooling API - Sistema de autenticación',
    'endpoints' => [
        'POST /api/auth.php?action=register' => 'Registrar usuario',
        'POST /api/auth.php?action=login' => 'Iniciar sesión',
        'GET /api/auth.php?action=verify' => 'Verificar sesión'
    ]
]);