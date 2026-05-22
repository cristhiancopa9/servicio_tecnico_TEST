<?php
require_once 'config/db.php';
// Creamos la consulta con el filtro IN
$estados_ids = [3, 4, 5];

$query_ordenes = "SELECT 
                    o.id, 
                    m.nombre_modelo AS modelo, 
                    c.nombre_completo AS cliente 
                  FROM ordenes_reparacion o
                  INNER JOIN modelos m ON o.id_modelo = m.id
                  INNER JOIN clientes c ON o.id_cliente = c.id
                  WHERE o.id_estado IN (" . implode(",", $estados_ids) . ") 
                  ORDER BY o.id DESC";

try {
    $stmtOrdenes = $pdo->query($query_ordenes);
    $ordenes_activas = $stmtOrdenes->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
    $ordenes_activas = [];
}
// 1. Consultar Proveedores
$stmtProv = $pdo->query("SELECT id, nombre_empresa FROM proveedores ORDER BY nombre_empresa ASC");
$proveedores = $stmtProv->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_producto = $_POST['id_producto'];
    $cantidad = intval($_POST['cantidad']);
    $nuevo_costo = !empty($_POST['nuevo_costo']) ? floatval($_POST['nuevo_costo']) : null;

    try {
        $pdo->beginTransaction();

        // 1. Actualizar el stock sumando la nueva cantidad
        if ($nuevo_costo) {
            $sql = "UPDATE productos SET stock_actual = stock_actual + :cantidad, costo_usd = :costo WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':cantidad' => $cantidad, ':costo' => $nuevo_costo, ':id' => $id_producto]);
        } else {
            $sql = "UPDATE productos SET stock_actual = stock_actual + :cantidad WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':cantidad' => $cantidad, ':id' => $id_producto]);
        }

        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Stock actualizado correctamente']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

// Consulta para traer productos activos
$query = "SELECT p.*, m.nombre AS marca 
          FROM productos p
          LEFT JOIN marcas m ON p.id_marca = m.id
          WHERE p.activo = 1 
            AND p.eliminado_en IS NULL
            AND p.stock_actual > 0 
          ORDER BY p.nombre ASC";

$stmt = $pdo->prepare($query);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

function obtenerDolarBlue()
{
    $url = "https://dolarapi.com/v1/dolares/blue";

    // Intentar obtener los datos
    $json = @file_get_contents($url);

    if ($json === FALSE) {
        return 1250; // Valor por defecto si la API no responde
    }

    $data = json_decode($json, true);
    return $data['venta'] ?? 1250;
}

$usd_blue = obtenerDolarBlue();
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inventario de Repuestos | CM TECH</title>
    <link rel="stylesheet" href="assets/css/style_verficha.css">
    <link rel="stylesheet" href="assets/pattern/patternLock.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<body class="dashboard-container">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <header class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>Inventario de Repuestos</h1>
                </div>
                <!-- Card de Cotización -->
                <div class="usd-card">
                    <span class="text-muted">USD Blue:</span>
                    <strong style="font-size: 1.2rem;">$<?= number_format($usd_blue, 0, ',', '.') ?></strong>
                    <div class="header-actions">
                        <!-- Botón 1: Para entrar mercadería nueva -->
                        <button class="btn-primary" onclick="abrirModal('stock')">
                            <i class="fas fa-plus-circle"></i> Cargar Stock
                        </button>

                        <!-- Botón 2: Para descontar un repuesto usado en una reparación -->
                        <button class="btn-accent" onclick="abrirModal('VinOrden')">
                            <i class="fas fa-link"></i> Vincular a Orden
                        </button>
                    </div>
                </div>
            </header>

            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>REPUESTO</th>
                            <th>TIPO</th>
                            <th>STOCK</th>
                            <th>PRECIO (USD)</th>
                            <th>PRECIO (ARS)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p):
                            // Lógica de color de stock
                            $stock_clase = ($p['stock_actual'] <= $p['stock_minimo']) ? 'stock-critico' : '';
                        ?>
                            <tr>
                                <td>
                                    <span class="font-bold"><?= htmlspecialchars($p['nombre']) ?></span>
                                    <span class="text-muted-sm"><?= htmlspecialchars($p['marca']) ?></span>
                                </td>
                                <td><span class="tipo-badge"><?= htmlspecialchars($p['detalle_tecnico'] ?: '-') ?></span></td>
                                <td>
                                    <span class="font-bold <?= $stock_clase ?>">
                                        <?= $p['stock_actual'] ?> unidades
                                    </span>
                                </td>
                                <td class="text-mono">$<?= number_format($p['costo_usd'], 2) ?></td>
                                <td class="font-bold">$<?= number_format($p['costo_usd'] * $usd_blue, 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <!-- Componente Modal -->
    <?php
    // 1. Primero haces la consulta a la base de datos
    $stmt = $pdo->query("SELECT id, nombre FROM marcas ORDER BY nombre ASC");
    $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Traes los productos para el otro select
    $stmtProd = $pdo->query("SELECT * FROM productos");
    $productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);
    include 'includes/modal_cargar_stock.php'; ?>
    <?php include 'includes/modal_nuevo_producto.php'; ?>
    <?php include 'includes/modal_vincular_repuesto.php'; ?>
    <!-- Scripts -->
    <script src="assets/js/modales.js"></script>
</body>

</html>