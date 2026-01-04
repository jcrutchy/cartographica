<?php

namespace cartographica\services\identity;

use cartographica\share\SharedConfig;

class Config extends SharedConfig
{
  protected static string $service="identity";

  public function validator(): IdentityValidator
  {
    return new IdentityValidator();
  }

  public static function loginLinkBase(): string
  {
    # TODO: need to get webroot from request to be able to handle different clients?
    return static::get("web_root")."/tools/client?token=";
  }
}
