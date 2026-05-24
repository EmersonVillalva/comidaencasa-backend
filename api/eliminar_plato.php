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

$data = json_decode(file_get_contents('php://input'), true);
$plato_id = $data['id'] ?? 0;

if ($plato_id <= 0) {
    echo json_encode(['error' => 'ID de plato requerido']);
    exit;
}

$sqlCheck = "SELECT id FROM menu WHERE id = ? AND restaurante_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $plato_id, $restaurante_id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Plato no encontrado o no pertenece a tu restaurante']);
    exit;
}

$sql = "DELETE FROM menu WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $plato_id);

if ($stmt->execute()) {
    echo json_encode(['mensaje' => 'Plato eliminado correctamente']);
} else {
    echo json_encode(['error' => 'Error al eliminar plato']);
}

$conn->close();
?>
