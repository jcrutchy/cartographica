<?php

namespace cartographica\services\atlas;

use cartographica\share\SharedConfig;

class Config extends SharedConfig
{
  public static string $service="atlas";

  public function validator(): AtlasValidator
  {
    return new AtlasValidator($this->pdo());
  }
}
