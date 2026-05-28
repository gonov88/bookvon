<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Gestión de Suscripción';

if (!isLoggedIn()) {
    redirect(APP_URL . '/pages/login.php');
}

define('LOG_FILE', __DIR__ . '/../webhook_log.txt');

function logMsg(string $msg): void {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " | " . $msg . "\n", FILE_APPEND);
}

$db = getDB();
$currentUser = getCurrentUser();
$mensaje = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sub_id = trim($_POST['sub_id'] ?? '');

    if (empty($sub_id)) {
        $error = 'No se encontró un ID de suscripción.';
    } else {
        $url = PADDLE_API_BASE . "/subscriptions/{$sub_id}/cancel";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['effective_from' => 'immediately']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . PADDLE_API_KEY,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        logMsg("CANCELAR: sub_id=$sub_id HTTP=$httpCode curlError=$curlError");

        if ($response === false || !empty($curlError)) {
            $error = "❌ Error de conexión: $curlError";
        } else {
            $resp = json_decode($response, true);
            if ($httpCode === 200 && isset($resp['data']['status'])) {
                // Cancelar en paddle_suscripciones
                try {
                    $db->prepare("UPDATE paddle_suscripciones SET status='canceled', actualizado_en=NOW() WHERE paddle_sub_id=?")
                       ->execute([$sub_id]);
                } catch(Exception $e) {}

                // Cancelar en suscripciones clásica
                   ->execute([$currentUser['id']]);
                $db->prepare("UPDATE usuarios SET rol='visitante' WHERE id=? AND rol='suscriptor'")
                   ->execute([$currentUser['id']]);

                logMsg("BD actualizada: sub_id=$sub_id → canceled");
                $mensaje = "✅ Suscripción cancelada correctamente. Tu acceso continuará hasta el fin del período actual.";
            } else {
                $detalle = $resp['error']['detail'] ?? $resp['error']['type'] ?? $response;
                $error = "❌ Error al cancelar (HTTP $httpCode): $detalle";
                logMsg("ERROR Paddle: HTTP=$httpCode detalle=$detalle");
            }
        }
    }
}

// Buscar suscripción Paddle activa del usuario
$paddleSub = null;
try {
    $ps = $db->prepare("SELECT * FROM paddle_suscripciones WHERE email=? ORDER BY id DESC LIMIT 1");
    $ps->execute([$currentUser['email']]);
    $paddleSub = $ps->fetch();
} catch(Exception $e) {}

// Suscripción clásica
$subClasica = null;
try {
    $sc = $db->prepare("SELECT s.*, s.plan as plan_nombre FROM paddle_suscripciones s WHERE s.email=(SELECT email FROM usuarios WHERE id=?) AND s.status='active' LIMIT 1");
    $sc->execute([$currentUser['id']]);
    $subClasica = $sc->fetch();
} catch(Exception $e) {}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Gestión de Suscripción</h1>
    </div>
</div>

<div style="max-width:700px;margin:48px auto;padding:0 24px;">

    <?php if ($mensaje): ?>
    <div style="background:#f0fdf4;color:#166534;padding:16px 20px;border-radius:8px;margin-bottom:24px;border:1px solid #bbf7d0;">
        <?= $mensaje ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div style="background:#fef2f2;color:#991b1b;padding:16px 20px;border-radius:8px;margin-bottom:24px;border:1px solid #fecaca;">
        <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- Estado actual -->
    <div style="background:white;border-radius:12px;box-shadow:0 2px 12px rgba(0,0,0,0.08);padding:28px;margin-bottom:24px;">
        <h2 style="font-size:18px;font-weight:700;color:var(--gray-900);margin-bottom:20px;">Estado de tu suscripción</h2>

        <?php if ($paddleSub && $paddleSub['status'] === 'active'): ?>
        <div style="display:flex;align-items:center;gap:16px;background:var(--blue-light);border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <span style="font-size:28px;">✅</span>
            <div>
                <div style="font-weight:700;font-size:15px;color:var(--blue-dark);">Suscripción activa — $1,99/mes (Paddle)</div>
                <div style="font-size:13px;color:var(--gray-500);margin-top:4px;">ID: <?= sanitize($paddleSub['paddle_sub_id']) ?></div>
            </div>
        </div>
        <form method="POST" onsubmit="return confirm('¿Estás seguro de que querés cancelar tu suscripción?')">
            <input type="hidden" name="sub_id" value="<?= sanitize($paddleSub['paddle_sub_id']) ?>">
            <button type="submit" style="background:#ef4444;color:white;padding:12px 28px;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;">
                Cancelar suscripción
            </button>
        </form>

        <?php elseif ($subClasica): ?>
        <div style="display:flex;align-items:center;gap:16px;background:var(--blue-light);border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <span style="font-size:28px;">✅</span>
            <div>
                <div style="font-weight:700;font-size:15px;color:var(--blue-dark);">Suscripción activa — <?= sanitize($subClasica['plan_nombre']) ?></div>
                <div style="font-size:13px;color:var(--gray-500);margin-top:4px;">Vence: <?= date('d/m/Y', strtotime($subClasica['fecha_fin'])) ?></div>
            </div>
        </div>
        <p style="font-size:13px;color:var(--gray-500);">Para cancelar, contactá a soporte.</p>

        <?php else: ?>
        <div style="text-align:center;padding:24px;color:var(--gray-500);">
            <p>No tenés una suscripción activa.</p>
            <a href="<?= APP_URL ?>/pages/suscripcion.php" style="display:inline-block;margin-top:12px;padding:12px 28px;background:var(--blue-dark);color:white;border-radius:6px;font-weight:700;text-decoration:none;">
                Suscribirme
            </a>
        </div>
        <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:8px;">
        <a href="<?= APP_URL ?>/pages/perfil.php" style="color:var(--blue-dark);font-size:14px;">← Volver a mi perfil</a>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
