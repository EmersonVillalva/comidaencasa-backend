<?php
require_once 'conexion.php';

$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$rol = $data['rol'] ?? 'cliente';
$ciudad = $data['ciudad'] ?? 'General';
$direccion = $data['direccion'] ?? '';

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
    $nombre_restaurante = $data['nombre_restaurante'] ?? '';
    $tipo_comida = $data['tipo_comida'] ?? 'Otros';
    
    if (empty($nombre_restaurante)) {
        echo json_encode(['error' => 'Debes ingresar el nombre del restaurante']);
        exit;
    }
    
    if (empty($ciudad)) {
        echo json_encode(['error' => 'Debes ingresar la ciudad del restaurante']);
        exit;
    }
    
    // Insertar el restaurante
    $sqlRest = "INSERT INTO restaurantes (nombre, tipo_comida, ciudad, descripcion) VALUES (?, ?, ?, ?)";
    $stmtRest = $conn->prepare($sqlRest);
    $stmtRest->bind_param("ssss", $nombre_restaurante, $tipo_comida, $ciudad, $descripcion);
    
    if (!$stmtRest->execute()) {
        echo json_encode(['error' => 'Error al crear el restaurante: ' . $conn->error]);
        exit;
    }
    
    $restaurante_id = $conn->insert_id;
}

// Insertar usuario
$sql = "INSERT INTO usuarios (nombre, email, password, rol, direccion, ciudad, restaurante_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssi", $nombre, $email, $password, $rol, $direccion, $ciudad, $restaurante_id);

if ($stmt->execute()) {
    echo json_encode([
        'mensaje' => $rol === 'restaurante' ? 'Restaurante registrado con éxito' : 'Usuario registrado con éxito',
        'rol' => $rol
    ]);
} else {
    echo json_encode(['error' => 'Error al registrar: ' . $conn->error]);
}

$conn->close();
?>
