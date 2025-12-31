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
    public static function ensure(string $private_key_filename, string $public_key_filename): void {
        if (file_exists($private_key_filename) && file_exists($public_key_filename)) return;
        $config=array(
          "config" => "C:/php/extras/ssl/openssl.cnf",
          "private_key_bits"=> 2048,
          "private_key_type" => OPENSSL_KEYTYPE_RSA
        );
        $private=openssl_pkey_new($config);
        if ($private === false)
        {
          $msg=array();
          while ($msg[]=openssl_error_string())
          {
          }
          var_dump($msg);
          die;
        }
        openssl_pkey_export($private, $privatePem,null,$config);
        $publicPem = openssl_pkey_get_details($private)["key"];
        file_put_contents($private_key_filename,$privatePem);
        file_put_contents($public_key_filename,$publicPem);
    }

    public static function loadPrivate(string $path): string {
        return file_get_contents($path);
    }

    public static function loadPublic(string $path): string {
        return file_get_contents($path);
    }
}
