<?php

/*

Keys.php
========

Purpose:
Both identity and island‑directory need keypair generation.
You want a single, consistent implementation.

Features:
- Auto‑generate RSA keys if missing

Responsibilities:
Keys::ensureKeypair($privatePath,$publicPath)
Keys::loadPrivate($path)
Keys::loadPublic($path)

*/

namespace cartographica\share;

class Keys {
    public static function ensure(string $privatePath, string $publicPath): void {
        if (file_exists($privatePath) && file_exists($publicPath)) return;

        $config = [
            "config" => "C:/php/extras/ssl/openssl.cnf",
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
            "private_key_bits" => 2048
        ];

        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $privatePem);
        $details = openssl_pkey_get_details($res);
        $publicPem = $details["key"];

        file_put_contents($privatePath, $privatePem);
        file_put_contents($publicPath, $publicPem);

        chmod($privatePath, 0600);
        chmod($publicPath, 0644);
    }

    public static function loadPrivate(string $path): string {
        return file_get_contents($path);
    }

    public static function loadPublic(string $path): string {
        return file_get_contents($path);
    }
}
