<?php

namespace cartographica\services\atlas\controllers;

use cartographica\share\Logger;
use cartographica\share\Request;
use cartographica\share\Response;
use cartographica\services\atlas\Config;

class ListNetworks
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

    # just respond with a dummy list of networks for now
    $network_list=[
      ["id"=>"network_01","name"=>"Real Earth"],
      ["id"=>"network_02","name"=>"The Expanse Solar System"],
      ["id"=>"network_03","name"=>"StarCraft Koprulu Sector"],
      ["id"=>"network_04","name"=>"Star Trek Universe"],
      ["id"=>"network_05","name"=>"Star Wars Galaxy"]
    ];
    Response::success(["network_list"=>$network_list]);
  }
}
