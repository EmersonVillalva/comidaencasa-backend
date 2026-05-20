<?php
// Cabeceras CORS
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

// Verificar que el pedido sigue disponible
$sqlCheck = "SELECT estado, repartidor_id FROM pedidos WHERE id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $pedido_id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();
$pedido = $result->fetch_assoc();

if (!$pedido) {
    echo json_encode(['error' => 'Pedido no encontrado']);
    exit;
}

if ($pedido['estado'] !== 'pendiente') {
    echo json_encode(['error' => 'Este pedido ya no está disponible']);
    exit;
}

if ($pedido['repartidor_id'] !== null) {
    echo json_encode(['error' => 'Este pedido ya tiene repartidor asignado']);
    exit;
}

// Asignar el pedido al repartidor y cambiar estado a "en camino"
$sqlUpdate = "UPDATE pedidos SET repartidor_id = ?, estado = 'en camino' WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("ii", $repartidor_id, $pedido_id);

if ($stmtUpdate->execute()) {
    echo json_encode(['mensaje' => 'Pedido aceptado', 'pedido_id' => $pedido_id]);
} else {
    echo json_encode(['error' => 'Error al aceptar el pedido']);
}

$conn->close();
?>
