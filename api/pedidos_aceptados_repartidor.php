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

// Buscar todos los pedidos que tiene asignado este repartidor
$sql = "SELECT p.*, r.nombre as restaurante_nombre,
        u.nombre as cliente_nombre, u.direccion as cliente_direccion
        FROM pedidos p 
        JOIN restaurantes r ON p.restaurante_id = r.id 
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.repartidor_id = ? 
        AND p.estado IN ('en camino', 'preparando')
        ORDER BY p.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $repartidor_id);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = [
        'id' => $row['id'],
        'restaurante' => $row['restaurante_nombre'],
        'cliente' => $row['cliente_nombre'],
        'cliente_direccion' => $row['cliente_direccion'] ?? 'No especificada',
        'total' => floatval($row['total']),
        'estado' => $row['estado']
    ];
}

echo json_encode($pedidos);
$conn->close();
?>
