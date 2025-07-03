<?php
// sap_quote_webhook.php

// --- 1) Credenciales y conexión MySQLi ---
$host   = 'localhost';
$dbname = 'maquinaria';
$user   = 'root';
$pass   = '5664193';

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("Error conexión DB: " . $mysqli->connect_error);
    echo "Error conexión DB";
    exit;
}

// --- 2) Token secreto y lectura de cabeceras ---
$SECRET_TOKEN = "TokenSecreto123";
$token = '';

// Intentar getallheaders (Apache), o bien buscar en $_SERVER
if (function_exists('getallheaders')) {
    $hdrs = getallheaders();
    if (isset($hdrs['X-SAP-Token'])) {
        $token = $hdrs['X-SAP-Token'];
    } elseif (isset($hdrs['x-sap-token'])) {
        $token = $hdrs['x-sap-token'];
    }
}
if (empty($token) && isset($_SERVER['HTTP_X_SAP_TOKEN'])) {
    $token = $_SERVER['HTTP_X_SAP_TOKEN'];
}

if ($token !== $SECRET_TOKEN) {
    http_response_code(401);
    echo "Acceso no autorizado";
    exit;
}

// --- 3) Leer y decodificar JSON del body ---
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);
if (!is_array($data)) {
    http_response_code(400);
    echo "Datos inválidos";
    exit;
}

$corr   = $data['correlationId']  ?? '';
$sapId  = $data['sapQuoteId']     ?? '';
$status = $data['status']         ?? '';
if (!$corr || !$status) {
    http_response_code(400);
    echo "Faltan campos requeridos";
    exit;
}

// --- 4) Normalizar estado para tu esquema de BD ---
$status = strtoupper($status);
if ($status === 'OK' || $status === 'CREADO_EN_SAP') {
    $newStatus = 'CREADO_EN_SAP';
} else {
    $newStatus = 'ERROR_SAP';
}

// --- 5) Preparar y ejecutar UPDATE con mysqli ---
$sql = "UPDATE cotizaciones
           SET sap_status   = ?,
               sap_quote_id = ?
         WHERE correlation_id = ?";

if ($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param('sss', $newStatus, $sapId, $corr);
    $stmt->execute();

    if ($stmt->errno) {
        // Error en ejecución
        error_log("MySQLi execute error ({$stmt->errno}): {$stmt->error}");
        http_response_code(500);
        echo "Error interno";
        $stmt->close();
        exit;
    }

    if ($stmt->affected_rows === 0) {
        // No se actualizó ninguna fila: revisar que exista ese correlation_id
        error_log("No se encontró fila para actualizar. correlation_id = $corr");
    }

    $stmt->close();
} else {
    error_log("MySQLi prepare error ({$mysqli->errno}): {$mysqli->error}");
    http_response_code(500);
    echo "Error interno";
    exit;
}

// --- 6) Responder OK al middleware ---
echo "OK";
