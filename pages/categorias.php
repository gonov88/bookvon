<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Categorías';
$db = getDB();

$cats = $db->query("SELECT c.*, COUNT(l.id) AS total
    FROM categorias c
    LEFT JOIN libros l ON l.categoria_id=c.id AND l.activo=1
    GROUP BY c.id ORDER BY c.nombre")->fetchAll();

$catIcons = ['Negocios y Finanzas'=>'📊','Desarrollo Personal'=>'🧠','Tecnología e Inteligencia Artificial'=>'🤖',
    'Programación y Desarrollo Web'=>'💻','Educación y Académico'=>'🎓','Salud y Bienestar'=>'💪',
    'Cocina y Gastronomía'=>'🍳','Ciencia Ficción y Fantasía'=>'🚀','Romance y Drama'=>'❤️',
    'Historia y Política'=>'📜','Arte, Diseño y Creatividad'=>'🎨','Infantil y Juvenil'=>'👶'];

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb"><a href="<?= APP_URL ?>">Inicio</a> / <span>Categorías</span></div>
        <h1>Todas las categorías</h1>
    </div>
</div>
<section class="section">
    <div class="section-inner">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
            <?php foreach ($cats as $cat):
                $icon = $catIcons[$cat['nombre']] ?? '📚';
            ?>
            <a href="<?= APP_URL ?>/pages/catalogo.php?categoria=<?= $cat['id'] ?>" style="border:1.5px solid var(--gray-300);border-radius:var(--radius-md);padding:20px;display:flex;gap:16px;align-items:flex-start;transition:var(--transition);" onmouseover="this.style.borderColor='var(--blue-dark)';this.style.background='var(--blue-light)';" onmouseout="this.style.borderColor='var(--gray-300)';this.style.background='white';">
                <span style="font-size:28px;flex-shrink:0;"><?= $icon ?></span>
                <div>
                    <div style="font-size:15px;font-weight:700;color:var(--gray-900);margin-bottom:4px;"><?= sanitize($cat['nombre']) ?></div>
                    <div style="font-size:12px;color:var(--blue-accent);font-weight:600;margin-bottom:6px;"><?= $cat['total'] ?> libro<?= $cat['total'] !== 1 ? 's' : '' ?></div>
                    <?php if ($cat['subcats']): ?>
                    <div style="font-size:12.5px;color:var(--gray-500);line-height:1.5;"><?= sanitize(substr($cat['subcats'], 0, 80)) ?>...</div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
