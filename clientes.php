<?php
// Iniciamos sesión para que funcione la validación de seguridad
session_start();

require_once 'config/db.php';

// Seguridad: Si no hay sesión, rebota al usuario al login
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Traemos los clientes con manejo de errores
try {
    $stmt = $pdo->query("SELECT * FROM clientes ORDER BY id DESC");
    $clientes = $stmt->fetchAll();
} catch (PDOException $e) {
    $stmt = $pdo->query("SELECT * FROM clientes");
    $clientes = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CM TECH | Directorio de Clientes</title>
    <link rel="stylesheet" href="assets/css/style_tablas.css">
    <link rel="stylesheet" href="assets/css/style_alertas.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style_modal.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <header>

            <div class="brand-meta">

                <h1>Directorio de Clientes</h1>
                <span>Visualiza y administra tus clientes.</span>

            </div>
            <div class="header-quick-actions">


                <button class="btn-header-quick btn-client" onclick="abrirModal('cliente')">
                    <i class="material-icons">person_add</i></i> <span>Registrar Cliente</span>
                </button>
            </div>
        </header>


        <div class="table-container">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>DNI / CUIT</th>
                            <th>Teléfono / WhatsApp</th>
                            <th>Email</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($clientes) > 0): ?>
                            <?php foreach ($clientes as $c): ?>
                                <tr>
                                    <td>
                                        <span class="td-main"><?php echo htmlspecialchars($c['nombre_completo']); ?></span>
                                    </td>
                                    <td>
                                        <span class="td-sub"><?php echo htmlspecialchars($c['documento']); ?></span>
                                    </td>
                                    <td>
                                        <a href="https://wa.me/54<?php echo $c['telefono']; ?>" target="_blank" class="whatsapp-link">
                                            <i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($c['telefono']); ?>
                                        </a>
                                    </td>
                                    <td class="td-sub"><?php echo htmlspecialchars($c['email']); ?></td>
                                    <td>
                                        <div class="actions-group">
                                            <a href="editar_cliente.php?id=<?php echo $c['id']; ?>" class="btn-icon btn-edit" title="Editar Cliente">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="historial_cliente.php?id=<?php echo $c['id']; ?>" class="btn-icon btn-history" title="Ver Historial">
                                                <i class="fas fa-history"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <i class="fas fa-users-slash"></i>
                                        <p>No hay clientes registrados aún.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div id="toast-alerta" class="toast-success">
            <div class="toast-content">
                <i class="fas fa-check-circle"></i>
                <div class="toast-text">
                    <span class="toast-title">¡Operación Éxito!</span>
                    <span class="toast-subtitle">Cliente registrado correctamente.</span>
                </div>
            </div>
            <button type="button" class="toast-close" onclick="cerrarToast()">&times;</button>
        </div>
    <?php endif; ?>

    <!-- Componente Modal -->
    <?php include 'includes/modal_clientes.php'; ?>

    <!-- Scripts -->
    <script src="assets/js/modales.js"></script>
    <script src="assets/js/alertas.js"></script>
</body>

</html>