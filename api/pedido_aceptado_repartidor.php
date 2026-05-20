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

// Buscar pedido que tiene asignado este repartidor y no está entregado
$sql = "SELECT p.*, r.nombre as restaurante_nombre,
        u.nombre as cliente_nombre, u.direccion as cliente_direccion
        FROM pedidos p 
        JOIN restaurantes r ON p.restaurante_id = r.id 
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.repartidor_id = ? 
        AND p.estado IN ('en camino', 'preparando')
        ORDER BY p.fecha DESC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['mensaje' => 'No hay pedidos en curso']);
    exit;
}

$pedido = $result->fetch_assoc();

echo json_encode([
    'id' => $pedido['id'],
    'restaurante' => $pedido['restaurante_nombre'],
    'cliente' => $pedido['cliente_nombre'],
    'cliente_direccion' => $pedido['cliente_direccion'] ?? 'No especificada',
    'total' => floatval($pedido['total']),
    'estado' => $pedido['estado']
]);

$conn->close();
?>
