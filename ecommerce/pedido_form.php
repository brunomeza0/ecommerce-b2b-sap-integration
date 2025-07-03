<?php
// File: pedido_form.php
session_start();


require_once __DIR__ . '/includes/db.php';
$mysqli->set_charset('utf8mb4');

// Cargar configuración IGV y tipo de cambio
$confRes = $mysqli->query("
    SELECT clave, valor
      FROM configuracion
     WHERE clave IN('igv','tipo_cambio')
");
$config = [];
while ($r = $confRes->fetch_assoc()) {
    $config[$r['clave']] = floatval($r['valor']);
}

// Cargar lista de productos (con precio y stock para cálculos)
$prodRes  = $mysqli->query("
    SELECT producto_id AS pid, descripcion, precio, stock
      FROM productos
");
$products = $prodRes->fetch_all(MYSQLI_ASSOC);

// Determinar modo: creación (desde cotización) o edición (pedido existente)
$mode   = '';
$items  = [];
if (isset($_GET['cotizacion_id']) && is_numeric($_GET['cotizacion_id'])) {
    $mode   = 'create';
    $cot_id = intval($_GET['cotizacion_id']);

    // Cabecera cotización
    $p = $mysqli->prepare("SELECT * FROM cotizaciones WHERE id=?");
    $p->bind_param('i',$cot_id);
    $p->execute();
    $cot = $p->get_result()->fetch_assoc();
    if (!$cot) exit(header('Location: ver_cotizaciones.php'));

    // Ítems originales de la cotización con stock
    $res   = $mysqli->query("
      SELECT ci.*, p.descripcion, p.stock
        FROM cotizacion_items ci
   LEFT JOIN productos p ON ci.producto_id=p.producto_id
       WHERE ci.cotizacion_id={$cot_id}
    ");
    $items = $res->fetch_all(MYSQLI_ASSOC);

    // Valores por defecto de pedido, usando configuración
    $pedido = [
      'igv'           => $config['igv'] ?? 18.00,
      'tipo_cambio'   => $config['tipo_cambio'] ?? 3.80,
      'schedule_line' => date('Y-m-d'),
      'orden_compra'  => ''
    ];
}
elseif (isset($_GET['pedido_id']) && is_numeric($_GET['pedido_id'])) {
    $mode      = 'edit';
    $pedido_id = intval($_GET['pedido_id']);

    // Cabecera pedido
    $p = $mysqli->prepare("SELECT * FROM pedidos WHERE id=?");
    $p->bind_param('i',$pedido_id);
    $p->execute();
    $pedido = $p->get_result()->fetch_assoc();
    if (!$pedido) exit(header('Location: ver_pedidos.php'));

    // Ítems del pedido con stock
    $res   = $mysqli->query("
      SELECT pi.*, p.descripcion, p.stock
        FROM pedido_items pi
   LEFT JOIN productos p ON pi.producto_id=p.producto_id
       WHERE pi.pedido_id={$pedido_id}
    ");
    $items = $res->fetch_all(MYSQLI_ASSOC);

    // Mantener valores previos
    $pedido['schedule_line'] = $pedido['schedule_line'] ?? date('Y-m-d');
}
else {
    header('Location: ver_cotizaciones.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo ($mode=='create'?'Crear':'Editar'); ?> Pedido – Maquinaria B2B</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f9; font-family: 'Roboto', sans-serif; }
    .navbar { margin-bottom: 30px; }
    .form-container { max-width: 1200px; margin: auto; }
    .card-custom { border-radius:8px; box-shadow:0 2px 6px rgba(0,0,0,0.1); margin-bottom:30px; }
    .card-custom .card-header { background:#004080; color:#fff; font-weight:500; }
    .table thead { background: #e9ecef; }
    .table-responsive { overflow-x:auto; }
    table { min-width: 1000px; }
    .btn-primary { background: #004080; border: none; }
    .btn-primary:hover { background: #003060; }
    .btn-success { background: #007b33; border: none; }
    .btn-success:hover { background: #005c29; }
    .btn-danger { background: #dc3545; border: none; }
  </style>
</head>
<body class="bg-light">

  <?php require_once __DIR__ . '/includes/header.php'; ?>

<div class="container form-container">
  <h3 class="mb-4"><i class="fas fa-clipboard-list mr-2 text-primary"></i>
    <?php echo ($mode=='create'?'Crear':'Editar'); ?> Pedido
  </h3>
  <form id="pedido-form" action="pedido_submit.php" method="post">
    <input type="hidden" name="mode" value="<?php echo $mode; ?>">
    <?php if($mode=='create'): ?>
      <input type="hidden" name="cotizacion_id" value="<?php echo $cot_id; ?>">
    <?php else: ?>
      <input type="hidden" name="pedido_id" value="<?php echo $pedido['id']; ?>">
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="card card-custom mb-4">
      <div class="card-header">Detalles del Pedido</div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label><i class="fas fa-building mr-1"></i>Cliente</label>
            <input type="text" class="form-control" name="cliente"
                   value="<?php echo htmlspecialchars($mode=='create' ? $cot['cliente'] : $pedido['cliente']); ?>"
                   readonly>
          </div>
          <?php if($mode=='create'): ?>
          <div class="form-group col-md-4">
            <label><i class="fas fa-calendar-alt mr-1"></i>Vigencia Cotización</label>
            <input type="text" class="form-control" readonly
                   value="<?php echo $cot['initial_date'].' → '.$cot['final_date']; ?>">
          </div>
          <?php endif; ?>
          <div class="form-group col-md-2">
            <label><i class="fas fa-percent mr-1"></i>IGV (%)</label>
            <input type="number" step="0.01" class="form-control" name="igv"
                   value="<?php echo $pedido['igv']; ?>">
          </div>
          <div class="form-group col-md-2">
            <label><i class="fas fa-exchange-alt mr-1"></i>Tipo de Cambio</label>
            <input type="number" step="0.0001" class="form-control tipo-cambio" name="tipo_cambio"
                   value="<?php echo $pedido['tipo_cambio']; ?>">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-md-4">
            <label><i class="fas fa-calendar-day mr-1"></i>Fecha solicitada de entrega</label>
            <input type="date" class="form-control" name="schedule_line"
                   value="<?php echo $pedido['schedule_line']; ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Ítems del pedido -->
    <h5 class="mb-2"><i class="fas fa-boxes mr-1"></i>Ítems del Pedido</h5>
    <button type="button" id="add-item" class="btn btn-sm btn-success mb-3">
      <i class="fas fa-plus mr-1"></i>Añadir Ítem
    </button>
    <div class="table-responsive">
      <table class="table table-bordered bg-white mb-4">
        <thead class="thead-light">
          <tr>
            <th>Producto</th>
            <th>Stock</th>
            <th>Cant.</th>
            <th>P. Unitario</th>
            <th>K004</th>
            <th>K005</th>
            <th>K007</th>
            <th>P. Final</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="items-body">
          <?php foreach($items as $i => $it): ?>
          <tr>
            <td>
              <input type="hidden" name="items[<?php echo $i; ?>][producto_id]"
                     value="<?php echo $it['producto_id']; ?>">
              <?php echo htmlspecialchars($it['producto_id'].' — '.$it['descripcion']); ?>
            </td>
            <td><?php echo $it['stock']; ?></td>
            <td><input type="number" name="items[<?php echo $i; ?>][cantidad]"
                       class="form-control cantidad" min="1"
                       value="<?php echo $it['cantidad']; ?>"></td>
            <td><input type="text" name="items[<?php echo $i; ?>][precio_unitario]"
                       class="form-control precio-unitario" readonly
                       value="<?php echo $it['precio_unitario']; ?>"></td>
            <td><input type="text" name="items[<?php echo $i; ?>][descuento_k004]"
                       class="form-control descuento" readonly
                       value="<?php echo $it['descuento_k004']; ?>"></td>
            <td><input type="text" name="items[<?php echo $i; ?>][descuento_k005]"
                       class="form-control descuento" readonly
                       value="<?php echo $it['descuento_k005']; ?>"></td>
            <td><input type="text" name="items[<?php echo $i; ?>][descuento_k007]"
                       class="form-control descuento" readonly
                       value="<?php echo $it['descuento_k007']; ?>"></td>
            <td><input type="text" name="items[<?php echo $i; ?>][precio_final]"
                       class="form-control precio-final" readonly
                       value="<?php echo $it['precio_final']; ?>"></td>
            <td>
              <button type="button" class="btn btn-sm btn-danger remove-row">
                <i class="fas fa-trash-alt"></i>
              </button>
            </td>
          </tr>
          <!-- Campos ocultos adicionales -->
          <input type="hidden" name="items[<?php echo $i; ?>][ref_quote]"
                 value="<?php echo htmlspecialchars($it['ref_quote'] ?? ''); ?>">
          <input type="hidden" name="items[<?php echo $i; ?>][plant]"
                 value="<?php echo htmlspecialchars($it['plant'] ?? ''); ?>">
          <input type="hidden" name="items[<?php echo $i; ?>][ref_quote_item]"
                 value="<?php echo htmlspecialchars($it['ref_quote_item'] ?? ''); ?>">
          <input type="hidden" name="items[<?php echo $i; ?>][dateFrom]"
                 value="<?php echo htmlspecialchars($pedido['schedule_line']); ?>">
          <input type="hidden" name="items[<?php echo $i; ?>][dateTo]"
                 value="<?php echo htmlspecialchars($pedido['schedule_line']); ?>">
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <button type="submit" class="btn btn-primary mr-2">
      <i class="fas fa-check mr-1"></i>
      <?php echo ($mode=='create'?'Crear Pedido':'Guardar Cambios'); ?>
    </button>
    <a href="<?php echo ($mode=='create'?'ver_cotizaciones.php':'ver_pedidos.php'); ?>"
       class="btn btn-secondary">
      <i class="fas fa-times mr-1"></i>Cancelar
    </a>
  </form>
</div>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
  var products = <?php echo json_encode($products, JSON_HEX_TAG); ?>;

  function recalcRow($tr) {
    var pu    = parseFloat($tr.find('.precio-unitario').val()) || 0;
    var d004  = parseFloat($tr.find('input[name*="[descuento_k004]"]').val()) || 0;
    var d005  = parseFloat($tr.find('input[name*="[descuento_k005]"]').val()) || 0;
    var d007  = parseFloat($tr.find('input[name*="[descuento_k007]"]').val()) || 0;
    var finalUnit = pu + d004 + d005 + d007;
    $tr.find('.precio-final').val(finalUnit.toFixed(2));
  }

  function recalcAll() {
    $('#items-body tr').each(function() {
      recalcRow($(this));
    });
  }

  // Cambiar producto: actualizar precio y stock
  $(document).on('change', '.sel-prod', function() {
    var o = this.selectedOptions[0], $tr = $(this).closest('tr');
    if (!o.dataset.precio) return;
    $tr.find('.precio-unitario').val(parseFloat(o.dataset.precio).toFixed(2));
    $tr.find('td').eq(1).text(o.dataset.stock);
    recalcRow($tr);
  });

  // Recálculo al cambiar cantidad
  $(document).on('input', '.cantidad', function() {
    recalcRow($(this).closest('tr'));
  });

  // Quitar fila
  $(document).on('click', '.remove-row', function() {
    $(this).closest('tr').remove();
    recalcAll();
  });

  // Añadir nueva fila
  $('#add-item').click(function() {
    var idx = $('#items-body tr').length;
    var $tr = $('<tr>');
    var sel = '<select class="form-control sel-prod" name="items['+idx+'][producto_id]">'+
              '<option value="">—Producto—</option>';
    products.forEach(function(p) {
      sel += '<option value="'+p.pid+'" data-precio="'+p.precio+'" data-stock="'+p.stock+'">'+
             p.pid+' — '+p.descripcion+' — Stock: '+p.stock+
             '</option>';
    });
    sel += '</select>';
    $tr.append('<td>'+sel+'</td>')
       .append('<td></td>')
       .append('<td><input type="number" name="items['+idx+'][cantidad]" class="form-control cantidad" value="1" min="1"></td>')
       .append('<td><input type="text" name="items['+idx+'][precio_unitario]" class="form-control precio-unitario" readonly></td>')
       .append('<td><input type="text" name="items['+idx+'][descuento_k004]" class="form-control descuento" readonly value="0"></td>')
       .append('<td><input type="text" name="items['+idx+'][descuento_k005]" class="form-control descuento" readonly value="0"></td>')
       .append('<td><input type="text" name="items['+idx+'][descuento_k007]" class="form-control descuento" readonly value="0"></td>')
       .append('<td><input type="text" name="items['+idx+'][precio_final]" class="form-control precio-final" readonly></td>')
       .append('<td><button type="button" class="btn btn-sm btn-danger remove-row"><i class="fas fa-trash-alt"></i></button></td>');
    // campos ocultos
    $tr.append('<input type="hidden" name="items['+idx+'][ref_quote]" value="">')
       .append('<input type="hidden" name="items['+idx+'][plant]" value="">')
       .append('<input type="hidden" name="items['+idx+'][ref_quote_item]" value="">')
       .append('<input type="hidden" name="items['+idx+'][dateFrom]" value="<?php echo date("Y-m-d"); ?>">')
       .append('<input type="hidden" name="items['+idx+'][dateTo]"   value="<?php echo date("Y-m-d"); ?>">');
    $('#items-body').append($tr);
  });

  // Inicializar cálculos
  $(function() { recalcAll(); });
</script>
</body>
</html>
