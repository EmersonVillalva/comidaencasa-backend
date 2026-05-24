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

// Incluir conexión (esto es una auto-referencia, mantener como está)
require_once 'conexion.php';

// Obtener token del header Authorization
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

// Si no hay token, error 401
if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

// Decodificar token para obtener ID del usuario
$usuario_id = explode(':', base64_decode($token))[0];

// Leer datos del cuerpo de la petición
$data = json_decode(file_get_contents('php://input'), true);
$pedido_id = $data['pedido_id'] ?? 0;
$nuevo_estado = $data['estado'] ?? '';

// Estados válidos del pedido
$estados_validos = ['pendiente', 'preparando', 'en camino', 'entregado', 'cancelado'];

// Validar que el estado sea correcto
if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['error' => 'Estado no válido']);
    exit;
}

// Actualizar estado del pedido
$sqlUpdate = "UPDATE pedidos SET estado = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($sqlUpdate);
$stmtUpdate->bind_param("si", $nuevo_estado, $pedido_id);

// Devolver respuesta
if ($stmtUpdate->execute()) {
    echo json_encode(['mensaje' => 'Estado actualizado correctamente', 'nuevo_estado' => $nuevo_estado]);
} else {
    echo json_encode(['error' => 'Error al actualizar']);
}

// Cerrar conexión
$conn->close();
?>
