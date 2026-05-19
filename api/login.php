<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// === CONFIGURACIÓN PARA RAILWAY ===
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQLDATABASE') ?: 'railway';

// Conexión a BD
$conn = new mysqli($host, $user, $password, $database, (int)$port);

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8");

// Leer datos del request
$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

// Buscar usuario por email
$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Verificar contraseña (texto plano, puedes mejorar con password_verify después)
    if ($password == $row['password']) {
        // Generar token simple
        $token = base64_encode($row['id'] . ':' . time());
        
        echo json_encode([
            'token' => $token,
            'usuario' => [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'email' => $row['email'],
                'rol' => $row['rol']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Contraseña incorrecta']);
    }
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no encontrado']);
}

$conn->close();
?>
