<?php

namespace cartographica\identity\smtp;

// ------------------------------------------------------------
// Load SMTP credentials from JSON file
// ------------------------------------------------------------
function load_credentials($relativePath) {
    $credFile = __DIR__ . "/" . $relativePath;

    if (!file_exists($credFile)) {
        throw new Exception("Credential file not found: $credFile");
    }

    $creds = json_decode(file_get_contents($credFile), true);

    if (!is_array($creds) || empty($creds['username']) || empty($creds['password'])) {
        throw new Exception("Credential file missing username/password");
    }

    return $creds;
}

// ------------------------------------------------------------
// Low-level SMTP command helper
// ------------------------------------------------------------
function smtp_cmd($conn, $cmd, $expect = null) {
    if ($cmd !== null) {
        fputs($conn, $cmd . "\r\n");
    }

    $response = "";
    while ($line = fgets($conn, 515)) {
        $response .= $line;
        if (preg_match('/^\d{3} /', $line)) {
            break;
        }
    }

    if ($expect && strpos($response, $expect) !== 0) {
        throw new Exception("Unexpected SMTP response: $response");
    }

    return $response;
}

// ------------------------------------------------------------
// Main reusable mail function
// ------------------------------------------------------------
function smtp_mail($host, $port, $username, $password, $from, $to, $subject, $htmlBody, $replyTo = null) {


    $context = stream_context_create([
        'ssl' => [
            'cafile' => '../../cartographica_data/identity/cacert.pem',
            'verify_peer' => true,
            'verify_peer_name' => true
        ]
    ]);
    
    $conn = stream_socket_client("$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);


    if (!$conn) {
        throw new Exception("Connection failed: $errstr ($errno)");
    }

    smtp_cmd($conn, null); // banner
    smtp_cmd($conn, "EHLO localhost", "250");

    // STARTTLS
    smtp_cmd($conn, "STARTTLS", "220");

    if (!stream_socket_enable_crypto($conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        throw new Exception("Failed to enable TLS");
    }

    // EHLO again after TLS
    smtp_cmd($conn, "EHLO localhost", "250");

    // AUTH LOGIN
    smtp_cmd($conn, "AUTH LOGIN", "334");
    smtp_cmd($conn, base64_encode($username), "334");
    smtp_cmd($conn, base64_encode($password), "235");

    // MAIL FROM / RCPT TO
    smtp_cmd($conn, "MAIL FROM:<$from>", "250");
    smtp_cmd($conn, "RCPT TO:<$to>", "250");

    // DATA
    smtp_cmd($conn, "DATA", "354");

    // Build headers
    $headers  = "From: $from\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "Subject: $subject\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    if ($replyTo) {
        $headers .= "Reply-To: $replyTo\r\n";
    }

    // Send message + end with a dot
    fputs($conn, $headers . "\r\n" . $htmlBody . "\r\n.\r\n");

    // Expect 250 OK
    $response = fgets($conn, 515);

    smtp_cmd($conn, "QUIT");
    fclose($conn);

    return true;
}
