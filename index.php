<?php
echo json_encode([
    'status' => 'API funcionando',
    'endpoints' => [
        'GET /api/restaurantes.php',
        'GET /api/menu.php?id={id}',
        'POST /api/login.php',
        'POST /api/register.php',
        'GET /api/pedidos.php',
        'POST /api/pedido_crear.php',
        'GET /api/perfil.php',
        'PUT /api/perfil.php'
    ]
]);
?>
