<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Suscripción';
$db = getDB();

// Si viene de success_premium.php con ?paddle_ok=1, activar al usuario en BD local
if (isset($_GET['paddle_ok']) && isLoggedIn()) {
    $currentUser = getCurrentUser();
    $email = $currentUser['email'] ?? '';
    if ($email) {
        // Marcar el usuario como suscriptor en la tabla usuarios
        $db->prepare("UPDATE usuarios SET rol='suscriptor' WHERE id=?")->execute([$_SESSION['usuario_id']]);
    }
}

$plan = null;
try { $plan = $db->query("SELECT * FROM planes WHERE activo=1 LIMIT 1")->fetch(); } catch(Exception $e) {}

include __DIR__ . '/../includes/header.php';
$currentUser = getCurrentUser();
$esSuscriptor = hasActiveSubscription();
?>

<div class="page-header">
    <div class="page-header-inner">
        <h1>Suscripción</h1>
    </div>
</div>

<!-- HERO -->
<section style="background:var(--blue-dark);padding:64px 24px;text-align:center;color:white;">
    <div style="max-width:680px;margin:0 auto;">
        <p style="font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#93C5FD;margin-bottom:14px;">Acceso completo</p>
        <h2 style="font-family:'Playfair Display',serif;font-size:clamp(28px,4vw,46px);font-weight:700;line-height:1.2;margin-bottom:16px;">
            Todo el catálogo por<br><em style="color:#93C5FD;font-style:normal;">$1,99 al mes</em>
        </h2>
        <p style="font-size:17px;color:#CBD5E1;max-width:480px;margin:0 auto 36px;">
            Sin sorpresas. Sin cargos por libro. Una sola suscripción para acceder a todos los ebooks de la plataforma.
        </p>

        <?php if ($esSuscriptor): ?>
        <div style="background:rgba(255,255,255,0.1);border:1.5px solid rgba(255,255,255,0.25);border-radius:12px;padding:20px 28px;display:inline-flex;align-items:center;gap:12px;">
            <span style="font-size:24px;">✅</span>
            <div style="text-align:left;">
                <div style="font-weight:700;font-size:15px;">Ya tenés una suscripción activa</div>
                <div style="font-size:13px;color:#93C5FD;margin-top:2px;">Podés leer todos los ebooks del catálogo</div>
            </div>
        </div>
        <?php elseif (isLoggedIn()): ?>
        <button onclick="abrirCheckoutPaddle()" style="background:white;color:var(--blue-dark);padding:16px 48px;border-radius:6px;font-size:17px;font-weight:800;cursor:pointer;border:none;transition:all .18s;box-shadow:0 4px 20px rgba(0,0,0,0.2);"
            onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 28px rgba(0,0,0,0.3)'"
            onmouseout="this.style.transform='';this.style.boxShadow='0 4px 20px rgba(0,0,0,0.2)'">
            Suscribirme por $1,99/mes
        </button>
        <p style="font-size:12.5px;color:#94A3B8;margin-top:14px;">Cancelá cuando quieras · Sin compromisos · Pago seguro con Paddle</p>
        <?php else: ?>
        <a href="<?= APP_URL ?>/pages/registro.php" style="display:inline-block;background:white;color:var(--blue-dark);padding:16px 48px;border-radius:6px;font-size:17px;font-weight:800;box-shadow:0 4px 20px rgba(0,0,0,0.2);">
            Crear cuenta y suscribirse
        </a>
        <p style="font-size:12.5px;color:#94A3B8;margin-top:14px;">Cancelá cuando quieras · Sin compromisos</p>
        <?php endif; ?>
    </div>
</section>

<!-- QUÉ INCLUYE -->
<section style="padding:64px 24px;background:white;">
    <div style="max-width:900px;margin:0 auto;">
        <h2 style="font-family:'Playfair Display',serif;font-size:28px;font-weight:700;text-align:center;margin-bottom:8px;">¿Qué incluye la suscripción?</h2>
        <p style="text-align:center;color:var(--gray-500);font-size:15px;margin-bottom:48px;">Todo lo que necesitás para leer sin límites.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:24px;">
            <?php
            $features = [
                ['📚','Catálogo completo','Accedé a todos los ebooks disponibles en la plataforma, sin restricciones por título.'],
                ['📱','Todos los dispositivos','Leé desde tu computadora, tablet o celular con cualquier navegador web.'],
                ['🔖','Historial de lectura','Guardá tu progreso y retomá exactamente donde lo dejaste.'],
                ['❤️','Lista de favoritos','Marcá los libros que más te gustan para encontrarlos fácilmente.'],
                ['🆕','Novedades incluidas','Cada libro nuevo que agreguemos está disponible automáticamente.'],
                ['🚫','Sin cargos extra','Un precio fijo mensual. Nunca te cobramos por un libro específico.'],
            ];
            foreach ($features as $f): ?>
            <div style="border:1.5px solid var(--gray-300);border-radius:10px;padding:24px;transition:all .18s;"
                 onmouseover="this.style.borderColor='var(--blue-dark)';this.style.background='var(--blue-light)'"
                 onmouseout="this.style.borderColor='var(--gray-300)';this.style.background='white'">
                <div style="font-size:28px;margin-bottom:12px;"><?= $f[0] ?></div>
                <div style="font-size:15px;font-weight:700;color:var(--gray-900);margin-bottom:6px;"><?= $f[1] ?></div>
                <div style="font-size:13.5px;color:var(--gray-500);line-height:1.6;"><?= $f[2] ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- PRECIO -->
<section style="padding:48px 24px;background:var(--gray-100);">
    <div style="max-width:560px;margin:0 auto;text-align:center;">
        <h2 style="font-family:'Playfair Display',serif;font-size:24px;font-weight:700;margin-bottom:32px;">Simple y transparente</h2>
        <div style="background:white;border-radius:12px;overflow:hidden;box-shadow:var(--shadow-md);border:2px solid var(--blue-dark);">
            <div style="background:var(--blue-dark);padding:24px 32px;text-align:center;">
                <div style="font-size:13px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:#93C5FD;margin-bottom:8px;">Suscripción bookvon</div>
                <div style="font-size:56px;font-weight:800;color:white;line-height:1;">$1,99</div>
                <div style="font-size:14px;color:#CBD5E1;margin-top:4px;">por mes · se renueva automáticamente</div>
            </div>
            <div style="padding:28px 32px;">
                <?php
                $items = [
                    'Acceso a todo el catálogo',
                    'Lectura ilimitada',
                    'Historial y favoritos',
                    'Novedades automáticas',
                    'Cancelación en cualquier momento',
                    'Sin publicidad',
                    'Pago seguro con Paddle',
                ];
                foreach ($items as $item): ?>
                <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--gray-100);font-size:14.5px;color:var(--gray-700);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="var(--blue-accent)" stroke-width="2.5" width="18" style="flex-shrink:0"><polyline points="20 6 9 17 4 12"/></svg>
                    <?= $item ?>
                </div>
                <?php endforeach; ?>
                <div style="margin-top:24px;">
                    <?php if ($esSuscriptor): ?>
                    <div style="text-align:center;padding:14px;background:var(--blue-light);border-radius:6px;font-size:14px;color:var(--blue-dark);font-weight:700;">✅ Ya sos suscriptor</div>
                    <?php elseif (isLoggedIn()): ?>
                    <button onclick="abrirCheckoutPaddle()" style="width:100%;padding:14px;background:var(--blue-dark);color:white;border:none;border-radius:6px;font-size:15px;font-weight:700;cursor:pointer;transition:all .18s;"
                        onmouseover="this.style.background='var(--blue-mid)'"
                        onmouseout="this.style.background='var(--blue-dark)'">
                        Suscribirme ahora — $1,99/mes
                    </button>
                    <?php else: ?>
                    <a href="<?= APP_URL ?>/pages/registro.php" style="display:block;text-align:center;padding:14px;background:var(--blue-dark);color:white;border-radius:6px;font-size:15px;font-weight:700;">
                        Crear cuenta y suscribirse
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <p style="margin-top:16px;font-size:12px;color:var(--gray-500);">
            🔒 Pago procesado por <strong>Paddle</strong> — nunca almacenamos datos de tarjeta.
        </p>
    </div>
</section>

<!-- FAQ -->
<section id="faq" style="padding:64px 24px;background:white;">
    <div style="max-width:680px;margin:0 auto;">
        <h2 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:700;text-align:center;margin-bottom:36px;">Preguntas frecuentes</h2>
        <?php $faqs = [
            ['¿Puedo cancelar en cualquier momento?','Sí, podés cancelar tu suscripción cuando quieras desde tu perfil, sin penalidades ni cargos adicionales. Seguís teniendo acceso hasta el final del período pago.'],
            ['¿Se renueva automáticamente?','Sí, la suscripción se renueva cada mes por $1,99 USD. Podés cancelar antes del próximo vencimiento si no querés continuar.'],
            ['¿Los libros nuevos se incluyen?','Absolutamente. Todos los ebooks que agreguemos a la plataforma quedan disponibles de inmediato para todos los suscriptores activos.'],
            ['¿Puedo leer desde el celular?','Sí, la plataforma funciona en cualquier dispositivo con navegador web: computadora, tablet y celular.'],
            ['¿Cómo se procesa el pago?','Usamos Paddle, un procesador de pagos seguro y certificado. Nunca almacenamos datos de tu tarjeta en nuestros servidores.'],
        ];
        foreach ($faqs as $faq): ?>
        <div style="border-bottom:1px solid var(--gray-200);padding:20px 0;">
            <div style="font-size:15.5px;font-weight:700;color:var(--gray-900);margin-bottom:8px;"><?= $faq[0] ?></div>
            <p style="font-size:14.5px;color:var(--gray-500);line-height:1.75;"><?= $faq[1] ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Paddle JS (Sandbox) -->
<script src="https://cdn.paddle.com/paddle/v2/paddle.js"></script>
<script>
  Paddle.Environment.set("sandbox");
  Paddle.Initialize({
    token: "<?= PADDLE_CLIENT_TOKEN ?>",
    eventCallback: function(event) {
      if (event.name === "checkout.completed") {
        window.location.href = "<?= APP_URL ?>/pages/success_premium.php";
      }
    }
  });

  function abrirCheckoutPaddle() {
    <?php if (isLoggedIn()): ?>
    var email = <?= json_encode($currentUser['email'] ?? '') ?>;
    Paddle.Checkout.open({
      items: [{ priceId: "<?= PADDLE_PRICE_ID ?>", quantity: 1 }],
      customer: email ? { email: email } : undefined,
      successUrl: "<?= APP_URL ?>/pages/success_premium.php"
    });
    <?php else: ?>
    window.location.href = "<?= APP_URL ?>/pages/registro.php";
    <?php endif; ?>
  }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
