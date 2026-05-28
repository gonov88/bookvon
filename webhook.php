<?php
// ─────────────────────────────────────────────────────────────
// webhook.php  — Bookvon + Paddle Sandbox
// Recibe eventos de Paddle y actualiza la BD u529882187_bookvon
// ─────────────────────────────────────────────────────────────
require_once __DIR__ . '/includes/config.php';

define('LOG_FILE', __DIR__ . '/webhook_log.txt');

function logMsg(string $msg): void {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . " | " . $msg . "\n", FILE_APPEND);
}

function getEmailFromPaddle(string $customer_id): string {
    if (empty($customer_id)) return '';
    $ch = curl_init(PADDLE_API_BASE . "/customers/" . $customer_id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . PADDLE_API_KEY]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);
    if ($curlError) { logMsg("getEmailFromPaddle ERROR: $curlError"); return ''; }
    $data = json_decode($response, true);
    $email = $data['data']['email'] ?? '';
    logMsg("getEmailFromPaddle: customer_id=$customer_id email=$email");
    return $email;
}

// 1. Capturar payload raw
$payload = '';
$input = fopen('php://input', 'r');
while ($chunk = fread($input, 1024)) { $payload .= $chunk; }
fclose($input);

logMsg("METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));
logMsg("PAYLOAD: " . $payload);

// 2. Verificar firma
$signatureHeader = $_SERVER['HTTP_PADDLE_SIGNATURE'] ?? '';
if (empty($signatureHeader)) {
    logMsg("ERROR: Cabecera Paddle-Signature ausente.");
    http_response_code(401); die(json_encode(['error' => 'Missing signature']));
}

$parts = [];
foreach (explode(';', $signatureHeader) as $part) {
    [$k, $v] = explode('=', $part, 2);
    $parts[$k] = $v;
}
$timestamp    = $parts['ts'] ?? '';
$receivedHash = $parts['h1'] ?? '';

if (empty($timestamp) || empty($receivedHash)) {
    logMsg("ERROR: Firma mal formada.");
    http_response_code(401); die(json_encode(['error' => 'Malformed signature']));
}

$expectedHash = hash_hmac('sha256', $timestamp . ':' . $payload, PADDLE_SECRET_KEY);
if (!hash_equals($expectedHash, $receivedHash)) {
    logMsg("ERROR: Firma inválida.");
    http_response_code(401); die(json_encode(['error' => 'Invalid signature']));
}
if (abs(time() - (int)$timestamp) > 300) {
    logMsg("ERROR: Evento demasiado antiguo.");
    http_response_code(400); die(json_encode(['error' => 'Event too old']));
}
logMsg("Firma verificada OK.");

// 3. Parsear JSON
$data = json_decode($payload, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    logMsg("ERROR: JSON inválido."); http_response_code(400); die();
}
$evento = $data['event_type'] ?? '';
logMsg("EVENTO: $evento");

// 4. Conexión BD via PDO (usa la configuración unificada)
$db = getDB();

// 5. Asegurar que existe la tabla paddle_suscripciones
$db->exec("
    CREATE TABLE IF NOT EXISTS `paddle_suscripciones` (
        `id`                 int(11) NOT NULL AUTO_INCREMENT,
        `paddle_customer_id` varchar(100) DEFAULT NULL,
        `paddle_sub_id`      varchar(100) DEFAULT NULL,
        `email`              varchar(255) DEFAULT NULL,
        `status`             varchar(50)  DEFAULT 'pending',
        `plan`               varchar(100) DEFAULT 'bookvon $1.99/mes',
        `creado_en`          datetime     DEFAULT CURRENT_TIMESTAMP,
        `actualizado_en`     datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `paddle_sub_id` (`paddle_sub_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// 6. Procesar evento y sincronizar con tabla usuarios
switch ($evento) {

    case 'subscription.created':
    case 'subscription.activated':
        $sub_id  = $data['data']['id']          ?? '';
        $cust_id = $data['data']['customer_id'] ?? '';
        $status  = $data['data']['status']      ?? 'active';
        $email   = $data['data']['customer']['email'] ?? '';
        if (empty($email) && !empty($cust_id)) {
            $email = getEmailFromPaddle($cust_id);
        }

        $stmt = $db->prepare("
            INSERT INTO paddle_suscripciones (paddle_customer_id, paddle_sub_id, email, status)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                status         = VALUES(status),
                email          = IF(email = '' OR email IS NULL, VALUES(email), email),
                actualizado_en = NOW()
        ");
        $stmt->execute([$cust_id, $sub_id, $email, $status]);
        logMsg("OK: paddle_suscripcion upsert. sub_id=$sub_id email=$email status=$status");

        // Sincronizar rol en usuarios
        if ($email && $status === 'active') {
            $db->prepare("UPDATE usuarios SET rol='suscriptor' WHERE email=? AND rol='visitante'")
               ->execute([$email]);
            // Insertar suscripción en tabla clásica si no existe activa
            $userStmt = $db->prepare("SELECT id FROM usuarios WHERE email=?");
            $userStmt->execute([$email]);
            $usuario = $userStmt->fetch();
            if ($usuario) {
                $uid = $usuario['id'];
                $existSub = $db->prepare("SELECT id FROM suscripciones WHERE usuario_id=? AND estado='activa' AND fecha_fin>=CURDATE()");
                $existSub->execute([$uid]);
                if (!$existSub->fetch()) {
                    $planId = (int)($db->query("SELECT id FROM planes WHERE activo=1 LIMIT 1")->fetchColumn());
                    if ($planId) {
                        $fin = date('Y-m-d', strtotime('+1 month'));
                        $db->prepare("INSERT INTO suscripciones (usuario_id, plan_id, estado, fecha_inicio, fecha_fin, tipo) VALUES (?,?,'activa',CURDATE(),?,'mensual')")
                           ->execute([$uid, $planId, $fin]);
                        logMsg("OK: suscripcion clasica creada para usuario_id=$uid");
                    }
                }
            }
        }
        break;

    case 'subscription.updated':
        $sub_id = $data['data']['id']     ?? '';
        $status = $data['data']['status'] ?? 'active';
        $db->prepare("UPDATE paddle_suscripciones SET status=?, actualizado_en=NOW() WHERE paddle_sub_id=?")
           ->execute([$status, $sub_id]);
        logMsg("OK: suscripcion actualizada. sub_id=$sub_id status=$status");
        break;

    case 'subscription.canceled':
        $sub_id = $data['data']['id'] ?? '';
        $db->prepare("UPDATE paddle_suscripciones SET status='canceled', actualizado_en=NOW() WHERE paddle_sub_id=?")
           ->execute([$sub_id]);
        logMsg("OK: suscripcion cancelada. sub_id=$sub_id");

        // Cancelar también en tabla clásica si aplica
        $ps = $db->prepare("SELECT email FROM paddle_suscripciones WHERE paddle_sub_id=?");
        $ps->execute([$sub_id]);
        $row = $ps->fetch();
        if ($row && $row['email']) {
            $uStmt = $db->prepare("SELECT id FROM usuarios WHERE email=?");
            $uStmt->execute([$row['email']]);
            $u = $uStmt->fetch();
            if ($u) {
                $db->prepare("UPDATE suscripciones SET estado='cancelada' WHERE usuario_id=? AND estado='activa'")
                   ->execute([$u['id']]);
                $db->prepare("UPDATE usuarios SET rol='visitante' WHERE id=? AND rol='suscriptor'")
                   ->execute([$u['id']]);
                logMsg("OK: usuario " . $row['email'] . " degradado a visitante.");
            }
        }
        break;

    default:
        logMsg("EVENTO no manejado: $evento");
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
