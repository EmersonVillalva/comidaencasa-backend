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

// Marcar como rechazado por este repartidor
$sqlUpdate = "UPDATE pedidos SET rechazado_por = CONCAT(IFNULL(rechazado_por, ''), ',', ?) WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("ii", $repartidor_id, $pedido_id);
$stmtUpdate->execute();

echo json_encode(['mensaje' => 'Pedido rechazado']);
$conn->close();
?>
