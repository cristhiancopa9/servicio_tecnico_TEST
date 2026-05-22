<?php
require_once 'config/db.php';
session_start(); // Para obtener el ID del usuario logueado

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_orden = $_POST['id_orden'];
    $nuevo_estado = $_POST['nuevo_estado'];
    $comentario = $_POST['comentario'] ?? '';
    $id_usuario = $_SESSION['user_id'] ?? 1; // Ajusta según tu variable de sesión

    try {
        $pdo->beginTransaction();

        // 1. Actualizar el estado y el diagnóstico en la orden principal
        $query_upd = "UPDATE ordenes_reparacion 
                      SET id_estado = :estado, 
                          diagnostico_tecnico = :diag 
                      WHERE id = :id";
        $stmt_upd = $pdo->prepare($query_upd);
        $stmt_upd->execute([
            'estado' => $nuevo_estado,
            'diag'   => $comentario,
            'id'     => $id_orden
        ]);

        // 2. Insertar en el historial de estados
        $query_hist = "INSERT INTO historial_estados_orden (id_orden, id_estado, id_usuario, fecha_cambio) 
                       VALUES (:id_o, :id_e, :id_u, NOW())";
        $stmt_hist = $pdo->prepare($query_hist);
        $stmt_hist->execute([
            'id_o' => $id_orden,
            'id_e' => $nuevo_estado,
            'id_u' => $id_usuario
        ]);

        $pdo->commit();
        header("Location: ver_ficha.php?id=" . $id_orden . "&msg=estado_actualizado");
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error al actualizar: " . $e->getMessage());
    }
}
