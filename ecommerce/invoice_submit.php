<?php
// invoice_submit.php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_POST['pedidoId'], $_POST['sap_order_id'], $_POST['sap_delivery'], $_POST['invoiceDate'])) {
    die('Faltan datos para generar factura.');
}

$pedidoId     = intval($_POST['pedidoId']);
$sapOrderId   = $_POST['sap_order_id'];
$sapDelivery  = $_POST['sap_delivery'];
$invoiceDate  = $_POST['invoiceDate'];

// 1) Obtener cliente para asociar factura (opcional)
$stmt = $mysqli->prepare("SELECT cliente FROM pedidos WHERE id = ?");
$stmt->bind_param('i', $pedidoId);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$res) die("Pedido no encontrado.");
$clienteId = $res['cliente'];

// 2) Insertar registro preliminar en facturas
$correlation_id = bin2hex(random_bytes(16));
$stmt = $mysqli->prepare("
    INSERT INTO facturas (id, correlation_id, cliente, fecha, sap_order_id, sap_invoice_id, sap_status)
    VALUES (NULL, ?, ?, NOW(), ?, '', 'PENDIENTE_SAP')
");
$stmt->bind_param('sss', $correlation_id, $clienteId, $sapOrderId);
$stmt->execute();
$facturaId = $stmt->insert_id;
$stmt->close();

// 3) Construir DTO de factura
$dto = [
    'orderNumber'    => $sapOrderId,
    'deliveryNumber' => $sapDelivery,
    'invoiceDate'    => $invoiceDate
];

// 4) Enviar solicitud al middleware (/api/invoice)
$ch = curl_init('http://localhost:8443/api/invoice');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => json_encode($dto),
    CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
    CURLOPT_USERPWD        => 'brunomeza0:5664193'
]);
$respBody = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    die("Error en envío al middleware: HTTP $httpCode – $respBody");
}

$resp = json_decode($respBody, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die('Respuesta inválida del middleware: ' . json_last_error_msg());
}

// 5) Actualizar factura en BD (estado inicial)
$sapInvoiceId = $resp['invoiceNumber'] ?? '';
$invoiceStatus = $resp['status'] ?? '';
$stmt = $mysqli->prepare("
    UPDATE facturas
       SET sap_invoice_id = ?, sap_status = ?
     WHERE sap_order_id = ?
");
$stmt->bind_param('sss', $sapInvoiceId, $invoiceStatus, $sapOrderId);
$stmt->execute();
$stmt->close();

// 6) Redirigir al detalle del pedido
header("Location: ver_pedido.php?id={$pedidoId}");
exit;
?>
