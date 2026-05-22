<?php
require_once 'config/db.php';

$id_orden = $_GET['id'] ?? null;

if (!$id_orden) {
    header("Location: dashboard.php");
    exit();
}

// ==========================================================================
// PROCESAR ACTUALIZACIÓN DE DATOS (EX-EDITAR_ORDEN.PHP)
// ==========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_editar_orden'])) {
    try {
        $sql = "UPDATE ordenes_reparacion SET 
                id_modelo = ?, 
                imei_serie = ?, 
                codigo_desbloqueo = ?, 
                falla_reportada = ?, 
                observaciones_ingreso = ?, 
                presupuesto_ars = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['id_modelo'],
            $_POST['imei'],
            $_POST['codigo'],
            $_POST['falla'],
            $_POST['obs'],
            $_POST['presupuesto'],
            $id_orden
        ]);
        header("Location: ver_ficha.php?id=$id_orden&msg=editado");
        exit();
    } catch (PDOException $e) {
        $error_edicion = "Error al actualizar los datos: " . $e->getMessage();
    }
}

// ==========================================================================
// CONSULTAS DE DATOS PARA RENDERIZAR LA FICHA
// ==========================================================================
$query_estados = "SELECT id, nombre FROM estados_orden ORDER BY id ASC";
$stmt_estados = $pdo->query($query_estados);
$todos_los_estados = $stmt_estados->fetchAll(PDO::FETCH_ASSOC);

// Traer todos los modelos para el desplegable de edición
$stmtModelos = $pdo->query("SELECT id, nombre_modelo FROM modelos ORDER BY nombre_modelo ASC");
$modelos = $stmtModelos->fetchAll(PDO::FETCH_ASSOC);

try {
    $query_repuestos = "SELECT 
                        p.nombre, 
                        p.costo_usd, 
                        p.costo_usd as costo_momento, 
                        o.valor_dolar_entrega 
                    FROM ordenes_repuestos orp
                    INNER JOIN productos p ON orp.id_producto = p.id
                    INNER JOIN ordenes_reparacion o ON orp.id_orden = o.id
                    WHERE orp.id_orden = :id";

    $stmt_rep = $pdo->prepare($query_repuestos);
    $stmt_rep->execute(['id' => $id_orden]);
    $repuestos = $stmt_rep->fetchAll(PDO::FETCH_ASSOC);

    $query = "SELECT o.*, 
                 e.nombre as estado_nombre, 
                 c.nombre_completo as cliente_nombre, c.telefono, c.email,
                 m.nombre_modelo as equipo_modelo,
                 s.nombre as sucursal_nombre,
                 u.nombre_completo as tecnico_nombre 
          FROM ordenes_reparacion o
          INNER JOIN clientes c ON o.id_cliente = c.id
          INNER JOIN modelos m ON o.id_modelo = m.id
          INNER JOIN estados_orden e ON o.id_estado = e.id
          LEFT JOIN sucursales s ON o.id_sucursal = s.id
          LEFT JOIN usuarios u ON o.id_tecnico = u.id
          WHERE o.id = :id";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id_orden]);
    $orden = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$orden) {
        die("La orden #$id_orden no existe.");
    }

    $query_h = "SELECT h.*, e.nombre as estado_nombre, u.nombre_completo as usuario_nombre
                FROM historial_estados_orden h
                INNER JOIN estados_orden e ON h.id_estado = e.id
                INNER JOIN usuarios u ON h.id_usuario = u.id
                WHERE h.id_orden = :id
                ORDER BY h.fecha_cambio DESC";

    $stmt_h = $pdo->prepare($query_h);
    $stmt_h->execute(['id' => $id_orden]);
    $historial = $stmt_h->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error crítico: " . $e->getMessage());
}

$clase_badge_principal = match ($orden['estado_nombre']) {
    'Ingresado'          => 'status-ingresado',
    'En Reparación'      => 'status-reparacion',
    'Listo para Entrega' => 'status-listo',
    'Falta Repuesto'     => 'status-falta',
    default              => 'status-defecto',
};
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Orden: <?= htmlspecialchars($orden['codigo_orden'] ?? '#' . $orden['id']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style_verficha.css">
</head>

<body>

    <div class="action-bar no-print">
        <div class="actions-left">
            <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver</a>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'editado'): ?>
                <span style="color: #10b981; font-size: 0.85rem; font-weight: 600; margin-left: 15px;"><i class="fas fa-check-circle"></i> ¡Ficha actualizada con éxito!</span>
            <?php endif; ?>
            <?php if (isset($error_edicion)): ?>
                <span style="color: #ef4444; font-size: 0.85rem; font-weight: 600; margin-left: 15px;"><i class="fas fa-exclamation-circle"></i> <?= $error_edicion ?></span>
            <?php endif; ?>
        </div>
        <div class="actions-right">
            <button onclick="window.print();" class="btn btn-print">
                <i class="fas fa-print"></i> Imprimir Ficha
            </button>
            <button onclick="abrirModalEstado()" class="btn btn-status">
                <i class="fas fa-sync-alt"></i> Cambiar Estado
            </button>
            <button onclick="abrirModalEditar()" class="btn btn-edit">
                <i class="fas fa-edit"></i> Modificar Datos
            </button>
        </div>
    </div>

    <div class="ficha-wrapper">

        <header class="ficha-header">
            <div class="header-title">
                <h1>ORDEN: <span><?= htmlspecialchars($orden['codigo_orden'] ?? '#' . $orden['id']) ?></span></h1>
                <span class="sucursal-tag"><i class="fas fa-store"></i> <?= htmlspecialchars($orden['sucursal_nombre'] ?? 'Casa Central') ?></span>
            </div>
            <div class="status-badge <?= $clase_badge_principal ?>"><?= htmlspecialchars($orden['estado_nombre']) ?></div>
        </header>

        <div class="row-info-cards">
            <div class="card card-compact">
                <h3><i class="fas fa-user"></i> Datos del Cliente</h3>
                <div class="info-row">
                    <div class="info-group">
                        <span class="info-label">Nombre Completo</span>
                        <div class="info-value"><?= htmlspecialchars($orden['cliente_nombre']); ?></div>
                    </div>
                    <div class="info-group">
                        <span class="info-label">Teléfono / WhatsApp</span>
                        <div class="info-value"><i class="fab fa-whatsapp" style="color:#22c55e;"></i> <?= htmlspecialchars($orden['telefono']); ?></div>
                    </div>
                    <?php if (!empty($orden['email'])): ?>
                        <div class="info-group">
                            <span class="info-label">Email</span>
                            <div class="info-value text-sm"><?= htmlspecialchars($orden['email']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card card-compact">
                <h3><i class="fas fa-mobile-alt"></i> Especificaciones del Equipo</h3>
                <div class="info-row">
                    <div class="info-group">
                        <span class="info-label">Modelo</span>
                        <div class="info-value-device"><i class="fas fa-mobile-screen-button"></i> <?= htmlspecialchars($orden['equipo_modelo']); ?></div>
                    </div>
                    <div class="info-group">
                        <span class="info-label">IMEI / Nro Serie</span>
                        <div class="info-value monospace"><?= htmlspecialchars($orden['imei_serie']); ?></div>
                    </div>
                    <div class="info-group">
                        <span class="info-label">PIN / Código</span>
                        <div class="info-value-code"><?= !empty($orden['codigo_desbloqueo']) ? htmlspecialchars($orden['codigo_desbloqueo']) : '<i>Sin código</i>'; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="left-col">
                <div class="card">
                    <h3><i class="fas fa-tools"></i> Detalles del Servicio</h3>
                    <div class="data-grid-vertical">
                        <div class="data-item-box">
                            <span class="label">Falla Reportada</span>
                            <div class="text-box"><?= nl2br(htmlspecialchars($orden['falla_reportada'])); ?></div>
                        </div>
                        <div class="data-item-box">
                            <span class="label">Diagnóstico del Técnico</span>
                            <div class="text-box diagnostico"><?= !empty($orden['diagnostico_tecnico']) ? nl2br(htmlspecialchars($orden['diagnostico_tecnico'])) : 'Sin diagnóstico asignado todavía...'; ?></div>
                        </div>
                        <?php if (!empty($orden['observaciones_ingreso'])): ?>
                            <div class="data-item-box">
                                <span class="label">Observaciones de Ingreso</span>
                                <div class="text-box"><?= nl2br(htmlspecialchars($orden['observaciones_ingreso'])); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card no-print border-danger">
                    <h3><i class="fas fa-box-open"></i> Repuestos Usados <small>(Uso Interno)</small></h3>
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>Repuesto</th>
                                <th style="text-align: right;">Costo Interno</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($repuestos)): ?>
                                <?php foreach ($repuestos as $rep): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($rep['nombre']); ?></strong></td>
                                        <td style="text-align: right;">
                                            <?php
                                            $dolar_dia = $rep['valor_dolar_entrega'] ?? 0;
                                            $costo_ars = $rep['costo_usd'] * $dolar_dia;
                                            if ($dolar_dia > 0): ?>
                                                <span class="price-ars">$<?= number_format($costo_ars, 2, ',', '.'); ?> ARS</span>
                                                <small class="price-usd">(U$D <?= number_format($rep['costo_usd'], 2); ?> x <?= $dolar_dia; ?>)</small>
                                            <?php else: ?>
                                                <span class="text-danger">Dólar no registrado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="table-empty">No se registraron repuestos en este trabajo.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="total-repuestos-box">
                        <?php
                        $total_costo_ars = 0;
                        foreach ($repuestos as $rep) {
                            $dolar_dia = $rep['valor_dolar_entrega'] ?? 0;
                            $total_costo_ars += ($rep['costo_usd'] * $dolar_dia);
                        }
                        echo "Costo Total Repuestos: $" . number_format($total_costo_ars, 2, ',', '.');
                        ?>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="price-box">
                    <span class="price-label">Total Presupuesto</span>
                    <h2>$<?= number_format($orden['presupuesto_ars'], 2, ',', '.'); ?> <span style="font-size:1.1rem; font-weight:500;">ARS</span></h2>
                    <div class="tecnico-asignado-label">
                        <i class="fas fa-user-gear"></i> Técnico: <?= htmlspecialchars($orden['tecnico_nombre'] ?? 'No asignado'); ?>
                    </div>
                </div>

                <div class="card no-print border-danger">
                    <h3><i class="fas fa-history"></i> Historial</h3>
                    <div class="timeline">
                        <?php foreach ($historial as $h): ?>
                            <div class="timeline-item">
                                <span class="timeline-date"><?= date('d/m H:i', strtotime($h['fecha_cambio'])); ?>hs</span>
                                <div class="timeline-content">
                                    <span class="timeline-status"><?= htmlspecialchars($h['estado_nombre']); ?></span>
                                    <small class="timeline-user">Por: <?= htmlspecialchars($h['usuario_nombre']); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="modalEstado" class="modal-overlay" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Actualizar Estado de Reparación</h3>
                <button type="button" onclick="cerrarModalEstado()" class="close-btn">&times;</button>
            </div>
            <form action="procesar_cambio_estado.php" method="POST">
                <input type="hidden" name="id_orden" value="<?= $id_orden; ?>">
                <div class="modal-body">
                    <label class="form-label">Seleccione Nuevo Estado:</label>
                    <select name="nuevo_estado" class="form-input" required>
                        <?php foreach ($todos_los_estados as $est): ?>
                            <option value="<?= $est['id']; ?>" <?= ($est['id'] == $orden['id_estado']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($est['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label class="form-label" style="margin-top:15px; display:block;">Diagnóstico / Avance Técnico:</label>
                    <textarea name="comentario" class="form-input text-area" placeholder="Escribí detalladamente la reparación o novedad..."><?= htmlspecialchars($orden['diagnostico_tecnico']); ?></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalEstado()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-status" style="background-color: var(--primary);">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/modal_editar_orden.php'; ?>

    <script>
        // Funciones para el Modal de Estado
        function abrirModalEstado() {
            document.getElementById('modalEstado').style.display = 'flex';
        }

        function cerrarModalEstado() {
            document.getElementById('modalEstado').style.display = 'none';
        }

        // Funciones para el Modal de Edición de Orden
        function abrirModalEditar() {
            document.getElementById('modalEditarOrden').style.display = 'flex';
        }

        function cerrarModalEditar() {
            document.getElementById('modalEditarOrden').style.display = 'none';
        }

        // Cerrar modales automáticamente si el usuario hace clic fuera de la caja blanca
        window.onclick = function(event) {
            var modalEstado = document.getElementById('modalEstado');
            var modalEditar = document.getElementById('modalEditarOrden');
            if (event.target == modalEstado) {
                modalEstado.style.display = "none";
            }
            if (event.target == modalEditar) {
                modalEditar.style.display = "none";
            }
        }
    </script>
</body>

</html>