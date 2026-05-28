<?php
require_once __DIR__ . '/access.php';
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Inicio';
$pageDesc = 'Miles de ebooks con una sola suscripción. Leé sin límites.';

$db = getDB();

$stmt = $db->query("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre 
                    FROM libros l 
                    JOIN categorias c ON l.categoria_id = c.id 
                    LEFT JOIN autores a ON l.autor_id = a.id
                    WHERE l.destacado = 1 AND l.activo = 1 
                    ORDER BY l.creado_en DESC LIMIT 8");
$destacados = $stmt->fetchAll();

$stmt2 = $db->query("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre 
                     FROM libros l 
                     JOIN categorias c ON l.categoria_id = c.id 
                     LEFT JOIN autores a ON l.autor_id = a.id
                     WHERE l.activo = 1 
                     ORDER BY l.creado_en DESC LIMIT 8");
$novedades = $stmt2->fetchAll();

$stmtCat = $db->query("SELECT c.*, COUNT(l.id) AS total 
                       FROM categorias c 
                       LEFT JOIN libros l ON l.categoria_id = c.id AND l.activo = 1 
                       GROUP BY c.id ORDER BY total DESC LIMIT 12");
$categorias = $stmtCat->fetchAll();

$stats = $db->query("SELECT 
    (SELECT COUNT(*) FROM libros WHERE activo=1) as total_libros,
    (SELECT COUNT(*) FROM usuarios) as total_usuarios,
    (SELECT COUNT(*) FROM categorias) as total_categorias")->fetch();

// Imágenes reales de Unsplash por categoría (libres de uso)
$catImages = [
    'Negocios y Finanzas'                    => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=400&q=80',
    'Desarrollo Personal'                    => 'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?w=400&q=80',
    'Tecnología e Inteligencia Artificial'   => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=400&q=80',
    'Programación y Desarrollo Web'          => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=400&q=80',
    'Educación y Académico'                  => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=400&q=80',
    'Salud y Bienestar'                      => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&q=80',
    'Cocina y Gastronomía'                   => 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=400&q=80',
    'Ciencia Ficción y Fantasía'             => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?w=400&q=80',
    'Romance y Drama'                        => 'https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=400&q=80',
    'Historia y Política'                    => 'https://images.unsplash.com/photo-1447069387593-a5de0862481e?w=400&q=80',
    'Arte, Diseño y Creatividad'             => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&q=80',
    'Infantil y Juvenil'                     => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&q=80',
];

include __DIR__ . '/includes/header.php';
?>

<!-- HERO -->
<section class="hero">
    <div class="hero-inner">

        <!-- LEFT: texto -->
        <div class="hero-text-col">
            <div class="hero-badge">
                <span class="hero-badge-dot"></span>
                Plataforma de lectura digital
            </div>
            <h1>Lee sin límites con una sola <em>suscripción</em></h1>
            <p class="hero-desc">Accedé a miles de ebooks en negocios, tecnología, ficción, desarrollo personal y más. Un precio fijo mensual, sin sorpresas.</p>
            <div class="hero-actions">
                <?php if (!isLoggedIn()): ?>
                    <a href="<?= APP_URL ?>/pages/registro.php" class="hero-btn-primary">Comenzar gratis →</a>
                    <a href="<?= APP_URL ?>/pages/catalogo.php" class="hero-btn-outline">Ver catálogo</a>
                <?php elseif (!hasActiveSubscription()): ?>
                    <a href="<?= APP_URL ?>/pages/suscripcion.php" class="hero-btn-primary">Activar suscripción →</a>
                    <a href="<?= APP_URL ?>/pages/catalogo.php" class="hero-btn-outline">Ver catálogo</a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/pages/catalogo.php" class="hero-btn-primary">Explorar libros →</a>
                    <a href="<?= APP_URL ?>/pages/mi-biblioteca.php" class="hero-btn-outline">Mi biblioteca</a>
                <?php endif; ?>
            </div>
            <div class="hero-stats">
                <div class="hero-stat"><strong><?= number_format($stats['total_libros']) ?>+</strong><span>Ebooks disponibles</span></div>
                <div class="hero-stat"><strong><?= $stats['total_categorias'] ?></strong><span>Categorías</span></div>
                <div class="hero-stat"><strong>∞</strong><span>Lecturas ilimitadas</span></div>
            </div>
        </div>

        <!-- RIGHT: carrusel de libros destacados -->
        <div class="hero-carousel-col">
            <div class="hero-carousel-label">📚 Libros destacados</div>
            <div class="hero-carousel-track-wrap">
                <div class="hero-carousel-track" id="heroCarouselTrack">
                    <?php
                    $allCarouselBooks = !empty($destacados) ? $destacados : $novedades;
                    foreach ($allCarouselBooks as $book):
                        $imgSrc = '';
                        if (!empty($book['portada'])) {
                            $imgSrc = (str_starts_with($book['portada'], 'http://') || str_starts_with($book['portada'], 'https://'))
                                ? $book['portada']
                                : APP_URL . '/' . ltrim($book['portada'], '/');
                        } else {
                            $imgSrc = $catImages[$book['categoria_nombre']] ?? 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&q=80';
                        }
                    ?>
                    <a href="<?= APP_URL ?>/pages/libro.php?slug=<?= urlencode($book['slug']) ?>" class="hero-carousel-card">
                        <div class="hcc-cover">
                            <img src="<?= $imgSrc ?>" alt="<?= sanitize($book['titulo']) ?>" loading="lazy" onerror="this.style.display='none'">
                            <span class="hcc-badge">Destacado</span>
                            <div class="hcc-overlay">
                                <span class="hcc-overlay-text">Ver libro →</span>
                            </div>
                        </div>
                        <div class="hcc-info">
                            <div class="hcc-cat"><?= sanitize($book['categoria_nombre']) ?></div>
                            <div class="hcc-title"><?= sanitize($book['titulo']) ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Controls -->
            <div class="hero-carousel-controls">
                <div class="hero-carousel-dots" id="heroDots"></div>
                <div class="hero-carousel-btns">
                    <button class="hc-btn" id="hcPrev" aria-label="Anterior">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14"><path d="m15 18-6-6 6-6"/></svg>
                    </button>
                    <button class="hc-btn" id="hcNext" aria-label="Siguiente">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="14"><path d="m9 18 6-6-6-6"/></svg>
                    </button>
                </div>
            </div>
        </div>

    </div>
    <div class="hero-wave">
        <svg viewBox="0 0 1440 48" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0 48 L0 32 Q360 0 720 28 Q1080 52 1440 20 L1440 48 Z" fill="var(--gray-100)"/>
        </svg>
    </div>
</section>

<!-- CATEGORÍAS CON IMÁGENES -->
<section class="section" style="background: var(--gray-100); padding: 48px 24px;">
    <div class="section-inner">
        <div class="section-head">
            <div>
                <h2>Explorar por categoría</h2>
                <p class="section-subtitle">Encontrá exactamente lo que buscás</p>
            </div>
            <a href="<?= APP_URL ?>/pages/categorias.php">Ver todas →</a>
        </div>
        <div class="cat-img-grid">
            <?php foreach ($categorias as $cat):
                $img = $catImages[$cat['nombre']] ?? 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&q=80';
            ?>
            <a href="<?= APP_URL ?>/pages/catalogo.php?categoria=<?= $cat['id'] ?>" class="cat-img-card">
                <div class="cat-img-wrap">
                    <img src="<?= $img ?>" alt="<?= sanitize($cat['nombre']) ?>" loading="lazy">
                    <div class="cat-img-overlay"></div>
                </div>
                <div class="cat-img-info">
                    <div class="cat-img-name"><?= sanitize($cat['nombre']) ?></div>
                    <div class="cat-img-count"><?= $cat['total'] ?> libro<?= $cat['total'] != 1 ? 's' : '' ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- LIBROS DESTACADOS -->
<?php if (!empty($destacados)): ?>
<section class="section">
    <div class="section-inner">
        <div class="section-head">
            <div>
                <h2>Libros destacados</h2>
                <p class="section-subtitle">Los más leídos de la plataforma</p>
            </div>
            <a href="<?= APP_URL ?>/pages/catalogo.php?filter=destacados">Ver todos →</a>
        </div>
        <div class="book-grid">
            <?php foreach ($destacados as $libro):
                if (empty($libro['portada'])) {
                    $libro['portada'] = $catImages[$libro['categoria_nombre']] ?? '';
                } elseif (!str_starts_with($libro['portada'], 'http')) {
                    $libro['portada'] = APP_URL . '/' . ltrim($libro['portada'], '/');
                }
            ?>
            <?php include __DIR__ . '/includes/book_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- BANNER SUSCRIPCIÓN -->
<?php if (!hasActiveSubscription()): ?>
<section class="sub-banner" style="padding:64px 24px;">
    <div style="max-width:600px;margin:0 auto;">
        <p style="font-size:12px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#93C5FD;margin-bottom:12px;">Sin complicaciones</p>
        <h2 style="font-family:'Playfair Display',serif;font-size:clamp(26px,3.5vw,40px);margin-bottom:14px;">Todo el catálogo por <em style="color:#93C5FD;font-style:normal;">$1,99 al mes</em></h2>
        <p style="color:#CBD5E1;font-size:16px;max-width:440px;margin:0 auto 32px;line-height:1.7;">Una sola suscripción. Acceso ilimitado. Cancelá cuando quieras.</p>
        <div style="display:flex;justify-content:center;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
            <?php foreach(['📚 Catálogo completo','📱 Todos los dispositivos','🔖 Historial guardado','🚫 Sin cargos extra'] as $item): ?>
            <span style="background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);padding:7px 14px;border-radius:20px;font-size:13px;color:#E2E8F0;"><?= $item ?></span>
            <?php endforeach; ?>
        </div>
        <a href="<?= APP_URL ?>/pages/suscripcion.php" class="hero-btn-primary" style="display:inline-flex;align-items:center;gap:8px;font-size:16px;padding:14px 36px;">
            Suscribirme — $1,99/mes
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
        </a>
    </div>
</section>
<?php endif; ?>

<!-- NOVEDADES -->
<?php if (!empty($novedades)): ?>
<section class="section" style="background:var(--gray-100);">
    <div class="section-inner">
        <div class="section-head">
            <div>
                <h2>Últimas incorporaciones</h2>
                <p class="section-subtitle">Novedades recientes al catálogo</p>
            </div>
            <a href="<?= APP_URL ?>/pages/catalogo.php?filter=nuevos">Ver todas →</a>
        </div>
        <div class="book-grid">
            <?php foreach ($novedades as $libro):
                if (empty($libro['portada'])) {
                    $libro['portada'] = $catImages[$libro['categoria_nombre']] ?? '';
                } elseif (!str_starts_with($libro['portada'], 'http')) {
                    $libro['portada'] = APP_URL . '/' . ltrim($libro['portada'], '/');
                }
            ?>
            <?php include __DIR__ . '/includes/book_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
