<?php
// File: pedido_submit.php
session_start();
// Conexión MySQL (maquinaria)
$mysqli = new mysqli('localhost','root','5664193','maquinaria');
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) die('Error de conexión: ' . $mysqli->connect_error);

// Recoger POST
$mode         = $_POST['mode'];
$cotizacionId = (int)($_POST['cotizacion_id'] ?? 0);
$pedidoId     = (int)($_POST['pedido_id'] ?? 0);
$cliente      = $_POST['cliente'];
$igv          = (float)$_POST['igv'];
$tipoCambio   = (float)$_POST['tipo_cambio'];
$fechaDesp    = $_POST['schedule_line'];
$direccion    = $_POST['direccion_entrega'] ?? '';

// Validación de stock antes de crear el pedido
foreach ($_POST['items'] as $it) {
    $st = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
    $st->bind_param('s', $it['producto_id']);
    $st->execute();
    $st->bind_result($stockAvail);
    $st->fetch();
    $st->close();
    if ($it['cantidad'] > $stockAvail) {
        die("Error: No hay suficiente stock para {$it['producto_id']}. Disponible: $stockAvail, solicitado: {$it['cantidad']}.");
    }
}

// Armar DTO para el middleware
$dto = [
  'correlationId'    => uniqid('ECOMMERCE_PEDIDO_'),
  'pedidoId'         => $mode==='edit' ? $pedidoId : null,
  'cotizacionId'     => $cotizacionId,
  'cliente'          => $cliente,
  'igv'              => $igv,
  'tipoCambio'       => $tipoCambio,
  'fechaDespacho'    => $fechaDesp,
  'direccionEntrega' => $direccion,
  'items'            => []
];
foreach ($_POST['items'] as $it) {
  $dto['items'][] = [
    'material'      => $it['producto_id'],
    'quantity'      => $it['cantidad'],
    'dateFrom'      => $_POST['schedule_line'],
    'dateTo'        => $_POST['schedule_line'],
    'refQuote'      => $it['ref_quote'],
    'plant'         => $it['plant'],
    'refQuoteItem'  => $it['ref_quote_item'],
    'description'   => $it['descripcion'] ?? '',
    'unit'          => $it['unit'] ?? '',
    'salesOrg'      => $it['salesOrg'] ?? '',
    'distrChan'     => $it['distrChan'] ?? '',
    'division'      => $it['division'] ?? ''
  ];
}

// Llamada al middleware (HTTP Basic Auth)
$ch = curl_init('http://localhost:8443/api/pedidos');
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
    die("Error al crear pedido en middleware: HTTP $httpCode → $respBody");
}
$resp = json_decode($respBody, true);

// Insertar/actualizar en la BD local
if ($mode==='create') {
    $st = $mysqli->prepare("
      INSERT INTO pedidos
        (correlation_id, cotizacion_id, cliente, igv, tipo_cambio, fecha_despacho, direccion_entrega, sap_order_id, sap_status)
      VALUES (?,?,?,?,?,?,?,?,?)
    ");
    $st->bind_param(
      "siiddssss",
      $dto['correlationId'], $cotizacionId, $cliente, $igv, $tipoCambio,
      $fechaDesp, $direccion, $resp['sapOrderId'], $resp['sapStatus']
    );
    $st->execute();
    $newId = $st->insert_id;
    $st->close();
} else {
    $newId = $pedidoId;
    $st = $mysqli->prepare("
      UPDATE pedidos
         SET cliente=?, igv=?, tipo_cambio=?, fecha_despacho=?, direccion_entrega=?, sap_order_id=?, sap_status=?
       WHERE id=?
    ");
    $st->bind_param(
      "ddssdssi",
      $cliente, $igv, $tipoCambio, $fechaDesp, $direccion,
      $resp['sapOrderId'], $resp['sapStatus'], $pedidoId
    );
    $st->execute();
    $st->close();
    // Eliminar líneas antiguas (si es edición)
    $mysqli->query("DELETE FROM pedido_items WHERE pedido_id=$pedidoId");
}

// Guardar líneas del pedido y actualizar stock
$st2 = $mysqli->prepare("
  INSERT INTO pedido_items
    (pedido_id, producto_id, cantidad, date_from, date_to, ref_quote, plant, ref_quote_item)
  VALUES (?,?,?,?,?,?,?,?)
");
//$updStock = $mysqli->prepare("UPDATE productos SET stock = stock - ? WHERE producto_id = ?");
foreach ($dto['items'] as $it) {
    $st2->bind_param(
      "isisssss",
      $newId,
      $it['material'],
      $it['quantity'],
      $it['dateFrom'],
      $it['dateTo'],
      $it['refQuote'],
      $it['plant'],
      $it['refQuoteItem']
    );
    $st2->execute();
    // Reducir stock
    //$updStock->bind_param('is', $it['quantity'], $it['material']);
    //$updStock->execute();
}
$st2->close();
//$updStock->close();

// Redirigir al listado de pedidos
header("Location: ver_pedidos.php");
exit;
