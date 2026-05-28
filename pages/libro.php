<?php
require_once __DIR__ . '/../includes/config.php';
$db = getDB();

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { redirect(APP_URL . '/pages/catalogo.php'); }

$stmt = $db->prepare("SELECT l.*, c.nombre AS categoria_nombre, 
                             a.nombre AS autor_nombre, a.bio AS autor_bio
                      FROM libros l
                      JOIN categorias c ON l.categoria_id=c.id
                      
                      LEFT JOIN autores a ON l.autor_id=a.id
                      WHERE l.slug=? AND l.activo=1");
$stmt->execute([$slug]);
$libro = $stmt->fetch();
if (!$libro) { redirect(APP_URL . '/pages/catalogo.php'); }

$db->prepare("UPDATE historial_lectura SET ultima_lectura=NOW() WHERE libro_id=? LIMIT 1")->execute([$libro['id']]);

$isFav = false;
if (isLoggedIn()) {
    $f = $db->prepare("SELECT id FROM favoritos WHERE usuario_id=? AND libro_id=?");
    $f->execute([$_SESSION['usuario_id'], $libro['id']]);
    $isFav = (bool)$f->fetch();
}

$progreso = null;
if (isLoggedIn()) {
    $p = $db->prepare("SELECT * FROM historial_lectura WHERE usuario_id=? AND libro_id=?");
    $p->execute([$_SESSION['usuario_id'], $libro['id']]);
    $progreso = $p->fetch();
}

$rel = $db->prepare("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre
                     FROM libros l JOIN categorias c ON l.categoria_id=c.id LEFT JOIN autores a ON l.autor_id=a.id
                     WHERE l.categoria_id=? AND l.id!=? AND l.activo=1 ORDER BY l.creado_en DESC LIMIT 4");
$rel->execute([$libro['categoria_id'], $libro['id']]);
$relacionados = $rel->fetchAll();

$reseStmt = $db->prepare("SELECT r.*, u.nombre AS usuario_nombre FROM resenias r JOIN usuarios u ON r.usuario_id=u.id WHERE r.libro_id=? ORDER BY r.creado_en DESC LIMIT 20");
$reseStmt->execute([$libro['id']]);
$resenias = $reseStmt->fetchAll();

// Promedio de puntuación
$avgPunt = 0;
if (!empty($resenias)) {
    $avgPunt = round(array_sum(array_column($resenias, 'puntuacion')) / count($resenias), 1);
}

// ¿El usuario ya dejó una reseña?
$miResenia = null;
if (isLoggedIn()) {
    $myR = $db->prepare("SELECT * FROM resenias WHERE usuario_id=? AND libro_id=?");
    $myR->execute([$_SESSION['usuario_id'], $libro['id']]);
    $miResenia = $myR->fetch();
}

$_CI = [
    'Negocios y Finanzas'                  => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=800&q=85',
    'Desarrollo Personal'                  => 'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?w=800&q=85',
    'Tecnología e Inteligencia Artificial' => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=800&q=85',
    'Programación y Desarrollo Web'        => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=800&q=85',
    'Educación y Académico'                => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=800&q=85',
    'Salud y Bienestar'                    => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800&q=85',
    'Cocina y Gastronomía'                 => 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=800&q=85',
    'Ciencia Ficción y Fantasía'           => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?w=800&q=85',
    'Romance y Drama'                      => 'https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=800&q=85',
    'Historia y Política'                  => 'https://images.unsplash.com/photo-1447069387593-a5de0862481e?w=800&q=85',
    'Arte, Diseño y Creatividad'           => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=800&q=85',
    'Infantil y Juvenil'                   => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=800&q=85',
];
function buildImgUrl(string $portada): string {
    if (empty($portada)) return '';
    if (str_starts_with($portada, 'http://') || str_starts_with($portada, 'https://')) return $portada;
    return APP_URL . '/' . ltrim($portada, '/');
}

$coverImg = buildImgUrl(
    !empty($libro['portada'])
        ? $libro['portada']
        : ($_CI[$libro['categoria_nombre']] ?? 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=800&q=85')
);

$pct = ($libro['paginas'] && $progreso)
    ? min(100, round($progreso['pagina_actual'] / $libro['paginas'] * 100))
    : 0;

$pageTitle = $libro['titulo'];
$pageDesc  = substr($libro['descripcion'] ?? '', 0, 160);

include __DIR__ . '/../includes/header.php';
?>

<!-- ── HERO DEL LIBRO ── -->
<div class="libro-hero">
    <div class="libro-hero-bg" style="background-image:url('<?= $coverImg ?>')"></div>
    <div class="libro-hero-overlay"></div>
    <div class="libro-hero-inner">
        <!-- Portada -->
        <div class="libro-hero-cover">
            <img src="<?= $coverImg ?>" alt="<?= sanitize($libro['titulo']) ?>">
            <?php if ($libro['destacado']): ?>
            <span class="libro-hero-badge">⭐ Destacado</span>
            <?php endif; ?>
        </div>
        <!-- Info rápida -->
        <div class="libro-hero-info">
            <div class="libro-hero-breadcrumb">
                <a href="<?= APP_URL ?>">Inicio</a>
                <span>/</span>
                <a href="<?= APP_URL ?>/pages/catalogo.php">Catálogo</a>
                <span>/</span>
                <a href="<?= APP_URL ?>/pages/catalogo.php?categoria=<?= $libro['categoria_id'] ?>"><?= sanitize($libro['categoria_nombre']) ?></a>
            </div>
            <h1 class="libro-hero-title"><?= sanitize($libro['titulo']) ?></h1>
            <?php if ($libro['autor_nombre']): ?>
            <p class="libro-hero-author">por <strong><?= sanitize($libro['autor_nombre']) ?></strong></p>
            <?php endif; ?>
            <div class="libro-hero-rating">
                <span style="color:#FBBF24;font-size:18px;letter-spacing:1px;">★★★★☆</span>
                <span style="font-size:13px;color:#CBD5E1;margin-left:6px;"><?= number_format($libro['total_lecturas'] ?? 0) ?> lecturas</span>
            </div>
            <!-- Meta chips -->
            <div class="libro-hero-chips">
                <?php if ($libro['paginas']): ?>
                <span class="lh-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg><?= $libro['paginas'] ?> pág.</span>
                <?php endif; ?>
                <span class="lh-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg><?= sanitize($libro['idioma'] ?? 'Español') ?></span>
                <?php if ($libro['fecha_publicacion']): ?>
                <span class="lh-chip"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><?= date('Y', strtotime($libro['fecha_publicacion'])) ?></span>
                <?php endif; ?>
                <span class="lh-chip">📄 PDF</span>
            </div>
            <!-- CTA -->
            <div class="libro-hero-cta">
                <?php if (hasActiveSubscription()): ?>
                    <a href="<?= APP_URL ?>/pages/leer.php?id=<?= $libro['id'] ?>" class="lh-btn-read">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                        <?= $progreso ? 'Continuar leyendo' : 'Comenzar a leer' ?>
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <button class="lh-btn-fav fav-btn <?= $isFav ? 'active' : '' ?>" data-id="<?= $libro['id'] ?>">
                        <svg viewBox="0 0 24 24" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" width="18"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        <?= $isFav ? 'Guardado' : 'Guardar' ?>
                    </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/pages/suscripcion.php" class="lh-btn-read">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Suscribirme para leer — $1/mes
                    </a>
                    <?php if (!isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/pages/login.php" class="lh-btn-fav">Iniciar sesión</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- Progreso -->
            <?php if ($progreso && $libro['paginas']): ?>
            <div class="libro-hero-progress">
                <div style="display:flex;justify-content:space-between;font-size:12px;color:#CBD5E1;margin-bottom:6px;">
                    <span>Progreso de lectura</span>
                    <span>Pág. <?= $progreso['pagina_actual'] ?> / <?= $libro['paginas'] ?> (<?= $pct ?>%)</span>
                </div>
                <div style="background:rgba(255,255,255,0.15);border-radius:4px;height:5px;">
                    <div style="background:#60A5FA;height:100%;border-radius:4px;width:<?= $pct ?>%;transition:width .4s ease;"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ── CUERPO DEL LIBRO ── -->
<div class="libro-body">

    <!-- COLUMNA PRINCIPAL -->
    <div class="libro-main">

        <!-- Descripción -->
        <?php if ($libro['descripcion']): ?>
        <div class="libro-section">
            <h2 class="libro-section-title">Descripción</h2>
            <div class="libro-desc"><?= nl2br(sanitize($libro['descripcion'])) ?></div>
        </div>
        <?php endif; ?>

        <!-- Sobre el autor -->
        <?php if ($libro['autor_nombre'] && $libro['autor_bio']): ?>
        <div class="libro-section">
            <h2 class="libro-section-title">Sobre el autor</h2>
            <div class="libro-autor-box">
                <div class="libro-autor-avatar"><?= strtoupper(substr($libro['autor_nombre'], 0, 1)) ?></div>
                <div>
                    <div style="font-weight:700;font-size:15px;margin-bottom:5px;"><?= sanitize($libro['autor_nombre']) ?></div>
                    <p style="font-size:14px;color:var(--gray-500);line-height:1.7;"><?= sanitize($libro['autor_bio']) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reseñas -->
        <div class="libro-section" id="seccion-resenias">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
                <h2 class="libro-section-title" style="margin:0;">
                    Reseñas
                    <?php if (!empty($resenias)): ?>
                    <span style="font-size:14px;font-weight:400;color:var(--gray-500);margin-left:6px;">(<?= count($resenias) ?>)</span>
                    <?php endif; ?>
                </h2>
                <?php if (!empty($resenias)): ?>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="color:#FBBF24;font-size:20px;letter-spacing:2px;">
                        <?= str_repeat('★', round($avgPunt)) ?><?= str_repeat('☆', 5 - round($avgPunt)) ?>
                    </span>
                    <span style="font-size:22px;font-weight:700;color:var(--gray-900);"><?= $avgPunt ?></span>
                    <span style="font-size:13px;color:var(--gray-500);">/ 5</span>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($resenias)): ?>
            <div style="text-align:center;padding:32px 0;color:var(--gray-400);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;margin:0 auto 10px;display:block;opacity:.4;"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Todavía no hay reseñas. ¡Sé el primero!
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:14px;margin-bottom:28px;" id="lista-resenias">
                <?php foreach ($resenias as $r): ?>
                <div class="resenia-card">
                    <div class="resenia-header">
                        <div class="resenia-avatar"><?= strtoupper(substr($r['usuario_nombre'], 0, 1)) ?></div>
                        <div>
                            <div style="font-weight:600;font-size:13.5px;color:var(--gray-900);"><?= sanitize($r['usuario_nombre']) ?></div>
                            <div style="color:#FBBF24;font-size:13px;line-height:1;"><?= str_repeat('★', $r['puntuacion']) ?><?= str_repeat('☆', 5 - $r['puntuacion']) ?></div>
                        </div>
                        <div style="margin-left:auto;font-size:12px;color:var(--gray-400);"><?= date('d/m/Y', strtotime($r['creado_en'])) ?></div>
                    </div>
                    <?php if ($r['comentario']): ?>
                    <p style="font-size:14px;color:var(--gray-700);line-height:1.7;margin-top:8px;"><?= sanitize($r['comentario']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Formulario de reseña -->
            <?php if (!isLoggedIn()): ?>
            <div class="resenia-cta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>¿Querés dejar una reseña? <a href="<?= APP_URL ?>/pages/login.php" style="color:var(--blue-accent);font-weight:600;">Iniciá sesión</a></span>
            </div>

            <?php elseif ($miResenia): ?>
            <div class="resenia-ya" id="resenia-ya">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <span style="font-size:13.5px;color:var(--gray-700);">Ya dejaste tu reseña:</span>
                    <span style="color:#FBBF24;"><?= str_repeat('★', $miResenia['puntuacion']) ?><?= str_repeat('☆', 5 - $miResenia['puntuacion']) ?></span>
                </div>
                <?php if ($miResenia['comentario']): ?>
                <p style="font-size:13.5px;color:var(--gray-600);margin-top:6px;font-style:italic;">"<?= sanitize($miResenia['comentario']) ?>"</p>
                <?php endif; ?>
                <button onclick="editarResenia()" style="margin-top:10px;font-size:12.5px;color:var(--blue-accent);background:none;border:none;cursor:pointer;padding:0;font-family:inherit;">✏️ Editar mi reseña</button>
            </div>

            <?php else: ?>
            <?php endif; ?>

            <!-- Formulario (siempre en el DOM si está logueado, oculto si ya tiene reseña) -->
            <?php if (isLoggedIn()): ?>
            <div class="resenia-form-wrap" id="resenia-form-wrap" <?= $miResenia ? 'style="display:none;"' : '' ?>>
                <h3 style="font-size:15px;font-weight:700;color:var(--gray-900);margin-bottom:14px;">
                    <?= $miResenia ? 'Editar tu reseña' : 'Dejá tu reseña' ?>
                </h3>
                <!-- Estrellas interactivas -->
                <div class="star-picker" id="star-picker">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" class="star-btn <?= ($miResenia && $miResenia['puntuacion'] >= $i) ? 'active' : '' ?>"
                            data-val="<?= $i ?>" onclick="setStars(<?= $i ?>)" onmouseover="hoverStars(<?= $i ?>)" onmouseout="resetStars()">★</button>
                    <?php endfor; ?>
                </div>
                <div id="star-label" style="font-size:12.5px;color:var(--gray-400);margin-bottom:12px;min-height:16px;">
                    <?= $miResenia ? ['','Malo','Regular','Bueno','Muy bueno','Excelente'][$miResenia['puntuacion']] : 'Seleccioná una puntuación' ?>
                </div>
                <input type="hidden" id="inp-puntuacion" value="<?= $miResenia ? $miResenia['puntuacion'] : 0 ?>">
                <textarea id="inp-comentario" class="resenia-textarea"
                          placeholder="Contá qué te pareció el libro (opcional)..."
                          maxlength="1000"><?= $miResenia ? sanitize($miResenia['comentario'] ?? '') : '' ?></textarea>
                <div style="display:flex;gap:10px;align-items:center;margin-top:10px;">
                    <button id="btn-enviar-resenia" onclick="enviarResenia(<?= $libro['id'] ?>)" class="btn-primary" style="font-size:13.5px;padding:10px 22px;">
                        <?= $miResenia ? 'Actualizar reseña' : 'Publicar reseña' ?>
                    </button>
                    <?php if ($miResenia): ?>
                    <button onclick="cancelarEdicion()" style="font-size:13px;color:var(--gray-500);background:none;border:none;cursor:pointer;font-family:inherit;">Cancelar</button>
                    <?php endif; ?>
                    <span id="resenia-msg" style="font-size:13px;display:none;"></span>
                </div>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- COLUMNA LATERAL -->
    <aside class="libro-aside">

        <!-- Ficha técnica -->
        <div class="libro-aside-box">
            <h3 class="libro-aside-title">Ficha del libro</h3>
            <dl class="libro-ficha">
                <?php if ($libro['autor_nombre']): ?>
                <div><dt>Autor</dt><dd><?= sanitize($libro['autor_nombre']) ?></dd></div>
                <?php endif; ?>
                <div><dt>Categoría</dt><dd><?= sanitize($libro['categoria_nombre']) ?></dd></div>
                <?php if ($libro['paginas']): ?>
                <div><dt>Páginas</dt><dd><?= $libro['paginas'] ?></dd></div>
                <?php endif; ?>
                <div><dt>Idioma</dt><dd><?= sanitize($libro['idioma'] ?? 'Español') ?></dd></div>
                <?php if ($libro['fecha_publicacion']): ?>
                <div><dt>Publicado</dt><dd><?= date('d/m/Y', strtotime($libro['fecha_publicacion'])) ?></dd></div>
                <?php endif; ?>
                <div><dt>Formato</dt><dd>PDF digital</dd></div>
                <div><dt>Lecturas</dt><dd><?= number_format($libro['total_lecturas'] ?? 0) ?></dd></div>
            </dl>
        </div>

        <!-- CTA suscripción si no tiene -->
        <?php if (!hasActiveSubscription()): ?>
        <div class="libro-aside-box" style="background:var(--blue-dark);border:none;color:white;text-align:center;">
            <div style="font-size:32px;margin-bottom:10px;">🔓</div>
            <h3 style="font-family:'Playfair Display',serif;font-size:17px;margin-bottom:8px;">Leé este libro</h3>
            <p style="font-size:13px;color:#CBD5E1;margin-bottom:16px;line-height:1.6;">Suscribite por $1/mes y accedé a todo el catálogo sin límites.</p>
            <a href="<?= APP_URL ?>/pages/suscripcion.php" style="display:block;background:white;color:var(--blue-dark);padding:11px;border-radius:5px;font-size:13.5px;font-weight:700;">Suscribirme — $1/mes</a>
        </div>
        <?php endif; ?>

        <!-- Compartir -->
        <div class="libro-aside-box">
            <h3 class="libro-aside-title">Compartir</h3>
            <div style="display:flex;gap:8px;">
                <a href="https://twitter.com/intent/tweet?text=<?= urlencode($libro['titulo']) ?>&url=<?= urlencode(APP_URL.'/pages/libro.php?slug='.$libro['slug']) ?>"
                   target="_blank" style="flex:1;text-align:center;padding:9px;border:1.5px solid var(--gray-300);border-radius:5px;font-size:13px;color:var(--gray-700);transition:all .18s;"
                   onmouseover="this.style.borderColor='var(--blue-dark)';this.style.color='var(--blue-dark)'"
                   onmouseout="this.style.borderColor='var(--gray-300)';this.style.color='var(--gray-700)'">
                    𝕏 Twitter
                </a>
                <a href="https://wa.me/?text=<?= urlencode($libro['titulo'].' - '.APP_URL.'/pages/libro.php?slug='.$libro['slug']) ?>"
                   target="_blank" style="flex:1;text-align:center;padding:9px;border:1.5px solid var(--gray-300);border-radius:5px;font-size:13px;color:var(--gray-700);transition:all .18s;"
                   onmouseover="this.style.borderColor='#16A34A';this.style.color='#16A34A'"
                   onmouseout="this.style.borderColor='var(--gray-300)';this.style.color='var(--gray-700)'">
                    📱 WhatsApp
                </a>
            </div>
        </div>
    </aside>
</div>

<!-- ── RELACIONADOS ── -->
<?php if (!empty($relacionados)): ?>
<section style="background:var(--gray-100);padding:48px 0;">
    <div style="max-width:var(--container);margin:0 auto;padding:0 24px;">
        <div class="section-head" style="margin-bottom:24px;">
            <h2>También en <em style="font-style:normal;color:var(--blue-dark)"><?= sanitize($libro['categoria_nombre']) ?></em></h2>
            <a href="<?= APP_URL ?>/pages/catalogo.php?categoria=<?= $libro['categoria_id'] ?>">Ver todos →</a>
        </div>
        <div class="book-grid">
            <?php foreach ($relacionados as $libro):
                if (empty($libro['portada'])) {
                    $libro['portada'] = $_CI[$libro['categoria_nombre']] ?? '';
                } elseif (!str_starts_with($libro['portada'], 'http')) {
                    $libro['portada'] = APP_URL . '/' . ltrim($libro['portada'], '/');
                }
            ?>
            <?php include __DIR__ . '/../includes/book_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<style>
/* ── RESEÑAS ── */
.resenia-card {
    padding: 16px 18px;
    background: var(--gray-100);
    border-radius: 10px;
    border: 1px solid var(--gray-200);
}
.resenia-header {
    display: flex; align-items: center; gap: 10px;
}
.resenia-avatar {
    width: 36px; height: 36px; flex-shrink: 0;
    background: var(--blue-dark); color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px;
}
.resenia-cta {
    display: flex; align-items: center; gap: 10px;
    padding: 14px 18px;
    background: var(--blue-light);
    border: 1.5px dashed var(--blue-accent);
    border-radius: 10px;
    font-size: 14px; color: var(--gray-600);
}
.resenia-ya {
    padding: 14px 18px;
    background: #F0FDF4;
    border: 1.5px solid #BBF7D0;
    border-radius: 10px;
    margin-bottom: 16px;
}
.resenia-form-wrap {
    background: var(--gray-100);
    border: 1.5px solid var(--gray-300);
    border-radius: 12px;
    padding: 20px;
    margin-top: 8px;
}
/* Selector de estrellas */
.star-picker { display: flex; gap: 4px; margin-bottom: 6px; }
.star-btn {
    background: none; border: none; cursor: pointer;
    font-size: 28px; color: var(--gray-300);
    padding: 0; line-height: 1;
    transition: color .15s, transform .1s;
}
.star-btn.active, .star-btn.hover { color: #FBBF24; }
.star-btn:hover { transform: scale(1.15); }
.resenia-textarea {
    width: 100%;
    min-height: 90px;
    padding: 10px 12px;
    border: 1.5px solid var(--gray-300);
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    color: var(--gray-800);
    resize: vertical;
    outline: none;
    transition: border-color .18s;
    background: white;
}
.resenia-textarea:focus { border-color: var(--blue-accent); }
</style>

<script>
// ── ESTRELLAS ──
let selectedStars = parseInt(document.getElementById('inp-puntuacion')?.value) || 0;
const starLabels  = ['','Malo','Regular','Bueno','Muy bueno','Excelente'];

function setStars(n) {
    selectedStars = n;
    document.getElementById('inp-puntuacion').value = n;
    document.getElementById('star-label').textContent = starLabels[n];
    updateStarUI(n, false);
}
function hoverStars(n) { updateStarUI(n, true); }
function resetStars()   { updateStarUI(selectedStars, false); }

function updateStarUI(n, isHover) {
    document.querySelectorAll('.star-btn').forEach(btn => {
        const v = parseInt(btn.dataset.val);
        btn.classList.toggle('active', !isHover && v <= n);
        btn.classList.toggle('hover',  isHover  && v <= n);
    });
}
// Inicializar estrellas guardadas
if (selectedStars > 0) updateStarUI(selectedStars, false);

// ── ENVIAR RESEÑA ──
async function enviarResenia(libroId) {
    const punt = parseInt(document.getElementById('inp-puntuacion').value);
    const com  = document.getElementById('inp-comentario').value.trim();
    const msg  = document.getElementById('resenia-msg');
    const btn  = document.getElementById('btn-enviar-resenia');

    if (!punt || punt < 1 || punt > 5) {
        showMsg(msg, '⚠️ Seleccioná una puntuación.', 'orange');
        return;
    }

    btn.disabled = true;
    btn.textContent = 'Guardando…';

    try {
        const fd = new FormData();
        fd.append('libro_id',   libroId);
        fd.append('puntuacion', punt);
        fd.append('comentario', com);

        const res  = await fetch('<?= APP_URL ?>/pages/ajax/guardar_resenia.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            showMsg(msg, '✅ ' + (data.msg || 'Reseña guardada.'), 'green');
            setTimeout(() => location.reload(), 1200);
        } else {
            showMsg(msg, '❌ ' + (data.error || 'Error al guardar.'), 'red');
            btn.disabled = false;
            btn.textContent = 'Publicar reseña';
        }
    } catch(e) {
        showMsg(msg, '❌ Error de conexión.', 'red');
        btn.disabled = false;
        btn.textContent = 'Publicar reseña';
    }
}

function showMsg(el, text, color) {
    el.textContent  = text;
    el.style.color  = color;
    el.style.display = 'inline';
}

function editarResenia() {
    document.getElementById('resenia-ya').style.display = 'none';
    document.getElementById('resenia-form-wrap').style.display = 'block';
    document.getElementById('resenia-form-wrap').querySelector('h3').textContent = 'Editar tu reseña';
    document.getElementById('btn-enviar-resenia').textContent = 'Actualizar reseña';
}
function cancelarEdicion() {
    document.getElementById('resenia-ya').style.display = 'block';
    document.getElementById('resenia-form-wrap').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>