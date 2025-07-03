<?php
// sap_order_webhook.php

// --- 1) Conexión a BD ---
$host   = 'localhost';
$dbname = 'maquinaria';
$user   = 'root';
$pass   = '5664193';

$mysqli = new mysqli($host, $user, $pass, $dbname);
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("Order webhook DB error: " . $mysqli->connect_error);
    echo "Error conexión DB";
    exit;
}

// --- 2) Validar token ---
$SECRET_TOKEN = "TokenSecreto123";
$token = '';
if (function_exists('getallheaders')) {
    $hdrs = getallheaders();
    $token = $hdrs['X-SAP-Token'] ?? $hdrs['x-sap-token'] ?? '';
}
if (!$token && isset($_SERVER['HTTP_X_SAP_TOKEN'])) {
    $token = $_SERVER['HTTP_X_SAP_TOKEN'];
}
if ($token !== $SECRET_TOKEN) {
    http_response_code(401);
    echo "Acceso no autorizado";
    error_log("Order webhook unauthorized, received token='$token'");
    exit;
}

// --- 3) Leer CSV plano desde el body ---
$raw = file_get_contents('php://input');
if (trim($raw) === '') {
    http_response_code(400);
    echo "Sin datos en el body";
    exit;
}
// Normalizar saltos de línea
$raw = str_replace(["\r\n","\r"], "\n", trim($raw));
$lines = explode("\n", $raw);
if (count($lines) < 2) {
    http_response_code(400);
    echo "Datos inválidos";
    exit;
}

// --- 4) Procesar cada línea omitiendo la cabecera ---
foreach ($lines as $i => $line) {
    if ($i === 0) continue;            // salto cabecera
    $line = trim($line);
    if ($line === '') continue;

    $cols = str_getcsv($line, ';');
    // Esperamos al menos 3 campos
    if (count($cols) < 3) {
        error_log("Order webhook: línea inválida #$i: '$line'");
        continue;
    }

    $corr     = trim($cols[0]);
    $sapOrder = trim($cols[1]);
    $status   = strtoupper(trim($cols[2]));

    if ($corr === '' || $sapOrder === '') {
        error_log("Order webhook: falta correlationId o sapOrderId en línea #$i");
        continue;
    }

    // Normalizar estado
    $newStatus = ($status === 'OK' || $status === 'CREADO_EN_SAP')
        ? 'CREADO_EN_SAP'
        : 'ERROR_SAP';

    // --- 5) UPDATE en la tabla pedidos ---
    $sql = "UPDATE pedidos
               SET sap_order_id = ?,
                   sap_status   = ?
             WHERE correlation_id = ?";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('sss', $sapOrder, $newStatus, $corr);
        $stmt->execute();
        if ($stmt->errno) {
            error_log("Order webhook MySQLi execute error ({$stmt->errno}): {$stmt->error}");
        } elseif ($stmt->affected_rows === 0) {
            error_log("Order webhook: no se encontró fila para actualizar correlation_id='$corr'");
        }
        $stmt->close();
    } else {
        error_log("Order webhook MySQLi prepare error ({$mysqli->errno}): {$mysqli->error}");
    }
}

// --- 6) Responder OK ---
echo "OK";
