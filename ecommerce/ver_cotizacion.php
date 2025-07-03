<?php
// File: ver_cotizacion.php
session_start();

require_once __DIR__ . '/includes/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ver_cotizaciones.php');
    exit;
}
$id = intval($_GET['id']);

// Cabecera de la cotización
$sql = "
    SELECT
      c.id,
      c.fecha,
      c.initial_date,
      c.final_date,
      c.sap_quote_id,
      c.sap_status,
      cl.razon_social
    FROM cotizaciones AS c
    JOIN clientes AS cl
      ON cl.cliente = c.cliente
    WHERE c.id = {$id}
";
$res  = $mysqli->query($sql);
$quote = $res->fetch_assoc();

// Ítems de la cotización (ahora con stock)
$itemSql = "
    SELECT
      ci.producto_id,
      p.descripcion,
      ci.cantidad,
      ci.precio_unitario,
      ci.descuento_k004,
      ci.descuento_k005,
      ci.descuento_k007,
      ci.precio_final,
      pr.stock
    FROM cotizacion_items AS ci
    LEFT JOIN productos AS p
      ON p.producto_id = ci.producto_id
    LEFT JOIN productos AS pr
      ON pr.producto_id = ci.producto_id
    WHERE ci.cotizacion_id = {$id}
";
$itemRes = $mysqli->query($itemSql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle Cotización #<?= $quote['id'] ?> – Maquinaria B2B</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: 'Roboto', sans-serif; }
    .detail-container { padding: 20px; }
    .card-custom { border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:30px; }
    .card-custom .card-header { background:#004080; color:#fff; font-weight:500; }
    .badge-success { background-color:#28a745; }
    .badge-warning { background-color:#ffc107; color:#212529; }
    .badge-danger  { background-color:#dc3545; }
    .btn-primary   { background-color:#004080; border:none; }
    .btn-primary:hover { background-color:#003060; }
    .table thead { background:#e9ecef; }
    .table-responsive { overflow-x:auto; }
    table { min-width:1000px; }
  </style>
</head>
<body class="bg-light">

  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <div class="container-fluid detail-container">
    <h2 class="mb-4"><i class="fas fa-file-alt mr-2 text-primary"></i>Detalle de Cotización #<?= $quote['id'] ?></h2>

    <div class="card card-custom mb-4">
      <div class="card-header">Información General</div>
      <div class="card-body">
        <p><strong><i class="fas fa-calendar-alt mr-1"></i>Fecha:</strong> <?= date('Y-m-d H:i', strtotime($quote['fecha'])) ?></p>
        <p><strong><i class="fas fa-building mr-1"></i>Cliente:</strong> <?= htmlspecialchars($quote['razon_social']) ?></p>
        <p><strong><i class="fas fa-clock mr-1"></i>Vigencia:</strong> <?= $quote['initial_date'] ?> al <?= $quote['final_date'] ?></p>
        <p><strong><i class="fas fa-hashtag mr-1"></i>SAP Quote ID:</strong> <?= $quote['sap_quote_id'] ?: '—' ?></p>
        <?php
          $st = strtoupper($quote['sap_status'] ?? '');
          if ($st === 'CREADO_EN_SAP') {
              $cls = 'success';
          } elseif (strpos($st, 'ERROR') !== false) {
              $cls = 'danger';
          } else {
              $cls = 'warning';
          }
        ?>
        <p><strong><i class="fas fa-info-circle mr-1"></i>SAP Status:</strong>
          <span class="badge badge-<?= $cls ?>"><?= $st ?: '—' ?></span>
        </p>
      </div>
    </div>

    <h4 class="mb-3"><i class="fas fa-list mr-2"></i>Items</h4>
    <?php if ($itemRes && $itemRes->num_rows > 0): ?>
      <?php $total = 0; ?>
      <div class="table-responsive mb-2">
        <table class="table table-bordered bg-white">
          <thead>
            <tr>
              <th>Producto ID</th>
              <th>Descripción</th>
              <th>Cantidad</th>
              <th>Stock</th>
              <th>Precio Unitario</th>
              <th>K004</th>
              <th>K005</th>
              <th>K007</th>
              <th>Precio Final</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($item = $itemRes->fetch_assoc()):
                $lineTotal = $item['precio_final'] * $item['cantidad'];
                $total += $lineTotal;
            ?>
            <tr>
              <td><?= htmlspecialchars($item['producto_id']) ?></td>
              <td><?= htmlspecialchars($item['descripcion']) ?></td>
              <td><?= $item['cantidad'] ?></td>
              <td><?= $item['stock'] ?></td>
              <td>$<?= number_format($item['precio_unitario'],2) ?></td>
              <td>$<?= number_format($item['descuento_k004'],2) ?></td>
              <td>$<?= number_format($item['descuento_k005'],2) ?></td>
              <td>$<?= number_format($item['descuento_k007'],2) ?></td>
              <td>$<?= number_format($item['precio_final'],2) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="text-right mb-4">
        <h5>Total a Pagar: <span class="text-primary">$<?= number_format($total,2) ?></span></h5>
      </div>
    <?php else: ?>
      <div class="alert alert-info">No hay items para esta cotización.</div>
    <?php endif; ?>

    <div class="d-flex">
      <a href="ver_cotizaciones.php" class="btn btn-secondary mr-2">
        <i class="fas fa-arrow-left mr-1"></i>Volver al listado
      </a>
      <a href="pedido_form.php?cotizacion_id=<?= $quote['id'] ?>" class="btn btn-primary">
        <i class="fas fa-truck mr-1"></i>Crear Pedido
      </a>
    </div>
  </div>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
