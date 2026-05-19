<?php
require_once 'conexion.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

if (empty($token)) {
    http_response_code(401);
    echo json_encode(['error' => 'Token requerido']);
    exit;
}

// Obtener ID del usuario desde el token
$usuario_id = explode(':', base64_decode($token))[0];

// Verificar que el usuario existe
$sqlCheck = "SELECT id, rol FROM usuarios WHERE id = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $usuario_id);
$stmtCheck->execute();
$userResult = $stmtCheck->get_result();
$usuario = $userResult->fetch_assoc();

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no válido']);
    exit;
}

// Si es restaurante, mostrar pedidos de SU restaurante
if ($usuario['rol'] === 'restaurante') {
    // Obtener el restaurante_id del usuario
    $sqlRest = "SELECT restaurante_id FROM usuarios WHERE id = ?";
    $stmtRest = $conn->prepare($sqlRest);
    $stmtRest->bind_param("i", $usuario_id);
    $stmtRest->execute();
    $restResult = $stmtRest->get_result();
    $restauranteData = $restResult->fetch_assoc();
    $restaurante_id = $restauranteData['restaurante_id'] ?? 0;
    
    // Pedidos del restaurante
    $sql = "SELECT p.*, r.nombre as restaurante_nombre, u.nombre as cliente_nombre
            FROM pedidos p 
            JOIN restaurantes r ON p.restaurante_id = r.id 
            JOIN usuarios u ON p.usuario_id = u.id
            WHERE p.restaurante_id = ? 
            ORDER BY p.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $restaurante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => $row['id'],
            'restaurante' => $row['restaurante_nombre'],
            'cliente' => $row['cliente_nombre'],
            'total' => $row['total'],
            'estado' => $row['estado'],
            'fecha' => $row['fecha']
        ];
    }
    
} else {
    // Si es cliente, mostrar SOLO sus pedidos
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
}

echo json_encode($pedidos);
$conn->close();
?>
