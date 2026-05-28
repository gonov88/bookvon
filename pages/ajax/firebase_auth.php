<?php
/**
 * Endpoint AJAX: recibe idToken de Firebase/Google y loguea al usuario.
 * POST { idToken: string }
 * Responde JSON { success, redirect } o { error }
 */
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Método no permitido.']);
    exit;
}

$body    = json_decode(file_get_contents('php://input'), true);
$idToken = trim($body['idToken'] ?? '');

if (!$idToken) {
    echo json_encode(['error' => 'Token vacío.']);
    exit;
}

try {
    $result = loginWithFirebase($idToken);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
    exit;
}

if (isset($result['error'])) {
    echo json_encode(['error' => $result['error']]);
    exit;
}

$redirect = $_GET['redirect'] ?? APP_URL . '/pages/catalogo.php';
echo json_encode(['success' => true, 'redirect' => $redirect]);
