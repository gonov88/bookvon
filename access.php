<?php
// ── Protección temporal desactivada ──
// La Basic Auth fue removida para facilitar las pruebas.
// Para reactivarla, descomentar el bloque de abajo.

/*
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!empty($_SESSION['usuario_id'])) { return; }

$usuario = 'bookvon';
$clave   = 'bookvon2026';

if (!isset($_SERVER['PHP_AUTH_USER']) ||
    $_SERVER['PHP_AUTH_USER'] !== $usuario ||
    $_SERVER['PHP_AUTH_PW']   !== $clave) {
    header('WWW-Authenticate: Basic realm="bookvon — Acceso restringido"');
    header('HTTP/1.0 401 Unauthorized');
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:60px">
          <h2>&#128274; Acceso restringido</h2>
          <p>Este sitio está en modo prueba.</p>
          </body></html>';
    exit;
}
*/
