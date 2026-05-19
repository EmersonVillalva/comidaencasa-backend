<?php
require_once 'conexion.php';

$sql = "SELECT id, nombre, descripcion, imagen FROM restaurantes";
$result = $conn->query($sql);

$restaurantes = [];
while ($row = $result->fetch_assoc()) {
    $restaurantes[] = $row;
}

echo json_encode($restaurantes);
$conn->close();
?>
