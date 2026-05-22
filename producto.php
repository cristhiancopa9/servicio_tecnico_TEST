<?php
require_once 'config/db.php';

// Control de sesión seguro
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Consulta corregida (id_tipo_producto)
    $query = "SELECT p.*, c.nombre AS categoria_nombre 
              FROM productos p
              LEFT JOIN categorias_dispositivos c ON p.id_tipo_producto = c.id
              WHERE p.activo = 1 AND p.stock_actual >= 0
          
              ORDER BY c.nombre ASC, p.nombre ASC";

    $stmt = $pdo->query($query);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inventario | CM TECH</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Vinculación a tu hoja de estilos principal -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_tablas.css">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header>
            <div class="brand-meta">
                <h1>Control de Inventario</h1>
                <span>Visualiza y administra las reparaciones del taller.</span>
            </div>
            <button class="btn-header-quick btn-order" onclick="abrirModalNuevo()">
                <i class="fas fa-plus"></i> Agregar Producto
            </button>
        </header>


        <!-- Tabla principal con clases vinculadas al CSS -->

        <div class="table-responsive">
            <table class="table-inventory">
                <thead>
                    <tr>
                        <th>Código</th> <!-- Columna 1 -->
                        <th>Categoría</th> <!-- Columna 2 -->
                        <th>Descripción</th>
                        <th style="text-align: center;">Stock</th>
                        <th>Costo (USD)</th>
                        <th>Venta (ARS)</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p):
                        $esLowStock = $p['stock_actual'] <= $p['stock_minimo'];
                    ?>
                        <tr>
                            <!-- Columna Código -->
                            <td>
                                <span class="td-icon-group"><?= htmlspecialchars($p['codigo_barras']) ?></span>
                            </td>

                            <!-- Columna Categoría -->
                            <td>
                                <span class="td-icon-group"><?= htmlspecialchars($p['categoria_nombre'] ?: 'Sin Categoría') ?></span>
                            </td>

                            <!-- Columna Descripción -->
                            <td>
                                <div class="td-icon-group"><?= htmlspecialchars($p['nombre']) ?></div>
                            </td>

                            <!-- Resto de columnas... -->
                            <td>
                                <div class="td-icon-group" <?= $esLowStock ? 'stock-low' : 'stock-ok' ?>">
                                    <?= $p['stock_actual'] ?>
                                </div>
                            </td>
                            <td><span class="usd-tag">U$D <?= number_format($p['costo_usd'], 2) ?></span></td>
                            <td><span class="price-tag">$<?= number_format($p['precio_venta_ars'], 0, ',', '.') ?></span></td>
                            <td style="text-align: center;">
                                <button class="btn-icon btn-history" title="Historial" onclick="verHistorial(<?= $p['id'] ?>)">
                                    <i class="fas fa-history"></i>
                                </button>
                                <button class="btn-icon btn-edit" title="Editar" onclick="abrirModalEditar(<?= $p['id'] ?>)">
                                    <i class="fas fa-pen"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </main>


</body>

</html>