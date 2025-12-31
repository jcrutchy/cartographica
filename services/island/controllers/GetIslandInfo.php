<?php

namespace cartographica\services\island\controllers;

use cartographica\share\Response;
use cartographica\services\island\Config;

class GetIslandInfo
{
    public function __construct() {}

    public function handle(): void
    {
        $config = json_decode(file_get_contents(Config::islandConfig()), true);

        Response::success([
            "name"        => $config["name"],
            "description" => $config["description"],
            "tags"        => $config["tags"],
            "version"     => $config["version"],
            "public_key"  => file_get_contents(Config::publicKey())
        ]);
    }
}
