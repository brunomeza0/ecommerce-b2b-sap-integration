<?php
// File: ver_pedido.php
session_start();

require_once __DIR__ . '/includes/db.php';
$mysqli->set_charset('utf8mb4');

// Obtener ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: ver_pedidos.php');
    exit;
}

// Cabecera del pedido, incluyendo información de factura
$stmt = $mysqli->prepare(
    "
    SELECT
      p.id,
      p.correlation_id,
      p.cliente,
      p.igv,
      DATE_FORMAT(p.fecha_despacho, '%Y-%m-%d') AS despacho,
      p.direccion_entrega,
      p.sap_order_id,
      p.sap_status,
      p.dispatch_delivery_number,
      p.dispatch_material_document,
      p.dispatch_status,
      f.id               AS factura_id,
      f.sap_invoice_id   AS invoice_number,
      f.sap_status       AS invoice_status
    FROM pedidos p
    LEFT JOIN facturas f
      ON f.sap_order_id = p.sap_order_id
    WHERE p.id = ?
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

// Líneas del pedido
$stmt2 = $mysqli->prepare(
    "
    SELECT
      pi.producto_id,
      pi.cantidad,
      DATE_FORMAT(pi.date_from, '%Y-%m-%d') AS date_from,
      DATE_FORMAT(pi.date_to,   '%Y-%m-%d') AS date_to,
      pi.ref_quote,
      pi.plant,
      pi.ref_quote_item,
      p.stock
    FROM pedido_items pi
    LEFT JOIN productos p ON p.producto_id = pi.producto_id
    WHERE pi.pedido_id = ?
    ORDER BY pi.id
    "
);
$stmt2->bind_param('i', $id);
$stmt2->execute();
$lines = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detalle Pedido #<?= $id ?> — Maquinaria B2B</title>
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
    .btn-success   { background:#28a745; border:none; }
    .btn-success:hover { background:#218838; }
    .table thead { background:#e9ecef; }
    .table-responsive { overflow-x:auto; }
    table { min-width:900px; }
  </style>
</head>
<body class="bg-light">

  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <div class="container-fluid detail-container">
    <h2 class="mb-4"><i class="fas fa-truck mr-2 text-primary"></i>Pedido #<?= $id ?> — <?= htmlspecialchars($cab['correlation_id']) ?></h2>

    <div class="card card-custom mb-4">
      <div class="card-header">Datos Generales</div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-3"><i class="fas fa-building mr-1"></i>Cliente</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['cliente']) ?></dd>

          <dt class="col-sm-3"><i class="fas fa-percent mr-1"></i>IGV (%)</dt>
          <dd class="col-sm-9"><?= $cab['igv'] ?></dd>

          <dt class="col-sm-3"><i class="fas fa-calendar-alt mr-1"></i>Fecha Despacho</dt>
          <dd class="col-sm-9"><?= $cab['despacho'] ?></dd>

          <dt class="col-sm-3"><i class="fas fa-map-marker-alt mr-1"></i>Dirección Entrega</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['direccion_entrega']) ?></dd>

          <dt class="col-sm-3"><i class="fas fa-hashtag mr-1"></i>SAP Order ID</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['sap_order_id'] ?: '—') ?></dd>

          <dt class="col-sm-3"><i class="fas fa-info-circle mr-1"></i>Status SAP</dt>
          <?php
            $st  = strtoupper($cab['sap_status'] ?? '');
            $cls = $st === 'CREADO_EN_SAP' ? 'success' : (strpos($st,'ERROR')!==false ? 'danger' : 'warning');
          ?>
          <dd class="col-sm-9"><span class="badge badge-<?= $cls ?>"><?= $st ?></span></dd>

          <dt class="col-sm-3"><i class="fas fa-box-open mr-1"></i>Entrega SAP</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['dispatch_delivery_number'] ?: '—') ?></dd>

          <dt class="col-sm-3"><i class="fas fa-file-alt mr-1"></i>Doc. Stock</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['dispatch_material_document'] ?: '—') ?></dd>

          <dt class="col-sm-3"><i class="fas fa-shipping-fast mr-1"></i>Status Despacho</dt>
          <?php
            $ds = strtoupper($cab['dispatch_status'] ?? '');
            if ($ds === '') { $ds = 'PENDIENTE'; }
            elseif ($ds === 'CREADO_EN_SAP') { $ds = 'COMPLETADO'; }
            elseif ($ds === 'ERROR_SAP') { $ds = 'ERROR'; }
            $dcls = strpos($ds,'ERROR')!==false ? 'danger' : ($ds==='COMPLETADO' ? 'success' : 'warning');
          ?>
          <dd class="col-sm-9"><span class="badge badge-<?= $dcls ?>"><?= $ds ?></span></dd>

          <dt class="col-sm-3"><i class="fas fa-file-invoice-dollar mr-1"></i>Factura SAP</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($cab['invoice_number'] ?: '—') ?></dd>

          <dt class="col-sm-3"><i class="fas fa-receipt mr-1"></i>Status Factura</dt>
          <?php
            $fs = strtoupper($cab['invoice_status'] ?? '');
            if ($fs === '') { $fs = 'PENDIENTE'; }
            elseif ($fs === 'CREADO_EN_SAP') { $fs = 'COMPLETADO'; }
            $fcls = strpos($fs,'ERROR')!==false ? 'danger' : ($fs==='COMPLETADO' ? 'success' : 'warning');
          ?>
          <dd class="col-sm-9"><span class="badge badge-<?= $fcls ?>"><?= $fs ?></span></dd>
        </dl>

        <?php if ($ds === 'COMPLETADO'): ?>
          <hr>
          <?php if ($fs === 'COMPLETADO' && $cab['factura_id']): ?>
            <h4 class="mt-4"><i class="fas fa-eye mr-2"></i>Factura Generada</h4>
            <a href="ver_factura.php?id=<?= $cab['factura_id'] ?>" class="btn btn-success mb-4">
              <i class="fas fa-file-invoice mr-1"></i>Ver Factura
            </a>
          <?php else: ?>
            <h4 class="mt-4"><i class="fas fa-file-invoice mr-2"></i>Generar Factura SAP</h4>
            <form action="invoice_submit.php" method="post" class="mb-4">
              <input type="hidden" name="pedidoId"     value="<?= $id ?>">
              <input type="hidden" name="sap_order_id" value="<?= htmlspecialchars($cab['sap_order_id']) ?>">
              <input type="hidden" name="sap_delivery" value="<?= htmlspecialchars($cab['dispatch_delivery_number']) ?>">
              <div class="form-group row">
                <label for="invoiceDate" class="col-sm-3 col-form-label">
                  <i class="fas fa-calendar-check mr-1"></i>Fecha de Factura
                </label>
                <div class="col-sm-3">
                  <input type="date" class="form-control" name="invoiceDate" id="invoiceDate"
                         value="<?= date('Y-m-d') ?>" required>
                </div>
              </div>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane mr-1"></i>Enviar Factura
              </button>
            </form>
          <?php endif; ?>
        <?php endif; ?>

      </div>
    </div>

    <h4 class="mb-3"><i class="fas fa-list-alt mr-2"></i>Líneas del Pedido</h4>
    <?php if (!empty($lines)): ?>
      <div class="table-responsive mb-4">
        <table class="table table-bordered bg-white table-sm">
          <thead>
            <tr>
              <th>Línea</th>
              <th>Material</th>
              <th>Cantidad</th>
              <th>Desde</th>
              <th>Hasta</th>
              <th>Ref. Cot.</th>
              <th>Planta</th>
              <th>Stock Inicial</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lines as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['ref_quote_item']) ?></td>
              <td><?= htmlspecialchars($it['producto_id']) ?></td>
              <td><?= $it['cantidad'] ?></td>
              <td><?= $it['date_from'] ?></td>
              <td><?= $it['date_to'] ?></td>
              <td><?= htmlspecialchars($it['ref_quote'] ?? '') ?></td>
              <td><?= htmlspecialchars($it['plant']) ?></td>
              <td><?= $it['stock'] ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">Este pedido no tiene líneas asociadas.</div>
    <?php endif; ?>

    <!-- Sección de Despacho -->
    <?php if (!$cab['dispatch_status'] || strpos(strtoupper($cab['dispatch_status']),'ERROR')!==false): ?>
      <div class="card card-custom mb-4">
        <div class="card-header">Proceso de Despacho</div>
        <div class="card-body">
          <form method="POST" action="dispatch_submit.php">
            <input type="hidden" name="pedido_id" value="<?= $id ?>">

            <div class="form-row">
              <div class="form-group col-md-4">
                <label for="shippingPoint"><i class="fas fa-warehouse mr-1"></i>Punto de Expedición</label>
                <select id="shippingPoint" name="shippingPoint" class="form-control" required>
                  <?php
                    $pts = array_unique(array_column($lines,'plant'));
                    foreach ($pts as $pt) {
                      echo "<option>".htmlspecialchars($pt)."</option>";
                    }
                    echo '<option>MI00</option>';
                  ?>
                </select>
              </div>
              <div class="form-group col-md-4">
                <label for="shippingDate"><i class="fas fa-calendar-day mr-1"></i>Fecha de Envío</label>
                <input type="date" id="shippingDate" name="shippingDate" class="form-control"
                       value="<?= date('Y-m-d') ?>" required>
              </div>
            </div>

            <fieldset class="border p-3 mb-3">
              <legend class="w-auto font-weight-bold"><i class="fas fa-map-marked-alt mr-1"></i>Dirección de Envío</legend>
              <div class="form-row">
                <div class="form-group col-md-4">
                  <label>Nombre completo</label>
                  <input type="text" name="addressOverride[Name1]" class="form-control" required>
                </div>
                <div class="form-group col-md-4">
                  <label>Calle y número</label>
                  <input type="text" name="addressOverride[Street]" class="form-control" required>
                </div>
                <div class="form-group col-md-2">
                  <label>CP</label>
                  <input type="text" name="addressOverride[PostalCode]" class="form-control" required>
                </div>
                <div class="form-group col-md-2">
                  <label>Ciudad</label>
                  <input type="text" name="addressOverride[City]" class="form-control" required>
                </div>
                <div class="form-group col-md-2">
                  <label>País</label>
                  <input type="text" name="addressOverride[Country]" class="form-control" required>
                </div>
              </div>
            </fieldset>

            <h5 class="mb-3"><i class="fas fa-boxes mr-1"></i>Líneas a Despachar</h5>
            <div class="table-responsive mb-3">
              <table class="table table-bordered table-sm">
                <thead class="thead-light">
                  <tr><th>Línea</th><th>Material</th><th>Cantidad</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($lines as $idx => $it): ?>
                  <tr>
                    <td><?= htmlspecialchars($it['ref_quote_item']) ?></td>
                    <td><?= htmlspecialchars($it['producto_id']) ?></td>
                    <td><?= $it['cantidad'] ?></td>
                  </tr>
                  <input type="hidden" name="items[<?= $idx ?>][line]"       value="<?= htmlspecialchars($it['ref_quote_item']) ?>">
                  <input type="hidden" name="items[<?= $idx ?>][material]"   value="<?= htmlspecialchars($it['producto_id']) ?>">
                  <input type="hidden" name="items[<?= $idx ?>][quantity]"   value="<?= $it['cantidad'] ?>">
                  <input type="hidden" name="items[<?= $idx ?>][plant]"      value="<?= htmlspecialchars($it['plant']) ?>">
                  <input type="hidden" name="items[<?= $idx ?>][storageLoc]" value="<?= htmlspecialchars($it['plant']) ?>">
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-truck-loading mr-1"></i>Despachar</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
    <!-- /Sección de Despacho -->

    <a href="ver_pedidos.php" class="btn btn-secondary"><i class="fas fa-arrow-left mr-1"></i>Volver al listado</a>
  </div>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
