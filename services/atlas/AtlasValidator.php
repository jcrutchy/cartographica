<?php

namespace cartographica\services\atlas;

use PDO;
use cartographica\share\BaseValidator;

# TODO: add repository for rules (per Copilot advice)
# TODO: consider moving the certificate validation into a new BaseValidator::validateCertificate

class AtlasValidator extends BaseValidator
{
  private PDO $pdo;

  private const MAX_METADATA_FIELDS=200;
  private const MAX_METADATA_LENGTH=20000;
  private const MIN_ISLAND_NAME_LENGTH=4;
  private const MAX_ISLAND_NAME_LENGTH=200;
  private const MAX_PUBLIC_KEY_LENGTH=10000;

  public function __construct(PDO $pdo)
  {
    $this->pdo=$pdo;
  }

  public function islandExists(string $island_name): bool
  {
    $stmt=$this->pdo->prepare("SELECT COUNT(*) FROM islands WHERE island_name = :island_name");
    $stmt->execute([":island_name"=>$island_name]);
    return $stmt->fetchColumn()>0;
  }

  public function validateIslandNameUnique(string $island_name): array
  {
    if ($this->islandExists($island_name))
    {
      return $this->result(false,"Island name already registered.");
    }
    return $this->result(true,$island_name);
  }

  public function validateIslandName(string $island_name): array
  {
    $island_name=trim(preg_replace('/\s+/',' ',$island_name));
    if ($island_name==="")
    {
      return $this->result(false,"Island name must not contain only spaces.");
    }
    if ($err=$this->validateStringLength($island_name,self::MIN_ISLAND_NAME_LENGTH, self::MAX_ISLAND_NAME_LENGTH,"Island name"))
    {
      return $err;
    }
    if (!preg_match('/^[A-Za-z0-9 _\-]+$/u', $island_name))
    {
      return $this->result(false, "Island name contains invalid characters.");
    }
    return $this->result(true,$island_name);
  }

  public function validateOwnerEmail(string $owner_email): array
  {
    return $this->validateEmail($owner_email);
  }

  public function validatePublicKey(string $public_key): array
  {
    $public_key=trim($public_key);
    if (strlen($public_key)>self::MAX_PUBLIC_KEY_LENGTH)
    {
      return $this->result(false,"public_key too large.");
    }
    /*if (!str_starts_with($public_key,"-----BEGIN PUBLIC KEY-----"))
    {
      return $this->result(false,"Invalid public_key format.");
    }
    if (!@openssl_pkey_get_public($public_key))
    {
      return $this->result(false,"Invalid public_key format.");
    }*/
    return $this->result(true,$public_key);
  }

  public function validateMetadata(array $metadata): array
  {
    if ($err=$this->validateAssocArray($metadata,"metadata"))
    {
      return $err;
    }
    if (count($metadata)>self::MAX_METADATA_FIELDS)
    {
      return $this->result(false,"Metadata contains too many fields.");
    }
    foreach ($metadata as $k => $v)
    {
      if (!is_string($k))
      {
        return $this->result(false,"Metadata keys must be strings.");
      }
    }
    ksort($metadata);
    $metadata_json=json_encode($metadata,JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($metadata_json===false)
    {
      return $this->result(false,"Error encoding metadata as JSON.");
    }
    if (strlen($metadata_json)>self::MAX_METADATA_LENGTH)
    {
      return $this->result(false,"Metadata too large.");
    }
    return $this->result(true,$metadata);
  }

  public function validateIslandKey(string $island_key): array
  {
    if ($island_key==="")
    {
      return $this->result(false,"island_key must not be empty.");
    }
    return $this->result(true,$island_key);
  }

  public function validateCertificateType(string $type): array
  {
    if ($type!=="island_certificate")
    {
      return $this->result(false,"Invalid certificate type.");
    }
    return $this->result(true,$type);
  }

  public function validateExpiry(int $expires_at): array
  {
    if ($expires_at<time())
    {
      return $this->result(false,"Certificate has expired.");
    }
    return $this->result(true,$expires_at);
  }

  public function validateCommonPayload(array $payload): array
  {
    $required=["island_name","owner_email","public_key"];
    if ($err=$this->requireFields($payload,$required))
    {
      return $err;
    }
    $result=$this->validateIslandName($payload["island_name"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $island_name=$result["payload"];
    $result=$this->validateOwnerEmail($payload["owner_email"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $owner_email=$result["payload"];
    $result=$this->validatePublicKey($payload["public_key"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $public_key=$result["payload"];
    $clean_payload=["island_name"=>$island_name,"owner_email"=>$owner_email,"public_key"=>$public_key];
    return $this->result(true,$clean_payload);
  }

  public function validateRegisterIslandPayload(array $payload): array
  {
    $required=["island_name","owner_email","public_key","metadata"];
    if ($err=$this->requireFields($payload,$required))
    {
      return $err;
    }
    $result=$this->validateCommonPayload($payload);
    if (!$result["valid"])
    {
      return $result;
    }
    $clean_payload=$result["payload"];
    $result=$this->validateMetadata($payload["metadata"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $clean_payload["metadata"]=$result["payload"];

    $result=$this->validateIslandNameUnique($island_name);
    if (!$result["valid"])
    {
      return $result;
    }
    return $this->result(true,$clean_payload);
  }

  public function validateVerifyCertificatePayload(array $payload): array
  {
    $required=["island_name","owner_email","public_key","type","expires_at","island_key"];
    if ($err=$this->requireFields($payload,$required))
    {
      return $err;
    }
    $result=$this->validateCommonPayload($payload);
    if (!$result["valid"])
    {
      return $result;
    }
    $clean_payload=$result["payload"];
    $result=$this->validateCertificateType($payload["type"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $clean_payload["type"]=$result["payload"];
    $result=$this->validateExpiry($payload["expires_at"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $clean_payload["expires_at"]=$result["payload"];
    $result=$this->validateIslandKey($payload["island_key"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $clean_payload["island_key"]=$result["payload"];
    return $this->result(true,$clean_payload);
  }
}
