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

$sqlUser = "SELECT id, nombre, ciudad, rol FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $repartidor_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$repartidor = $userResult->fetch_assoc();

if (!$repartidor || $repartidor['rol'] !== 'repartidor') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$ciudad_repartidor = $repartidor['ciudad'] ?? 'General';

$sql = "SELECT p.*, r.nombre as restaurante_nombre, r.ciudad as restaurante_ciudad,
        u.nombre as cliente_nombre, u.direccion as cliente_direccion
        FROM pedidos p 
        JOIN restaurantes r ON p.restaurante_id = r.id 
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.estado = 'pendiente' 
        AND p.repartidor_id IS NULL
        AND (r.ciudad = ? OR r.ciudad = 'General')
        ORDER BY p.fecha ASC
        LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $ciudad_repartidor);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['mensaje' => 'No hay pedidos disponibles', 'pedido' => null]);
    exit;
}

$pedido = $result->fetch_assoc();

echo json_encode([
    'id' => $pedido['id'],
    'restaurante' => $pedido['restaurante_nombre'],
    'cliente' => $pedido['cliente_nombre'],
    'cliente_direccion' => $pedido['cliente_direccion'] ?? 'No especificada',
    'total' => floatval($pedido['total']),
    'ciudad' => $pedido['restaurante_ciudad'] ?? 'General'
]);

$conn->close();
?>
