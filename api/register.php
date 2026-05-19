<?php
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? explode('@', $data['email'])[0];
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$direccion = $data['direccion'] ?? '';

$sql = "INSERT INTO usuarios (nombre, email, password, rol, direccion) VALUES (?, ?, ?, 'cliente', ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $nombre, $email, $password, $direccion);

if ($stmt->execute()) {
    echo json_encode(['mensaje' => 'Usuario registrado con éxito']);
} else {
    echo json_encode(['error' => 'Error al registrar: ' . $conn->error]);
}

$conn->close();
?>
