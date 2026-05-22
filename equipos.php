<?php
require_once 'config/db.php'; // Carga la conexión y la sesión

// Seguridad: Si no hay sesión, rebota al usuario al login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}


// Consulta para traer equipos únicos que ya han sido entregados al menos una vez
$query_equipos = "SELECT 
                    m.nombre_modelo, 
                    o.imei_serie, 
                    c.nombre_completo AS dueno, 
                    MAX(o.fecha_ingreso) AS ultimo_servicio,
                    COUNT(o.id) AS visitas
                  FROM ordenes_reparacion o
                  INNER JOIN modelos m ON o.id_modelo = m.id
                  INNER JOIN clientes c ON o.id_cliente = c.id
                  WHERE o.id_estado = (SELECT id FROM estados_orden WHERE nombre = 'Entregado')
                  GROUP BY o.imei_serie
                  ORDER BY ultimo_servicio DESC";

$stmt = $pdo->prepare($query_equipos);
$stmt->execute();
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CM TECH SERVICIO TECNICO | Dashboard</title>
    <!-- Importamos el diseño y los iconos -->
    <link rel="stylesheet" href="assets/css/style_hstorials.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_tablas.css">
    <link rel="stylesheet" href="assets/css/style_modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="dashboard-container">

    <!-- Barra Lateral -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Contenido Principal -->
    <div>

        <main class="main-content">

            <header>
                <div class="brand-meta">
                    <h1>Historial de Equipos</h1>
                    <span>Base de datos de dispositivos entregados y su recurrencia.</span>
                </div>
            </header>

            <div class="table-container">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>MARCA/MODELO</th>
                            <th>N° DE SERIE</th>
                            <th>DUEÑO</th>
                            <th>ÚLTIMO SERVICIO</th>
                            <th>VISITAS</th>
                            <th>ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($equipos) > 0): ?>
                            <?php foreach ($equipos as $equipo): ?>
                                <tr>
                                    <td class="font-bold"><?= htmlspecialchars($equipo['nombre_modelo']) ?></td>
                                    <td class="text-mono"><?= htmlspecialchars($equipo['imei_serie']) ?></td>
                                    <td><?= htmlspecialchars($equipo['dueno']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($equipo['ultimo_servicio'])) ?></td>
                                    <td>
                                        <span class="visit-badge">
                                            <i class="fas fa-revolving-dot"></i> <?= $equipo['visitas'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-history" onclick="verHistorial('<?= $equipo['imei_serie'] ?>')">
                                            <i class="fas fa-clock-rotate-left"></i> Historial
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-state">No hay equipos entregados en el registro.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <!-- Fondo oscuro del Modal Nuevo Modelo -->
    <div id="modalModelo" class="modal-overlay">
        <div class="modal-content" style="width: 450px;">
            <div class="modal-header">
                <h2><i class="fas fa-mobile-alt"></i> Agregar Nuevo Modelo</h2>
                <button onclick="cerrarModalModelo()" class="btn-close">&times;</button>
            </div>

            <form id="formNuevoModelo" class="modal-body">
                <div class="form-group">
                    <label>Marca</label>
                    <select class="form-input" required>
                        <option value="">Seleccionar Marca...</option>
                        <option>Samsung</option>
                        <option>Apple</option>
                        <option>Motorola</option>
                        <option>Xiaomi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Nombre del Modelo</label>
                    <input type="text" class="form-input" placeholder="Ej: Galaxy S23 Ultra" required>
                </div>

                <div class="form-group">
                    <label>Categoría</label>
                    <select class="form-input">
                        <option>Smartphone</option>
                        <option>Tablet</option>
                        <option>Smartwatch</option>
                        <option>Notebook</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Observaciones de Hardware (Opcional)</label>
                    <textarea class="form-input" rows="2" placeholder="Ej: Usa pegamento T-7000, tornillos Pentalobe..."></textarea>
                </div>

                <div class="modal-footer">
                    <button type="button" onclick="cerrarModalModelo()" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Modelo</button>
                </div>
            </form>
        </div>
    </div>
    <?php include 'includes/modal_historial.php'; ?>
    <script>
        // Referencias a los modales
        const modalModelo = document.getElementById('modalModelo');

        // Funciones para Modelo
        function abrirModalModelo() {
            modalModelo.style.display = 'flex';
        }

        function cerrarModalModelo() {
            modalModelo.style.display = 'none';
        }

        // Cerrar cualquier modal si se hace clic fuera del contenido
        window.onclick = function(event) {
            if (event.target == modalModelo) cerrarModalModelo();
        }
    </script>
    <script>
        // Funciones para controlar el Modal de Historial
        const modalHistorial = document.getElementById('modalHistorial');

        function cerrarModalHistorial() {
            modalHistorial.style.display = 'none';
        }

        // Actualizamos el window.onclick para que sirva para ambos modales
        window.onclick = function(event) {
            if (event.target == modalModelo) cerrarModalModelo();
            if (event.target == modalHistorial) cerrarModalHistorial();
        }
    </script>
    <script src="assets\js\historial.js"></script>
</body>

</html>