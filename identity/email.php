<?php
function send_login_email(string $email, string $token)
{
  $link=LOGIN_EMAIL_LINK_PREFIX.urlencode($token);
  $subject="Cartographica: login link";
  $body="
<!DOCTYPE html>
<html>
<head>
<title>".$subject."</title>
<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\">

<style type=\"text/css\">
*
  {
    font-size: 14px;
    font-family: sans-serif;
  }
</style>
</head>
<body>
  <h1>Welcome to Cartographica</h1>
  <p>Click <a href=\"".$link."\">here</a> to login to Cartographica.</p>
  <p>This link expires in 10 minutes.</p>
</body>
</html>
  ";
  send_email($email,$subject,$body);
  send_email(ADMIN_EMAIL,"cartographica: email login requested","player with email \"".$email."\" has requested a login link sent to their email");
}

function send_email($to,$subject,$body)
{
  \cartographica\identity\smtp\smtp_mail(
    host:     SMTP_HOST,
    port:     SMTP_PORT,
    username: SMTP_CREDENTIALS['username'],
    password: SMTP_CREDENTIALS['password'],
    from:     SMTP_FROM_ADDRESS,
    to:       $to,
    subject:  $subject,
    htmlBody: $body,
    replyTo:  null
  );
}

function email_admin($message,$subject)
{
  send_email(ADMIN_EMAIL,$subject,$message);
}
