<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || getCurrentUser()['rol'] !== 'admin') { redirect(APP_URL . '/index.php'); }
$pageTitle = 'Usuarios';
$db = getDB();

$usuarios = $db->query("SELECT u.*, s.estado AS sub_estado, s.fecha_fin, p.nombre AS plan_nombre
    FROM usuarios u
    LEFT JOIN paddle_suscripciones s ON s.email=u.email AND s.status='active'
    LEFT JOIN planes p ON s.plan_id=p.id
    ORDER BY u.creado_en DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="admin-layout">
    <div class="admin-sidebar">
        <div class="sidebar-logo">📚 Admin</div>
        <nav>
            <a href="<?= APP_URL ?>/admin/index.php">Dashboard</a>
            <a href="<?= APP_URL ?>/admin/libros.php">Libros</a>
            <a href="<?= APP_URL ?>/admin/usuarios.php" class="active">Usuarios</a>
            <a href="<?= APP_URL ?>/admin/suscripciones.php">Suscripciones</a>
            <a href="<?= APP_URL ?>/index.php">Ver sitio</a>
        </nav>
    </div>
    <div class="admin-content">
        <h1 style="font-family:'Playfair Display',serif;font-size:24px;margin-bottom:24px;">Usuarios (<?= count($usuarios) ?>)</h1>
        <div class="admin-card">
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Suscripción</th><th>Plan</th><th>Vence</th><th>Registrado</th></tr></thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><strong><?= sanitize($u['nombre']) ?></strong></td>
                            <td style="font-size:13px;"><?= sanitize($u['email']) ?></td>
                            <td><span class="badge <?= $u['rol']==='admin' ? 'badge-blue' : 'badge-green' ?>"><?= $u['rol'] ?></span></td>
                            <td><span class="badge <?= $u['sub_estado']==='activa' ? 'badge-green' : 'badge-red' ?>"><?= $u['sub_estado'] ?? 'Sin suscripción' ?></span></td>
                            <td><?= sanitize($u['plan_nombre'] ?? '—') ?></td>
                            <td style="font-size:13px;"><?= $u['fecha_fin'] ? date('d/m/Y', strtotime($u['fecha_fin'])) : '—' ?></td>
                            <td style="font-size:13px;"><?= date('d/m/Y', strtotime($u['creado_en'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
