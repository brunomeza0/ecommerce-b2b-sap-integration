<?php
// File: ver_cotizaciones.php
session_start();

require_once __DIR__ . '/includes/db.php';

// Consulta de cotizaciones con datos del cliente
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
    ORDER BY c.fecha DESC
";
$result = $mysqli->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ver Cotizaciones – Maquinaria B2B</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: 'Roboto', sans-serif; }
    .table-container { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    .table-container h2 { font-weight: 500; color: #004080; }
    .badge-success { background-color: #28a745; }
    .badge-warning { background-color: #ffc107; color: #212529; }
    .badge-danger  { background-color: #dc3545; }
    .btn-primary  { background-color: #004080; border: none; }
    .btn-primary:hover { background-color: #003060; }
    .table thead { background: #e9ecef; }
  </style>
</head>
<body class="bg-light">

  <!-- Navbar -->
  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <div class="container my-5">
    <div class="table-container">
      <h2 class="mb-4"><i class="fas fa-file-alt mr-2"></i>Listado de Cotizaciones</h2>
      <?php if ($result && $result->num_rows > 0): ?>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Desde</th>
                <th>Hasta</th>
                <th>SAP Quote ID</th>
                <th>Estado</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php
                $status = $row['sap_status'];
                if ($status === 'CREADO_EN_SAP') {
                    $badgeClass = 'success';
                    $label = 'CREADO EN SAP';
                } elseif (stripos($status, 'ERROR') !== false) {
                    $badgeClass = 'danger';
                    $label = strtoupper($status);
                } else {
                    $badgeClass = 'warning';
                    $label = strtoupper($status);
                }
              ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= date('Y-m-d H:i', strtotime($row['fecha'])) ?></td>
                <td><?= htmlspecialchars($row['razon_social']) ?></td>
                <td><?= $row['initial_date'] ?></td>
                <td><?= $row['final_date'] ?></td>
                <td><?= $row['sap_quote_id'] ?? '—' ?></td>
                <td>
                  <span class="badge badge-<?= $badgeClass ?>">
                    <?= $label ?>
                  </span>
                </td>
                <td>
                  <a href="ver_cotizacion.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-primary">
                    <i class="fas fa-eye mr-1"></i>Ver detalle
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="alert alert-info mb-0">
          <i class="fas fa-info-circle mr-1"></i>No se encontraron cotizaciones.
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
