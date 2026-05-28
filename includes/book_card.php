<?php
// Variables esperadas: $libro (array con titulo, slug, categoria_nombre, autor_nombre, destacado, total_lecturas, portada)

// Mapa de imágenes por categoría como fallback
$_bookCatImages = [
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

$_imgSrc = '';
if (!empty($libro['portada'])) {
    $_imgSrc = (str_starts_with($libro['portada'], 'http://') || str_starts_with($libro['portada'], 'https://'))
        ? $libro['portada']
        : APP_URL . '/' . ltrim($libro['portada'], '/');
} else {
    $_imgSrc = $_bookCatImages[$libro['categoria_nombre'] ?? ''] ?? 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=400&q=80';
}
?>
<a href="<?= APP_URL ?>/pages/libro.php?slug=<?= urlencode($libro['slug']) ?>" class="book-card">
    <div class="book-cover">
        <img src="<?= $_imgSrc ?>"
             alt="<?= sanitize($libro['titulo']) ?>"
             loading="lazy"
             onerror="this.style.display='none'">
        <?php if (!empty($libro['destacado'])): ?>
            <span class="book-badge">Destacado</span>
        <?php endif; ?>
    </div>
    <div class="book-info">
        <div class="book-category"><?= sanitize($libro['categoria_nombre'] ?? '') ?></div>
        <div class="book-title"><?= sanitize($libro['titulo']) ?></div>
        <?php if (!empty($libro['autor_nombre'])): ?>
            <div class="book-author"><?= sanitize($libro['autor_nombre']) ?></div>
        <?php endif; ?>
        <div class="book-rating">
            <span class="stars">★★★★☆</span>
            <span class="rating-count">(<?= number_format($libro['total_lecturas'] ?? 0) ?>)</span>
        </div>
    </div>
</a>
