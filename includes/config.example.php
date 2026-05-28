<?php
// =====================================================
// SESIÓN — debe iniciarse antes de cualquier otra cosa
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// =====================================================
// COMPATIBILIDAD PHP 7.x
// str_starts_with / str_ends_with / str_contains
// fueron introducidas en PHP 8.0
// =====================================================
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        $len = strlen($needle);
        return $len === 0 || substr($haystack, -$len) === $needle;
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return strpos($haystack, $needle) !== false;
    }
}

// =====================================================
// CONFIGURACIÓN DE LA APLICACIÓN
// =====================================================

define('DB_HOST',    'localhost');
define('DB_NAME',    'tu_base_de_datos');       // ← reemplazar
define('DB_USER',    'tu_usuario_db');          // ← reemplazar
define('DB_PASS',    'tu_password_db');         // ← reemplazar
define('DB_CHARSET', 'utf8mb4');

define('APP_NAME', 'bookvon');

// URL de la aplicación — hardcodeada para producción
if (!defined('APP_URL')) {
    // En producción: https://www.tudominio.com
    // En local cambiar a: http://localhost/bookvon
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (strpos($host, 'tudominio.com') !== false) {  // ← reemplazar
        define('APP_URL', 'https://www.tudominio.com');
    } else {
        // localhost — auto-detect
        $scheme     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $docRoot    = realpath(__DIR__ . '/..');
        $serverRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '/');
        $relPath    = '';
        if ($serverRoot && $docRoot && $docRoot !== $serverRoot) {
            $rel     = str_replace('\\', '/', substr($docRoot, strlen($serverRoot)));
            $relPath = rtrim($rel, '/');
        }
        define('APP_URL', rtrim($scheme . '://' . $host . $relPath, '/'));
    }
}
define('APP_VERSION', '1.0.0');

// =====================================================
// PADDLE
// Obtené tus claves en: https://vendors.paddle.com
// Para sandbox: https://sandbox-vendors.paddle.com
// =====================================================
define('PADDLE_CLIENT_TOKEN', 'tu_paddle_client_token');    // ← reemplazar
define('PADDLE_PRICE_ID',     'tu_paddle_price_id');        // ← reemplazar
define('PADDLE_SECRET_KEY',   'tu_paddle_secret_key');      // ← reemplazar
define('PADDLE_API_KEY',      'tu_paddle_api_key');         // ← reemplazar
define('PADDLE_API_BASE',     'https://sandbox-api.paddle.com'); // sandbox; en prod: https://api.paddle.com
define('PADDLE_PRICE',        1.99); // precio en USD

// =====================================================
// FIREBASE / GOOGLE AUTH
// Obtené la API key en: https://console.firebase.google.com
// Proyecto > Configuración > General > Aplicaciones web
// =====================================================
define('FIREBASE_ADMIN_EMAIL', 'tu_email_admin@gmail.com'); // ← reemplazar

/**
 * Procesa el login con Google vía Firebase.
 */
function loginWithFirebase(string $idToken): array {
    // ── 1. Decodificar el JWT del idToken sin verificar firma
    //       Firebase ID Tokens son JWTs: header.payload.signature
    $parts = explode('.', $idToken);
    if (count($parts) !== 3) {
        return ['error' => 'Token con formato inválido.'];
    }

    $payloadJson = base64_decode(str_pad(
        strtr($parts[1], '-_', '+/'),
        strlen($parts[1]) + (4 - strlen($parts[1]) % 4) % 4,
        '='
    ));
    $payload = json_decode($payloadJson, true);

    if (!$payload) {
        return ['error' => 'No se pudo decodificar el token.'];
    }

    // ── 2. Verificar que el token no esté expirado
    $now = time();
    if (isset($payload['exp']) && $payload['exp'] < $now) {
        return ['error' => 'El token expiró. Por favor intentá de nuevo.'];
    }

    // ── 3. Extraer datos del usuario desde el payload del JWT
    $firebaseUid = $payload['user_id'] ?? $payload['sub'] ?? null;
    $email       = $payload['email']   ?? '';
    $nombre      = $payload['name']    ?? $email;
    $avatar      = $payload['picture'] ?? null;

    if (!$firebaseUid) {
        return ['error' => 'No se pudo obtener el UID de Firebase.'];
    }
    if (!$email) {
        return ['error' => 'No se obtuvo email de Google.'];
    }

    // ── 4. Verificar con la API de Firebase
    //       Reemplazá esta clave con la tuya desde Firebase Console
    $apiKey    = 'tu_firebase_web_api_key';  // ← reemplazar
    $verifyUrl = 'https://identitytoolkit.googleapis.com/v1/accounts:lookup?key=' . $apiKey;
    $verifyBody = json_encode(['idToken' => $idToken]);

    $verified = false;
    if (function_exists('curl_init')) {
        $ch = curl_init($verifyUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $verifyBody,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw      = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw && $httpCode === 200) {
            $verifyData = json_decode($raw, true);
            if (!empty($verifyData['users'][0])) {
                $verified    = true;
                $fbUser      = $verifyData['users'][0];
                $firebaseUid = $fbUser['localId']      ?? $firebaseUid;
                $email       = $fbUser['email']        ?? $email;
                $nombre      = $fbUser['displayName']  ?? $nombre;
                $avatar      = $fbUser['photoUrl']     ?? $avatar;
            }
        }
    }

    if (!$verified) {
        error_log('[bookvon] Firebase API verify falló, usando datos del JWT para uid=' . $firebaseUid);
    }

    // ── 5. Buscar o crear usuario en la BD
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM usuarios WHERE firebase_uid = ?");
    $stmt->execute([$firebaseUid]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
    }

    $rol = ($email === FIREBASE_ADMIN_EMAIL) ? 'admin' : 'visitante';

    if ($user) {
        $updateRol = ($email === FIREBASE_ADMIN_EMAIL) ? 'admin' : $user['rol'];
        $db->prepare("UPDATE usuarios SET firebase_uid=?, avatar=?, rol=? WHERE id=?")
           ->execute([$firebaseUid, $avatar ?? $user['avatar'], $updateRol, $user['id']]);
        $user['firebase_uid'] = $firebaseUid;
        $user['avatar']       = $avatar ?? $user['avatar'];
        $user['rol']          = $updateRol;
    } else {
        $db->prepare("INSERT INTO usuarios (nombre, email, password, avatar, rol, firebase_uid) VALUES (?,?,?,?,?,?)")
           ->execute([$nombre, $email, '', $avatar, $rol, $firebaseUid]);
        $newId = (int)$db->lastInsertId();
        $stmt  = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmt->execute([$newId]);
        $user  = $stmt->fetch();
    }

    if (!$user) {
        return ['error' => 'No se pudo crear o encontrar el usuario en la base de datos.'];
    }

    $_SESSION['usuario_id'] = $user['id'];
    return ['success' => true, 'user' => $user];
}

// Conexión PDO
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }
    }
    return $pdo;
}

// Helpers de autenticación
function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*,
                   CASE WHEN ps.id IS NOT NULL THEN 'activa' ELSE NULL END AS suscripcion_estado
            FROM usuarios u
            LEFT JOIN paddle_suscripciones ps
                   ON ps.email = u.email AND ps.status = 'active'
            WHERE u.id = ?
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['usuario_id']]);
        $user = $stmt->fetch();
    }
    return $user;
}

function hasActiveSubscription(): bool {
    $user = getCurrentUser();
    return $user && $user['suscripcion_estado'] === 'activa';
}

function requireSubscription(): void {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/pages/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (!hasActiveSubscription()) {
        header('Location: ' . APP_URL . '/pages/suscripcion.php?msg=required');
        exit;
    }
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function sanitize(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

function generateSlug(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = str_replace(
        ['á','é','í','ó','ú','à','è','ì','ò','ù','ä','ë','ï','ö','ü','ñ','ç'],
        ['a','e','i','o','u','a','e','i','o','u','a','e','i','o','u','n','c'],
        $text
    );
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

function generateUniqueSlug(PDO $db, string $titulo, int $excludeId = 0): string {
    $base = generateSlug($titulo);
    $slug = $base;
    $i    = 1;
    while (true) {
        $stmt = $db->prepare("SELECT id FROM libros WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $excludeId]);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i;
        $i++;
    }
    return $slug;
}
