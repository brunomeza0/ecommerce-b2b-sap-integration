<?php
// clientes_service.php
header('Content-Type: application/json; charset=utf-8');

// Credenciales de la base de datos
$host   = 'localhost';
$dbname = 'maquinaria';
$user   = 'root';
$pass   = '5664193';

// Conexión PDO
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error'   => 'Error de conexión a la base de datos',
        'message' => $e->getMessage()
    ]);
    exit;
}

// Sólo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Use POST.']);
    exit;
}

// Leer y decodificar JSON
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

// Resultado
$result = [
    'inserted' => 0,
    'updated'  => 0,
    'deleted'  => 0,
    'errors'   => []
];

// Preparamos sentencia con "upsert"
$sql = "
INSERT INTO clientes (
    cliente, razon_social, direccion, ciudad, region,
    cp, pais, centro, orgventa, canal,
    division, moneda, grp_precio, cond_pago
) VALUES (
    :cliente, :razon_social, :direccion, :ciudad, :region,
    :cp, :pais, :centro, :orgventa, :canal,
    :division, :moneda, :grp_precio, :cond_pago
)
ON DUPLICATE KEY UPDATE
    razon_social = VALUES(razon_social),
    direccion    = VALUES(direccion),
    ciudad       = VALUES(ciudad),
    region       = VALUES(region),
    cp           = VALUES(cp),
    centro       = VALUES(centro),
    orgventa     = VALUES(orgventa),
    canal        = VALUES(canal),
    division     = VALUES(division),
    moneda       = VALUES(moneda),
    grp_precio   = VALUES(grp_precio),
    cond_pago    = VALUES(cond_pago)
";
$stmt = $pdo->prepare($sql);

// Para eliminación posterior: recopilar llaves recibidas
$tupleParts = [];
$paramsForDelete = [];

// Iterar cada cliente recibido
foreach ($data as $item) {
    // Upsert
    try {
        $stmt->execute([
            ':cliente'       => $item['Cliente']      ?? null,
            ':razon_social'  => $item['RazónSocial'] ?? null,
            ':direccion'     => $item['Dirección']   ?? null,
            ':ciudad'        => $item['Ciudad']      ?? null,
            ':region'        => $item['Región']      ?? null,
            ':cp'            => $item['CP']          ?? null,
            ':pais'          => $item['País']        ?? null,
            ':centro'        => $item['Centro']      ?? null,
            ':orgventa'      => $item['OrgVenta']    ?? null,
            ':canal'         => $item['Canal']       ?? null,
            ':division'      => $item['División']    ?? null,
            ':moneda'        => $item['Moneda']      ?? null,
            ':grp_precio'    => $item['GrpPrecio']   ?? null,
            ':cond_pago'     => $item['CondPago']    ?? null,
        ]);
        $rc = $stmt->rowCount();
        if ($rc === 1) {
            $result['inserted']++;
        } elseif ($rc === 2) {
            $result['updated']++;
        }
    } catch (PDOException $e) {
        $result['errors'][] = [
            'item'    => $item,
            'message' => $e->getMessage()
        ];
    }
    // Preparar para delete: combinacion cliente+division
    $tupleParts[] = '(?, ?)';
    $paramsForDelete[] = $item['Cliente'] ?? null;
    $paramsForDelete[] = $item['División'] ?? null;
}

// Eliminar registros que ya no vienen en el JSON (por cliente + división)
if (!empty($tupleParts)) {
    $sqlDel = 'DELETE FROM clientes WHERE (cliente, division) NOT IN ' . implode(', ', $tupleParts);
    $delStmt = $pdo->prepare($sqlDel);
    $delStmt->execute($paramsForDelete);
    $result['deleted'] = $delStmt->rowCount();
}

// Devolver resultado
echo json_encode($result);
