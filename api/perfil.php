<?php
require_once 'conexion.php';

$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');
$usuario_id = explode(':', base64_decode($token))[0];

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $sql = "SELECT id, nombre, email, direccion, rol FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo json_encode($result->fetch_assoc());
    
} elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = $data['nombre'] ?? '';
    $email = $data['email'] ?? '';
    $direccion = $data['direccion'] ?? '';
    
    $sql = "UPDATE usuarios SET nombre = ?, email = ?, direccion = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $nombre, $email, $direccion, $usuario_id);
    
    if ($stmt->execute()) {
        echo json_encode(['mensaje' => 'Perfil actualizado']);
    } else {
        echo json_encode(['error' => 'Error al actualizar']);
    }
}

$conn->close();
?>
