<?php

/*

Certificate.php
===============

Purpose:
All services need to verify certificates.
You want a single source of truth for certificate rules.
This becomes the heart of your trust model.

*/

namespace cartographica\share;

use cartographica\share\SharedConfig;

# TODO: consider moving the certificate validation into a new BaseValidator::validateCertificate

class Certificate
{

  private const ALLOWED_TYPES = [
      "email_token",
      "session_token",
      "island_certificate",
      "atlas_certificate"
  ];

  private static function sign_payload(array $payload,string $privateKey): array
  {
    $json=json_encode($payload,JSON_UNESCAPED_SLASHES);
    if (!$json)
    {
      return ["valid"=>false,"error"=>"Error encoding payload for signing."];
    }
    if (!openssl_sign($json,$signature,$privateKey,OPENSSL_ALGO_SHA256))
    {
      return ["valid"=>false,"error"=>"Error signing payload."];
    }
    return ["valid"=>true,"signature"=>base64_encode($signature)];
  }

  private static function verify_payload(array $payload,string $signature,string $publicKey): array
  {
    $json=json_encode($payload,JSON_UNESCAPED_SLASHES);
    if (!$json)
    {
      return ["valid"=>false,"error"=>"Error encoding payload for verification."];
    }
    $result=openssl_verify($json,base64_decode($signature),$publicKey,OPENSSL_ALGO_SHA256);
    if ($result===1)
    {
      return ["valid"=>true];
    }
    if ($result===0)
    {
      return ["valid"=>false,"error"=>"Invalid signature."];
    }
    return ["valid"=>false,"error"=>"OpenSSL error during signature verification."];
  }

  public static function random_id(int $bytes=32): string
  {
    return bin2hex(random_bytes($bytes));
  }

  public static function issue(SharedConfig $config,array $extra,int $expiry,string $type): array
  {
    $time=time();
    $payload=["issued_at"=>$time,"expires_at"=>$time+$expiry,"type"=>$type];
    $payload=array_merge($payload,$extra);
    $privateKey=$config->privateKey();
    if (file_exists($privateKey)==false)
    {
      return ["valid"=>false,"error"=>"Private key not found."];
    }
    $privateKey=file_get_contents($privateKey);
    if (!$privateKey)
    {
      return ["valid"=>false,"error"=>"Unable to load private key."];
    }
    $result=self::sign_payload($payload,$privateKey);
    if ($result["valid"]==false)
    {
      return $result;
    }
    $signature=$result["signature"];
    return ["valid"=>true,"payload"=>$payload,"signature"=>$signature];
  }

  public static function verify(SharedConfig $config,string $certificateJson): array
  {
    $certificate=json_decode($certificateJson,true);
    if (!is_array($certificate))
    {
      return ["valid"=>false,"error"=>"Certificate is not an array."];
    }
    if (!isset($certificate["payload"]) || !isset($certificate["signature"]))
    {
      return ["valid"=>false,"error"=>"Certificate missing payload and/or signature."];
    }
    $payload=$certificate["payload"];
    $signature=$certificate["signature"];
    if (!is_array($payload) || !is_string($signature))
    {
      return ["valid"=>false,"error"=>"Either payload is not an array or signature is not a string."];
    }
    if (!isset($payload["issued_at"]))
    {
      return ["valid"=>false,"error"=>"Payload missing issued_at."];
    }
    if (!isset($payload["expires_at"]))
    {
      return ["valid"=>false,"error"=>"Payload missing expires_at."];
    }
    if (!isset($payload["type"]))
    {
      return ["valid"=>false,"error"=>"Payload missing type."];
    }
    if ($payload["issued_at"]>(time()+300))
    {
      return ["valid"=>false,"error"=>"Certificate/token issued in the future."];
    }
    if (!in_array($payload["type"],self::ALLOWED_TYPES,true))
    {
      return ["valid"=>false,"error"=>"Unknown certificate type."];
    }
    if (time()>$payload["expires_at"])
    {
      return ["valid"=>false,"error"=>"Certificate/token has expired."];
    }
    if ($payload["expires_at"]<=$payload["issued_at"])
    {
      return ["valid"=>false,"error"=>"Invalid expiry time (before issued_at)."];
    }
    $publicKey=$config->publicKey();
    if (file_exists($publicKey)==false)
    {
      return ["valid"=>false,"error"=>"Public key not found."];
    }
    $publicKey=file_get_contents($publicKey);
    if (!$publicKey)
    {
      return ["valid"=>false,"error"=>"Unable to load public key."];
    }
    $result=self::verify_payload($payload,$signature,$publicKey);
    if ($result["valid"]==false)
    {
      return $result;
    }
    return ["valid"=>true,"payload"=>$payload];
  }
}
