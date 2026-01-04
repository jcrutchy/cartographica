<?php

/*

Smtp.php
========

Purpose:
An SMTP client for all services.

Features:
- Configurable CA file

Responsibilities:
Smtp::loadCredentials($path)
Smtp::send($from,$to,$subject,$htmlBody,$replyTo)

Usage:
$creds=Smtp::loadCredentials("smtp_credentials.json");
$mailer=new Smtp("smtp.gmail.com",587,$creds["username"],$creds["password"],"cacert.pem");
$mailer->send("no-reply@cartographica.com",$email,"Login link","<p>Click here to log in.</p>");

*/

namespace cartographica\share;

use cartographica\share\SharedConfig;
use cartographica\share\Env;
use Exception;

class Smtp
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $cafile;
    private $conn;

    public function __construct() {
        $this->host=SharedConfig::get("smtp_host");
        $this->port=SharedConfig::get("smtp_port");
        $this->cafile=Env::dataRoot().SharedConfig::get("cacert_file_relative");
        $this->loadCredentials();
    }

    // ------------------------------------------------------------
    // Load credentials from JSON file
    // ------------------------------------------------------------
    private function loadCredentials(): void
    {
        $path=Env::dataRoot().SharedConfig::get("smtp_creds_file_relative");
        if (!file_exists($path)) {
            throw new Exception("SMTP credential file not found: $path");
        }

        $creds = json_decode(file_get_contents($path), true);

        if (!is_array($creds) || empty($creds['username']) || empty($creds['password'])) {
            throw new Exception("SMTP credential file missing username/password");
        }

        $this->username = $creds['username'];
        $this->password = $creds['password'];
    }

    // ------------------------------------------------------------
    // Connect + STARTTLS + AUTH LOGIN
    // ------------------------------------------------------------
    private function connect(): void
    {
        
        $context = stream_context_create([
            'ssl' => [
                'cafile'            => $this->cafile,
                'verify_peer'       => true,
                'verify_peer_name'  => true
            ]
        ]);

        $this->conn = stream_socket_client(
            "{$this->host}:{$this->port}",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->conn) {
            throw new Exception("SMTP connection failed: $errstr ($errno)");
        }

        $this->cmd(null); // banner
        $this->cmd("EHLO localhost", "250");

        // STARTTLS
        $this->cmd("STARTTLS", "220");

        if (!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new Exception("Failed to enable TLS");
        }

        // EHLO again after TLS
        $this->cmd("EHLO localhost", "250");

        // AUTH LOGIN
        $this->cmd("AUTH LOGIN", "334");
        $this->cmd(base64_encode($this->username), "334");
        $this->cmd(base64_encode($this->password), "235");
    }

    // ------------------------------------------------------------
    // Low-level SMTP command helper
    // ------------------------------------------------------------
    private function cmd(?string $cmd, ?string $expect = null): string
    {
        if ($cmd !== null) {
            fputs($this->conn, $cmd . "\r\n");
        }

        $response = "";
        while ($line = fgets($this->conn, 515)) {
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
    // Send an email
    // ------------------------------------------------------------
    public function send(
        string $to,
        string $subject,
        string $htmlBody
    ): bool {
        $from=SharedConfig::get("smtp_from_email");
        $replyTo=null;

        $this->connect();

        // MAIL FROM / RCPT TO
        $this->cmd("MAIL FROM:<$from>", "250");
        $this->cmd("RCPT TO:<$to>", "250");

        // DATA
        $this->cmd("DATA", "354");

        // Headers
        $headers  = "From: $from\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $subject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        if ($replyTo) {
            $headers .= "Reply-To: $replyTo\r\n";
        }

        // Send message
        fputs($this->conn, $headers . "\r\n" . $htmlBody . "\r\n.\r\n");

        // Expect 250 OK
        $resp = fgets($this->conn, 515);

        $this->cmd("QUIT");
        fclose($this->conn);

        return true;
    }
}
