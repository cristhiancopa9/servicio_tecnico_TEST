<?php
require_once 'config/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("ID de cliente no proporcionado.");

// Procesar la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE clientes SET 
                nombre_completo = ?, 
                documento = ?, 
                telefono = ?, 
                email = ? 
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['nombre'],
            $_POST['dni'],
            $_POST['telefono'],
            $_POST['email'],
            $id
        ]);
        header("Location: clientes.php?msg=cliente_actualizado");
        exit();
    } catch (PDOException $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// Obtener datos actuales
$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch();

if (!$cliente) die("Cliente no encontrado.");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Cliente</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f8fafc;
            padding: 40px;
        }

        .form-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        h2 {
            color: #1e293b;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #64748b;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .btn-save {
            background: #10b981;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            width: 100%;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .btn-back {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #94a3b8;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <div class="form-container">
        <h2><i class="fas fa-user-edit"></i> Editar Cliente</h2>

        <?php if (isset($error)): ?>
            <p style="color:red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Nombre Completo</label>
                <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre_completo']) ?>" required>
            </div>

            <div class="form-group">
                <label>DNI / CUIT</label>
                <input type="text" name="dni" value="<?= htmlspecialchars($cliente['documento']) ?>">
            </div>

            <div class="form-group">
                <label>Teléfono / WhatsApp</label>
                <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono']) ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>">
            </div>

            <button type="submit" class="btn-save">Guardar Cambios</button>
            <a href="clientes.php" class="btn-back">Cancelar</a>
        </form>
    </div>

</body>

</html>