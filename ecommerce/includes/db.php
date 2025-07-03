<?php
// includes/db.php

// Parámetros de conexión
$DB_HOST   = 'localhost';
$DB_NAME   = 'maquinaria';
$DB_USER   = 'root';
$DB_PASS   = '5664193';

// Conectar
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("Error conexión DB: " . $mysqli->connect_error);
    echo "Error conexión DB";
    exit;
}

// Opcional: función helper para usar la conexión
function db() {
    global $mysqli;
    return $mysqli;
}

