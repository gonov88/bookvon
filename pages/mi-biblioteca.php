<?php
require_once __DIR__ . '/../includes/config.php';
requireSubscription();
$pageTitle = 'Mi Biblioteca';
$db = getDB();

$userId = $_SESSION['usuario_id'];

$stmt = $db->prepare(
    "SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre,
            h.pagina_actual, h.completado, h.ultima_lectura
     FROM historial_lectura h
     JOIN libros l ON h.libro_id = l.id
     JOIN categorias c ON l.categoria_id = c.id
     LEFT JOIN autores a ON l.autor_id = a.id
     WHERE h.usuario_id = ?
     ORDER BY h.ultima_lectura DESC"
);
$stmt->execute([$userId]);
$libros = $stmt->fetchAll();

// Mapa de imágenes de categoría (igual que book_card.php)
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

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb"><a href="<?= APP_URL ?>">Inicio</a> / <span>Mi Biblioteca</span></div>
        <h1>Mi Biblioteca</h1>
    </div>
</div>

<section class="section">
    <div class="section-inner">
        <?php if (empty($libros)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            <h3>Tu biblioteca está vacía</h3>
            <p>Todavía no leíste ningún libro. ¡Explorá el catálogo para empezar!</p>
            <a href="<?= APP_URL ?>/pages/catalogo.php" class="btn-primary" style="display:inline-flex;margin-top:16px;">Ir al catálogo</a>
        </div>
        <?php else: ?>

        <p style="margin-bottom:24px;color:var(--gray-500);">
            <?= count($libros) ?> libro<?= count($libros) !== 1 ? 's' : '' ?> en tu biblioteca
        </p>

        <div class="book-grid">
            <?php foreach ($libros as $libro):
                // Resolver imagen igual que book_card.php
                if (!empty($libro['portada'])) {
                    $imgSrc = (str_starts_with($libro['portada'], 'http://') || str_starts_with($libro['portada'], 'https://'))
                        ? $libro['portada']
                        : APP_URL . '/' . ltrim($libro['portada'], '/');
                } else {
                    $imgSrc = $catImages[$libro['categoria_nombre'] ?? ''] ?? 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&q=80';
                }
                $pct = ($libro['paginas'] > 0) ? min(100, round($libro['pagina_actual'] / $libro['paginas'] * 100)) : 0;
            ?>
            <a href="<?= APP_URL ?>/pages/leer.php?id=<?= $libro['id'] ?>" class="book-card">
                <div class="book-cover">
                    <img src="<?= $imgSrc ?>"
                         alt="<?= sanitize($libro['titulo']) ?>"
                         loading="lazy"
                         onerror="this.style.display='none'">

                    <?php if ($libro['completado']): ?>
                        <span class="book-badge" style="background:#16A34A;">✓ Leído</span>
                    <?php elseif ($pct > 0): ?>
                        <span class="book-badge"><?= $pct ?>%</span>
                    <?php endif; ?>

                    <!-- Barra de progreso en la parte inferior de la portada -->
                    <?php if ($pct > 0): ?>
                    <div style="position:absolute;bottom:0;left:0;right:0;height:4px;background:rgba(0,0,0,0.25);">
                        <div style="height:100%;width:<?= $pct ?>%;background:#60A5FA;transition:width .4s ease;"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="book-info">
                    <div class="book-category"><?= sanitize($libro['categoria_nombre'] ?? '') ?></div>
                    <div class="book-title"><?= sanitize($libro['titulo']) ?></div>
                    <?php if (!empty($libro['autor_nombre'])): ?>
                        <div class="book-author"><?= sanitize($libro['autor_nombre']) ?></div>
                    <?php endif; ?>
                    <div class="book-rating" style="margin-top:4px;">
                        <?php if ($libro['paginas'] > 0): ?>
                            <span style="font-size:11.5px;color:var(--gray-500);">
                                Pág. <?= $libro['pagina_actual'] ?> / <?= $libro['paginas'] ?>
                            </span>
                        <?php else: ?>
                            <span style="font-size:11.5px;color:var(--gray-500);">
                                <?= date('d/m/Y', strtotime($libro['ultima_lectura'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/../includes/footer.php'; ?>
