<?php
define("DB_PATH","../../cartographica_data/identity/db.sqlite");

define("CA_PRIVATE_KEY","../../cartographica_data/identity/ca_private.pem");
define("CA_PUBLIC_KEY","../../cartographica_data/identity/ca_public.pem");

define("EMAIL_FROM", "jared.crutchfield@wideband.net.au");

define("SMTP_CREDENTIALS_FILE","../../cartographica_data/identity/smtp_credentials.txt");
define("SMTP_HOST","mail.aussiebroadband.com.au");
define("SMTP_PORT",587);
define("SMTP_FROM_ADDRESS","jared.crutchfield@wideband.net.au");

define("LOGIN_EMAIL_LINK_PREFIX","http://localhost/cartographica/client?token=");

$smtp_creds=\cartographica\identity\smtp\load_credentials(SMTP_CREDENTIALS_FILE);

define("SMTP_CREDENTIALS",$smtp_creds);


define("ADMIN_EMAIL","jared.crutchfield@wideband.net.au");
