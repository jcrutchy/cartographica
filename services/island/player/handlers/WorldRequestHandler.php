<?php

namespace cartographica\services\island\player\handlers;

use cartographica\services\island\world\state\IslandState;
use cartographica\services\island\world\state\PlayerState;
use cartographica\services\island\player\server\PlayerWebSocketClient;
use cartographica\services\island\core\protocol\Messages;
use cartographica\services\island\Config;
use cartographica\share\Env;

class WorldRequestHandler
{

    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function handle(PlayerWebSocketClient $client, array $msg, $state)
    {
        if ($msg['type'] !== Messages::REQUEST_WORLD) return;

        $tilemap=$state->tilemap->export();

        $asset_path=Env::serviceData("island")."/".$this->config->islandId."/assets/";

        $default_tileset_img=null;
        $default_tileset_cfg=null;
        if (!$msg['default_tileset'])
        {
          $tilesetPath=$asset_path."default_terrain.png";
          $tilesetConfigPath=$asset_path."default_terrain.json";
          $default_tileset_img=base64_encode(file_get_contents($tilesetPath));
          $default_tileset_cfg=json_decode(file_get_contents($tilesetConfigPath), true);
        }

        $client->send([
            'type' => Messages::WORLD,
            'tilemap' => $tilemap,
            'island_id' => $this->config->islandId,
            'players' => array_map(fn($p) => $p->export(), $state->players),
            'default_tileset' => ["img" => $default_tileset_img, "cfg" => $default_tileset_cfg]
        ]);
        echo "Sending WORLD to {$client->player->id}\n";
    }

}
