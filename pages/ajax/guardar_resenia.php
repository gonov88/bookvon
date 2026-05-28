<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Debés iniciar sesión para dejar una reseña.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
    exit;
}

$libroId   = (int)($_POST['libro_id']   ?? 0);
$puntuacion = (int)($_POST['puntuacion'] ?? 0);
$comentario = trim($_POST['comentario']  ?? '');

if (!$libroId || $puntuacion < 1 || $puntuacion > 5) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos.']);
    exit;
}

// Verificar que el libro existe
$db = getDB();
$check = $db->prepare("SELECT id FROM libros WHERE id=? AND activo=1");
$check->execute([$libroId]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Libro no encontrado.']);
    exit;
}

$userId = $_SESSION['usuario_id'];

// ¿Ya tiene reseña? → UPDATE, si no → INSERT
$exist = $db->prepare("SELECT id FROM resenias WHERE usuario_id=? AND libro_id=?");
$exist->execute([$userId, $libroId]);

if ($exist->fetch()) {
    $db->prepare("UPDATE resenias SET puntuacion=?, comentario=? WHERE usuario_id=? AND libro_id=?")
       ->execute([$puntuacion, $comentario ?: null, $userId, $libroId]);
    echo json_encode(['success' => true, 'msg' => 'Reseña actualizada.']);
} else {
    $db->prepare("INSERT INTO resenias (usuario_id, libro_id, puntuacion, comentario) VALUES (?,?,?,?)")
       ->execute([$userId, $libroId, $puntuacion, $comentario ?: null]);
    echo json_encode(['success' => true, 'msg' => 'Reseña publicada. ¡Gracias!']);
}
