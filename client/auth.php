<?php
declare(strict_types=1);

ini_set("display_errors","on");
ini_set("error_reporting",E_ALL);

require_once("smtp.php");

/*
|--------------------------------------------------------------------------
| Cartographica Authentication Server (PHP)
| - Loads config from auth.conf
| - Uses SQLite for tokens + sessions
| - Uses your smtp_mail() helper for sending magic links
| - Provides:
|     POST /api/send-login-link
|     GET  /auth?token=...
|     GET  /api/me
|     POST /api/logout
|--------------------------------------------------------------------------
*/



function api_error(string $message, int $status=500): void
{
  http_response_code($status);
  header('Content-Type: application/json');
  $out=array();
  $out["ok"]=false;
  $out["error"]=$message;
  echo json_encode($out);
  exit;
}

//api_error("test");

// ------------------------------------------------------------
// Load config
// ------------------------------------------------------------
$configPath = __DIR__ . '/auth.conf';

if (file_exists($configPath)==false)
{
  api_error("auth.conf file not found");
}

$config=file_get_contents($configPath);

if (empty($config)==true)
{
  api_error("auth.conf unable to be opened or is empty");
}

$config = json_decode($config,true);

if (!is_array($config)) {
    api_error("Invalid auth.conf");
}

$APP_URL  = $config['app_url'];
$DB_FILE  = __DIR__ . '/' . $config['db_file'];
$MAIL_LOG = __DIR__ . '/' . $config['mail_log'];

$logDir = dirname($MAIL_LOG);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// ------------------------------------------------------------
// Utility functions
// ------------------------------------------------------------
function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
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

function parse_json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function set_session_cookie(string $sessionId, ?int $expiresAt): void {
    setcookie(
        "session",
        $sessionId,
        [
            "expires"  => $expiresAt ?? 0,
            "path"     => "/",
            "secure"   => !empty($_SERVER['HTTPS']),
            "httponly" => true,
            "samesite" => "Lax"
        ]
    );
}

function clear_session_cookie(): void {
    setcookie(
        "session",
        "",
        [
            "expires"  => time() - 3600,
            "path"     => "/",
            "secure"   => !empty($_SERVER['HTTPS']),
            "httponly" => true,
            "samesite" => "Lax"
        ]
    );
}

// ------------------------------------------------------------
// Database
// ------------------------------------------------------------
function get_db(string $file): PDO {
    static $pdo = null;
    if ($pdo === null) {

        try {
            $pdo = new PDO("sqlite:$file");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Log the real error for debugging
            //error_log("DB error in send-login-link: " . $e->getMessage());
        
            // Return a safe API error to the client
            api_error("Error connecting to database.", 500);
        }


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

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(32) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        );
    ");

}

// ------------------------------------------------------------
// Email sending (your SMTP helper)
// ------------------------------------------------------------
function send_magic_link_email(string $email, string $link, string $username, array $config): void {

    // Log email for debugging
    file_put_contents(
        $config['mail_log'],
        date('c') . " To: $email\n$link\n---\n",
        FILE_APPEND
    );

    // If SMTP disabled, stop here
    if (empty($config['smtp']['enabled'])) {
        return;
    }

    // Load credentials
    $creds = \cartographica\lib\smtp\smtp_load_credentials($config['smtp']['credentials_file']);

    // Send email using your helper
    \cartographica\lib\smtp\smtp_mail(
        host:     $config['smtp']['host'],
        port:     $config['smtp']['port'],
        username: $creds['username'],
        password: $creds['password'],
        from:     $config['smtp']['from'],
        to:       $email,
        subject:  "Your Cartographica Login Link",
        htmlBody: "<p>Hello <b>$username</b>,</p>
                   <p>Click below to log in:</p>
                   <p><a href='$link'>$link</a></p>
                   <p>This link expires in 10 minutes.</p>",
        replyTo:  $config['smtp']['reply_to'] ?? null
    );
}

// ------------------------------------------------------------
// Routing helpers
// ------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';




$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script = $_SERVER['SCRIPT_NAME'];

if (str_starts_with($path, $script)) {
    $path = substr($path, strlen($script));
}

$path = '/' . ltrim($path, '/'); // normalize


//$path   = strtok($_SERVER['REQUEST_URI'], '?') ?: '/';





$pdo = get_db($DB_FILE);

// ------------------------------------------------------------
// Cleanup expired tokens + sessions
// ------------------------------------------------------------
$now = time();

try {
    $pdo->prepare("DELETE FROM login_tokens WHERE expires_at <= :now OR used = 1")
        ->execute([':now' => $now]);
    
    $pdo->prepare("DELETE FROM sessions WHERE expires_at IS NOT NULL AND expires_at <= :now")
        ->execute([':now' => $now]);
} catch (PDOException $e) {
    // Log the real error for debugging
    //error_log("DB error in send-login-link: " . $e->getMessage());

    // Return a safe API error to the client
    api_error("Error deleting expired login tokens and sessions from database.", 500);
}



// ------------------------------------------------------------
// ROUTES
// ------------------------------------------------------------

// ------------------------------------------------------------
// POST /api/send-login-link
// ------------------------------------------------------------
if ($method === 'POST' && $path === '/api/send-login-link') {

    $body = parse_json_body();
    $username = trim($body['username'] ?? 'Explorer');
    $email    = trim($body['email'] ?? '');
    $remember = !empty($body['remember']) ? 1 : 0;

    if (empty($username)==true)
    {
        api_error("Username cannot be empty",400);
    }

    if (empty($email)==true)
    {
        api_error("Email cannot be empty",400);
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        api_error("Invalid username format", 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    {
        api_error('Invalid email',400);
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $existing = $stmt->fetchColumn();
    } catch (Throwable $e) {   // catches EVERYTHING
        //error_log("DB error in send-login-link: " . $e->getMessage());
        api_error("A server error occurred. Please try again later.");
    }

    
    if ($existing) {
        api_error("That username is already taken", 409);
    }

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
$userId = $stmt->fetchColumn();


    if (!$userId) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email)
                VALUES (?, ?)
            ");
            $stmt->execute([$username, $email]);
            $userId = $pdo->lastInsertId();
        } catch (Throwable $e) {
            //error_log("User insert failed: " . $e->getMessage());
            api_error("Could not create user account. Please try again later.", 500);
        }
    }

    $token = generate_token();
    $expiresAt = minutes_from_now(10);



    $stmt = $pdo->prepare("
        INSERT INTO login_tokens (token, username, email, remember, expires_at)
        VALUES (:token, :username, :email, :remember, :expires_at)
    ");

    $stmt->execute([
        ':token'      => $token,
        ':username'   => $username,
        ':email'      => $email,
        ':remember'   => $remember,
        ':expires_at' => $expiresAt
    ]);

    $link = $APP_URL . "/auth?token=" . urlencode($token);




    send_magic_link_email($email, $link, $username, $config);

    json_response(['ok' => true]);
}

// ------------------------------------------------------------
// GET /auth?token=...
// ------------------------------------------------------------
if ($method === 'GET' && $path === '/auth') {

    $token = $_GET['token'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM login_tokens WHERE token = :token");
    $stmt->execute([':token' => $token]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record || $record['used'] || $record['expires_at'] <= time()) {
        http_response_code(400);
        echo "Invalid or expired token";
        exit;
    }

    // Mark token used
    $pdo->prepare("UPDATE login_tokens SET used = 1 WHERE token = :token")
        ->execute([':token' => $token]);

    // Create session
    $sessionId = generate_token();
    $expiresAt = $record['remember'] ? days_from_now(180) : null;

    $pdo->prepare("
        INSERT INTO sessions (session_id, username, expires_at)
        VALUES (:session_id, :username, :expires_at)
    ")->execute([
        ':session_id' => $sessionId,
        ':username'   => $record['username'],
        ':expires_at' => $expiresAt
    ]);

    set_session_cookie($sessionId, $expiresAt);

    redirect('/');
}

// ------------------------------------------------------------
// GET /api/me
// ------------------------------------------------------------
if ($method === 'GET' && $path === '/api/me') {

    $sessionId = $_COOKIE['session'] ?? null;

    if (!$sessionId) {
        json_response(['authenticated' => false]);
    }

    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_id = :id");
    $stmt->execute([':id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        json_response(['authenticated' => false], 401);
    }

    json_response([
        'authenticated' => true,
        'username'      => $session['username']
    ]);
}

// ------------------------------------------------------------
// POST /api/logout
// ------------------------------------------------------------
if ($method === 'POST' && $path === '/api/logout') {

    $sessionId = $_COOKIE['session'] ?? null;

    if ($sessionId) {
        $pdo->prepare("DELETE FROM sessions WHERE session_id = :id")
            ->execute([':id' => $sessionId]);
    }

    clear_session_cookie();

    json_response(['ok' => true]);
}

// ------------------------------------------------------------
// Default route
// ------------------------------------------------------------
api_error("unknown error: ".$path);
exit;
