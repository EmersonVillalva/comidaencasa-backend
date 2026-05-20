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

// Verificar que el usuario es repartidor
$sqlUser = "SELECT rol FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$usuario = $userResult->fetch_assoc();

if (!$usuario || $usuario['rol'] !== 'repartidor') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo para repartidores.']);
    exit;
}

// Obtener pedidos en estado "en camino" (para repartidores)
$sql = "SELECT p.*, r.nombre as restaurante_nombre, u.nombre as cliente_nombre, u.direccion as cliente_direccion
        FROM pedidos p 
        JOIN restaurantes r ON p.restaurante_id = r.id 
        JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.estado = 'en camino' OR p.estado = 'preparando'
        ORDER BY p.fecha ASC";

$result = $conn->query($sql);

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = [
        'id' => $row['id'],
        'restaurante' => $row['restaurante_nombre'],
        'cliente' => $row['cliente_nombre'],
        'direccion' => $row['cliente_direccion'] ?? 'No especificada',
        'total' => floatval($row['total']),
        'estado' => $row['estado'],
        'fecha' => $row['fecha']
    ];
}

echo json_encode($pedidos);
$conn->close();
?>
