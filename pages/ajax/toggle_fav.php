<?php
require_once __DIR__ . '/../../includes/config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'msg' => 'Debés iniciar sesión para guardar favoritos.']);
    exit;
}

$libroId = (int)($_POST['libro_id'] ?? 0);
if (!$libroId) { echo json_encode(['success' => false]); exit; }

$db = getDB();
$userId = $_SESSION['usuario_id'];

$check = $db->prepare("SELECT id FROM favoritos WHERE usuario_id=? AND libro_id=?");
$check->execute([$userId, $libroId]);

if ($check->fetch()) {
    $db->prepare("DELETE FROM favoritos WHERE usuario_id=? AND libro_id=?")->execute([$userId, $libroId]);
    echo json_encode(['success' => true, 'isFav' => false]);
} else {
    $db->prepare("INSERT INTO favoritos (usuario_id, libro_id) VALUES (?,?)")->execute([$userId, $libroId]);
    echo json_encode(['success' => true, 'isFav' => true]);
}
