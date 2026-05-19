<?php
require_once 'conexion.php';

$ciudad = isset($_GET['ciudad']) ? $_GET['ciudad'] : '';

if (!empty($ciudad)) {
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
