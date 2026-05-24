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

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

$usuario_id = explode(':', base64_decode($token))[0];

$sqlUser = "SELECT restaurante_id, rol FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$usuario = $userResult->fetch_assoc();

if (!$usuario || $usuario['rol'] !== 'restaurante') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo para restaurantes.']);
    exit;
}

$restaurante_id = $usuario['restaurante_id'];

if (!$restaurante_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Tu cuenta no está vinculada a ningún restaurante']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? '';
$descripcion = $data['descripcion'] ?? '';
$precio = $data['precio'] ?? 0;

if (empty($nombre) || $precio <= 0) {
    echo json_encode(['error' => 'Nombre y precio son requeridos']);
    exit;
}

$sql = "INSERT INTO menu (restaurante_id, nombre, descripcion, precio) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issd", $restaurante_id, $nombre, $descripcion, $precio);

if ($stmt->execute()) {
    echo json_encode([
        'mensaje' => 'Plato agregado correctamente',
        'id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['error' => 'Error al agregar plato: ' . $conn->error]);
}

$conn->close();
?>
