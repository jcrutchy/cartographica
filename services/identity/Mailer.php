<?php
namespace cartographica\services\identity;

use cartographica\share\Smtp;
use cartographica\share\SharedConfig;
use cartographica\share\Template;

class Mailer
{
    private Smtp $smtp;

    public function __construct()
    {
        $creds = Smtp::loadCredentials(Config::smtpCredentials());

        $this->smtp = new Smtp(
            host: SharedConfig::get("smtp_host"),
            port: SharedConfig::get("smtp_port"),
            username: $creds["username"],
            password: $creds["password"],
            cafile: Config::smtpCafile()
        );
    }

    public function sendLoginLink(string $email, string $link): void
    {
        $html = Template::render(
            __DIR__ . "/templates/login_email.html",
            [
                "email" => $email,
                "link"  => $link
            ]
        );

        $this->smtp->send(
            from: Config::smtpFrom(),
            to: $email,
            subject: "Your Cartographica Login Link",
            htmlBody: $html
        );
    }
}
