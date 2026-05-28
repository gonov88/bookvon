<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = '¡Suscripción activada!';

// Si el usuario está logueado, actualizamos su rol a suscriptor
if (isLoggedIn()) {
    $db = getDB();
    $currentUser = getCurrentUser();
    $email = $currentUser['email'] ?? '';

    // Marcar rol como suscriptor en usuarios
    $db->prepare("UPDATE usuarios SET rol='suscriptor' WHERE id=?")
       ->execute([$_SESSION['usuario_id']]);

    // Insertar en suscripciones clásica para compatibilidad con el sistema existente
    try {
        $planId = (int)($db->query("SELECT id FROM planes WHERE activo=1 LIMIT 1")->fetchColumn());
        if ($planId) {
               ->execute([$_SESSION['usuario_id']]);
            $fin = date('Y-m-d', strtotime('+1 month'));
            $db->prepare("INSERT INTO suscripciones (usuario_id, plan_id, estado, fecha_inicio, fecha_fin, tipo) VALUES (?,?,'activa',CURDATE(),?,'mensual')")
               ->execute([$_SESSION['usuario_id'], $planId, $fin]);
        }
    } catch (Exception $e) { /* La suscripción paddle ya se registra via webhook */ }
}

include __DIR__ . '/../includes/header.php';
?>

<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px 24px;background:var(--gray-100);">
    <div style="background:white;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.1);padding:48px 40px;max-width:480px;width:100%;text-align:center;">
        <div style="font-size:64px;margin-bottom:24px;">🎉</div>
        <h1 style="font-family:'Playfair Display',serif;font-size:28px;font-weight:700;color:var(--gray-900);margin-bottom:12px;">¡Membresía activada!</h1>
        <p style="color:var(--gray-500);font-size:15px;line-height:1.6;margin-bottom:32px;">
            Ya tenés acceso a todos los ebooks de la plataforma.<br>
            Recibirás un email de confirmación en breve.
        </p>
        <a href="<?= APP_URL ?>/pages/catalogo.php" style="display:inline-block;background:var(--blue-dark);color:white;padding:14px 36px;border-radius:8px;font-size:16px;font-weight:700;text-decoration:none;margin-bottom:12px;">
            📚 Ir al catálogo
        </a>
        <br>
        <a href="<?= APP_URL ?>/index.php" style="display:inline-block;color:var(--blue-dark);font-size:14px;margin-top:8px;text-decoration:underline;">
            Volver al inicio
        </a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
