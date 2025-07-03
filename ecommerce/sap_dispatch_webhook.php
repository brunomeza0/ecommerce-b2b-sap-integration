<?php
// File: sap_dispatch_webhook.php
// Este endpoint recibe el resultado final de SAP y actualiza el pedido en e-commerce,
// y sólo entonces descuenta stock de la base de datos.

// 1) Conexión a BD
$host   = 'localhost';
$dbname = 'maquinaria';
$user   = 'root';
$pass   = '5664193';

$mysqli = new mysqli($host, $user, $pass, $dbname);
$mysqli->set_charset('utf8mb4');
if ($mysqli->connect_error) {
    http_response_code(500);
    error_log("DispatchResult webhook DB connection error: " . $mysqli->connect_error);
    die("Error de conexión a BD");
}

// 2) Autenticación por token secreto
$SECRET_TOKEN = "TokenSecreto123";
$token = '';

// Puede venir en cabeceras Apache o en $_SERVER
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

// 3) Leer cuerpo JSON enviado por el middleware (resultado del despacho)
$input = file_get_contents('php://input');
$data  = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die("Bad Request: JSON inválido");
}

// 4) Extraer datos del JSON de resultado de despacho
$orderNumber    = $data['orderNumber']      ?? '';
$deliveryNumber = $data['deliveryNumber']   ?? '';
$materialDoc    = $data['materialDocument'] ?? '';
$status         = strtoupper($data['status'] ?? '');

// 5) Determinar estado final normalizado para almacenar en BD
$newStatus = in_array($status, ['S','CREADO_EN_SAP','COMPLETADO'])
           ? 'COMPLETADO'
           : 'ERROR';

// 6) Actualizar el pedido en la BD con la información del despacho
if ($orderNumber === '') {
    http_response_code(400);
    die("Bad Request: faltan identificadores");
}

$upd = $mysqli->prepare(
    "UPDATE pedidos
        SET dispatch_delivery_number   = ?,
            dispatch_material_document = ?,
            dispatch_status            = ?
      WHERE sap_order_id = ?"
);
$upd->bind_param('ssss', $deliveryNumber, $materialDoc, $newStatus, $orderNumber);
$upd->execute();
if ($upd->errno) {
    error_log("DispatchResult webhook pedidos update error ({$upd->errno}): {$upd->error}");
    http_response_code(500);
    die("Error al actualizar pedido");
}
$upd->close();

// 6.1) Si SAP confirmó COMPLETADO, descontar stock de cada línea
if ($newStatus === 'COMPLETADO') {
    // 6.1.1) Obtener el ID interno del pedido
    $pedidoId = 0;
    $sel = $mysqli->prepare("SELECT id FROM pedidos WHERE sap_order_id = ?");
    $sel->bind_param('s', $orderNumber);
    $sel->execute();
    $res = $sel->get_result();
    if ($row = $res->fetch_assoc()) {
        $pedidoId = (int)$row['id'];
    }
    $sel->close();

    if ($pedidoId > 0) {
        // 6.1.2) Cargar todas las líneas de pedido
        $linesRes = $mysqli->prepare(
          "SELECT producto_id, cantidad
             FROM pedido_items
            WHERE pedido_id = ?"
        );
        $linesRes->bind_param('i', $pedidoId);
        $linesRes->execute();
        $lines = $linesRes->get_result();
        $linesRes->close();

        // 6.1.3) Preparar statement de descuento
        $updStock = $mysqli->prepare(
          "UPDATE productos
              SET stock = stock - ?
            WHERE producto_id = ?"
        );

        // 6.1.4) Descontar por cada línea
        while ($line = $lines->fetch_assoc()) {
            $updStock->bind_param('is', $line['cantidad'], $line['producto_id']);
            $updStock->execute();
        }
        $updStock->close();
    }
}

// 7) Registrar evento en bitácora de pedidos (tabla log_pedidos)
$pedidoId = $pedidoId ?? 0;  // si no vino antes
$stmtLog = $mysqli->prepare(
    "INSERT INTO log_pedidos (pedido_id, evento, mensaje, fecha)
     VALUES (?, ?, ?, NOW())"
);
$evento  = ($newStatus === 'COMPLETADO') ? 'DESPACHO_CREADO' : 'DESPACHO_ERROR';
$mensaje = ($newStatus === 'COMPLETADO')
         ? "Despacho creado en SAP. Delivery={$deliveryNumber}, MaterialDoc={$materialDoc}"
         : "Error en creación de despacho SAP: " . ($data['messages'][0]['text'] ?? '—');
$stmtLog->bind_param('iss', $pedidoId, $evento, $mensaje);
$stmtLog->execute();
$stmtLog->close();

// 8) Responder OK al middleware
http_response_code(200);
echo "OK";
?>