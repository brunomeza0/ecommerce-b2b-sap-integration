<?php
// File: ver_pedidos.php
session_start();

require_once __DIR__ . '/includes/db.php';
$mysqli->set_charset('utf8mb4');

// Consulta de todos los pedidos
$res = $mysqli->query("
    SELECT
      id,
      correlation_id,
      cliente,
      DATE_FORMAT(fecha,           '%Y-%m-%d %H:%i') AS fecha,
      igv,
      DATE_FORMAT(fecha_despacho,  '%Y-%m-%d')       AS despacho,
      direccion_entrega,
      sap_order_id,
      sap_status
    FROM pedidos
    ORDER BY fecha DESC
");
$pedidos = $res->fetch_all(MYSQLI_ASSOC);
$res->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Listado de Pedidos – Maquinaria B2B</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: #f4f6f9;
      font-family: 'Roboto', sans-serif;
    }
    .table-container {
      background: #fff;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .table-container h2 {
      font-weight: 500;
      color: #004080;
    }
    .badge-success { background-color: #28a745; }
    .badge-warning { background-color: #ffc107; color: #212529; }
    .badge-danger  { background-color: #dc3545; }
    .btn-primary   { background-color: #004080; border: none; }
    .btn-primary:hover { background-color: #003060; }
    .table thead { background: #e9ecef; }

    /* Asegurar scroll horizontal */
    .table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    /* Garantizar que la tabla sea lo suficientemente ancha para mostrar todas las columnas */
    table {
      min-width: 1200px;
    }
  </style>
</head>
<body class="bg-light">

  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <div class="container-fluid my-5">
    <div class="table-container">
      <h2 class="mb-4"><i class="fas fa-truck mr-2"></i>Pedidos Registrados</h2>
      <?php if (!empty($pedidos)): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Correlation</th>
                <th>Cliente</th>
                <th>Fecha</th>
                <th>IGV&nbsp;%</th>
                <th>Despacho</th>
                <th>Dirección</th>
                <th>SAP Order</th>
                <th>Status SAP</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($pedidos as $p): ?>
                <?php
                  $st = strtoupper($p['sap_status']);
                  if ($st === 'CREADO_EN_SAP') {
                      $cls = 'success';
                  } elseif (strpos($st, 'ERROR') !== false) {
                      $cls = 'danger';
                  } else {
                      $cls = 'warning';
                  }
                ?>
                <tr>
                  <td><?= $p['id'] ?></td>
                  <td><?= htmlspecialchars($p['correlation_id']) ?></td>
                  <td><?= htmlspecialchars($p['cliente']) ?></td>
                  <td><?= $p['fecha'] ?></td>
                  <td><?= $p['igv'] ?></td>
                  <td><?= $p['despacho'] ?></td>
                  <td><?= htmlspecialchars($p['direccion_entrega']) ?></td>
                  <td><?= htmlspecialchars($p['sap_order_id'] ?: '—') ?></td>
                  <td>
                    <span class="badge badge-<?= $cls ?>">
                      <?= $st ?>
                    </span>
                  </td>
                  <td>
                    <a href="ver_pedido.php?id=<?= $p['id'] ?>"
                       class="btn btn-sm btn-primary">
                      <i class="fas fa-eye mr-1"></i>Ver
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle mr-1"></i>No se encontraron pedidos.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
