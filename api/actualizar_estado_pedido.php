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

// Obtener datos del usuario
$sqlUser = "SELECT restaurante_id, rol FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$usuario = $userResult->fetch_assoc();

if ($usuario['rol'] !== 'restaurante') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Solo restaurantes pueden actualizar estados.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$pedido_id = $data['pedido_id'] ?? 0;
$nuevo_estado = $data['estado'] ?? '';

$estados_validos = ['pendiente', 'preparando', 'en camino', 'entregado', 'cancelado'];

if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['error' => 'Estado no válido']);
    exit;
}

// Verificar que el pedido pertenece a SU restaurante
$sqlCheck = "SELECT p.id FROM pedidos p 
             WHERE p.id = ? AND p.restaurante_id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("ii", $pedido_id, $usuario['restaurante_id']);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Pedido no encontrado o no pertenece a tu restaurante']);
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
