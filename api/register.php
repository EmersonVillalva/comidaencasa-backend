<?php
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$rol = $data['rol'] ?? 'cliente';
$direccion = $data['direccion'] ?? '';
$nombre_restaurante = $data['nombre_restaurante'] ?? '';
$descripcion_restaurante = $data['descripcion_restaurante'] ?? '';

// Validar rol
$roles_validos = ['cliente', 'restaurante', 'repartidor'];
if (!in_array($rol, $roles_validos)) {
    $rol = 'cliente';
}

if (empty($email) || empty($password)) {
    echo json_encode(['error' => 'Email y contraseña requeridos']);
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

$restaurante_id = null;

// Si es restaurante, crear el restaurante primero
if ($rol === 'restaurante') {
    if (empty($nombre_restaurante)) {
        echo json_encode(['error' => 'Debes ingresar el nombre del restaurante']);
        exit;
    }
    
    // Insertar el restaurante
    $sqlRest = "INSERT INTO restaurantes (nombre, descripcion) VALUES (?, ?)";
    $stmtRest = $conn->prepare($sqlRest);
    $stmtRest->bind_param("ss", $nombre_restaurante, $descripcion_restaurante);
    
    if (!$stmtRest->execute()) {
        echo json_encode(['error' => 'Error al crear el restaurante: ' . $conn->error]);
        exit;
    }
    
    $restaurante_id = $conn->insert_id;
}

// Insertar usuario
$sql = "INSERT INTO usuarios (nombre, email, password, rol, direccion, restaurante_id) VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $nombre, $email, $password, $rol, $direccion, $restaurante_id);

if ($stmt->execute()) {
    echo json_encode([
        'mensaje' => $rol === 'restaurante' ? 'Restaurante y usuario creados con éxito' : 'Usuario registrado con éxito',
        'rol' => $rol,
        'restaurante_id' => $restaurante_id
    ]);
} else {
    echo json_encode(['error' => 'Error al registrar usuario: ' . $conn->error]);
}

$conn->close();
?>
