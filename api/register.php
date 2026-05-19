<?php
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? explode('@', $data['email'])[0];
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$rol = $data['rol'] ?? 'cliente';
$direccion = $data['direccion'] ?? '';
$restaurante_id = $data['restaurante_id'] ?? null;

// Validar rol
$roles_validos = ['cliente', 'restaurante', 'repartidor'];
if (!in_array($rol, $roles_validos)) {
    $rol = 'cliente';
}

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Email y contraseña requeridos']);
    exit;
}

// Si es restaurante, verificar que seleccionó un restaurante válido
if ($rol === 'restaurante' && !$restaurante_id) {
    echo json_encode(['error' => 'Debes seleccionar un restaurante']);
    exit;
}

// Verificar si el email ya existe
$sqlCheck = "SELECT id FROM usuarios WHERE email = ?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("s", $email);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['error' => 'El email ya está registrado']);
    exit;
}

// Insertar usuario
$sql = "INSERT INTO usuarios (nombre, email, password, rol, direccion, restaurante_id) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $nombre, $email, $password, $rol, $direccion, $restaurante_id);

if ($stmt->execute()) {
    echo json_encode([
        'mensaje' => 'Usuario registrado con éxito',
        'rol' => $rol
    ]);
} else {
    echo json_encode(['error' => 'Error al registrar: ' . $conn->error]);
}

$conn->close();
?>
