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

// Obtener datos del usuario
$sqlUser = "SELECT id, nombre, email, rol, restaurante_id FROM usuarios WHERE id = ?";
$stmtUser = $conn->prepare($sqlUser);
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$userResult = $stmtUser->get_result();
$usuario = $userResult->fetch_assoc();

if (!$usuario) {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no válido']);
    exit;
}

$rol = $usuario['rol'];
$pedidos = [];

// Si es RESTAURANTE: ver pedidos de SU restaurante
if ($rol === 'restaurante') {
    $restaurante_id = $usuario['restaurante_id'];
    
    if (!$restaurante_id) {
        echo json_encode(['error' => 'Tu cuenta no está vinculada a ningún restaurante', 'pedidos' => []]);
        exit;
    }
    
    $sql = "SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email
            FROM pedidos p 
            JOIN usuarios u ON p.usuario_id = u.id 
            WHERE p.restaurante_id = ? 
            ORDER BY p.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $restaurante_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => $row['id'],
            'cliente' => $row['cliente_nombre'],
            'cliente_email' => $row['cliente_email'],
            'total' => $row['total'],
            'estado' => $row['estado'],
            'fecha' => $row['fecha'],
            'rol' => 'restaurante'
        ];
    }
    
} 
// Si es CLIENTE: ver SOLO sus propios pedidos
else if ($rol === 'cliente') {
    $sql = "SELECT p.*, r.nombre as restaurante_nombre 
            FROM pedidos p 
            JOIN restaurantes r ON p.restaurante_id = r.id 
            WHERE p.usuario_id = ? 
            ORDER BY p.fecha DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => $row['id'],
            'restaurante' => $row['restaurante_nombre'],
            'total' => $row['total'],
            'estado' => $row['estado'],
            'fecha' => $row['fecha'],
            'rol' => 'cliente'
        ];
    }
}
// Otros roles (repartidor, etc.)
else {
    $pedidos = [];
}

echo json_encode($pedidos);
$conn->close();
?>
