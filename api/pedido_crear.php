<?php
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
$restaurante_id = $data['restaurante_id'] ?? 0;
$total = $data['total'] ?? 0;
$items = $data['items'] ?? [];

if (empty($items)) {
    echo json_encode(['error' => 'Carrito vacío']);
    exit;
}

$conn->begin_transaction();

try {
    $sql = "INSERT INTO pedidos (usuario_id, restaurante_id, total, estado) VALUES (?, ?, ?, 'pendiente')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iid", $usuario_id, $restaurante_id, $total);
    $stmt->execute();
    $pedido_id = $conn->insert_id;
    
    foreach ($items as $item) {
        $sql2 = "INSERT INTO detalle_pedidos (pedido_id, menu_id, cantidad, subtotal) VALUES (?, ?, ?, ?)";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("iiid", $pedido_id, $item['menu_id'], $item['cantidad'], $item['subtotal']);
        $stmt2->execute();
    }
    
    $conn->commit();
    echo json_encode(['mensaje' => 'Pedido creado', 'pedido_id' => $pedido_id]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['error' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
