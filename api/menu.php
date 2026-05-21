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

$restaurante_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($restaurante_id <= 0) {
    echo json_encode([]);
    exit;
}

$sql = "SELECT * FROM menu WHERE restaurante_id = ? ORDER BY id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $restaurante_id);
$stmt->execute();
$result = $stmt->get_result();

$menu = [];
while ($row = $result->fetch_assoc()) {
    $menu[] = [
        'id' => $row['id'],
        'nombre' => $row['nombre'],
        'descripcion' => $row['descripcion'],
        'precio' => floatval($row['precio'])
    ];
}

echo json_encode($menu);
$conn->close();
?>
