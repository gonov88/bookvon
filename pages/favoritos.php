<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn()) { redirect(APP_URL . '/pages/login.php'); }
$pageTitle = 'Mis Favoritos';
$db = getDB();

$stmt = $db->prepare("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre 
                      FROM favoritos f
                      JOIN libros l ON f.libro_id=l.id
                      JOIN categorias c ON l.categoria_id=c.id
                      LEFT JOIN autores a ON l.autor_id=a.id
                      WHERE f.usuario_id=? ORDER BY f.creado_en DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$libros = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="page-header-inner">
        <div class="breadcrumb"><a href="<?= APP_URL ?>">Inicio</a> / <span>Favoritos</span></div>
        <h1>Mis Favoritos</h1>
    </div>
</div>
<section class="section">
    <div class="section-inner">
        <?php if (empty($libros)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
            <h3>No tenés favoritos todavía</h3>
            <p>Marcá libros con ❤️ desde su página de detalle para guardarlos acá.</p>
            <a href="<?= APP_URL ?>/pages/catalogo.php" class="btn-primary" style="display:inline-flex;margin-top:16px;">Explorar catálogo</a>
        </div>
        <?php else: ?>
        <p style="margin-bottom:24px;color:var(--gray-500);"><?= count($libros) ?> libro<?= count($libros)!==1?'s':'' ?> guardado<?= count($libros)!==1?'s':'' ?></p>
        <div class="book-grid">
            <?php foreach ($libros as $libro): ?>
            <?php include __DIR__ . '/../includes/book_card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
