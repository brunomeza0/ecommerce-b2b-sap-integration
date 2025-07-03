<?php
// File: dispatch_submit.php

session_start();

// 0) Conexión a la BD
require_once __DIR__ . '/includes/db.php';
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
    die('Error de conexión (includes/db.php): ' . $mysqli->connect_error);
}

// 1) Recoger y validar campos básicos
$pedidoId     = isset($_POST['pedido_id'])   ? (int) $_POST['pedido_id'] : 0;
$shippingPt   = trim($_POST['shippingPoint'] ?? '');
$shippingDate = trim($_POST['shippingDate'] ?? '');

// --- NUEVO BLOQUE ---
// Asegúrate de que la fecha venga en AAAA-MM-DD
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $shippingDate)) {
    die('Formato de fecha de envío inválido.');
}

// Convierte a AAAAMMDD (lo que requiere tu middleware/ABAP)
$shippingDate = date('Ymd', strtotime($shippingDate));
// --- FIN BLOQUE NUEVO ---

// 2) Validaciones de presencia
if ($pedidoId <= 0) {
    die('Falta el ID de pedido para despachar.');
}
if ($shippingPt === '' || $shippingDate === '') {
    die('Faltan datos obligatorios para el despacho (punto o fecha).');
}

// 3) Recoger override de dirección (si viene del formulario)
$addr = $_POST['addressOverride'] ?? [];
$addrName       = trim($addr['Name1']      ?? '');
$addrStreet     = trim($addr['Street']     ?? '');
$addrPostalCode = trim($addr['PostalCode'] ?? '');
$addrCity       = trim($addr['City']       ?? '');
$addrCountry    = trim($addr['Country']    ?? '');

// 4) Leer líneas desde POST o, si no hay, desde pedido_items
$itemsRaw = $_POST['items'] ?? [];
if (!is_array($itemsRaw) || count($itemsRaw) === 0) {
    $itemsRaw = [];
    $stmtItems = $mysqli->prepare("
        SELECT producto_id   AS material,
               cantidad       AS cantidad,
               plant          AS plant,
               ref_quote_item AS ref_quote_item
          FROM pedido_items
         WHERE pedido_id = ?
    ");
    $stmtItems->bind_param('i', $pedidoId);
    $stmtItems->execute();
    $resItems = $stmtItems->get_result();
    while ($row = $resItems->fetch_assoc()) {
        $itemsRaw[] = [
            'material'   => $row['material'],
            'cantidad'   => $row['cantidad'],
            'plant'      => $row['plant'],
            'storageLoc' => $row['plant'],
            'line'       => $row['ref_quote_item'] ?? ''
        ];
    }
    $stmtItems->close();
}

// 5) Limpiar y reindexar ítems válidos
$items = [];
foreach ($itemsRaw as $it) {
    $material   = trim($it['material']   ?? '');
    $cantidad   = (float) ($it['quantity'] ?? $it['cantidad'] ?? 0);
    $plant      = trim($it['plant']      ?? '');
    $storageLoc = trim($it['storageLoc'] ?? '');

    if ($material === '' || $cantidad <= 0) {
        continue;
    }

    // Usar número de línea original si existe, sino numerar secuencialmente
    $lineVal = isset($it['line']) ? (string) $it['line'] : '';
    $lineVal = trim($lineVal);
    if ($lineVal === '') {
        $lineVal = count($items) + 1;
    }

    $items[] = [
        'line'       => $lineVal,
        'material'   => $material,
        'quantity'   => $cantidad,
        'plant'      => $plant,
        'storageLoc' => $storageLoc,
    ];
}

if (count($items) === 0) {
    die('No hay líneas de ítem válidas para despachar.');
}

// 6) Obtener SAP Order ID asociado al pedido
$stmt0 = $mysqli->prepare("SELECT sap_order_id FROM pedidos WHERE id = ?");
$stmt0->bind_param('i', $pedidoId);
$stmt0->execute();
$res0 = $stmt0->get_result()->fetch_assoc();
$stmt0->close();

if (!$res0 || empty($res0['sap_order_id'])) {
    die("El pedido ID $pedidoId no existe o no tiene sap_order_id.");
}
$sapOrderId = $res0['sap_order_id'];

// 7) Construir DTO de despacho para enviar al middleware
$dto = [
    'orderNumber'     => $sapOrderId,
    'shippingPoint'   => $shippingPt,
    'shippingDate'    => $shippingDate,
    'addressOverride' => [
        'name1'      => $addrName,
        'street'     => $addrStreet,
        'postalCode' => $addrPostalCode,
        'city'       => $addrCity,
        'country'    => $addrCountry,
    ],
    'items' => $items
];

// 8) Enviar la solicitud de despacho al middleware (API REST)
$ch = curl_init('http://localhost:8443/api/dispatch');
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

// 9) Guardar resultado preliminar en la BD (estado inicial PENDIENTE_SAP)
$deliveryNumber   = $resp['deliveryNumber']   ?? '';
$materialDocument = $resp['materialDocument'] ?? '';
$dispatchStatus   = $resp['status']           ?? '';

$stmt2 = $mysqli->prepare("
    UPDATE pedidos
       SET dispatch_delivery_number   = ?,
           dispatch_material_document = ?,
           dispatch_status            = ?
     WHERE id = ?
");
$stmt2->bind_param('sssi',
    $deliveryNumber,
    $materialDocument,
    $dispatchStatus,
    $pedidoId
);
$stmt2->execute();
$stmt2->close();

// 10) Redirigir al detalle del pedido
header("Location: ver_pedido.php?id={$pedidoId}");
exit;
?>
