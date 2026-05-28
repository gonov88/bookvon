<?php
require_once __DIR__ . '/../includes/config.php';
if (!isLoggedIn() || getCurrentUser()['rol'] !== 'admin') { redirect(APP_URL . '/index.php'); }
$pageTitle = 'Gestión de Libros';
$db = getDB();

// ── Helpers ──
define('UPLOAD_BASE', __DIR__ . '/../uploads/');

function getSubcatSlug(PDO $db, ?int $subId): string {
    if (!$subId) return 'sin-categoria';
}

// ── LISTA ──
$page    = max(1,(int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page-1)*$perPage;
$total   = (int)$db->query("SELECT COUNT(*) FROM libros")->fetchColumn();
$libros  = $db->query("SELECT l.*, c.nombre AS cat, sc.nombre AS subcat
                        FROM libros l 
                        JOIN categorias c ON l.categoria_id=c.id
                                                ORDER BY l.creado_en DESC LIMIT $perPage OFFSET $offset")->fetchAll();

$cats    = $db->query("SELECT * FROM categorias ORDER BY nombre")->fetchAll();
$autores = $db->query("SELECT * FROM autores ORDER BY nombre")->fetchAll();
$subcats = [];

include __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <div class="admin-sidebar">
        <div class="sidebar-logo">📚 Admin</div>
        <nav>
            <a href="<?= APP_URL ?>/admin/index.php">Dashboard</a>
            <a href="<?= APP_URL ?>/admin/libros.php" class="active">Libros</a>
            <a href="<?= APP_URL ?>/admin/usuarios.php">Usuarios</a>
            <a href="<?= APP_URL ?>/admin/suscripciones.php">Suscripciones</a>
            <a href="<?= APP_URL ?>/index.php">Ver sitio</a>
        </nav>
    </div>

    <div class="admin-content">
        <?php if (isset($_GET['ok'])): ?>
        <div class="alert alert-success">✓ <?= $_GET['ok']==='saved' ? 'Libro guardado correctamente.' : 'Libro eliminado.' ?></div>
        <?php endif; ?>
        <?php if ($uploadError): ?>
        <div class="alert alert-error">⚠️ <?= sanitize($uploadError) ?></div>
        <?php endif; ?>

        <!-- ── FORMULARIO ── -->
        <div class="admin-card" style="margin-bottom:24px;">
            <h2 style="font-size:17px;font-weight:700;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid var(--gray-300);">
                <?= $editLibro ? '✏️ Editar libro' : '➕ Agregar nuevo libro' ?>
            </h2>

            <form method="POST" enctype="multipart/form-data" id="libroForm">
                <?php if ($editLibro): ?>
                <input type="hidden" name="id" value="<?= $editLibro['id'] ?>">
                <input type="hidden" name="archivo_pdf_actual" value="<?= sanitize($editLibro['archivo_pdf'] ?? '') ?>">
                <?php endif; ?>

                <!-- Fila 1: Título + Categoría -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Título *</label>
                        <input class="form-control" type="text" name="titulo"
                               value="<?= sanitize($editLibro['titulo'] ?? '') ?>" required
                               placeholder="Nombre del ebook">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Categoría *</label>
                        <select class="form-control" name="categoria_id" id="sel-cat" required>
                            <option value="">— Seleccioná —</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($editLibro['categoria_id'] ?? '')==$c['id']?'selected':'' ?>>
                                <?= sanitize($c['nombre']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Fila 2: Autor -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    

                <!-- Fila 3: Páginas + Portada URL -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Páginas</label>
                        <input class="form-control" type="number" name="paginas" min="1"
                               value="<?= $editLibro['paginas'] ?? '' ?>" placeholder="Ej: 320">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">URL de portada <small style="color:var(--gray-500)">(o subí imagen abajo)</small></label>
                        <input class="form-control" type="text" name="portada_url"
                               value="<?= sanitize($editLibro['portada'] ?? '') ?>"
                               placeholder="https://... o dejar vacío">
                    </div>
                </div>

                <!-- Fila 4: Upload portada + Upload PDF -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">

                    <!-- Portada imagen -->
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Subir imagen de portada</label>
                        <div class="upload-zone" id="zone-portada" onclick="document.getElementById('inp-portada').click()">
                            <?php if (!empty($editLibro['portada'])): ?>
                            <img src="<?= sanitize($editLibro['portada']) ?>" id="preview-portada"
                                 style="height:80px;object-fit:cover;border-radius:4px;margin-bottom:8px;">
                            <?php else: ?>
                            <div id="preview-portada" style="display:none;"></div>
                            <?php endif; ?>
                            <div class="upload-zone-icon">🖼️</div>
                            <div class="upload-zone-label">JPG, PNG, WEBP · Hacé clic o arrastrá</div>
                            <input type="file" id="inp-portada" name="portada_img"
                                   accept=".jpg,.jpeg,.png,.webp" style="display:none"
                                   onchange="previewImg(this,'preview-portada','zone-portada')">
                        </div>
                    </div>

                    <!-- PDF -->
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Subir archivo PDF <small style="color:var(--gray-500)">(máx. 50 MB)</small></label>
                        <div class="upload-zone" id="zone-pdf" onclick="document.getElementById('inp-pdf').click()">
                            <?php if (!empty($editLibro['archivo_pdf'])): ?>
                            <div class="upload-zone-existing">
                                📄 <?= basename($editLibro['archivo_pdf']) ?>
                                <span style="color:var(--gray-500);font-size:11px;display:block;margin-top:2px;">Subí uno nuevo para reemplazarlo</span>
                            </div>
                            <?php endif; ?>
                            <div class="upload-zone-icon" id="pdf-icon">📤</div>
                            <div class="upload-zone-label" id="pdf-label">PDF · Hacé clic o arrastrá</div>
                            <input type="file" id="inp-pdf" name="archivo_pdf"
                                   accept=".pdf,application/pdf" style="display:none"
                                   onchange="showPdfName(this)">
                        </div>
                        <!-- Ruta resultante -->
                        <div id="pdf-destino" style="margin-top:8px;font-size:12px;color:var(--blue-accent);display:none;">
                            📁 Se guardará en: <code id="pdf-path-preview"></code>
                        </div>
                    </div>
                </div>

                <!-- Descripción -->
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea class="form-control" name="descripcion" rows="3"
                              placeholder="Breve sinopsis del libro..."><?= sanitize($editLibro['descripcion'] ?? '') ?></textarea>
                </div>

                <!-- Checkboxes -->
                <div style="display:flex;gap:24px;margin-bottom:20px;">
                    <label style="display:flex;align-items:center;gap:7px;font-size:14px;cursor:pointer;">
                        <input type="checkbox" name="destacado" <?= ($editLibro['destacado'] ?? 0)?'checked':'' ?>>
                        <span>⭐ Destacado</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:7px;font-size:14px;cursor:pointer;">
                        <input type="checkbox" name="activo" <?= (!$editLibro || $editLibro['activo'])?'checked':'' ?>>
                        <span>✅ Activo (visible en catálogo)</span>
                    </label>
                </div>

                <div style="display:flex;align-items:center;gap:12px;">
                    <button type="submit" class="btn-primary">
                        <?= $editLibro ? '💾 Guardar cambios' : '➕ Agregar libro' ?>
                    </button>
                    <?php if ($editLibro): ?>
                    <a href="<?= APP_URL ?>/admin/libros.php" style="font-size:14px;color:var(--gray-500);">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ── TABLA ── -->
        <div class="admin-card">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
                <h2 style="font-size:16px;font-weight:700;"><?= $total ?> libro<?= $total!=1?'s':'' ?> en total</h2>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Portada</th>
                            <th>Título</th>
                            <th>Categoría / Subcat.</th>
                            <th>PDF</th>
                            <th>Pág.</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($libros as $l): ?>
                        <tr>
                            <td>
                                <?php if ($l['portada']): ?>
                                <img src="<?= sanitize($l['portada']) ?>"
                                     style="width:38px;height:52px;object-fit:cover;border-radius:3px;border:1px solid var(--gray-300);">
                                <?php else: ?>
                                <div style="width:38px;height:52px;background:var(--gray-100);border-radius:3px;display:flex;align-items:center;justify-content:center;font-size:18px;">📚</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-weight:600;font-size:13.5px;"><?= sanitize($l['titulo']) ?></div>
                            </td>
                            <td>
                                <span class="badge badge-blue"><?= sanitize($l['cat']) ?></span>
                                <?php if ($l['subcat']): ?>
                                <span style="font-size:11.5px;color:var(--gray-500);display:block;margin-top:3px;"><?= sanitize($l['subcat']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($l['archivo_pdf']): ?>
                                <a href="<?= APP_URL . '/' . sanitize($l['archivo_pdf']) ?>" target="_blank"
                                   style="display:inline-flex;align-items:center;gap:4px;font-size:12px;color:var(--blue-accent);">
                                    📄 Ver PDF
                                </a>
                                <?php else: ?>
                                <span style="font-size:12px;color:var(--gray-500);">Sin PDF</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px;"><?= $l['paginas'] ?: '—' ?></td>
                            <td>
                                <span class="badge <?= $l['activo']?'badge-green':'badge-red' ?>">
                                    <?= $l['activo']?'Activo':'Inactivo' ?>
                                </span>
                                <?php if ($l['destacado']): ?>
                                <span class="badge badge-blue" style="margin-top:3px;display:block;">⭐ Destacado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:6px;flex-wrap:wrap;">
                                    <a href="?edit=<?= $l['id'] ?>" class="action-btn action-btn-edit">Editar</a>
                                    <a href="<?= APP_URL ?>/pages/libro.php?slug=<?= $l['slug'] ?>" target="_blank"
                                       class="action-btn action-btn-edit">Ver</a>
                                    <a href="?del=<?= $l['id'] ?>" class="action-btn action-btn-del"
                                       onclick="return confirm('¿Eliminár «<?= sanitize($l['titulo']) ?>»?\nEsto también borrará el PDF del servidor.')">Eliminar</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Preview de imagen portada
function previewImg(input, previewId, zoneId) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        let img = document.getElementById(previewId);
        if (!img || img.tagName !== 'IMG') {
            img = document.createElement('img');
            img.id = previewId;
            img.style = 'height:80px;object-fit:cover;border-radius:4px;margin-bottom:8px;';
            document.getElementById(zoneId).prepend(img);
        }
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// Mostrar nombre del PDF y path de destino
function showPdfName(input) {
    const file = input.files[0];
    if (!file) return;
    document.getElementById('pdf-icon').textContent = '✅';
    document.getElementById('pdf-label').textContent = file.name;

    updatePdfPath();
}

function updatePdfPath() {
    const pdfInput = document.getElementById('inp-pdf');
    if (!pdfInput.files[0]) return;

    const subSelect = document.getElementById('sel-sub');
    const subText   = subSelect.options[subSelect.selectedIndex]?.text?.trim() || '';
    const slug      = subText
        .toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/[\s-]+/g, '-')
        .replace(/^-|-$/g, '') || 'sin-categoria';

    const fileName = pdfInput.files[0].name
        .toLowerCase()
        .replace(/\s+/g, '-');

    document.getElementById('pdf-path-preview').textContent = `uploads/${slug}/${fileName}`;
    document.getElementById('pdf-destino').style.display = 'block';
}

document.getElementById('sel-sub').addEventListener('change', updatePdfPath);

// Drag & drop para las zonas de upload
['zone-portada','zone-pdf'].forEach(zoneId => {
    const zone = document.getElementById(zoneId);
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('drag-over');
        const inputId = zoneId === 'zone-portada' ? 'inp-portada' : 'inp-pdf';
        const input   = document.getElementById(inputId);
        input.files   = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
