<?php
// sap_invoice_webhook.php
// Endpoint que recibe resultado de SAP para facturas

// 1) Conexión a BD
$host   = 'localhost';
$dbname = 'maquinaria';
$user   = 'root';
$pass   = '5664193';

$mysqli = new mysqli($host, $user, $pass, $dbname);
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("InvoiceResult webhook DB connection error: " . $mysqli->connect_error);
    die("Error de conexión a BD");
}

// 2) Autenticación por token
$SECRET_TOKEN = "TokenSecreto123";
$token = '';
if (function_exists('apache_request_headers')) {
    $hdrs = apache_request_headers();
    $token = $hdrs['X-SAP-Token'] ?? $hdrs['x-sap-token'] ?? '';
}
if (!$token && isset($_SERVER['HTTP_X_SAP_TOKEN'])) {
    $token = $_SERVER['HTTP_X_SAP_TOKEN'];
}
if ($token !== $SECRET_TOKEN) {
    http_response_code(403);
    die("Unauthorized: token inválido");
}

// 3) Leer cuerpo JSON
$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die("Bad Request: JSON inválido");
}

// 4) Extraer datos de resultado de factura
$orderNumber  = $data['orderNumber']   ?? '';
$invoiceNumber = $data['invoiceNumber'] ?? '';
$status       = $data['status']        ?? '';

$newStatus = ($status === 'S' || $status === 'COMPLETADO' || $status === 'CREADO_EN_SAP')
           ? 'COMPLETADO'
           : 'ERROR';

if ($orderNumber === '') {
    http_response_code(400);
    die("Bad Request: faltan identificadores");
}

// 5) Actualizar la factura en BD
$upd = $mysqli->prepare("
    UPDATE facturas
       SET sap_invoice_id = ?, sap_status = ?
     WHERE sap_order_id = ?
");
$upd->bind_param('sss', $invoiceNumber, $newStatus, $orderNumber);
$upd->execute();
if ($upd->errno) {
    error_log("InvoiceResult webhook DB update error ({$upd->errno}): {$upd->error}");
    http_response_code(500);
    die("Error al actualizar factura en BD");
}
$upd->close();

// 6) (Opcional) Registrar log de evento
$facturaId = 0;
if ($sel = $mysqli->prepare("SELECT id FROM facturas WHERE sap_order_id = ?")) {
    $sel->bind_param('s', $orderNumber);
    $sel->execute();
    $res = $sel->get_result();
    if ($res && $row = $res->fetch_assoc()) {
        $facturaId = (int)$row['id'];
    }
    $sel->close();
}
if ($facturaId) {
    $evento = ($newStatus === 'COMPLETADO') ? 'FACTURA_CREADA' : 'FACTURA_ERROR';
    $mensaje = ($newStatus === 'COMPLETADO')
             ? "Factura creada en SAP. Invoice={$invoiceNumber}"
             : ("Error en creación de factura SAP: " . ($data['messages'][0]['text'] ?? ''));
    $stmtLog = $mysqli->prepare("
        INSERT INTO log_facturas (log_id, factura_id, evento, mensaje, fecha)
        VALUES (NULL, ?, ?, ?, NOW())
    ");
    $stmtLog->bind_param('iss', $facturaId, $evento, $mensaje);
    $stmtLog->execute();
    $stmtLog->close();
}

// 7) Responder OK
echo "OK";
?>
