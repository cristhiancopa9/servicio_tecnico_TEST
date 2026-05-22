<?php
// Desactivar cualquier salida de errores de PHP que rompa el JSON
error_reporting(0);
header('Content-Type: application/json');

require_once 'config/db.php';

$imei = $_GET['imei_serie'] ?? '';

if (!$imei) {
    echo json_encode(['error' => 'Falta el IMEI']);
    exit;
}

try {
    // Consulta optimizada
    $query = "SELECT 
                o.id, 
                o.fecha_ingreso, 
                o.falla_reportada, 
                o.diagnostico_tecnico,
                m.nombre_modelo,
                IFNULL(u.nombre_completo, 'No asignado') as tecnico
              FROM ordenes_reparacion o
              INNER JOIN modelos m ON o.id_modelo = m.id
              LEFT JOIN usuarios u ON o.id_tecnico = u.id
              WHERE o.imei_serie = :imei
              ORDER BY o.fecha_ingreso DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':imei' => $imei]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$resultados) {
        echo json_encode([
            'equipo' => 'Desconocido',
            'historial' => []
        ]);
        exit;
    }

    $historial = [];
    foreach ($resultados as $row) {
        $historial[] = [
            'id' => $row['id'],
            'fecha' => date('d/m/Y', strtotime($row['fecha_ingreso'])),
            'falla' => $row['falla_reportada'],
            'solucion' => $row['diagnostico_tecnico'],
            'tecnico' => $row['tecnico']
        ];
    }

    // Enviamos el nombre del modelo usando el primer resultado
    echo json_encode([
        'equipo' => $resultados[0]['nombre_modelo'],
        'historial' => $historial
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
