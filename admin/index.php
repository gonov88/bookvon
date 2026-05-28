<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || getCurrentUser()['rol'] !== 'admin') {
    redirect(APP_URL . '/index.php');
}
$pageTitle = 'Panel de Administración';
$db = getDB();

$stats = $db->query("SELECT
    (SELECT COUNT(*) FROM libros WHERE activo=1) as libros,
    (SELECT COUNT(*) FROM usuarios) as usuarios,
    (SELECT COUNT(*) FROM paddle_suscripciones WHERE status='active') as suscripciones_activas,
    (SELECT COUNT(*) FROM categorias) as categorias")->fetch();

$recentLibros = $db->query("SELECT l.*, c.nombre AS cat FROM libros l JOIN categorias c ON l.categoria_id=c.id ORDER BY l.creado_en DESC LIMIT 10")->fetchAll();
$recentUsers  = $db->query("SELECT u.*, s.estado as sub_estado FROM usuarios u LEFT JOIN paddle_suscripciones s ON s.email=u.email AND s.status='active' ORDER BY u.creado_en DESC LIMIT 8")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <div class="admin-sidebar">
        <div class="sidebar-logo">📚 <?= APP_NAME ?> Admin</div>
        <nav>
            <a href="<?= APP_URL ?>/admin/index.php" class="active">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                Dashboard
            </a>
            <a href="<?= APP_URL ?>/admin/libros.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                Libros
            </a>
            <a href="<?= APP_URL ?>/admin/usuarios.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                Usuarios
            </a>
            <a href="<?= APP_URL ?>/admin/suscripciones.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Suscripciones
            </a>
            <a href="<?= APP_URL ?>/index.php">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                Ver sitio
            </a>
        </nav>
    </div>

    <div class="admin-content">
        <h1 style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:24px;">Dashboard</h1>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= $stats['libros'] ?></h3>
                <p>📚 Libros publicados</p>
            </div>
            <div class="stat-card" style="border-color:var(--green);">
                <h3><?= $stats['usuarios'] ?></h3>
                <p>👥 Usuarios registrados</p>
            </div>
            <div class="stat-card" style="border-color:var(--gold);">
                <h3><?= $stats['suscripciones_activas'] ?></h3>
                <p>⭐ Suscripciones activas</p>
            </div>
            <div class="stat-card" style="border-color:#8B5CF6;">
                <h3><?= $stats['categorias'] ?></h3>
                <p>🗂️ Categorías</p>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="admin-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h2 style="font-size:16px;font-weight:700;">Últimos libros</h2>
                    <a href="<?= APP_URL ?>/admin/libros.php" style="font-size:13px;color:var(--blue-accent);">Ver todos</a>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Título</th><th>Categoría</th><th>Lecturas</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentLibros as $l): ?>
                            <tr>
                                <td><?= sanitize($l['titulo']) ?></td>
                                <td><span class="badge badge-blue"><?= sanitize($l['cat']) ?></span></td>
                                <td>—</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="admin-card">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                    <h2 style="font-size:16px;font-weight:700;">Últimos usuarios</h2>
                    <a href="<?= APP_URL ?>/admin/usuarios.php" style="font-size:13px;color:var(--blue-accent);">Ver todos</a>
                </div>
                <div class="table-wrapper">
                    <table>
                        <thead><tr><th>Nombre</th><th>Suscripción</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentUsers as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600;font-size:13.5px;"><?= sanitize($u['nombre']) ?></div>
                                    <div style="font-size:12px;color:var(--gray-500);"><?= sanitize($u['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge <?= $u['sub_estado'] === 'activa' ? 'badge-green' : 'badge-red' ?>">
                                        <?= $u['sub_estado'] === 'activa' ? 'Activa' : 'Sin suscripción' ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
