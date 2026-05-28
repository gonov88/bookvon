<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode([]); exit; }

$db = getDB();
$stmt = $db->prepare(
    "SELECT l.id, l.titulo, l.slug, a.nombre AS autor_nombre, c.nombre AS categoria_nombre, l.portada
     FROM libros l
     JOIN categorias c ON l.categoria_id = c.id
     LEFT JOIN autores a ON l.autor_id = a.id
     WHERE l.activo = 1 AND (l.titulo LIKE ? OR a.nombre LIKE ? OR c.nombre LIKE ?)
     ORDER BY l.destacado DESC, l.creado_en DESC
     LIMIT 6"
);
$stmt->execute(["%$q%", "%$q%", "%$q%"]);
$rows = $stmt->fetchAll();

$results = array_map(function($r) {
    // Resolver URL de portada
    $portada = '';
    if (!empty($r['portada'])) {
        $portada = (str_starts_with($r['portada'], 'http')) ? $r['portada'] : APP_URL . '/' . ltrim($r['portada'], '/');
    }
    return [
        'titulo'    => $r['titulo'],
        'slug'      => $r['slug'],
        'autor'     => $r['autor_nombre'] ?? '',
        'categoria' => $r['categoria_nombre'] ?? '',
        'portada'   => $portada,
        'url'       => APP_URL . '/pages/libro.php?slug=' . urlencode($r['slug']),
    ];
}, $rows);

echo json_encode($results);
