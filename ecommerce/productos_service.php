<?php
// productos_service.php
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
// --- Añadimos la columna stock tanto en INSERT como en ON DUPLICATE KEY UPDATE ---
$sql = "
INSERT INTO productos (
    producto_id, descripcion, um, orgventas, canal,
    grupo, centro, borrado_planta, precio, stock
) VALUES (
    :producto_id, :descripcion, :um, :orgventas, :canal,
    :grupo, :centro, :borrado_planta, :precio, :stock
)
ON DUPLICATE KEY UPDATE
    descripcion     = VALUES(descripcion),
    um              = VALUES(um),
    orgventas       = VALUES(orgventas),
    canal           = VALUES(canal),
    grupo           = VALUES(grupo),
    centro          = VALUES(centro),
    borrado_planta  = VALUES(borrado_planta),
    precio          = VALUES(precio),
    stock           = VALUES(stock)
";
$stmt = $pdo->prepare($sql);

// Para eliminación posterior: recopilar descripciones recibidas
$placeholders = [];
$paramsForDelete = [];

// Iterar cada producto recibido
foreach ($data as $item) {
    try {
        $stmt->execute([
            ':producto_id'     => $item['ProductoID']     ?? null,
            ':descripcion'     => $item['Descripción']    ?? null,
            ':um'              => $item['UM']             ?? null,
            ':orgventas'       => $item['OrgVentas']      ?? null,
            ':canal'           => $item['Canal']          ?? null,
            ':grupo'           => $item['Grupo']          ?? null,
            ':centro'          => $item['Centro']         ?? null,
            ':borrado_planta'  => $item['BorradoPlanta']  ?? null,
            ':precio'          => $item['Precio']         ?? null,
            // --- Aquí se mapea el Stock del JSON al parámetro :stock ---
            ':stock'           => $item['Stock']          ?? 0,
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

    // Preparar para delete: usamos 'descripcion' como clave (tal como antes)
    $placeholders[]     = '?';
    $paramsForDelete[]  = $item['Descripción'] ?? null;
}

// Eliminar registros cuyos 'descripcion' ya no vienen en el JSON
if (!empty($placeholders)) {
    $inList = implode(',', $placeholders);
    $sqlDel = "DELETE FROM productos WHERE descripcion NOT IN ($inList)";
    $delStmt = $pdo->prepare($sqlDel);
    $delStmt->execute($paramsForDelete);
    $result['deleted'] = $delStmt->rowCount();
}

// Devolver resultado
echo json_encode($result);
