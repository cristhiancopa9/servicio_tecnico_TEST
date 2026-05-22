<?php
// Desactivar la visualización de errores HTML para que no rompan el JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');
require_once 'config/db.php';

// Capturar el cuerpo de la petición
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || empty($data['repuestos'])) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos o lista vacía.']);
    exit;
}

try {
    $pdo->beginTransaction();

    foreach ($data['repuestos'] as $item) {
        // 1. Descontar Stock (Asegúrate que los nombres de columnas coincidan)
        $upd = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?");
        $upd->execute([$item['cant'], $item['idProd']]);

        // 2. Vincular a la orden
        // TABLA: ordenes_repuestos (id_orden, id_producto, cantidad, precio_cobrado_ars, fecha)
        $ins = $pdo->prepare("INSERT INTO ordenes_repuestos (id_orden, id_producto, cantidad, precio_cobrado_ars) VALUES (?, ?, ?, ?)");
        $ins->execute([
            $data['id_orden'],
            $item['idProd'],
            $item['cant'],
            $item['precio']
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Vínculo exitoso. Stock actualizado.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => 'Error en base de datos: ' . $e->getMessage()]);
}
