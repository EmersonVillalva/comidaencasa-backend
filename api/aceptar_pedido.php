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

$repartidor_id = explode(':', base64_decode($token))[0];

$data = json_decode(file_get_contents('php://input'), true);
$pedido_id = $data['pedido_id'] ?? 0;

// Verificar que el pedido existe y está pendiente
$sqlCheck = "SELECT id FROM pedidos WHERE id = ? AND estado = 'pendiente' AND (repartidor_id IS NULL OR repartidor_id = 0)";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $pedido_id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Pedido no disponible']);
    exit;
}

// Asignar pedido al repartidor
$sqlUpdate = "UPDATE pedidos SET repartidor_id = ?, estado = 'en camino' WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("ii", $repartidor_id, $pedido_id);

if ($stmtUpdate->execute()) {
    echo json_encode(['mensaje' => 'Pedido aceptado', 'pedido_id' => $pedido_id]);
} else {
    echo json_encode(['error' => 'Error al aceptar']);
}

$conn->close();
?>
