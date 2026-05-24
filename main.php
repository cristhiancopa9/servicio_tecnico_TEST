<?php
session_start();
include 'config/db.php'; // Tu archivo de conexión

// LÓGICA DE RECORDARME AUTOMÁTICA
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT id, nombre FROM usuarios WHERE token_recordado = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nombre'] = $user['nombre'];
        $_SESSION['id_rol'] = $user['id_rol'];
        header("Location: dashboard.php");
        exit();
    }
}

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | CMTECH WEB</title>
    <link rel="stylesheet" href="assets/css/style_login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <div class="sidebar-logo">
                    <h2>CM<span>TECH</span> <i class="fas fa-bolt"></i></h2>
                </div>
                <p>Gestión de Servicio Técnico</p>
            </div>
            <?php if (isset($_GET['registro'])): ?>
                <p style="color: #28a745; text-align: center;">¡Empresa registrada! Ya puedes iniciar sesión.</p>
            <?php endif; ?>
            <form action="auth/login_proceso.php" method="POST" class="login-form">
                <?php if (isset($_GET['error'])): ?>
                    <p style="color: #ff4d4d; text-align: center; font-size: 0.9rem; margin-bottom: 15px;">
                        <i class="fas fa-exclamation-circle"></i> Usuario o contraseña incorrectos
                    </p>
                <?php endif; ?>

                <div class="form-group">
                    <label><i class="fas fa-user"></i> Email / Usuario</label>
                    <input type="text" name="username" class="form-input" placeholder="tu@email.com" required autofocus>
                </div>

                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Contraseña</label>
                    <input type="password" name="password" class="form-input" placeholder="••••••••" required>
                </div>

                <div class="login-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Recordarme
                    </label>
                    <a href="auth/recuperar.php" class="forgot-pass">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Ingresar al Sistema</button>

                <div style="margin-top: 20px; text-align: center;">
                    <p style="color: #ccc; font-size: 0.9rem;">¿No tienes una cuenta?</p>
                    <a href="auth/registro.php" class="btn-register" style="color: #3B82F6; text-decoration: none; font-weight: bold;">Registra tu empresa aquí</a>
                </div>
            </form>
        </div>
        <p class="login-footer">&copy; 2026 CM TECH - Todos los derechos reservados</p>
    </div>
</body>

</html>

</html>
