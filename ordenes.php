<?php

/** @var array stmt_ordenes */

?>
<?php
require_once 'config/db.php';
require_once 'includes/funciones_dashboard.php';

// 1. Seguridad y Autenticación
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// 2. Carga de datos para Selects (Modales)
$sucursales = obtenerLista($pdo, 'sucursales', 'nombre');
$clientes   = obtenerLista($pdo, 'clientes', 'nombre_completo');
$modelos    = obtenerLista($pdo, 'modelos', 'nombre_modelo');
$stmt_ordenes = obtenerOrdenesRecientes($pdo);
// 3. Gestión de Filtros y Listado
$filtro_estado = $_GET['estado'] ?? 'Todos los Estados';

$sql_listado = "SELECT o.id, o.codigo_orden, o.fecha_ingreso, e.nombre as estado, 
                       c.nombre_completo as cliente, m.nombre_modelo as modelo,
                       u.nombre_completo as tecnico
                FROM ordenes_reparacion o
                INNER JOIN clientes c ON o.id_cliente = c.id
                INNER JOIN modelos m ON o.id_modelo = m.id
                INNER JOIN estados_orden e ON o.id_estado = e.id
                LEFT JOIN usuarios u ON o.id_tecnico = u.id"; // Corregido el LEFT JOIN

if ($filtro_estado !== 'Todos los Estados') {
    $sql_listado .= " WHERE e.nombre = :filtro";
}
$sql_listado .= " ORDER BY o.fecha_ingreso DESC";

$stmt = $pdo->prepare($sql_listado);
if ($filtro_estado !== 'Todos los Estados') {
    $stmt->bindParam(':filtro', $filtro_estado);
}
$stmt->execute();
$ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CM TECH | Gestión de Órdenes</title>

    <!-- Estilos Core -->
    <link rel="stylesheet" href="assets/css/style_alertas.css">
    <link rel="stylesheet" href="assets/css/style_tablas.css">
    <link rel="stylesheet" href="assets/css/style_verficha.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts para un look profesional -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>

    <!-- Sidebar Reutilizable -->
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">

        <!-- Encabezado de Página -->
        <header>
            <div class="brand-meta">
                <h1>Gestión de Órdenes</h1>
                <span>Visualiza y administra las reparaciones del taller.</span>
            </div>
            <div class="header-quick-actions">
                <div class="filter-group">
                    <i class="fas fa-filter filter-icon"></i>
                    <select class="filter-select" onchange="location.href='ordenes.php?estado=' + this.value;">
                        <?php
                        $opciones = ['Todos los Estados', 'Diagnóstico', 'Entregado', 'En Reparación', 'Listo para Entrega'];
                        foreach ($opciones as $opcion): ?>
                            <option value="<?= $opcion ?>" <?= $filtro_estado == $opcion ? 'selected' : '' ?>>
                                <?= $opcion ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn-header-quick btn-order" onclick="abrirModal('orden')">
                    <i class="fa-solid fa-plus"></i>
                    <span>Nueva Orden</span>
                </button>
            </div>
        </header>

        <!-- Alertas de Estado -->
        <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> La orden se ha generado correctamente.
            </div>
        <?php endif; ?>

        <!-- Tabla de Datos -->
        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>NRO ORDEN</th>
                            <th>Fecha Ingreso</th>
                            <th>Cliente</th>
                            <th>Equipo / Modelo</th>
                            <th>Estado Actual</th>
                            <th>Técnico</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt_ordenes && $stmt_ordenes->rowCount() > 0):
                            while ($orden = $stmt_ordenes->fetch(PDO::FETCH_ASSOC)):
                                // Mapeo moderno y limpio de clases según el estado
                                $clase_status = match ($orden['estado']) {
                                    'Ingresado'         => 'badge-blue',
                                    'En Reparación'     => 'badge-yellow',
                                    'Listo para Entrega' => 'badge-green',
                                    'Falta Repuesto'    => 'badge-red',
                                    default             => 'badge-gray',
                                };
                        ?>
                                <tr>
                                    <!-- ID de la Orden -->
                                    <td class="orden-id">
                                        <span>
                                            <?= htmlspecialchars($orden['codigo_orden']) ?>
                                        </span>
                                    </td>

                                    <!-- Fecha de Ingreso -->
                                    <td>
                                        <div class="date-cell">
                                            <i class="far fa-calendar-alt"></i>
                                            <span><?= date('d M, Y', strtotime($orden['fecha_ingreso'])) ?></span>
                                        </div>
                                    </td>

                                    <!-- Cliente -->
                                    <td class="td-main"><?= htmlspecialchars($orden['cliente']) ?></td>

                                    <!-- Equipo / Modelo -->
                                    <td>
                                        <span class="device-info">
                                            <i class="fas fa-mobile-alt"></i> <?= htmlspecialchars($orden['modelo']) ?>
                                        </span>
                                    </td>

                                    <!-- Estado de Reparación -->
                                    <td>
                                        <span class="badge <?= $clase_status; ?>">
                                            <i class="fas fa-circle"></i> <?= htmlspecialchars($orden['estado']); ?>
                                        </span>
                                    </td>

                                    <!-- Técnico Asignado -->
                                    <td>
                                        <div class="td-sub">
                                            <i class="fas fa-user-gear"></i>
                                            <span><?= htmlspecialchars($orden['tecnico'] ?? 'Pendiente') ?></span>
                                        </div>
                                    </td>

                                    <!-- Acciones Dinámicas -->
                                    <td>
                                        <div class="table-actions">
                                            <a href="ver_ficha.php?id=<?= $orden['id'] ?>" class="btn-icon btn-view" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="editar_orden.php?id=<?= $orden['id'] ?>" class="btn-icon btn-edit" title="Editar Orden">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php
                            endwhile; // <-- CORREGIDO: Antes tenías endforeach y rompía PHP
                        else:
                            ?>
                            <!-- Estado Vacío (colspan ajustado a 7 columnas reales) -->
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <p>No hay órdenes registradas actualmente.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Componente Modal -->
    <?php include 'includes/modal_orden.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/modales.js"></script>
</body>

</html>