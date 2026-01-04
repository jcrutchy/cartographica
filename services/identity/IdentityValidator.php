<?php

namespace cartographica\services\identity;

use cartographica\share\BaseValidator;

# TODO: add repository for rules (per Copilot advice)

class IdentityValidator extends BaseValidator
{

  public function validateRedeemPayload(array $payload): array
  {
    $required=["email","type","issued_at","expires_at","player_id","email_token_id"];
    if ($err=$this->requireFields($payload,$required))
    {
      return $err;
    }
    # TODO: update similar to AtlasValidator using field validator methods
    # TODO: consider moving the certificate validation into a new BaseValidator::validateCertificate
    return $this->result(true,$payload);
  }

  public function validateRequestLoginPayload(array $payload): array
  {
    $required=["email"];
    if ($err=$this->requireFields($payload,$required))
    {
      return $err;
    }
    $result=$this->validateEmail($payload["email"]);
    if (!$result["valid"])
    {
      return $result;
    }
    $email=$result["payload"];
    $clean_payload=["email"=>$email];
    return $this->result(true,$clean_payload);
  }

  public function validateVerifyPayload(array $payload): array
  {
    $required=["email","type","issued_at","expires_at","player_id","email_token_id"];
    if ($err=$this->requireFields($payload,$required))
    {
      return $err;
    }
    # TODO: update similar to AtlasValidator using field validator methods
    # TODO: consider moving the certificate validation into a new BaseValidator::validateCertificate
    return $this->result(true,$payload);
  }

}
