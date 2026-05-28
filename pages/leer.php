<?php
require_once __DIR__ . '/../includes/config.php';
requireSubscription();

$db      = getDB();
$libroId = (int)($_GET['id'] ?? 0);
if (!$libroId) { redirect(APP_URL . '/pages/catalogo.php'); }

$stmt = $db->prepare("SELECT l.*, c.nombre AS categoria_nombre, a.nombre AS autor_nombre
                      FROM libros l
                      JOIN categorias c ON l.categoria_id=c.id
                      LEFT JOIN autores a ON l.autor_id=a.id
                      WHERE l.id=? AND l.activo=1");
$stmt->execute([$libroId]);
$libro = $stmt->fetch();
if (!$libro) { redirect(APP_URL . '/pages/catalogo.php'); }

$userId = $_SESSION['usuario_id'];

// Obtener/crear historial
$hStmt = $db->prepare("SELECT * FROM historial_lectura WHERE usuario_id=? AND libro_id=?");
$hStmt->execute([$userId, $libroId]);
$historial = $hStmt->fetch();

// Guardar página actual si viene por POST (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagina'])) {
    header('Content-Type: application/json');
    $pag = max(1, (int)$_POST['pagina']);
    $completado = ($libro['paginas'] && $pag >= $libro['paginas']) ? 1 : 0;
    if ($historial) {
        $db->prepare("UPDATE historial_lectura SET pagina_actual=?,completado=?,ultima_lectura=NOW() WHERE usuario_id=? AND libro_id=?")
           ->execute([$pag, $completado, $userId, $libroId]);
    } else {
        $db->prepare("INSERT INTO historial_lectura (usuario_id,libro_id,pagina_actual,completado) VALUES (?,?,?,?)")
           ->execute([$userId, $libroId, $pag, $completado]);
    }
    echo json_encode(['ok' => true, 'pagina' => $pag]);
    exit;
}

// Registrar primera lectura si no existe
if (!$historial) {
    $db->prepare("INSERT INTO historial_lectura (usuario_id,libro_id,pagina_actual) VALUES (?,?,1)")
       ->execute([$userId, $libroId]);
    $paginaActual = 1;
} else {
    $paginaActual = (int)$historial['pagina_actual'];
}

// Imagen de portada para el placeholder
$_CI = [
    'Negocios y Finanzas'                  => 'https://images.unsplash.com/photo-1554224155-6726b3ff858f?w=400&q=80',
    'Desarrollo Personal'                  => 'https://images.unsplash.com/photo-1506784983877-45594efa4cbe?w=400&q=80',
    'Tecnología e Inteligencia Artificial' => 'https://images.unsplash.com/photo-1677442135703-1787eea5ce01?w=400&q=80',
    'Programación y Desarrollo Web'        => 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=400&q=80',
    'Educación y Académico'                => 'https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=400&q=80',
    'Salud y Bienestar'                    => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=400&q=80',
    'Cocina y Gastronomía'                 => 'https://images.unsplash.com/photo-1466637574441-749b8f19452f?w=400&q=80',
    'Ciencia Ficción y Fantasía'           => 'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?w=400&q=80',
    'Romance y Drama'                      => 'https://images.unsplash.com/photo-1474552226712-ac0f0961a954?w=400&q=80',
    'Historia y Política'                  => 'https://images.unsplash.com/photo-1447069387593-a5de0862481e?w=400&q=80',
    'Arte, Diseño y Creatividad'           => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?w=400&q=80',
    'Infantil y Juvenil'                   => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=400&q=80',
];
// Imagen de portada — puede ser URL completa (Unsplash) o ruta relativa (uploads/)
function buildImgUrl(string $portada): string {
    if (empty($portada)) return '';
    // Ya es URL absoluta (http/https)
    if (str_starts_with($portada, 'http://') || str_starts_with($portada, 'https://')) {
        return $portada;
    }
    // Ruta relativa → anteponer APP_URL
    return APP_URL . '/' . ltrim($portada, '/');
}

$coverImg = buildImgUrl(
    !empty($libro['portada'])
        ? $libro['portada']
        : ($_CI[$libro['categoria_nombre']] ?? '')
);
$pdfUrl   = !empty($libro['archivo_pdf']) ? APP_URL . '/' . $libro['archivo_pdf'] : '';
$pct      = ($libro['paginas'] && $paginaActual) ? min(100, round($paginaActual / $libro['paginas'] * 100)) : 0;
$pageTitle = 'Leyendo: ' . $libro['titulo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($pageTitle) ?> — <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --blue-dark:  #0F3460;
            --blue-mid:   #1a4a8a;
            --blue-light: #E8F0FE;
            --gray-900:   #1A1A1A;
            --gray-700:   #404040;
            --gray-500:   #737373;
            --gray-300:   #D4D4D4;
            --gray-100:   #F5F5F5;
            --white:      #FFFFFF;
            --toolbar-h:  52px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; overflow: hidden; font-family: 'DM Sans', sans-serif; background: #1a1a2e; }
        a { text-decoration: none; color: inherit; }

        /* ── TOOLBAR ── */
        .reader-toolbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: var(--toolbar-h);
            background: #0d1117;
            border-bottom: 1px solid rgba(255,255,255,.1);
            display: flex; align-items: center; gap: 0;
            padding: 0 16px;
        }
        .rt-back {
            display: flex; align-items: center; gap: 7px;
            color: #94A3B8; font-size: 13px; padding: 6px 12px;
            border-radius: 5px; transition: all .18s; white-space: nowrap;
        }
        .rt-back:hover { background: rgba(255,255,255,.08); color: white; }
        .rt-divider { width: 1px; height: 24px; background: rgba(255,255,255,.12); margin: 0 12px; flex-shrink: 0; }
        .rt-cover {
            width: 26px; height: 34px; border-radius: 3px; object-fit: cover;
            flex-shrink: 0;
        }
        .rt-title-block { flex: 1; min-width: 0; padding: 0 12px; }
        .rt-title {
            font-size: 14px; font-weight: 600; color: white;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .rt-author { font-size: 11.5px; color: #64748B; margin-top: 1px; }
        .rt-progress-wrap { display: flex; align-items: center; gap: 10px; }
        .rt-progress-bar {
            width: 120px; height: 4px; background: rgba(255,255,255,.15); border-radius: 2px;
        }
        .rt-progress-fill { height: 100%; background: #3B82F6; border-radius: 2px; transition: width .4s ease; }
        .rt-progress-pct { font-size: 11.5px; color: #64748B; white-space: nowrap; }
        .rt-actions { display: flex; align-items: center; gap: 4px; }
        .rt-btn {
            display: flex; align-items: center; gap: 5px;
            padding: 6px 11px; border-radius: 5px;
            font-size: 12.5px; color: #94A3B8;
            transition: all .18s; cursor: pointer; border: none; background: none;
            font-family: inherit;
        }
        .rt-btn:hover { background: rgba(255,255,255,.08); color: white; }
        .rt-btn.active { background: rgba(59,130,246,.2); color: #60A5FA; }

        /* ── LAYOUT PRINCIPAL ── */
        .reader-layout {
            position: fixed;
            top: var(--toolbar-h); left: 0; right: 0; bottom: 0;
            display: grid;
            grid-template-columns: 1fr;
            transition: grid-template-columns .25s ease;
        }
        .reader-layout.with-panel { grid-template-columns: 1fr 300px; }

        /* ── ÁREA DEL PDF ── */
        .reader-pdf-area {
            position: relative;
            height: 100%;
            background: #525659;
            overflow: hidden;
        }
        .reader-iframe {
            width: 100%; height: 100%;
            border: none; display: block;
        }

        /* ── PLACEHOLDER (sin PDF) ── */
        .reader-placeholder {
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            height: 100%; gap: 20px; text-align: center;
            padding: 40px;
        }
        .rp-cover {
            width: 140px; border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0,0,0,.5);
        }
        .rp-title {
            font-family: 'Playfair Display', serif;
            font-size: 26px; font-weight: 700;
            color: white; max-width: 500px; line-height: 1.3;
        }
        .rp-author { font-size: 15px; color: #94A3B8; }
        .rp-desc { font-size: 14.5px; color: #64748B; max-width: 500px; line-height: 1.7; }
        .rp-alert {
            background: rgba(59,130,246,.15);
            border: 1px solid rgba(59,130,246,.3);
            color: #93C5FD; padding: 14px 20px;
            border-radius: 8px; font-size: 13.5px;
            max-width: 480px; line-height: 1.6;
        }

        /* ── PANEL LATERAL ── */
        .reader-panel {
            background: #0d1117;
            border-left: 1px solid rgba(255,255,255,.1);
            height: 100%; overflow-y: auto;
            display: flex; flex-direction: column;
        }
        .panel-section {
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }
        .panel-section-title {
            font-size: 10.5px; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1.2px; color: #475569; margin-bottom: 14px;
        }
        /* Info del libro en panel */
        .panel-book-cover {
            width: 80px; border-radius: 5px; margin: 0 auto 12px;
            display: block; box-shadow: 0 8px 24px rgba(0,0,0,.4);
        }
        .panel-book-title {
            font-family: 'Playfair Display', serif;
            font-size: 15px; font-weight: 700; color: white;
            text-align: center; line-height: 1.4; margin-bottom: 4px;
        }
        .panel-book-author { font-size: 12px; color: #64748B; text-align: center; margin-bottom: 14px; }
        /* Meta chips */
        .panel-meta { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }
        .panel-chip {
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px; padding: 4px 10px;
            font-size: 11.5px; color: #94A3B8;
        }
        /* Progreso circular + barra */
        .panel-progress { text-align: center; }
        .progress-ring-wrap {
            position: relative; width: 90px; height: 90px; margin: 0 auto 10px;
        }
        .progress-ring-bg { fill: none; stroke: rgba(255,255,255,.08); stroke-width: 6; }
        .progress-ring-fill {
            fill: none; stroke: #3B82F6; stroke-width: 6;
            stroke-linecap: round;
            stroke-dasharray: 226;
            stroke-dashoffset: <?= 226 - round(226 * $pct / 100) ?>;
            transform: rotate(-90deg); transform-origin: 50% 50%;
            transition: stroke-dashoffset .6s ease;
        }
        .progress-ring-text {
            position: absolute; inset: 0;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
        }
        .progress-ring-pct { font-size: 20px; font-weight: 700; color: white; line-height: 1; }
        .progress-ring-label { font-size: 10px; color: #64748B; margin-top: 2px; }
        .panel-progress-detail {
            font-size: 12px; color: #64748B; margin-top: 6px;
        }
        /* Actualizar página manual */
        .panel-page-form {
            display: flex; gap: 8px; align-items: center; margin-top: 12px;
        }
        .panel-page-form input {
            flex: 1; padding: 7px 10px;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 5px; color: white;
            font-size: 13px; font-family: inherit; outline: none;
            text-align: center;
        }
        .panel-page-form input:focus { border-color: #3B82F6; }
        .panel-page-form button {
            padding: 7px 14px; background: #3B82F6; color: white;
            border: none; border-radius: 5px; font-size: 12.5px;
            font-weight: 600; cursor: pointer; transition: all .18s;
            font-family: inherit;
        }
        .panel-page-form button:hover { background: #2563EB; }
        /* Acciones rápidas */
        .panel-actions { display: flex; flex-direction: column; gap: 8px; }
        .panel-action-btn {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; border-radius: 7px;
            background: rgba(255,255,255,.05);
            border: 1px solid rgba(255,255,255,.08);
            color: #94A3B8; font-size: 13px;
            transition: all .18s; cursor: pointer;
            font-family: inherit; text-align: left;
        }
        .panel-action-btn:hover { background: rgba(255,255,255,.1); color: white; }
        .panel-action-btn svg { flex-shrink: 0; }
        /* Nota completado */
        .panel-completed {
            background: rgba(34,197,94,.12);
            border: 1px solid rgba(34,197,94,.25);
            border-radius: 8px; padding: 12px 14px;
            text-align: center; color: #86EFAC;
            font-size: 13px; line-height: 1.5;
        }

        /* Scrollbar oscuro para el panel */
        .reader-panel::-webkit-scrollbar { width: 5px; }
        .reader-panel::-webkit-scrollbar-track { background: transparent; }
        .reader-panel::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 3px; }

        /* Responsive */
        @media (max-width: 768px) {
            .reader-layout.with-panel { grid-template-columns: 1fr; }
            .reader-panel { display: none; }
            .reader-layout.with-panel .reader-panel { display: flex; }
            .reader-layout.with-panel .reader-pdf-area { display: none; }
            .rt-progress-wrap { display: none; }
        }
        @media (max-width: 480px) {
            .rt-author, .rt-divider { display: none; }
        }
    </style>
</head>
<body>

<!-- ── TOOLBAR ── -->
<div class="reader-toolbar">
    <a href="<?= APP_URL ?>/pages/libro.php?slug=<?= urlencode($libro['slug']) ?>" class="rt-back">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Volver
    </a>
    <div class="rt-divider"></div>
    <?php if ($coverImg): ?>
    <img src="<?= $coverImg ?>" class="rt-cover" alt="">
    <?php endif; ?>
    <div class="rt-title-block">
        <div class="rt-title"><?= sanitize($libro['titulo']) ?></div>
        <?php if ($libro['autor_nombre']): ?>
        <div class="rt-author"><?= sanitize($libro['autor_nombre']) ?></div>
        <?php endif; ?>
    </div>
    <div class="rt-progress-wrap">
        <div class="rt-progress-bar">
            <div class="rt-progress-fill" id="tb-progress" style="width:<?= $pct ?>%"></div>
        </div>
        <span class="rt-progress-pct" id="tb-pct"><?= $pct ?>%</span>
    </div>
    <div class="rt-divider"></div>
    <div class="rt-actions">
        <button class="rt-btn" id="btn-panel" onclick="togglePanel()" title="Panel lateral">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
            Panel
        </button>
        <button class="rt-btn" id="btn-fs" onclick="toggleFullscreen()" title="Pantalla completa">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/></svg>
            Pantalla completa
        </button>
        <?php if ($pdfUrl): ?>
        <a href="<?= $pdfUrl ?>" download class="rt-btn" title="Descargar PDF">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Descargar
        </a>
        <?php endif; ?>
    </div>
</div>

<!-- ── LAYOUT ── -->
<div class="reader-layout" id="readerLayout">

    <!-- PDF -->
    <div class="reader-pdf-area">
        <?php if ($pdfUrl): ?>
        <iframe
            src="<?= $pdfUrl ?>#toolbar=1&navpanes=0&scrollbar=1&page=<?= $paginaActual ?>"
            class="reader-iframe"
            id="pdfFrame"
            title="Visor de ebook: <?= sanitize($libro['titulo']) ?>"
            allowfullscreen>
        </iframe>
        <?php else: ?>
        <div class="reader-placeholder">
            <?php if ($coverImg): ?>
            <img src="<?= $coverImg ?>" class="rp-cover" alt="<?= sanitize($libro['titulo']) ?>">
            <?php endif; ?>
            <h1 class="rp-title"><?= sanitize($libro['titulo']) ?></h1>
            <?php if ($libro['autor_nombre']): ?>
            <p class="rp-author">por <?= sanitize($libro['autor_nombre']) ?></p>
            <?php endif; ?>
            <?php if ($libro['descripcion']): ?>
            <p class="rp-desc"><?= nl2br(sanitize(substr($libro['descripcion'], 0, 300))) ?>...</p>
            <?php endif; ?>
            <div class="rp-alert">
                📄 El archivo PDF de este libro todavía no fue subido.<br>
                El administrador puede subirlo desde el <strong>Panel Admin → Libros → Editar</strong>.
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- PANEL LATERAL -->
    <aside class="reader-panel" id="readerPanel">

        <!-- Info del libro -->
        <div class="panel-section">
            <div class="panel-section-title">Libro actual</div>
            <?php if ($coverImg): ?>
            <img src="<?= $coverImg ?>" class="panel-book-cover" alt="">
            <?php endif; ?>
            <div class="panel-book-title"><?= sanitize($libro['titulo']) ?></div>
            <?php if ($libro['autor_nombre']): ?>
            <div class="panel-book-author"><?= sanitize($libro['autor_nombre']) ?></div>
            <?php endif; ?>
            <div class="panel-meta">
                <span class="panel-chip">📚 <?= sanitize($libro['categoria_nombre']) ?></span>
                <?php if ($libro['paginas']): ?>
                <span class="panel-chip">📄 <?= $libro['paginas'] ?> págs.</span>
                <?php endif; ?>
                <span class="panel-chip">🌐 <?= sanitize($libro['idioma'] ?? 'Español') ?></span>
            </div>
        </div>

        <!-- Progreso de lectura -->
        <div class="panel-section">
            <div class="panel-section-title">Tu progreso</div>
            <div class="panel-progress">
                <div class="progress-ring-wrap">
                    <svg width="90" height="90" viewBox="0 0 90 90">
                        <circle class="progress-ring-bg" cx="45" cy="45" r="36"/>
                        <circle class="progress-ring-fill" id="ring-fill" cx="45" cy="45" r="36"/>
                    </svg>
                    <div class="progress-ring-text">
                        <span class="progress-ring-pct" id="ring-pct"><?= $pct ?>%</span>
                        <span class="progress-ring-label">leído</span>
                    </div>
                </div>
                <?php if ($libro['paginas']): ?>
                <div class="panel-progress-detail" id="panel-page-detail">
                    Página <strong id="span-pag-actual"><?= $paginaActual ?></strong> de <?= $libro['paginas'] ?>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($historial && $historial['completado']): ?>
            <div class="panel-completed" style="margin-top:14px;">
                ✅ ¡Libro completado!<br>
                <span style="font-size:11.5px;">Lo terminaste el <?= date('d/m/Y', strtotime($historial['ultima_lectura'])) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($libro['paginas']): ?>
            <div class="panel-page-form">
                <input type="number" id="inp-page" min="1" max="<?= $libro['paginas'] ?>"
                       value="<?= $paginaActual ?>" placeholder="Pág.">
                <button onclick="savePage()">Guardar</button>
            </div>
            <div style="font-size:11px;color:#475569;margin-top:6px;text-align:center;">
                Actualizá tu página para guardar el progreso
            </div>
            <?php endif; ?>
        </div>

        <!-- Acciones rápidas -->
        <div class="panel-section">
            <div class="panel-section-title">Acciones</div>
            <div class="panel-actions">
                <a href="<?= APP_URL ?>/pages/libro.php?slug=<?= urlencode($libro['slug']) ?>" class="panel-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>
                    Ver ficha del libro
                </a>
                <?php if ($pdfUrl): ?>
                <a href="<?= $pdfUrl ?>" download class="panel-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Descargar PDF
                </a>
                <?php endif; ?>
                <a href="<?= APP_URL ?>/pages/catalogo.php?categoria=<?= $libro['categoria_id'] ?>" class="panel-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                    Más de <?= sanitize($libro['categoria_nombre']) ?>
                </a>
                <a href="<?= APP_URL ?>/pages/mi-biblioteca.php" class="panel-action-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    Mi biblioteca
                </a>
            </div>
        </div>

    </aside>
</div>

<script>
const LIBRO_ID   = <?= $libroId ?>;
const TOTAL_PAGS = <?= $libro['paginas'] ?: 0 ?>;
const SAVE_URL   = '<?= APP_URL ?>/pages/leer.php?id=<?= $libroId ?>';

let panelOpen = true;

// Abrir panel por defecto en desktop
function initPanel() {
    if (window.innerWidth < 900) {
        panelOpen = false;
        document.getElementById('readerLayout').classList.remove('with-panel');
    } else {
        panelOpen = true;
        document.getElementById('readerLayout').classList.add('with-panel');
        document.getElementById('btn-panel').classList.add('active');
    }
}
initPanel();

function togglePanel() {
    panelOpen = !panelOpen;
    document.getElementById('readerLayout').classList.toggle('with-panel', panelOpen);
    document.getElementById('btn-panel').classList.toggle('active', panelOpen);
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen?.();
        document.getElementById('btn-fs').classList.add('active');
    } else {
        document.exitFullscreen?.();
        document.getElementById('btn-fs').classList.remove('active');
    }
}

// Guardar página actual
async function savePage() {
    const inp = document.getElementById('inp-page');
    if (!inp) return;
    const pag = Math.max(1, Math.min(parseInt(inp.value) || 1, TOTAL_PAGS || 9999));
    inp.value = pag;

    try {
        const fd = new FormData();
        fd.append('pagina', pag);
        const res  = await fetch(SAVE_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) updateProgress(pag);
    } catch(e) { console.warn('No se pudo guardar el progreso'); }
}

function updateProgress(pag) {
    if (!TOTAL_PAGS) return;
    const pct = Math.min(100, Math.round(pag / TOTAL_PAGS * 100));
    const circumference = 226;
    const offset = circumference - Math.round(circumference * pct / 100);

    // Ring SVG
    const ring = document.getElementById('ring-fill');
    if (ring) ring.style.strokeDashoffset = offset;

    // Textos
    const ringPct = document.getElementById('ring-pct');
    const tbPct   = document.getElementById('tb-pct');
    const tbBar   = document.getElementById('tb-progress');
    const spanPag = document.getElementById('span-pag-actual');

    if (ringPct) ringPct.textContent = pct + '%';
    if (tbPct)   tbPct.textContent   = pct + '%';
    if (tbBar)   tbBar.style.width   = pct + '%';
    if (spanPag) spanPag.textContent = pag;
}

// Guardar automáticamente cada 2 minutos
setInterval(() => {
    const inp = document.getElementById('inp-page');
    if (inp && parseInt(inp.value) > 0) savePage();
}, 120000);
</script>
</body>
</html>
