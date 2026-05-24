<?php
// Cabeceras CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Responder a peticiones OPTIONS (preflight)
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

$data = json_decode(file_get_contents('php://input'), true);
$pedido_id = $data['pedido_id'] ?? 0;
$nuevo_estado = $data['estado'] ?? '';

$estados_validos = ['pendiente', 'preparando', 'en camino', 'entregado', 'cancelado'];

if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['error' => 'Estado no válido']);
    exit;
}

// Actualizar estado
$sqlUpdate = "UPDATE pedidos SET estado = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("si", $nuevo_estado, $pedido_id);

if ($stmtUpdate->execute()) {
    echo json_encode(['mensaje' => 'Estado actualizado correctamente', 'nuevo_estado' => $nuevo_estado]);
} else {
    echo json_encode(['error' => 'Error al actualizar']);
}

$conn->close();
?>
