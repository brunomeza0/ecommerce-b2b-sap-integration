<?php
// File: ver_factura.php
session_start();

require_once __DIR__ . '/includes/db.php';
$mysqli->set_charset('utf8mb4');

// Obtener ID de factura
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ver_pedidos.php');
    exit;
}

// 1) Cabecera de la factura + datos del pedido para el IGV y cliente
$stmt = $mysqli->prepare(
    "
    SELECT
      f.id,
      f.correlation_id,
      f.cliente,
      DATE_FORMAT(f.fecha, '%Y-%m-%d %H:%i') AS fecha_factura,
      f.sap_order_id,
      f.sap_invoice_id,
      f.sap_status,
      p.id         AS pedido_id,
      p.igv        AS pedido_igv
    FROM facturas f
    LEFT JOIN pedidos p
      ON p.sap_order_id = f.sap_order_id
    WHERE f.id = ?
    "
);
$stmt->bind_param('i', $id);
$stmt->execute();
$cab = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cab) {
    header('Location: ver_pedidos.php');
    exit;
}

// 2) Intentar leer líneas desde factura_items
$stmt2 = $mysqli->prepare(
    "
    SELECT
      fi.producto_id,
      fi.cantidad,
      fi.precio_unitario,
      fi.precio_total,
      p.descripcion
    FROM factura_items fi
    LEFT JOIN productos p ON p.producto_id = fi.producto_id
    WHERE fi.factura_id = ?
    ORDER BY fi.id
    "
);
$stmt2->bind_param('i', $id);
$stmt2->execute();
$lines = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

// 3) Si no hay líneas, cargar desde pedido_items + cliente_material o productos
if (empty($lines) && $cab['pedido_id']) {
    $pid = $cab['pedido_id'];
    // Padding de cliente a 10 dígitos (cliente en facturas viene sin ceros a la izquierda)
    $paddedCliente = str_pad($cab['cliente'], 10, '0', STR_PAD_LEFT);

    $stmt3 = $mysqli->prepare(
      "
      SELECT
        pi.producto_id,
        pi.cantidad,
        COALESCE(cm.precio, p.precio) AS precio_unitario,
        p.descripcion
      FROM pedido_items pi
      LEFT JOIN cliente_material cm
        ON cm.cliente  = ?
       AND cm.material = pi.producto_id
      LEFT JOIN productos p
        ON p.producto_id = pi.producto_id
      WHERE pi.pedido_id = ?
      ORDER BY pi.id
      "
    );
    $stmt3->bind_param('si', $paddedCliente, $pid);
    $stmt3->execute();
    $raw = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt3->close();

    $lines = [];
    foreach ($raw as $it) {
        $subtotal = $it['cantidad'] * $it['precio_unitario'];
        $lines[] = [
          'producto_id'     => $it['producto_id'],
          'descripcion'     => $it['descripcion'],
          'cantidad'        => $it['cantidad'],
          'precio_unitario' => $it['precio_unitario'],
          'precio_total'    => $subtotal,
        ];
    }
}

// 4) Calcular totales (subtotal, IGV y total)
$subTotal = 0.0;
foreach ($lines as $it) {
    $subTotal += $it['precio_total'];
}
$igvRate   = floatval($cab['pedido_igv']);      // porcentaje IGV
$igvAmount = $subTotal * $igvRate / 100.0;
$total     = $subTotal + $igvAmount;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle Factura #<?= htmlspecialchars($cab['id']) ?> — Maquinaria B2B</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body { background:#f4f6f9; font-family:'Roboto',sans-serif; }
    .detail-container { padding:20px; }
    .card-custom { border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:30px; }
    .card-custom .card-header { background:#004080; color:#fff; font-weight:500; }
    .badge-success { background:#28a745; }
    .badge-warning { background:#ffc107; color:#212529; }
    .badge-danger  { background:#dc3545; }
    .btn-primary   { background:#004080; border:none; }
    .btn-primary:hover { background:#003060; }
    .btn-secondary { background:#6c757d; border:none; }
    .btn-secondary:hover { background:#5a6268; }
    .table thead { background:#e9ecef; }
    .table-responsive { overflow-x:auto; }
    table { min-width:800px; }
  </style>
</head>
<body class="bg-light">

  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <div class="container-fluid detail-container">
    <h2 class="mb-4">
      <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i>
      Factura #<?= htmlspecialchars($cab['id']) ?> — <?= htmlspecialchars($cab['correlation_id']) ?>
    </h2>

    <div class="card card-custom mb-4">
      <div class="card-header">Datos de la Factura</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3"><i class="fas fa-building mr-1"></i>Cliente</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['cliente']) ?></dd>

          <dt class="col-sm-3"><i class="fas fa-calendar-alt mr-1"></i>Fecha Emisión</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['fecha_factura']) ?></dd>

          <dt class="col-sm-3"><i class="fas fa-hashtag mr-1"></i>SAP Order ID</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['sap_order_id'] ?: '—') ?></dd>

          <dt class="col-sm-3"><i class="fas fa-file-alt mr-1"></i>SAP Invoice ID</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['sap_invoice_id'] ?: '—') ?></dd>

          <dt class="col-sm-3"><i class="fas fa-info-circle mr-1"></i>Status SAP</dt>
          <?php
            $fs = strtoupper($cab['sap_status'] ?? '');
            if ($fs === '') { $fs = 'PENDIENTE'; }
            $fcls = strpos($fs,'ERROR')!==false ? 'danger' : ($fs==='COMPLETADO' ? 'success' : 'warning');
          ?>
          <dd class="col-sm-9"><span class="badge badge-<?= $fcls ?>"><?= $fs ?></span></dd>
        </dl>
      </div>
    </div>

    <h4 class="mb-3"><i class="fas fa-list-alt mr-2"></i>Detalles de Items</h4>
    <?php if (!empty($lines)): ?>
      <div class="table-responsive mb-4">
        <table class="table table-bordered bg-white table-sm">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Descripción</th>
              <th class="text-right">Cantidad</th>
              <th class="text-right">P. Unitario</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lines as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['producto_id']) ?></td>
              <td><?= htmlspecialchars($it['descripcion']) ?></td>
              <td class="text-right"><?= htmlspecialchars($it['cantidad']) ?></td>
              <td class="text-right"><?= number_format($it['precio_unitario'], 2) ?></td>
              <td class="text-right"><?= number_format($it['precio_total'],   2) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="4" class="text-right">Subtotal</th>
              <th class="text-right"><?= number_format($subTotal, 2) ?></th>
            </tr>
            <tr>
              <th colspan="4" class="text-right">IGV (<?= number_format($igvRate,2) ?>%)</th>
              <th class="text-right"><?= number_format($igvAmount,2) ?></th>
            </tr>
            <tr>
              <th colspan="4" class="text-right">Total</th>
              <th class="text-right"><?= number_format($total, 2) ?></th>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No se hallaron items para esta factura.</div>
    <?php endif; ?>

    <a href="ver_pedidos.php" class="btn btn-secondary">
      <i class="fas fa-arrow-left mr-1"></i>Volver al listado de pedidos
    </a>
  </div>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
