<?php
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$sql = "SELECT * FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if ($password == $row['password']) {
        $token = base64_encode($row['id'] . ':' . time());
        
        echo json_encode([
            'token' => $token,
            'usuario' => [
                'id' => $row['id'],
                'nombre' => $row['nombre'],
                'email' => $row['email'],
                'rol' => $row['rol'],
                'restaurante_id' => $row['restaurante_id']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Contraseña incorrecta']);
    }
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Usuario no encontrado']);
}

$conn->close();
?>
