<?php
require_once __DIR__ . '/../includes/config.php';
$pageTitle = 'Catálogo';
$db = getDB();

$categoriaId = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filter      = $_GET['filter'] ?? '';
$orden       = $_GET['orden']  ?? 'destacados';
$search      = trim($_GET['q'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;
$offset      = ($page - 1) * $perPage;

$_CI = [
    'Negocios y Finanzas'                  => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=500&q=85',
    'Desarrollo Personal'                  => 'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?w=500&q=85',
    'Tecnología e Inteligencia Artificial' => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=500&q=85',
    'Programación y Desarrollo Web'        => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=500&q=85',
    'Educación y Académico'                => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=500&q=85',
    'Salud y Bienestar'                    => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=500&q=85',
    'Cocina y Gastronomía'                 => 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=500&q=85',
    'Ciencia Ficción y Fantasía'           => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?w=500&q=85',
    'Romance y Drama'                      => 'https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=500&q=85',
    'Historia y Política'                  => 'https://images.unsplash.com/photo-1447069387593-a5de0862481e?w=500&q=85',
    'Arte, Diseño y Creatividad'           => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=500&q=85',
    'Infantil y Juvenil'                   => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=500&q=85',
];
$_DEFAULT = 'https://images.unsplash.com/photo-1524995997946-a1c2e315a42f?w=500&q=85';

$catIcons = [
    'Negocios y Finanzas'=>'📊','Desarrollo Personal'=>'🧠',
    'Tecnología e Inteligencia Artificial'=>'🤖','Programación y Desarrollo Web'=>'💻',
    'Educación y Académico'=>'🎓','Salud y Bienestar'=>'💪',
    'Cocina y Gastronomía'=>'🍳','Ciencia Ficción y Fantasía'=>'🚀',
    'Romance y Drama'=>'❤️','Historia y Política'=>'📜',
    'Arte, Diseño y Creatividad'=>'🎨','Infantil y Juvenil'=>'👶',
];

// ── Query ──
$where  = ['l.activo = 1'];
$params = [];
if ($categoriaId)             { $where[] = 'l.categoria_id = ?';    $params[] = $categoriaId; }
if ($filter === 'destacados') { $where[] = 'l.destacado = 1'; }
if ($search)                  { $where[] = '(l.titulo LIKE ? OR a.nombre LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

switch ($orden) {
    case 'nuevos':   $orderSQL = 'l.creado_en DESC'; break;
    case 'titulo':   $orderSQL = 'l.titulo ASC';     break;
    case 'lecturas': $orderSQL = 'l.creado_en DESC'; break;
    default:         $orderSQL = 'l.destacado DESC, l.creado_en DESC';
}
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$cntStmt = $db->prepare("SELECT COUNT(*) FROM libros l LEFT JOIN autores a ON l.autor_id=a.id $whereSQL");
$cntStmt->execute($params);
$total      = (int)$cntStmt->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $db->prepare("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre
                      FROM libros l
                      JOIN categorias c ON l.categoria_id=c.id
                      LEFT JOIN autores a ON l.autor_id=a.id
                      $whereSQL ORDER BY $orderSQL LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$libros = $stmt->fetchAll();

// Sidebar data
$cats = $db->query("SELECT c.*, COUNT(l.id) AS total
                    FROM categorias c
                    LEFT JOIN libros l ON l.categoria_id=c.id AND l.activo=1
                    GROUP BY c.id ORDER BY c.nombre")->fetchAll();

$catName = '';
if ($categoriaId) {
    $cn = $db->prepare("SELECT nombre FROM categorias WHERE id=?");
    $cn->execute([$categoriaId]);
    $catName = $cn->fetchColumn();
}

include __DIR__ . '/../includes/header.php';
?>

<!-- ── PAGE HEADER ── -->
<div class="cat-page-header">
    <div class="cat-page-header-inner">
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/index.php">Inicio</a>
            <span>›</span>
            <span>Catálogo</span>
            <?php if ($catName): ?>
            <span>›</span>
            <span><?= sanitize($catName) ?></span>
            <?php endif; ?>
        </div>
        <h1 class="cat-page-title">
            <?php if ($search): ?>
                Resultados para "<em><?= sanitize($search) ?></em>"
            <?php elseif ($catName): ?>
                <?= sanitize($catName) ?>
            <?php else: ?>
                Catálogo completo
            <?php endif; ?>
        </h1>
    </div>
</div>

<div class="cat-layout">

    <!-- SIDEBAR -->
    <aside class="cat-sidebar">

        <?php if (!hasActiveSubscription()): ?>
        <div class="csb-promo">
            <div class="csb-promo-icon">📚</div>
            <div class="csb-promo-title">Leé sin límites</div>
            <div class="csb-promo-text">Accedé a todo el catálogo por solo <strong>$1/mes</strong></div>
            <a href="<?= APP_URL ?>/pages/suscripcion.php" class="csb-promo-btn">Suscribirme</a>
        </div>
        <?php endif; ?>

        <div class="csb-section">
            <div class="csb-section-title">Categorías</div>
            <ul class="filter-list">
                <li class="filter-item <?= !$categoriaId ? 'active' : '' ?>" data-cat="">
                    <span class="fi-icon">📚</span>
                    <span class="fi-name">Todos</span>
                    <span class="fi-count"><?= array_sum(array_column($cats, 'total')) ?></span>
                </li>
                <?php foreach ($cats as $cat): ?>
                <li class="filter-item <?= $categoriaId == $cat['id'] ? 'active' : '' ?>" data-cat="<?= $cat['id'] ?>">
                    <span class="fi-icon"><?= $catIcons[$cat['nombre']] ?? '📖' ?></span>
                    <span class="fi-name"><?= sanitize($cat['nombre']) ?></span>
                    <span class="fi-count"><?= $cat['total'] ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

    </aside>

    <!-- CONTENIDO -->
    <main class="cat-main">

        <!-- Toolbar -->
        <div class="cat-toolbar">
            <div class="cat-toolbar-count">
                <strong><?= number_format($total) ?></strong> libro<?= $total!==1?'s':'' ?>
                <?php if ($search): ?><span class="cat-toolbar-query"> — "<em><?= sanitize($search) ?></em>"</span><?php endif; ?>
            </div>
            <div class="cat-toolbar-sort">
                <label for="sort-select">Ordenar por:</label>
                <select id="sort-select">
                    <option value="destacados" <?= $orden==='destacados'?'selected':'' ?>>Más populares</option>
                    <option value="nuevos"     <?= $orden==='nuevos'    ?'selected':'' ?>>Más recientes</option>
                    <option value="titulo"     <?= $orden==='titulo'    ?'selected':'' ?>>Título A–Z</option>
                    <option value="lecturas"   <?= $orden==='lecturas'  ?'selected':'' ?>>Más leídos</option>
                </select>
            </div>
        </div>

        <?php if (empty($libros)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:56px;height:56px;margin:0 auto 16px;opacity:.3;display:block;"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            <h3>No se encontraron libros</h3>
            <p>Probá con otra categoría o término de búsqueda.</p>
            <a href="<?= APP_URL ?>/pages/catalogo.php" class="btn-primary" style="display:inline-flex;margin-top:20px;">Ver todo el catálogo</a>
        </div>
        <?php else: ?>

        <!-- GRILLA -->
        <div class="cat-grid-3col">
            <?php foreach ($libros as $libro):
                if (empty($libro['portada'])) {
                    $img = $_CI[$libro['categoria_nombre']] ?? $_DEFAULT;
                } elseif (str_starts_with($libro['portada'], 'http')) {
                    $img = $libro['portada'];
                } else {
                    $img = APP_URL . '/' . ltrim($libro['portada'], '/');
                }
            ?>
            <a href="<?= APP_URL ?>/pages/libro.php?slug=<?= urlencode($libro['slug']) ?>" class="cat3-card">
                <div class="cat3-cover">
                    <img src="<?= $img ?>" alt="<?= sanitize($libro['titulo']) ?>" loading="lazy">
                    <?php if ($libro['destacado']): ?>
                    <span class="cat3-badge">Destacado</span>
                    <?php endif; ?>
                    <div class="cat3-hover-cta">
                        <span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                            Ver libro
                        </span>
                    </div>
                </div>
                <div class="cat3-info">
                    <div class="cat3-cat"><?= sanitize($libro['categoria_nombre']) ?></div>
                    <h3 class="cat3-title"><?= sanitize($libro['titulo']) ?></h3>
                    <?php if ($libro['autor_nombre']): ?>
                    <div class="cat3-author"><?= sanitize($libro['autor_nombre']) ?></div>
                    <?php endif; ?>
                    <div class="cat3-footer">
                        <div class="cat3-stars">★★★★<span style="color:var(--gray-300);">★</span></div>
                        <div class="cat3-reads"><?= number_format($libro['total_lecturas'] ?? 0) ?> lecturas</div>
                    </div>
                    <?php if (!hasActiveSubscription()): ?>
                    <div class="cat3-sub-tag">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="11"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Requiere suscripción
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- PAGINACIÓN -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination" style="margin-top:48px;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page-1])) ?>">‹ Anterior</a>
            <?php endif; ?>
            <?php for ($i = max(1,$page-2); $i <= min($totalPages,$page+2); $i++): ?>
            <?php if ($i===$page): ?>
            <span class="current"><?= $i ?></span>
            <?php else: ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"><?= $i ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$page+1])) ?>">Siguiente ›</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
