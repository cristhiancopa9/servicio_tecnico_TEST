<?php
require_once 'config/db.php';
require_once 'includes/funciones_dashboard.php';

// Seguridad: Si no hay sesión, rebota al usuario al login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Obtener Listas 
$sucursales = obtenerLista($pdo, 'sucursales', 'nombre');
$clientes = obtenerLista($pdo, 'clientes', 'nombre_completo');
$modelos  = obtenerLista($pdo, 'modelos', 'nombre_modelo');

// Datos Perfil
$userName      = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
$userRoleName  = obtenerNombreRol($_SESSION['id_rol'] ?? 0); // Función que crearemos
$userInitials  = obtenerIniciales($userName);

// Carga de datos usando las funciones
$contar_ingresados = contarOrdenesPorEstado($pdo, 'Ingresado');
$stats = obtenerEstadisticas($pdo);
$stmt_ordenes = obtenerOrdenesRecientes($pdo);
$todas_las_ordenes = $pdo->query("SELECT o.id, c.nombre_completo as cliente_nombre, m.nombre_modelo as equipo_modelo 
    FROM ordenes_reparacion o 
    JOIN clientes c ON o.id_cliente = c.id 
    JOIN modelos m ON o.id_modelo = m.id 
    ORDER BY o.id DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);

// 2. Para el Modal (Las 10 más recientes)
$ordenes_recientes = $pdo->query("SELECT o.id, c.nombre_completo as cliente_nombre, m.nombre_modelo as equipo_modelo 
    FROM ordenes_reparacion o 
    JOIN clientes c ON o.id_cliente = c.id 
    JOIN modelos m ON o.id_modelo = m.id 
    ORDER BY o.fecha_ingreso DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CM TECH | Dashboard</title>

    <!-- Fuentes y Iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Lato:wght@400;700&display=swap"
        rel="stylesheet">
    <!-- Estilos (Asegúrate de que las rutas sean correctas) -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_modal.css">
    <link rel="stylesheet" href="assets/css/style_tablas.css">
    <link rel="stylesheet" href="assets/pattern/patternLock.css">

    <!-- Scripts Base -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/pattern/patternLock.js"></script>
</head>

<body>

    <!-- Navegación Lateral -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- CONTENIDO PRINCIPAL -->
    <main class="main-content">

        <header>
            <div class="brand-meta">
                <h1>Tablero de Control</h1>
                <span>Servicio Tecnico — Dashboard unificado</span>
            </div>
            <!-- BOTONES DE ACCIÓN RÁPIDA -->
            <div class="header-quick-actions">
                <button class="btn-header-quick btn-order" onclick="abrirModal('orden')">
                    <i class="fa-solid fa-plus"></i>
                    <span>Nueva Orden</span>
                </button>
                <button class="btn-header-quick btn-sale" onclick="abrirModal('venta')">
                    <i class="fa-solid fa-cart-plus"></i>
                    <span>Nueva Venta</span>
                </button>
                <button class="btn-header-quick btn-client" onclick="abrirModal('cliente')">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <span>Buscar Cliente</span>
                </button>
                <button class="btn-header-quick btn-expense" onclick="abrirModal('gasto')">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span>Gasto</span>
                </button>
            </div>

        </header>

        <!-- SCORECARDS (NÚMEROS DE HOY) -->
        <section class="scorecards-grid">
            <div class="card sales">
                <div class="card-info">
                    <p>Ventas del Día</p>
                    <h3 id="stat-sales">$45,200</h3>
                    <div class="card-delta up"><i class="fa-solid fa-arrow-trend-up"></i> +12% vs ayer</div>
                </div>
                <div class="card-icon" style="background: var(--success-light); color: var(--success);"><i
                        class="fa-solid fa-cash-register"></i></div>
            </div>
            <div class="card vault">
                <div class="card-info">
                    <p>Ingresos en Caja</p>
                    <h3 id="stat-vault">$128,000</h3>
                    <div class="card-delta up"><i class="fa-solid fa-arrow-trend-up"></i> +5% vs ayer</div>
                </div>
                <div class="card-icon" style="background: var(--primary-light); color: var(--primary);"><i
                        class="fa-solid fa-vault"></i></div>
            </div>
            <div class="card repairs">
                <div class="card-info">
                    <p>Equipos Ingresados</p>
                    <h3 id="stat-orders"><?= $contar_ingresados ?></h3>
                    <div class="card-delta down"><i class="fa-solid fa-arrow-trend-down"></i> -2 vs ayer</div>
                </div>
                <div class="card-icon" style="background: var(--warning-light); color: var(--warning);"><i
                        class="fa-solid fa-screwdriver-wrench"></i></div>
            </div>
            <div class="card debt">
                <div class="card-info">
                    <p>Cobranza Pendiente</p>
                    <h3 id="stat-debt">$15,800</h3>
                    <div class="card-delta neutral"><i class="fa-solid fa-clock"></i> Vence hoy/mañana</div>
                </div>
                <div class="card-icon" style="background: #f5f3ff; color: var(--accent-purple);"><i
                        class="fa-solid fa-calendar-day"></i></div>
            </div>
        </section>

        <!-- SECCIÓN DE TABLA ORDENES RECIENTES -->
        <section class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-list-ul"></i> Órdenes de Servicio Recientes</h2>
                <a href="ordenes.php" class="btn btn-secondary">Ver Todas</a>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID ORDEN</th>
                            <th>CLIENTE</th>
                            <th>EQUIPO</th>
                            <th>ESTADO</th>
                            <th class="text-right">ACCIÓN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($stmt_ordenes && $stmt_ordenes->rowCount() > 0): ?>
                            <?php while ($orden = $stmt_ordenes->fetch(PDO::FETCH_ASSOC)):
                                $clase_status = 'badge-gray';
                                if ($orden['estado'] == 'Ingresado') $clase_status = 'badge-blue';
                                if ($orden['estado'] == 'En Reparación') $clase_status = 'badge-yellow';
                                if ($orden['estado'] == 'Listo para Entrega') $clase_status = 'badge-green';
                                if ($orden['estado'] == 'Falta Repuesto') $clase_status = 'badge-red';
                            ?>
                                <tr>
                                    <td class="orden_color"><span> <?= htmlspecialchars($orden['codigo_orden']) ?></span></td>
                                    <td>
                                        <div class="td-main"><?php echo htmlspecialchars($orden['cliente']); ?></div>
                                        <div class="td-sub">Cliente Registrado</div>
                                    </td>
                                    <td>
                                        <div class="td-icon-group">
                                            <i class="fas fa-mobile-alt"></i>
                                            <span><?php echo htmlspecialchars($orden['modelo']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $clase_status; ?>">
                                            <i class="fas fa-circle"></i> <?php echo htmlspecialchars($orden['estado']); ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <a href="ver_ficha.php?id=<?php echo $orden['id']; ?>" class="btn-action">
                                            <i class="fas fa-eye"></i> Gestionar
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
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
        </section>

    </main>


    <!-- Modales -->
    <?php include 'includes/modal_orden.php'; ?>
    <?php include 'includes/modal_clientes.php'; ?>
    <!-- Scripts Finales -->
    <script src="assets\js\dashboard.js"></script>
    <script src="assets\js\modales.js"></script>
    <div id="modalGasto" class="modal-overlay" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h4><i class="fas fa-minus-circle"></i> Registrar Gasto de Caja</h4>
            </div>
            <form action="procesos/guardar_gasto.php" method="POST" class="modal-body">
                <input type="text" name="descripcion" placeholder="¿En qué se gastó?" required>
                <input type="number" step="0.01" name="monto" placeholder="Monto $" required>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" onclick="cerrarModalGasto()" class="btn btn-secondary">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalGasto() {
            document.getElementById('modalGasto').style.display = 'flex';
        }

        function cerrarModalGasto() {
            document.getElementById('modalGasto').style.display = 'none';
        }

        function abrirModalVenta() {
            document.getElementById('modalVenta').style.display = 'flex';
        }

        function cerrarModalVenta() {
            document.getElementById('modalVenta').style.display = 'none';
        }

        // Función básica para agregar un item a la tabla (Simulada)
        function agregarArticulo() {
            const input = document.getElementById('buscarProducto');
            if (input.value === "") return;

            const tabla = document.getElementById('itemsVenta');
            const row = tabla.insertRow();

            // Aquí podrías separar el nombre del precio usando un split si el value tiene ese formato
            row.innerHTML = `
        <td>${input.value}</td>
        <td>1</td>
        <td>$0.00</td>
        <td>$0.00</td>
        <td><button type="button" onclick="this.parentElement.parentElement.remove()" style="color:red; border:none; background:none; cursor:pointer;"><i class="fas fa-trash"></i></button></td> `;

            input.value = ""; // Limpiar buscador
        }

        // Cerrar al hacer clic fuera del modal
        window.onclick = function(event) {
            let modal = document.getElementById('modalVenta');
            if (event.target == modal) {
                cerrarModalVenta();
            }
        }
    </script>

    <!-- <div id="modalVenta" class="modal-venta">
        <div class="modal-content-venta">
            <div class="modal-header">
                <h3><i class="fas fa-shopping-cart"></i> Nueva Venta Rápida</h3>
                <button class="close-modal" onclick="cerrarModalVenta()">&times;</button>
            </div>

            <form id="formNuevaVenta" action="procesos/guardar_venta.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Buscar Producto/Repuesto</label>
                        <div class="search-item-box">
                            <input type="text" id="buscarProducto" placeholder="Escanea código o escribe nombre..." list="listaProductos">
                            <datalist id="listaProductos">
                                <option value="Pantalla iPhone 11 - $45.000">
                                <option value="Batería Samsung S20 - $15.000">
                                <option value="Templado Genérico - $2.500">
                            </datalist>
                            <button type="button" class="btn-add-item" onclick="agregarArticulo()"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="venta-detalle">
                        <table id="tablaVenta">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cant.</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="itemsVenta">
                            </tbody>
                        </table>
                    </div>

                    <div class="venta-resumen">
                        <div class="total-row">
                            <span>TOTAL A PAGAR:</span>
                            <strong id="totalVenta">$0.00</strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Método de Pago</label>
                        <select name="metodo_pago" required>
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia / QR</option>
                            <option value="debito">Débito / Crédito</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-secondary" onclick="cerrarModalVenta()">Cancelar</button>
                    <button type="submit" class="btn-primary">Finalizar Venta</button>
                </div>
            </form>
        </div>
    </div>
    -->

</body>

</html>