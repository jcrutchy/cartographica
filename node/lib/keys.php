<?php

function generate_node_keys()
{
  $configargs = array(
    "config" => "C:/php/extras/ssl/openssl.cnf",
    "private_key_bits"=> 2048,
    "private_key_type" => OPENSSL_KEYTYPE_RSA
  );
  $private=openssl_pkey_new($configargs);
  if ($private === false) {
      echo "OpenSSL key generation failed:\n";
      while ($msg = openssl_error_string()) {
          echo "  - $msg\n";
      }
      exit(1);
  }
  openssl_pkey_export($private, $privatePem, null, $configargs);
  $publicPem = openssl_pkey_get_details($private)["key"];
  file_put_contents(NODE_PRIVATE_KEY,$privatePem);
  file_put_contents(NODE_PUBLIC_KEY,$publicPem);
  echo "Node keypair generated.\n";
}
