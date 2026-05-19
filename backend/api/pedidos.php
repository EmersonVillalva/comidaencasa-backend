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

$sql = "SELECT p.*, r.nombre as restaurante_nombre 
        FROM pedidos p 
        JOIN restaurantes r ON p.restaurante_id = r.id 
        WHERE p.usuario_id = ? 
        ORDER BY p.fecha DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$result = $stmt->get_result();

$pedidos = [];
while ($row = $result->fetch_assoc()) {
    $pedidos[] = [
        'id' => $row['id'],
        'restaurante' => $row['restaurante_nombre'],
        'total' => $row['total'],
        'estado' => $row['estado'],
        'fecha' => $row['fecha']
    ];
}

echo json_encode($pedidos);
$conn->close();
?>