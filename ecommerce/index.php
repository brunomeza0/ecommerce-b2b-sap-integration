<?php
// File: index.php
session_start();
$errors = [];  // Para acumular errores de stock

$step_in = isset($_REQUEST['step']) ? intval($_REQUEST['step']) : 1;
if ($step_in === 1) {
    $_SESSION['machines']    = [];
    $_SESSION['accessories'] = [];
}

require_once __DIR__ . '/includes/db.php';
// Credenciales API middleware
$apiUser = 'brunomeza0';
$apiPass = '5664193';

// Funciones de obtención de datos
function getClientMaterialData($mysqli, $cliente, $material) {
    $stmt = $mysqli->prepare(
        "SELECT precio, descuento_k004, descuento_k005, descuento_k007
         FROM cliente_material
         WHERE cliente = ? AND material = ?"
    );
    $stmt->bind_param('ss', $cliente, $material);
    $stmt->execute();
    $stmt->bind_result($precio, $d004, $d005, $d007);
    if ($stmt->fetch()) {
        $stmt->close();
        return [
            'precio'   => floatval($precio),
            'desc004'  => floatval($d004),
            'desc005'  => floatval($d005),
            'desc007'  => floatval($d007),
        ];
    }
    $stmt->close();
    return ['precio'=>0.00,'desc004'=>0.00,'desc005'=>0.00,'desc007'=>0.00];
}

function getProductInfo($mysqli, $cliente, $material) {
    $stmt = $mysqli->prepare(
        "SELECT descripcion
         FROM cliente_material
         WHERE cliente = ? AND material = ?"
    );
    $stmt->bind_param('ss', $cliente, $material);
    $stmt->execute();
    $stmt->bind_result($descripcion);
    if ($stmt->fetch()) {
        $stmt->close();
        return ['descripcion' => $descripcion];
    }
    $stmt->close();
    return ['descripcion' => $material];
}

// Manejo de pasos
$step = isset($_POST['step']) ? intval($_POST['step']) : 1;

if ($step === 1 && isset($_POST['company'], $_POST['initial_date'], $_POST['final_date'])) {
    $_SESSION['company']      = $_POST['company'];
    $_SESSION['initial_date'] = $_POST['initial_date'];
    $_SESSION['final_date']   = $_POST['final_date'];
    $step = 2;
}

if ($step === 2) {
    if (isset($_POST['add_machine'], $_POST['machine_type'], $_POST['machine_model'], $_POST['machine_qty'])) {
        $pid       = $_POST['machine_model'];
        $groupName = $_POST['machine_type'];
        $qty       = max(1, intval($_POST['machine_qty']));
        $stStock   = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
        $stStock->bind_param('s', $pid);
        $stStock->execute();
        $stStock->bind_result($stockAvail);
        $stStock->fetch();
        $stStock->close();
        if ($qty > $stockAvail) {
            $errors[] = "No hay suficiente stock de <strong>$pid</strong>. Disponible: $stockAvail, solicitado: $qty.";
        } else {
            $_SESSION['machines'][] = ['pid'=>$pid,'type'=>$groupName,'qty'=>$qty];
        }
    }
    if (isset($_POST['update_machine'], $_POST['index'], $_POST['machine_qty'])) {
        $i = intval($_POST['index']);
        if (isset($_SESSION['machines'][$i])) {
            $newQty = max(1, intval($_POST['machine_qty']));
            $pid    = $_SESSION['machines'][$i]['pid'];
            $stStock = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
            $stStock->bind_param('s', $pid);
            $stStock->execute();
            $stStock->bind_result($stockAvail);
            $stStock->fetch();
            $stStock->close();
            if ($newQty > $stockAvail) {
                $errors[] = "No hay suficiente stock de <strong>$pid</strong> para actualizar cantidad. Disponible: $stockAvail, solicitado: $newQty.";
            } else {
                $_SESSION['machines'][$i]['qty'] = $newQty;
            }
        }
    }
    if (isset($_POST['delete_machine'], $_POST['index'])) {
        $i = intval($_POST['index']);
        if (isset($_SESSION['machines'][$i])) {
            array_splice($_SESSION['machines'], $i, 1);
        }
    }
    if (isset($_POST['to_accessories'])) {
        $step = 3;
    }
}

if ($step === 3) {
    if (isset($_POST['add_accessory'], $_POST['accessory_pid'], $_POST['accessory_qty'])) {
        $pid = $_POST['accessory_pid'];
        $qty = max(1, intval($_POST['accessory_qty']));
        $stStock = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
        $stStock->bind_param('s', $pid);
        $stStock->execute();
        $stStock->bind_result($stockAvail);
        $stStock->fetch();
        $stStock->close();
        if ($qty > $stockAvail) {
            $errors[] = "No hay suficiente stock de <strong>$pid</strong>. Disponible: $stockAvail, solicitado: $qty.";
        } else {
            $_SESSION['accessories'][] = ['pid'=>$pid,'qty'=>$qty];
        }
    }
    if (isset($_POST['update_accessory'], $_POST['index'], $_POST['accessory_qty'])) {
        $i = intval($_POST['index']);
        if (isset($_SESSION['accessories'][$i])) {
            $newQty = max(1, intval($_POST['accessory_qty']));
            $pid    = $_SESSION['accessories'][$i]['pid'];
            $stStock = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
            $stStock->bind_param('s', $pid);
            $stStock->execute();
            $stStock->bind_result($stockAvail);
            $stStock->fetch();
            $stStock->close();
            if ($newQty > $stockAvail) {
                $errors[] = "No hay suficiente stock de <strong>$pid</strong> para actualizar cantidad. Disponible: $stockAvail, solicitado: $newQty.";
            } else {
                $_SESSION['accessories'][$i]['qty'] = $newQty;
            }
        }
    }
    if (isset($_POST['delete_accessory'], $_POST['index'])) {
        $i = intval($_POST['index']);
        if (isset($_SESSION['accessories'][$i])) {
            array_splice($_SESSION['accessories'], $i, 1);
        }
    }
    if (isset($_POST['to_summary'])) {
        $step = 4;
    }
}

if ($step === 4 && isset($_POST['generate'])) {
    foreach (array_merge(
        array_map(function($it){ return ['pid'=>$it['pid'],'qty'=>$it['qty']]; }, $_SESSION['machines']),
        array_map(function($a){ return ['pid'=>$a['pid'],'qty'=>$a['qty']]; }, $_SESSION['accessories'])
    ) as $line) {
        $stStock = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
        $stStock->bind_param('s', $line['pid']);
        $stStock->execute();
        $stStock->bind_result($stockAvail);
        $stStock->fetch();
        $stStock->close();
        if ($line['qty'] > $stockAvail) {
            $errors[] = "Al generar la cotización no hay suficiente stock de <strong>{$line['pid']}</strong>. Disponible: $stockAvail, solicitado: {$line['qty']}.";
        }
    }
    if (empty($errors)) {
        $stmt = $mysqli->prepare(
            "INSERT INTO cotizaciones (cliente, fecha, initial_date, final_date)
             VALUES (?, NOW(), ?, ?)"
        );
        $stmt->bind_param('sss', $_SESSION['company'], $_SESSION['initial_date'], $_SESSION['final_date']);
        $stmt->execute();
        $cotizacion_id = $mysqli->insert_id;
        $stmt->close();

        function insertItem($mysqli, $cot_id, $pid, $qty, $cliente) {
            $data = getClientMaterialData($mysqli, $cliente, $pid);
            $precio_unit       = $data['precio'];
            $d004_amt          = $data['desc004'];
            $d005_amt          = $data['desc005'];
            $d007_amt          = $data['desc007'];
            $precio_final_unit = $precio_unit + $d004_amt + $d005_amt + $d007_amt;

            $ins = $mysqli->prepare(
                "INSERT INTO cotizacion_items
                 (cotizacion_id, producto_id, cantidad, precio_unitario, descuento_k004, descuento_k005, descuento_k007, precio_final)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $ins->bind_param(
                'isiddddd',
                $cot_id, $pid, $qty,
                $precio_unit,
                $data['desc004'],
                $data['desc005'],
                $data['desc007'],
                $precio_final_unit
            );
            $ins->execute();
            $ins->close();
        }

        if (!empty($_SESSION['machines'])) {
            foreach ($_SESSION['machines'] as $it) {
                insertItem($mysqli, $cotizacion_id, $it['pid'], $it['qty'], $_SESSION['company']);
            }
        }
        if (!empty($_SESSION['accessories'])) {
            foreach ($_SESSION['accessories'] as $a) {
                insertItem($mysqli, $cotizacion_id, $a['pid'], $a['qty'], $_SESSION['company']);
            }
        }

        function generarUUID() {
            $data = openssl_random_pseudo_bytes(16);
            assert(strlen($data) == 16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        }
        $correlationId = generarUUID();
        $upd = $mysqli->prepare(
            "UPDATE cotizaciones
             SET sap_status = 'PENDIENTE_SAP', correlation_id = ?
             WHERE id = ?"
        );
        $upd->bind_param('si', $correlationId, $cotizacion_id);
        $upd->execute();
        $upd->close();

        $valInicio = date('Ymd', strtotime($_SESSION['initial_date']));
        $valFin    = date('Ymd', strtotime($_SESSION['final_date']));
        $cotizacionData = [
            'correlationId' => $correlationId,
            'quoteId'       => (int)$cotizacion_id,
            'customerCode'  => $_SESSION['company'],
            'initialDate'   => $valInicio,
            'finalDate'     => $valFin,
            'items'         => []
        ];
        if (!empty($_SESSION['machines'])) {
            foreach ($_SESSION['machines'] as $it) {
                $d  = getClientMaterialData($mysqli, $_SESSION['company'], $it['pid']);
                $pr = getProductInfo($mysqli, $_SESSION['company'], $it['pid']);
                $cotizacionData['items'][] = [
                    'productCode' => $it['pid'],
                    'description' => $pr['descripcion'],
                    'unit'        => 'EA',
                    'quantity'    => $it['qty'],
                    'price'       => $d['precio']
                ];
            }
        }
        if (!empty($_SESSION['accessories'])) {
            foreach ($_SESSION['accessories'] as $a) {
                $d  = getClientMaterialData($mysqli, $_SESSION['company'], $a['pid']);
                $pr = getProductInfo($mysqli, $_SESSION['company'], $a['pid']);
                $cotizacionData['items'][] = [
                    'productCode' => $a['pid'],
                    'description' => $pr['descripcion'],
                    'unit'        => 'EA',
                    'quantity'    => $a['qty'],
                    'price'       => $d['precio']
                ];
            }
        }
        $requestBody   = json_encode($cotizacionData);
        $middlewareUrl = "http://localhost:8443/api/cotizacion";
        $ch = curl_init($middlewareUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($ch, CURLOPT_USERPWD, $apiUser . ':' . $apiPass);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === false || $httpCode !== 200) {
            error_log("Error al enviar cotización $cotizacion_id a SAP (corr: $correlationId): " . curl_error($ch));
        }
        curl_close($ch);

        $message = "✅ Tu consulta ha sido generada con éxito. (ID cotización: $cotizacion_id)";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inquiry – Maquinaria B2B</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: 'Roboto', sans-serif; }
    .step-nav .nav-link.active { background: #004080; color:#fff; font-weight:600; }
    .card-custom { border:0; border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:30px; }
    .form-section { padding:30px; background:#fff; border-radius:8px; margin-bottom:30px; }
    .input-group-text { background:#e9ecef; border:0; }
    .btn-primary { background:#004080; border:0; }
    .btn-success { background:#007b33; border:0; }
    .table thead { background:#ecf0f1; }
  </style>
</head>
<body>
  <?php require_once __DIR__ . '/includes/header.php'; ?>

  <div class="container my-5">
    <?php if (!empty($message) && empty($errors)): ?>
      <div class="alert alert-success shadow-sm"><?= $message ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger shadow-sm">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= $e ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <ul class="nav nav-pills justify-content-center mb-4 step-nav">
      <li class="nav-item">
        <a class="nav-link <?= $step===1?'active':'' ?>">1. Empresa & Fechas</a>
      </li>
      <li class="nav-item mx-2">
        <a class="nav-link <?= $step===2?'active':'' ?>">2. Maquinaria</a>
      </li>
      <li class="nav-item mx-2">
        <a class="nav-link <?= $step===3?'active':'' ?>">3. Accesorios</a>
      </li>
      <li class="nav-item">
        <a class="nav-link <?= $step===4?'active':'' ?>">4. Resumen</a>
      </li>
    </ul>

    <?php if (empty($message) || !empty($errors)): ?>

      <!-- Paso 1 -->
      <?php if ($step === 1): ?>
      <div class="form-section">
        <form method="post">
          <input type="hidden" name="step" value="1">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label><i class="fas fa-building mr-2"></i>Empresa</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-tags"></i></span>
                </div>
                <select class="form-control" name="company" required>
                  <option value="">—Seleccione—</option>
                  <?php
                    $rs = $mysqli->query("SELECT cliente, razon_social FROM clientes");
                    while ($r = $rs->fetch_assoc()):
                  ?>
                  <option value="<?= $r['cliente'] ?>"><?= htmlspecialchars($r['razon_social']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
            </div>
            <div class="form-group col-md-3">
              <label><i class="fas fa-calendar-alt mr-2"></i>Validez desde</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                </div>
                <input type="date" class="form-control" name="initial_date" required>
              </div>
            </div>
            <div class="form-group col-md-3">
              <label><i class="fas fa-calendar-check mr-2"></i>Validez hasta</label>
              <div class="input-group">
                <div class="input-group-prepend">
                  <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                </div>
                <input type="date" class="form-control" name="final_date" required>
              </div>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-block">Continuar al Paso 2</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Paso 2 -->
      <?php if ($step === 2):
        $client = $_SESSION['company'];
        $rs = $mysqli->query("
          SELECT p.producto_id, gn.grupo_name
          FROM productos p
          JOIN grupo_names gn ON p.grupo = gn.grupo_short
          WHERE p.grupo != 'ACC'
        ");
        $machine_map = [];
        while ($r = $rs->fetch_assoc()) {
          $pid       = $r['producto_id'];
          $groupName = $r['grupo_name'];
          $info      = getClientMaterialData($mysqli, $client, $pid);
          $st = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
          $st->bind_param('s', $pid);
          $st->execute();
          $st->bind_result($stockVal);
          $st->fetch();
          $st->close();
          $machine_map[$groupName][] = [
            'pid'=>$pid,
            'precio'=>$info['precio'],
            'd004'=>$info['desc004'],
            'd005'=>$info['desc005'],
            'd007'=>$info['desc007'],
            'stock'=>intval($stockVal)
          ];
        }
      ?>
      <div class="form-section">
        <form method="post" class="mb-4">
          <input type="hidden" name="step" value="2">
          <div class="form-row align-items-end">
            <div class="form-group col-md-4">
              <label><i class="fas fa-cogs mr-2"></i>Tipo de Maquinaria</label>
              <select id="machine_type" class="form-control" name="machine_type" onchange="updateModels()" required>
                <option value="">—Tipo—</option>
                <?php foreach ($machine_map as $name => $_): ?>
                  <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-4">
              <label><i class="fas fa-industry mr-2"></i>Modelo</label>
              <select id="machine_model" class="form-control" name="machine_model" required>
                <option value="">—Modelo—</option>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label><i class="fas fa-hashtag mr-2"></i>Cantidad</label>
              <input type="number" class="form-control" name="machine_qty" min="1" value="1" required>
            </div>
            <div class="form-group col-md-2 text-right">
              <button name="add_machine" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i>Añadir
              </button>
            </div>
          </div>
        </form>

        <?php if (!empty($_SESSION['machines'])): ?>
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr><th>Tipo</th><th>Modelo</th><th>Cantidad</th><th>Stock</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              <?php foreach ($_SESSION['machines'] as $i => $it):
                $st2 = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
                $st2->bind_param('s', $it['pid']);
                $st2->execute();
                $st2->bind_result($stk);
                $st2->fetch();
                $st2->close();
              ?>
              <tr>
                <td><?= htmlspecialchars($it['type']) ?></td>
                <td><?= htmlspecialchars($it['pid']) ?></td>
                <td><?= htmlspecialchars($it['qty']) ?></td>
                <td><?= $stk ?></td>
                <td>
                  <form method="post" class="form-inline d-inline">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <input type="number" name="machine_qty" value="<?= htmlspecialchars($it['qty']) ?>"
                           min="1" class="form-control form-control-sm mr-1" style="width:60px;">
                    <button name="update_machine" class="btn btn-sm btn-primary">OK</button>
                  </form>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="step" value="2">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <button name="delete_machine" class="btn btn-sm btn-danger">✕</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="step" value="2">
          <button name="to_accessories" class="btn btn-primary btn-block">Siguiente: Accesorios &rarr;</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Paso 3 -->
      <?php if ($step === 3):
        $client = $_SESSION['company'];
        $rs2 = $mysqli->query("SELECT producto_id FROM productos WHERE grupo='ACC'");
        $acc_map = [];
        while ($r2 = $rs2->fetch_assoc()) {
          $pid2 = $r2['producto_id'];
          $info = getClientMaterialData($mysqli, $client, $pid2);
          $st = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
          $st->bind_param('s', $pid2);
          $st->execute();
          $st->bind_result($stockAcc);
          $st->fetch();
          $st->close();
          $acc_map[] = [
            'pid'=>$pid2,
            'precio'=>$info['precio'],
            'd004'=>$info['desc004'],
            'd005'=>$info['desc005'],
            'd007'=>$info['desc007'],
            'stock'=>intval($stockAcc)
          ];
        }
      ?>
      <div class="form-section">
        <form method="post" class="mb-4">
          <input type="hidden" name="step" value="3">
          <div class="form-row align-items-end">
            <div class="form-group col-md-6">
              <label><i class="fas fa-plug mr-2"></i>Accesorio</label>
              <select id="accessory_pid" class="form-control" name="accessory_pid" required>
                <option value="">—Seleccione—</option>
                <?php foreach ($acc_map as $a): ?>
                  <option value="<?= htmlspecialchars($a['pid']) ?>"
                          data-precio="<?= $a['precio'] ?>"
                          data-d004="<?= $a['d004'] ?>"
                          data-d005="<?= $a['d005'] ?>"
                          data-d007="<?= $a['d007'] ?>"
                          data-stock="<?= $a['stock'] ?>">
                    <?= htmlspecialchars($a['pid']) ?> — $<?= number_format($a['precio'],2) ?> — Stock: <?= $a['stock'] ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group col-md-2">
              <label><i class="fas fa-hashtag mr-2"></i>Cantidad</label>
              <input type="number" class="form-control" id="accessory_qty" name="accessory_qty" min="1" value="1" required>
            </div>
            <div class="form-group col-md-4 text-right">
              <button name="add_accessory" class="btn btn-success">
                <i class="fas fa-plus mr-1"></i>Añadir
              </button>
            </div>
          </div>
        </form>

        <?php if (!empty($_SESSION['accessories'])): ?>
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr><th>Accesorio</th><th>Cantidad</th><th>Stock</th><th>Acciones</th></tr>
            </thead>
            <tbody>
              <?php foreach ($_SESSION['accessories'] as $i => $a):
                $st3 = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
                $st3->bind_param('s', $a['pid']);
                $st3->execute();
                $st3->bind_result($stkAcc);
                $st3->fetch();
                $st3->close();
              ?>
              <tr>
                <td><?= htmlspecialchars($a['pid']) ?></td>
                <td><?= htmlspecialchars($a['qty']) ?></td>
                <td><?= $stkAcc ?></td>
                <td>
                  <form method="post" class="form-inline d-inline">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <input type="number" name="accessory_qty" value="<?= htmlspecialchars($a['qty']) ?>"
                           min="1" class="form-control form-control-sm mr-1" style="width:60px;">
                    <button name="update_accessory" class="btn btn-sm btn-primary">OK</button>
                  </form>
                  <form method="post" class="d-inline">
                    <input type="hidden" name="step" value="3">
                    <input type="hidden" name="index" value="<?= $i ?>">
                    <button name="delete_accessory" class="btn btn-sm btn-danger">✕</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <form method="post">
          <input type="hidden" name="step" value="3">
          <button name="to_summary" class="btn btn-primary btn-block">Siguiente: Resumen &rarr;</button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Paso 4 -->
      <?php if ($step === 4):
        $stmt = $mysqli->prepare("SELECT razon_social, direccion, ciudad FROM clientes WHERE cliente=?");
        $stmt->bind_param('s', $_SESSION['company']);
        $stmt->execute();
        $stmt->bind_result($razon, $dir, $ciud);
        $stmt->fetch();
        $stmt->close();
      ?>
      <div class="form-section">
        <h4 class="mb-4"><i class="fas fa-file-alt mr-2"></i>Resumen de tu Consulta</h4>
        <p><strong>Empresa:</strong> <?= htmlspecialchars($razon) ?></p>
        <p><strong>Dirección:</strong> <?= htmlspecialchars($dir) ?>, <?= htmlspecialchars($ciud) ?></p>
        <p><strong>Validez:</strong> <?= htmlspecialchars($_SESSION['initial_date']) ?> al <?= htmlspecialchars($_SESSION['final_date']) ?></p>

        <?php if (!empty($_SESSION['machines'])): ?>
        <h5 class="mt-4"><i class="fas fa-cogs mr-2"></i>Maquinarias</h5>
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th>Tipo</th><th>Modelo</th><th>Cantidad</th><th>Stock</th>
                <th>Precio U.</th><th>K004</th><th>K005</th><th>K007</th><th>Precio Final U.</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($_SESSION['machines'] as $it):
                $d    = getClientMaterialData($mysqli, $_SESSION['company'], $it['pid']);
                $pu   = $d['precio']; $a004=$d['desc004']; $a005=$d['desc005']; $a007=$d['desc007'];
                $pfu  = $pu+$a004+$a005+$a007;
                $st4 = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
                $st4->bind_param('s', $it['pid']);
                $st4->execute();
                $st4->bind_result($stkReal);
                $st4->fetch();
                $st4->close();
              ?>
              <tr>
                <td><?= htmlspecialchars($it['type']) ?></td>
                <td><?= htmlspecialchars($it['pid']) ?></td>
                <td><?= htmlspecialchars($it['qty']) ?></td>
                <td><?= $stkReal ?></td>
                <td>$<?= number_format($pu,2) ?></td>
                <td>$<?= number_format($a004,2) ?></td>
                <td>$<?= number_format($a005,2) ?></td>
                <td>$<?= number_format($a007,2) ?></td>
                <td>$<?= number_format($pfu,2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['accessories'])): ?>
        <h5 class="mt-4"><i class="fas fa-plug mr-2"></i>Accesorios</h5>
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <thead>
              <tr>
                <th>Accesorio</th><th>Cantidad</th><th>Stock</th>
                <th>Precio U.</th><th>K004</th><th>K005</th><th>K007</th><th>Precio Final U.</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($_SESSION['accessories'] as $a):
                $d    = getClientMaterialData($mysqli, $_SESSION['company'], $a['pid']);
                $pu   = $d['precio']; $a004=$d['desc004']; $a005=$d['desc005']; $a007=$d['desc007'];
                $pfu  = $pu+$a004+$a005+$a007;
                $st5 = $mysqli->prepare("SELECT stock FROM productos WHERE producto_id = ?");
                $st5->bind_param('s', $a['pid']);
                $st5->execute();
                $st5->bind_result($stkAccReal);
                $st5->fetch();
                $st5->close();
              ?>
              <tr>
                <td><?= htmlspecialchars($a['pid']) ?></td>
                <td><?= htmlspecialchars($a['qty']) ?></td>
                <td><?= $stkAccReal ?></td>
                <td>$<?= number_format($pu,2) ?></td>
                <td>$<?= number_format($a004,2) ?></td>
                <td>$<?= number_format($a005,2) ?></td>
                <td>$<?= number_format($a007,2) ?></td>
                <td>$<?= number_format($pfu,2) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>

        <form method="post" class="mt-4">
          <input type="hidden" name="step" value="4">
          <button name="generate" class="btn btn-primary btn-block">
            <i class="fas fa-paper-plane mr-1"></i>Generar consulta
          </button>
        </form>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>

  <script>
    var machineMap = <?= json_encode($machine_map ?? []) ?>;
    function updateModels() {
      var t = document.getElementById('machine_type').value;
      var m = document.getElementById('machine_model');
      m.innerHTML = '<option value="">—Modelo—</option>';
      if (machineMap[t]) {
        machineMap[t].forEach(function(it) {
          var o = document.createElement('option');
          o.value = it.pid;
          o.text  = it.pid+' — $'+parseFloat(it.precio).toFixed(2)+' — Stock:'+it.stock;
          o.dataset.precio=it.precio; o.dataset.d004=it.d004; o.dataset.d005=it.d005; o.dataset.d007=it.d007; o.dataset.stock=it.stock;
          m.add(o);
        });
      }
	  m.onchange = function() {
        var sel = m.selectedOptions[0];
        if (sel && sel.dataset.precio) {
          p.textContent = 'Precio: $' + parseFloat(sel.dataset.precio).toFixed(2)
                        + ' | K004: $' + parseFloat(sel.dataset.d004).toFixed(2)
                        + ' K005: $' + parseFloat(sel.dataset.d005).toFixed(2)
                        + ' K007: $' + parseFloat(sel.dataset.d007).toFixed(2);
          s.textContent = 'Stock: ' + sel.dataset.stock;
        } else {
          p.textContent = '';
          s.textContent = '';
        }
      };
    }
     document.getElementById('accessory_pid')?.addEventListener('change', function(){
      var sel = this.selectedOptions[0],
          info = document.getElementById('accessory_price'),
          stockInfo = document.getElementById('accessory_stock');
      if (sel && sel.dataset.precio) {
        info.textContent = 'Precio: $' + parseFloat(sel.dataset.precio).toFixed(2)
                         + ' | K004: $' + parseFloat(sel.dataset.d004).toFixed(2)
                         + ' K005: $' + parseFloat(sel.dataset.d005).toFixed(2)
                         + ' K007: $' + parseFloat(sel.dataset.d007).toFixed(2);
        stockInfo.textContent = 'Stock: ' + sel.dataset.stock;
      } else {
        info.textContent = '';
        stockInfo.textContent = '';
      }
    });
  </script>

  <?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
