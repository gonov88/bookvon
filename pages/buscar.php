<?php
require_once __DIR__ . '/../includes/config.php';
$q = trim($_GET['q'] ?? '');
$pageTitle = $q ? "Buscar: $q" : 'Búsqueda';
$db = getDB();

$libros = [];
if ($q) {
    $stmt = $db->prepare("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre 
                          FROM libros l 
                          JOIN categorias c ON l.categoria_id=c.id 
                          LEFT JOIN autores a ON l.autor_id=a.id
                          WHERE l.activo=1 AND (l.titulo LIKE ? OR a.nombre LIKE ? OR c.nombre LIKE ?)
                          ORDER BY l.destacado DESC, l.creado_en DESC LIMIT 40");
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    $libros = $stmt->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div class="page-header-inner">
        <h1>Resultados para "<?= sanitize($q) ?>"</h1>
    </div>
</div>
<section class="section">
    <div class="section-inner">
        <?php if (!$q): ?>
            <div class="empty-state"><h3>Ingresá un término para buscar</h3></div>
        <?php elseif (empty($libros)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <h3>Sin resultados</h3>
                <p>No encontramos libros que coincidan con "<?= sanitize($q) ?>".</p>
            </div>
        <?php else: ?>
            <p style="margin-bottom:20px;color:var(--gray-500);"><?= count($libros) ?> resultado<?= count($libros) !== 1 ? 's' : '' ?> encontrado<?= count($libros) !== 1 ? 's' : '' ?></p>
            <div class="book-grid">
                <?php foreach ($libros as $libro): ?>
                <?php include __DIR__ . '/../includes/book_card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
