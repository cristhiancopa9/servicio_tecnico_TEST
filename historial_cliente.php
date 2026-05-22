<?php
require_once 'config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Obtener y validar el ID del cliente
$id_cliente = $_GET['id'] ?? null;
if (!$id_cliente) {
    header("Location: clientes.php");
    exit();
}

// Inicializar variables para evitar el error de "Undefined variable"
$reparaciones = [];
$total_reparaciones = 0;
$total_productos = 0;
$compras = [];

try {
    // 2. Datos del Cliente
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmt->execute([$id_cliente]);
    $cliente = $stmt->fetch();

    if (!$cliente) {
        die("Cliente no encontrado.");
    }

    // 3. Traer Reparaciones
    // NOTA: Asegúrate de que los nombres de tablas (modelos, estados_orden) coincidan con tu DB
    $query_rep = "SELECT o.*, m.nombre_modelo, e.nombre as estado_nombre 
                  FROM ordenes_reparacion o
                  LEFT JOIN modelos m ON o.id_modelo = m.id
                  LEFT JOIN estados_orden e ON o.id_estado = e.id
                  WHERE o.id_cliente = ? 
                  ORDER BY o.id DESC";

    $stmt_rep = $pdo->prepare($query_rep);
    $stmt_rep->execute([$id_cliente]);
    $reparaciones = $stmt_rep->fetchAll();

    // 4. Calcular Total de Reparaciones
    foreach ($reparaciones as $r) {
        // Usamos el campo presupuesto_ars (asegúrate que se llame así en tu tabla)
        $total_reparaciones += (float)($r['presupuesto_ars'] ?? 0);
    }

    // 5. Intentar traer ventas (si la tabla existe)
    try {
        $stmt_v = $pdo->prepare("SELECT total FROM ventas WHERE id_cliente = ?");
        $stmt_v->execute([$id_cliente]);
        while ($v = $stmt_v->fetch()) {
            $total_productos += (float)$v['total'];
        }
    } catch (Exception $e) {
        // Si la tabla ventas no existe aún, el total de productos queda en 0
        $total_productos = 0;
    }
} catch (PDOException $e) {
    die("Error de base de datos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Historial: <?= htmlspecialchars($cliente['nombre_completo']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-stat {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            border-left: 5px solid #6366f1;
        }

        .card-stat h4 {
            margin: 0;
            color: #64748b;
            font-size: 0.9rem;
        }

        .card-stat .amount {
            font-size: 1.8rem;
            font-weight: bold;
            color: #1e293b;
            margin-top: 10px;
        }

        .card-total {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .history-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .badge-info {
            background: #e0f2fe;
            color: #0369a1;
        }
    </style>
</head>

<body class="dashboard-container">
    <div class="main-content" style="padding: 40px;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h1>Historial de Cliente: <span style="color: #6366f1;"><?= htmlspecialchars($cliente['nombre_completo']) ?></span></h1>
            <a href="clientes.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Directorio</a>
        </div>

        <!-- TARJETAS DE RESUMEN GASTOS -->
        <div class="summary-cards">
            <div class="card-stat">
                <h4>Total en Reparaciones</h4>
                <div class="amount">$<?= number_format($total_reparaciones, 2) ?></div>
            </div>
            <div class="card-stat">
                <h4>Total en Compras / Artículos</h4>
                <div class="amount">$<?= number_format($total_productos, 2) ?></div>
            </div>
            <div class="card-stat card-total">
                <h4>Gasto Total Histórico</h4>
                <div class="amount">$<?= number_format($total_reparaciones + $total_productos, 2) ?></div>
            </div>
        </div>

        <!-- SECCIÓN REPARACIONES -->
        <div class="history-section">
            <h3><i class="fas fa-tools"></i> Historial de Reparaciones</h3>
            <table width="100%" style="margin-top:15px; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #f1f5f9; text-align: left;">
                        <th style="padding:12px;">Fecha</th>
                        <th>Equipo</th>
                        <th>Falla</th>
                        <th>Estado</th>
                        <th>Monto</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reparaciones as $r): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding:12px;"><?= date('d/m/Y', strtotime($r['fecha_ingreso'])) ?></td>
                            <td><?= htmlspecialchars($r['nombre_modelo']) ?></td>
                            <td style="font-size:0.85rem; color:#64748b;"><?= substr($r['falla_reportada'], 0, 40) ?>...</td>
                            <td><span class="badge badge-info"><?= $r['estado_nombre'] ?></span></td>
                            <td><strong>$<?= number_format($r['presupuesto_ars'], 2) ?></strong></td>
                            <td><a href="ver_ficha.php?id=<?= $r['id'] ?>" class="btn-edit-small"><i class="fas fa-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECCIÓN COMPRAS -->
        <div class="history-section">
            <h3><i class="fas fa-shopping-cart"></i> Compras de Artículos</h3>
            <?php if (empty($compras)): ?>
                <p style="color:#94a3b8; margin-top:15px;">Este cliente aún no ha comprado productos en el local.</p>
            <?php else: ?>
                <table width="100%" style="margin-top:15px;">
                    <!-- Aquí iría la tabla de ventas similar a la anterior -->
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>