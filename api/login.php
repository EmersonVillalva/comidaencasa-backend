<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email y contraseña son requeridos']);
    exit;
}

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Verificar contraseña (texto plano)
    if ($password == $row['password']) {
        $token = base64_encode($row['id'] . ':' . time());
        
        echo json_encode([
            'token' => $token,
            'usuario' => [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'email' => $row['email'],
                'rol' => $row['rol'],
                'ciudad' => $row['ciudad'] ?? 'General',
                'restaurante_id' => $row['restaurante_id'] ?? null
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
