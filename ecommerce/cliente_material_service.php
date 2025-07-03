<?php
// cliente_material_service.php
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
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$result = [
    'inserted' => 0,
    'updated'  => 0,
    'deleted'  => 0,
    'errors'   => []
];

// 1) Upsert
$sql = "
INSERT INTO cliente_material (
    cliente, nombre_cliente, material, descripcion,
    precio, descuento_k004, udescuento_k004,
    descuento_k005, udescuento_k005,
    descuento_k007, udescuento_k007
) VALUES (
    :cliente, :nombre_cliente, :material, :descripcion,
    :precio, :descuento_k004, :udescuento_k004,
    :descuento_k005, :udescuento_k005,
    :descuento_k007, :udescuento_k007
)
ON DUPLICATE KEY UPDATE
    nombre_cliente     = VALUES(nombre_cliente),
    descripcion        = VALUES(descripcion),
    precio             = VALUES(precio),
    descuento_k004     = VALUES(descuento_k004),
    udescuento_k004    = VALUES(udescuento_k004),
    descuento_k005     = VALUES(descuento_k005),
    udescuento_k005    = VALUES(udescuento_k005),
    descuento_k007     = VALUES(descuento_k007),
    udescuento_k007    = VALUES(udescuento_k007)
";
$stmt = $pdo->prepare($sql);

// Para eliminación posterior
$conditionsForDelete = [];
$paramsForDelete     = [];

foreach ($data as $item) {
    try {
        $stmt->execute([
            ':cliente'              => $item['Cliente']              ?? null,
            ':nombre_cliente'       => $item['Nombre Cliente']       ?? null,
            ':material'             => $item['Material']             ?? null,
            ':descripcion'          => $item['Descripción']          ?? null,
            ':precio'               => $item['Precio']               ?? null,
            ':descuento_k004'       => $item['Desc_K004']            ?? null,
            ':udescuento_k004'      => $item['UDesc_K004']           ?? null,
            ':descuento_k005'       => $item['Desc_K005']            ?? null,
            ':udescuento_k005'      => $item['UDesc_K005']           ?? null,
            ':descuento_k007'       => $item['Desc_K007']            ?? null,
            ':udescuento_k007'      => $item['UDesc_K007']           ?? null,
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

    // Para delete: armamos condición (cliente, material)
    $conditionsForDelete[] = "(cliente = ? AND material = ?)";
    $paramsForDelete[]     = $item['Cliente'] ?? null;
    $paramsForDelete[]     = $item['Material'] ?? null;
}

// 2) Eliminar los registros que ya no vienen
if (!empty($conditionsForDelete)) {
    $whereNotIn = 'NOT (' . implode(' OR ', $conditionsForDelete) . ')';
    $sqlDel     = "DELETE FROM cliente_material WHERE $whereNotIn";
    $delStmt    = $pdo->prepare($sqlDel);
    $delStmt->execute($paramsForDelete);
    $result['deleted'] = $delStmt->rowCount();
}

// Devolver resultado
echo json_encode($result);