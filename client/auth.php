<?php
declare(strict_types=1);

// ==========================
// Config
// ==========================

$APP_URL = getenv('APP_URL') ?: 'http://localhost:8000';
$DB_FILE = __DIR__ . '/auth.db';
$LOG_DIR = __DIR__ . '/logs';
$MAIL_LOG = $LOG_DIR . '/mail.log';

// Ensure logs directory
if (!is_dir($LOG_DIR)) {
    mkdir($LOG_DIR, 0777, true);
}

// ==========================
// Helpers
// ==========================

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect(string $url, int $status = 302): void {
    header('Location: ' . $url, true, $status);
    exit;
}

function get_db(string $dbFile): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        init_db($pdo);
    }
    return $pdo;
}

function init_db(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_tokens (
            token TEXT PRIMARY KEY,
            username TEXT NOT NULL,
            email TEXT NOT NULL,
            remember INTEGER NOT NULL,
            expires_at INTEGER NOT NULL,
            used INTEGER NOT NULL DEFAULT 0
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sessions (
            session_id TEXT PRIMARY KEY,
            username TEXT NOT NULL,
            expires_at INTEGER
        );
    ");
}

function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

function minutes_from_now(int $min): int {
    return time() + $min * 60;
}

function days_from_now(int $days): int {
    return time() + $days * 24 * 60 * 60;
}

function send_magic_link_email(string $email, string $link, string $username, string $mailLog): void {
    $subject = 'Your Cartographica login link';
    $body = "Hi {$username},\n\n"
          . "Click the link below to log in to Cartographica:\n\n"
          . "{$link}\n\n"
          . "This link will expire in 10 minutes.\n\n"
          . "If you didn't request this, you can ignore this email.\n";

    // For development: log the mail instead of actually sending
    $logLine = date('c') . " To: {$email}\nSubject: {$subject}\n\n{$body}\n---\n\n";
    file_put_contents($mailLog, $logLine, FILE_APPEND);

    // If you have mail() configured and want to actually send emails:
    // @mail($email, $subject, $body);
}

// Simple router helpers
function request_method(): string {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

function request_path(): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $pos = strpos($uri, '?');
    if ($pos !== false) {
        $uri = substr($uri, 0, $pos);
    }
    return rtrim($uri, '/') ?: '/';
}

// Parse JSON body
function parse_json_body(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

// Cookie helpers
function get_session_id_from_cookie(): ?string {
    return $_COOKIE['session'] ?? null;
}

function set_session_cookie(string $sessionId, ?int $expiresAt): void {
    $params = session_get_cookie_params();
    $options = [
        'expires'  => $expiresAt ?? 0,
        'path'     => '/',
        'domain'   => $params['domain'] ?: '',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ];
    setcookie('session', $sessionId, $options);
}

function clear_session_cookie(): void {
    setcookie('session', '', [
        'expires'  => time() - 3600,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

// Cleanup expired stuff occasionally (cheap)
function cleanup_expired(PDO $pdo): void {
    $now = time();
    // Remove expired or used tokens
    $stmt = $pdo->prepare("DELETE FROM login_tokens WHERE expires_at <= :now OR used = 1");
    $stmt->execute([':now' => $now]);

    // Remove expired sessions
    $stmt = $pdo->prepare("DELETE FROM sessions WHERE expires_at IS NOT NULL AND expires_at <= :now");
    $stmt->execute([':now' => $now]);
}

// ==========================
// Routing
// ==========================

$method = request_method();
$path   = request_path();
$pdo    = get_db($DB_FILE);

// Basic CORS / headers (tweak if needed)
header('X-Powered-By: Cartographica-PHP-Auth');

cleanup_expired($pdo);

if ($method === 'POST' && $path === '/api/send-login-link') {
    // --------------------------
    // POST /api/send-login-link
    // --------------------------
    $body = parse_json_body();
    $username = isset($body['username']) ? trim((string)$body['username']) : '';
    $email    = isset($body['email']) ? trim((string)$body['email']) : '';
    $remember = !empty($body['remember']) ? 1 : 0;

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['error' => 'Valid email is required'], 400);
    }

    if ($username === '') {
        $username = 'Explorer';
    }

    $token     = generate_token();
    $expiresAt = minutes_from_now(10);

    $stmt = $pdo->prepare("
        INSERT INTO login_tokens (token, username, email, remember, expires_at, used)
        VALUES (:token, :username, :email, :remember, :expires_at, 0)
    ");
    $stmt->execute([
        ':token'      => $token,
        ':username'   => $username,
        ':email'      => $email,
        ':remember'   => $remember,
        ':expires_at' => $expiresAt,
    ]);

    $link = $APP_URL . '/auth?token=' . urlencode($token);
    send_magic_link_email($email, $link, $username, $MAIL_LOG);

    json_response(['ok' => true]);

} elseif ($method === 'GET' && $path === '/auth') {
    // --------------------------
    // GET /auth?token=...
    // --------------------------
    $token = isset($_GET['token']) ? (string)$_GET['token'] : '';

    if ($token === '') {
        http_response_code(400);
        echo 'Missing token';
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM login_tokens WHERE token = :token");
    $stmt->execute([':token' => $token]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
        http_response_code(400);
        echo 'Invalid or expired token';
        exit;
    }

    $now = time();

    if ((int)$record['used'] === 1 || (int)$record['expires_at'] <= $now) {
        // Mark as used/expired
        $stmt = $pdo->prepare("DELETE FROM login_tokens WHERE token = :token");
        $stmt->execute([':token' => $token]);

        http_response_code(400);
        echo 'This link is invalid or has expired';
        exit;
    }

    // Mark token as used
    $stmt = $pdo->prepare("UPDATE login_tokens SET used = 1 WHERE token = :token");
    $stmt->execute([':token' => $token]);

    // Create session
    $sessionId  = generate_token();
    $remember   = (int)$record['remember'] === 1;
    $expiresAt  = $remember ? days_from_now(180) : null; // 6 months or session-only

    $stmt = $pdo->prepare("
        INSERT INTO sessions (session_id, username, expires_at)
        VALUES (:session_id, :username, :expires_at)
    ");
    $stmt->execute([
        ':session_id' => $sessionId,
        ':username'   => $record['username'],
        ':expires_at' => $expiresAt,
    ]);

    set_session_cookie($sessionId, $expiresAt);

    // Redirect to your game root (same origin)
    redirect('/');

} elseif ($method === 'GET' && $path === '/api/me') {
    // --------------------------
    // GET /api/me
    // --------------------------
    $sessionId = get_session_id_from_cookie();

    if ($sessionId === null || $sessionId === '') {
        json_response(['authenticated' => false], 401);
    }

    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_id = :session_id");
    $stmt->execute([':session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        json_response(['authenticated' => false], 401);
    }

    if ($session['expires_at'] !== null && (int)$session['expires_at'] <= time()) {
        // Expired
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $sessionId]);
        clear_session_cookie();
        json_response(['authenticated' => false], 401);
    }

    json_response([
        'authenticated' => true,
        'username'      => $session['username'],
    ]);

} elseif ($method === 'POST' && $path === '/api/logout') {
    // --------------------------
    // POST /api/logout
    // --------------------------
    $sessionId = get_session_id_from_cookie();
    if ($sessionId !== null && $sessionId !== '') {
        $stmt = $pdo->prepare("DELETE FROM sessions WHERE session_id = :session_id");
        $stmt->execute([':session_id' => $sessionId]);
    }
    clear_session_cookie();
    json_response(['ok' => true]);

} elseif ($method === 'GET' && $path === '/') {
    // --------------------------
    // Simple root for testing
    // --------------------------
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Cartographica Auth Test</title>
    </head>
    <body>
        <h1>Cartographica Auth Server</h1>
        <p>This is the auth backend. Your game client would typically be served from here.</p>
        <p>Try calling <code>/api/send-login-link</code> from your client.</p>
    </body>
    </html>
    <?php
    exit;

} else {
    http_response_code(404);
    echo 'Not found';
    exit;
}
