<?php
function send_login_email(string $email, string $token) {
    $link = "https://yourdomain.com/identity/index.php?action=redeem&token=" . urlencode($token);
    $subject = "Your Cartographica Login Link";
    $body = "Click to log in: $link\nThis link expires in 10 minutes.";
    //mail($email, $subject, $body, "From: " . EMAIL_FROM);

/*
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
*/

}
