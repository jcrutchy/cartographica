<?php

namespace cartographica\services\atlas\controllers;

use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\atlas\Config;

class ListIslands
{
  private Request $req;

  public function __construct(Request $req)
  {
    $this->req=$req;
  }

  public function handle(): void
  {
    $config=new Config();
    $pdo=$config->pdo();
    $validator=$config->validator($pdo);

    # accepts network_id

    # just respond with a dummy list of islands (for a dummy selected network) for now
    $island_list=[
      ["id"=>"island_01","ws_url"=>"localhost:8081","name"=>"Noob Island"],
      ["id"=>"island_02","ws_url"=>"localhost:8080","name"=>"West Side"],
      ["id"=>"island_03","ws_url"=>"localhost:8080","name"=>"Aussie Slackers"],
      ["id"=>"island_04","ws_url"=>"localhost:8080","name"=>"Euro"],
      ["id"=>"island_05","ws_url"=>"localhost:8080","name"=>"Frozen Wasteland"],
      ["id"=>"island_06","ws_url"=>"localhost:8080","name"=>"The Desert"],
      ["id"=>"island_07","ws_url"=>"localhost:8080","name"=>"Mad Max"],
      ["id"=>"island_08","ws_url"=>"localhost:8080","name"=>"Natural Disasterland"]
    ];
    Response::success(["island_list"=>$island_list]);
  }
}
