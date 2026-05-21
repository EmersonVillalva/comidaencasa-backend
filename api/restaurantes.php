<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'conexion.php';

// Obtener ciudad desde la petición GET
$ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';
$token = isset($_GET['token']) ? $_GET['token'] : '';

// Si hay token, obtener ciudad del usuario autenticado
if (!empty($token)) {
    $usuario_id = explode(':', base64_decode($token))[0];
    $sqlUser = "SELECT ciudad FROM usuarios WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $usuario_id);
    $stmtUser->execute();
    $resultUser = $stmtUser->get_result();
    $usuario = $resultUser->fetch_assoc();
    if ($usuario && !empty($usuario['ciudad'])) {
        $ciudad = $usuario['ciudad'];
    }
}

// Filtrar restaurantes por ciudad
if (!empty($ciudad) && $ciudad !== 'General') {
    $sql = "SELECT * FROM restaurantes WHERE ciudad = ? OR ciudad = 'General' ORDER BY nombre";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ciudad);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM restaurantes ORDER BY nombre";
    $result = $conn->query($sql);
}

$restaurantes = [];
while ($row = $result->fetch_assoc()) {
    $restaurantes[] = $row;
}

echo json_encode($restaurantes);
$conn->close();
?>
