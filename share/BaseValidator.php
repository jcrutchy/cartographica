<?php

namespace cartographica\share;

# TODO: consider moving the share/Certificate validation into a new BaseValidator::validateCertificate

abstract class BaseValidator
{
    /**
     * Standard result wrapper used by all validators.
     */
    protected function result(bool $valid, $payload): array
    {
        return ["valid" => $valid, "payload" => $payload];
    }

    /**
     * Ensure required fields exist in the payload.
     */
    protected function requireFields(array $payload, array $fields): ?array
    {
        $missing = array_diff($fields, array_keys($payload));
        if (!empty($missing)) {
            return $this->result(false, "Missing fields: " . implode(", ", $missing));
        }
        return null;
    }

    /**
     * Validate email format.
     */
    protected function validateEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->result(false, "Invalid email format.");
        }
        $domain=substr(strrchr($email,"@"),1);
        if ($domain && !checkdnsrr($domain,"MX"))
        {
            return $this->result(false, "Email domain not found.");
        }
        return $this->result(true,$email);
    }

    /**
     * Validate that metadata is an associative array.
     */
    protected function validateAssocArray($value, string $fieldName = "metadata"): ?array
    {
        if (!is_array($value) || array_is_list($value)) {
            return $this->result(false, "$fieldName must be an associative array.");
        }
        return null;
    }

    /**
     * Validate string length.
     */
    protected function validateStringLength(string $value, int $min, int $max, string $fieldName): ?array
    {
        $len = strlen($value);
        if ($len < $min || $len > $max) {
            return $this->result(false, "$fieldName must be between $min and $max characters long.");
        }
        return null;
    }
}
