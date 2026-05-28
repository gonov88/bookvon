<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(APP_URL . '/pages/login.php'); }

$pageTitle = 'Mi Perfil';
$db        = getDB();
$userId    = $_SESSION['usuario_id'];

// Datos del usuario
$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Suscripción activa (tabla Paddle)
$subStmt = $db->prepare(
    "SELECT s.*, 'bookvon Premium' AS plan_nombre, 1.99 AS precio_mensual, NULL AS precio_anual, 'Acceso completo al catálogo' AS plan_desc
     FROM paddle_suscripciones s
     WHERE s.email = (SELECT email FROM usuarios WHERE id = ?) AND s.status = 'active'
     ORDER BY s.creado_en DESC LIMIT 1"
);
$subStmt->execute([$userId]);
$suscripcion = $subStmt->fetch();

// Historial de suscripciones (tabla Paddle)
$histSub = $db->prepare(
    "SELECT s.*, 'bookvon Premium' AS plan_nombre
     FROM paddle_suscripciones s
     WHERE s.email = (SELECT email FROM usuarios WHERE id = ?)
     ORDER BY s.creado_en DESC LIMIT 5"
);
$histSub->execute([$userId]);
$historialSubs = $histSub->fetchAll();

// Estadísticas
$stats = [];

// Libros leídos (completados)
$s = $db->prepare("SELECT COUNT(*) FROM historial_lectura WHERE usuario_id = ? AND completado = 1");
$s->execute([$userId]); $stats['leidos'] = (int)$s->fetchColumn();

// Libros en progreso
$s = $db->prepare("SELECT COUNT(*) FROM historial_lectura WHERE usuario_id = ? AND completado = 0");
$s->execute([$userId]); $stats['en_progreso'] = (int)$s->fetchColumn();

// Favoritos
$s = $db->prepare("SELECT COUNT(*) FROM favoritos WHERE usuario_id = ?");
$s->execute([$userId]); $stats['favoritos'] = (int)$s->fetchColumn();

// Reseñas escritas
$s = $db->prepare("SELECT COUNT(*) FROM resenias WHERE usuario_id = ?");
$s->execute([$userId]); $stats['resenias'] = (int)$s->fetchColumn();

// Último libro leído
$ultimoStmt = $db->prepare(
    "SELECT l.titulo, l.slug, l.portada, c.nombre AS categoria_nombre,
            h.pagina_actual, h.completado, h.ultima_lectura, l.paginas
     FROM historial_lectura h
     JOIN libros l ON h.libro_id = l.id
     JOIN categorias c ON l.categoria_id = c.id
     WHERE h.usuario_id = ?
     ORDER BY h.ultima_lectura DESC LIMIT 1"
);
$ultimoStmt->execute([$userId]);
$ultimoLibro = $ultimoStmt->fetch();

// Días restantes — Paddle renueva mensualmente
$diasRestantes = 30;

$catImages = [
    'Negocios y Finanzas'                  => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=200&q=80',
    'Desarrollo Personal'                  => 'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?w=200&q=80',
    'Tecnología e Inteligencia Artificial' => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=200&q=80',
    'Programación y Desarrollo Web'        => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=200&q=80',
    'Educación y Académico'                => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=200&q=80',
    'Salud y Bienestar'                    => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=200&q=80',
    'Cocina y Gastronomía'                 => 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=200&q=80',
    'Ciencia Ficción y Fantasía'           => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?w=200&q=80',
    'Romance y Drama'                      => 'https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=200&q=80',
    'Historia y Política'                  => 'https://images.unsplash.com/photo-1447069387593-a5de0862481e?w=200&q=80',
    'Arte, Diseño y Creatividad'           => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=200&q=80',
    'Infantil y Juvenil'                   => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=200&q=80',
];

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb"><a href="<?= APP_URL ?>">Inicio</a> / <span>Mi Perfil</span></div>
        <h1>Mi Perfil</h1>
    </div>
</div>

<section class="section">
    <div class="section-inner">
        <div class="perfil-layout">

            <!-- ── COLUMNA IZQUIERDA ── -->
            <aside class="perfil-aside">

                <!-- Tarjeta de usuario -->
                <div class="perfil-card">
                    <div class="perfil-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?= sanitize($user['avatar']) ?>" alt="Avatar" class="perfil-avatar-img">
                        <?php else: ?>
                            <span><?= strtoupper(substr($user['nombre'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="perfil-nombre"><?= sanitize($user['nombre']) ?></div>
                    <div class="perfil-email"><?= sanitize($user['email']) ?></div>
                    <div class="perfil-rol-badge <?= $user['rol'] === 'admin' ? 'admin' : ($suscripcion ? 'suscriptor' : 'visitante') ?>">
                        <?php if ($user['rol'] === 'admin'): ?>
                            👑 Administrador
                        <?php elseif ($suscripcion): ?>
                            ⭐ Suscriptor activo
                        <?php else: ?>
                            👤 Visitante
                        <?php endif; ?>
                    </div>
                    <div class="perfil-desde">
                        Miembro desde <?= date('d/m/Y', strtotime($user['creado_en'])) ?>
                    </div>
                </div>

                <!-- Accesos rápidos -->
                <div class="perfil-card perfil-links">
                    <div class="perfil-card-title">Accesos rápidos</div>
                    <a href="<?= APP_URL ?>/pages/mi-biblioteca.php" class="perfil-link-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Mi Biblioteca
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" style="margin-left:auto;opacity:.4;"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                    <a href="<?= APP_URL ?>/pages/favoritos.php" class="perfil-link-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        Mis Favoritos
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" style="margin-left:auto;opacity:.4;"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                    <a href="<?= APP_URL ?>/pages/catalogo.php" class="perfil-link-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Catálogo
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" style="margin-left:auto;opacity:.4;"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                    <?php if ($user['rol'] === 'admin'): ?>
                    <a href="<?= APP_URL ?>/admin/index.php" class="perfil-link-item" style="color:var(--blue-accent);">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        Panel Admin
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" style="margin-left:auto;opacity:.4;"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                    <?php endif; ?>
                    <a href="<?= APP_URL ?>/pages/logout.php" class="perfil-link-item" style="color:#EF4444;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Cerrar sesión
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" style="margin-left:auto;opacity:.4;"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </div>

            </aside>

            <!-- ── COLUMNA PRINCIPAL ── -->
            <main class="perfil-main">

                <!-- Estadísticas -->
                <div class="perfil-stats-grid">
                    <div class="perfil-stat-card">
                        <div class="perfil-stat-icon" style="background:#DBEAFE;color:#1D4ED8;">📖</div>
                        <div class="perfil-stat-num"><?= $stats['leidos'] ?></div>
                        <div class="perfil-stat-label">Libros leídos</div>
                    </div>
                    <div class="perfil-stat-card">
                        <div class="perfil-stat-icon" style="background:#FEF9C3;color:#B45309;">📑</div>
                        <div class="perfil-stat-num"><?= $stats['en_progreso'] ?></div>
                        <div class="perfil-stat-label">En progreso</div>
                    </div>
                    <div class="perfil-stat-card">
                        <div class="perfil-stat-icon" style="background:#FCE7F3;color:#BE185D;">❤️</div>
                        <div class="perfil-stat-num"><?= $stats['favoritos'] ?></div>
                        <div class="perfil-stat-label">Favoritos</div>
                    </div>
                    <div class="perfil-stat-card">
                        <div class="perfil-stat-icon" style="background:#DCFCE7;color:#15803D;">⭐</div>
                        <div class="perfil-stat-num"><?= $stats['resenias'] ?></div>
                        <div class="perfil-stat-label">Reseñas escritas</div>
                    </div>
                </div>

                <!-- Estado de suscripción -->
                <div class="perfil-section">
                    <div class="perfil-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg>
                        Estado de suscripción
                    </div>

                    <?php if ($suscripcion): ?>
                    <div class="sub-status-card activa">
                        <div class="sub-status-header">
                            <div>
                                <div class="sub-status-badge">✓ Activa</div>
                                <div class="sub-plan-nombre"><?= sanitize($suscripcion['plan_nombre']) ?></div>
                                <div class="sub-plan-desc"><?= sanitize($suscripcion['plan_desc'] ?? '') ?></div>
                            </div>
                            <div class="sub-dias-wrap">
                                <div class="sub-dias-num"><?= $diasRestantes ?></div>
                                <div class="sub-dias-label">días restantes</div>
                            </div>
                        </div>
                        <div class="sub-barra-wrap">
                            <?php
                                $pctSub   = 100;
                                $barColor = '#22C55E';
                            ?>
                            <div class="sub-barra-bg">
                                <div class="sub-barra-fill" style="width:<?= $pctSub ?>%;background:<?= $barColor ?>;"></div>
                            </div>
                            <div class="sub-barra-labels">
                                <span>Activa desde: <?= date('d/m/Y', strtotime($suscripcion['creado_en'])) ?></span>
                                <span>Renovación: mensual automática</span>
                            </div>
                        </div>
                        <div class="sub-meta-row">
                            <span class="sub-chip">
                                📅 Plan mensual
                            </span>
                            <span class="sub-chip">
                                💰 $1,99/mes
                            </span>
                        </div>
                        <?php if ($diasRestantes <= 7): ?>
                        <div class="sub-alert-renovar">
                            ⚠️ Tu suscripción vence pronto. <a href="<?= APP_URL ?>/pages/suscripcion.php">Renovar ahora →</a>
                        </div>
                        <?php endif; ?>
                        <div style="margin-top:12px;">
                            <a href="<?= APP_URL ?>/pages/cancelar_suscripcion.php" style="font-size:13px;color:#ef4444;text-decoration:underline;">
                                Cancelar suscripción
                            </a>
                        </div>
                    </div>

                    <?php else: ?>
                    <div class="sub-status-card inactiva">
                        <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                            <div style="font-size:40px;">📚</div>
                            <div>
                                <div class="sub-status-badge inactiva">Sin suscripción activa</div>
                                <p style="font-size:14px;color:var(--gray-600);margin-top:6px;max-width:420px;line-height:1.6;">
                                    Suscribite para acceder a todo el catálogo de forma ilimitada.
                                </p>
                                <a href="<?= APP_URL ?>/pages/suscripcion.php" class="btn-primary" style="display:inline-flex;margin-top:12px;font-size:13.5px;padding:10px 20px;">
                                    Ver planes y precios
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Último libro leído -->
                <?php if ($ultimoLibro): ?>
                <div class="perfil-section">
                    <div class="perfil-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        Seguí leyendo
                    </div>
                    <?php
                        $imgU = !empty($ultimoLibro['portada'])
                            ? (str_starts_with($ultimoLibro['portada'], 'http') ? $ultimoLibro['portada'] : APP_URL . '/' . ltrim($ultimoLibro['portada'], '/'))
                            : ($catImages[$ultimoLibro['categoria_nombre']] ?? '');
                        $pctU = ($ultimoLibro['paginas'] > 0)
                            ? min(100, round($ultimoLibro['pagina_actual'] / $ultimoLibro['paginas'] * 100))
                            : 0;
                    ?>
                    <div class="ultimo-libro-card">
                        <?php if ($imgU): ?>
                        <img src="<?= $imgU ?>" alt="" class="ultimo-libro-cover">
                        <?php endif; ?>
                        <div class="ultimo-libro-info">
                            <div class="ultimo-libro-cat"><?= sanitize($ultimoLibro['categoria_nombre']) ?></div>
                            <div class="ultimo-libro-titulo"><?= sanitize($ultimoLibro['titulo']) ?></div>
                            <?php if ($ultimoLibro['paginas'] > 0): ?>
                            <div class="ultimo-libro-prog">
                                <div class="ultimo-prog-barra-bg">
                                    <div class="ultimo-prog-barra-fill" style="width:<?= $pctU ?>%"></div>
                                </div>
                                <span><?= $pctU ?>% completado — Pág. <?= $ultimoLibro['pagina_actual'] ?> / <?= $ultimoLibro['paginas'] ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="ultimo-libro-fecha">
                                Última lectura: <?= date('d/m/Y H:i', strtotime($ultimoLibro['ultima_lectura'])) ?>
                            </div>
                            <a href="<?= APP_URL ?>/pages/leer.php?id=<?php
                                $idStmt = $db->prepare("SELECT id FROM libros WHERE slug=?");
                                $idStmt->execute([$ultimoLibro['slug']]);
                                echo $idStmt->fetchColumn();
                            ?>" class="btn-primary" style="display:inline-flex;margin-top:14px;font-size:13.5px;padding:10px 20px;">
                                <?= $ultimoLibro['completado'] ? '📖 Releer' : '▶ Continuar leyendo' ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Historial de suscripciones -->
                <?php if (!empty($historialSubs)): ?>
                <div class="perfil-section">
                    <div class="perfil-section-title">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="10"/></svg>
                        Historial de suscripciones
                    </div>
                    <div class="perfil-hist-table">
                        <div class="perfil-hist-header">
                            <span>Plan</span><span>Tipo</span><span>Inicio</span><span>Vencimiento</span><span>Estado</span>
                        </div>
                        <?php foreach ($historialSubs as $hs): ?>
                        <div class="perfil-hist-row">
                            <span><?= sanitize($hs['plan_nombre']) ?></span>
                            <span>Mensual</span>
                            <span><?= date('d/m/Y', strtotime($hs['creado_en'])) ?></span>
                            <span>Renovación automática</span>
                            <span>
                                <span class="hist-estado <?= $hs['status'] ?>">
                                    <?php
                                        $estadoMap = ['activa'=>'✓ Activa','vencida'=>'✗ Vencida','cancelada'=>'— Cancelada'];
                                        echo isset($estadoMap[$hs['status']]) ? $estadoMap[$hs['status']] : $hs['status'];
                                    ?>
                                </span>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
</section>

<style>
/* ── PERFIL LAYOUT ── */
.perfil-layout {
    display: grid;
    grid-template-columns: 260px 1fr;
    gap: 28px;
    align-items: start;
}

/* ── ASIDE ── */
.perfil-aside { display: flex; flex-direction: column; gap: 20px; position: sticky; top: calc(var(--nav-h) + 20px); }

.perfil-card {
    background: var(--white);
    border: 1.5px solid var(--gray-200);
    border-radius: 14px;
    padding: 24px 20px;
    text-align: center;
}
.perfil-avatar {
    width: 80px; height: 80px; border-radius: 50%;
    background: var(--blue-dark); color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 32px; font-weight: 700;
    margin: 0 auto 14px; overflow: hidden;
}
.perfil-avatar-img { width: 100%; height: 100%; object-fit: cover; }
.perfil-nombre { font-size: 17px; font-weight: 700; color: var(--gray-900); margin-bottom: 4px; }
.perfil-email  { font-size: 13px; color: var(--gray-500); margin-bottom: 12px; word-break: break-all; }
.perfil-rol-badge {
    display: inline-block; padding: 4px 14px;
    border-radius: 20px; font-size: 12px; font-weight: 700;
    margin-bottom: 10px;
}
.perfil-rol-badge.admin      { background: #FEF3C7; color: #B45309; }
.perfil-rol-badge.suscriptor { background: #DCFCE7; color: #15803D; }
.perfil-rol-badge.visitante  { background: var(--gray-100); color: var(--gray-500); }
.perfil-desde { font-size: 12px; color: var(--gray-400); }

.perfil-links { text-align: left; padding: 16px 0; }
.perfil-card-title { font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--gray-400); padding: 0 16px; margin-bottom: 8px; }
.perfil-link-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; font-size: 13.5px; color: var(--gray-700);
    border-radius: 8px; transition: background .15s;
    text-decoration: none;
}
.perfil-link-item:hover { background: var(--gray-100); color: var(--blue-dark); }

/* ── MAIN ── */
.perfil-main { display: flex; flex-direction: column; gap: 24px; }

/* Stats */
.perfil-stats-grid {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;
}
.perfil-stat-card {
    background: var(--white); border: 1.5px solid var(--gray-200);
    border-radius: 12px; padding: 18px 14px; text-align: center;
    transition: box-shadow .18s, transform .18s;
}
.perfil-stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); transform: translateY(-2px); }
.perfil-stat-icon { width: 40px; height: 40px; border-radius: 10px; font-size: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px; }
.perfil-stat-num   { font-size: 28px; font-weight: 800; color: var(--gray-900); line-height: 1; }
.perfil-stat-label { font-size: 12px; color: var(--gray-500); margin-top: 4px; }

/* Sección */
.perfil-section {
    background: var(--white); border: 1.5px solid var(--gray-200);
    border-radius: 14px; padding: 22px 24px;
}
.perfil-section-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 15px; font-weight: 700; color: var(--gray-900);
    margin-bottom: 18px; padding-bottom: 14px;
    border-bottom: 1.5px solid var(--gray-100);
}

/* Suscripción activa */
.sub-status-card {
    border-radius: 10px; padding: 20px;
}
.sub-status-card.activa   { background: #F0FDF4; border: 1.5px solid #BBF7D0; }
.sub-status-card.inactiva { background: var(--gray-100); border: 1.5px solid var(--gray-200); }

.sub-status-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.sub-status-badge {
    display: inline-block; padding: 3px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 700; margin-bottom: 6px;
    background: #22C55E; color: white;
}
.sub-status-badge.inactiva { background: var(--gray-300); color: var(--gray-700); }
.sub-plan-nombre { font-size: 20px; font-weight: 800; color: var(--gray-900); }
.sub-plan-desc   { font-size: 13px; color: var(--gray-500); margin-top: 3px; }

.sub-dias-wrap { text-align: center; flex-shrink: 0; }
.sub-dias-num   { font-size: 36px; font-weight: 800; color: var(--gray-900); line-height: 1; }
.sub-dias-label { font-size: 11px; color: var(--gray-500); text-transform: uppercase; letter-spacing: .5px; }

.sub-barra-bg   { height: 8px; background: rgba(0,0,0,.08); border-radius: 4px; overflow: hidden; }
.sub-barra-fill { height: 100%; border-radius: 4px; transition: width .6s ease; }
.sub-barra-labels { display: flex; justify-content: space-between; font-size: 11.5px; color: var(--gray-500); margin-top: 6px; }

.sub-meta-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
.sub-chip {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 20px;
    background: white; border: 1px solid var(--gray-200);
    font-size: 12.5px; color: var(--gray-700);
}
.sub-alert-renovar {
    margin-top: 14px; padding: 10px 14px;
    background: #FEF3C7; border: 1px solid #FCD34D;
    border-radius: 8px; font-size: 13px; color: #92400E;
}
.sub-alert-renovar a { color: #B45309; font-weight: 700; }

/* Último libro */
.ultimo-libro-card {
    display: flex; gap: 18px; align-items: flex-start;
}
.ultimo-libro-cover {
    width: 72px; height: 100px; object-fit: cover;
    border-radius: 5px; flex-shrink: 0;
    box-shadow: 0 4px 14px rgba(0,0,0,.15);
}
.ultimo-libro-cat   { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--blue-accent); margin-bottom: 4px; }
.ultimo-libro-titulo { font-size: 16px; font-weight: 700; color: var(--gray-900); line-height: 1.4; margin-bottom: 10px; }
.ultimo-libro-prog  { display: flex; flex-direction: column; gap: 5px; margin-bottom: 8px; }
.ultimo-prog-barra-bg   { height: 6px; background: var(--gray-200); border-radius: 3px; overflow: hidden; }
.ultimo-prog-barra-fill { height: 100%; background: var(--blue-accent); border-radius: 3px; }
.ultimo-libro-prog span { font-size: 12px; color: var(--gray-500); }
.ultimo-libro-fecha { font-size: 12px; color: var(--gray-400); }

/* Historial tabla */
.perfil-hist-table { display: flex; flex-direction: column; gap: 0; border: 1px solid var(--gray-200); border-radius: 8px; overflow: hidden; }
.perfil-hist-header {
    display: grid; grid-template-columns: 2fr 1fr 1.2fr 1.2fr 1.2fr;
    gap: 8px; padding: 10px 16px;
    background: var(--gray-100);
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: var(--gray-500);
}
.perfil-hist-row {
    display: grid; grid-template-columns: 2fr 1fr 1.2fr 1.2fr 1.2fr;
    gap: 8px; padding: 12px 16px;
    font-size: 13.5px; color: var(--gray-700);
    border-top: 1px solid var(--gray-100);
    align-items: center;
}
.hist-estado {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 700;
}
.hist-estado.activa    { background: #DCFCE7; color: #15803D; }
.hist-estado.vencida   { background: #FEE2E2; color: #B91C1C; }
.hist-estado.cancelada { background: var(--gray-100); color: var(--gray-500); }

/* Responsive */
@media (max-width: 1024px) {
    .perfil-stats-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 860px) {
    .perfil-layout { grid-template-columns: 1fr; }
    .perfil-aside  { position: static; }
    .perfil-hist-header, .perfil-hist-row { grid-template-columns: 2fr 1fr 1fr 1fr; }
    .perfil-hist-header span:nth-child(3),
    .perfil-hist-row   span:nth-child(3)  { display: none; }
}
@media (max-width: 520px) {
    .perfil-stats-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
    .perfil-hist-header, .perfil-hist-row { grid-template-columns: 2fr 1fr 1fr; }
    .perfil-hist-header span:nth-child(4),
    .perfil-hist-row   span:nth-child(4)  { display: none; }
}
</style>

<?php include __DIR__ . '/../includes/footer.php'; ?>
