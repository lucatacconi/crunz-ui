<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

$app->group('/navigation', function (RouteCollectorProxy $group) {

    $group->get('/', function (Request $request, Response $response, array $args) {

        $jwt_payload = $request->getAttribute("token");

        if(empty($jwt_payload)) throw new Exception("ERROR - Security session not found");

        $data = [];
        $navigation_map = [];
        $routes = [];
        $bootstrapPage = '';

        if(!empty($jwt_payload["userType"])){

            $app_config = $this->get('configs')["app_configs"];

            if( !empty($app_config["navigation"]) && !empty($app_config["navigation"]["navigationMap"])){
                $navigation_map_orig = $app_config["navigation"]["navigationMap"];

                if(!empty($navigation_map_orig)){
                    foreach($navigation_map_orig as $item_cnt => $item_data){
                        if(!empty($item_data)){
                            if(isset($item_data["visible"]) && $item_data["visible"] == false){
                                continue;
                            }

                            if(!empty($item_data["allowed"]) && !in_array($jwt_payload["userType"], $item_data["allowed"])) {
                                continue;
                            }

                            unset($nav_element);
                            if(!empty($item_data["subMenuItems"])){

                                //This menu item is a submenu
                                $item_data["type"] = "SUBM";

                                $nav_element = $item_data;
                                unset($nav_element["allowed"]);
                                $nav_element["subMenuItems"] = [];

                                foreach($item_data["subMenuItems"] as $subitem_cnt => $subitem_data){

                                    if(isset($subitem_data["visible"]) && $subitem_data["visible"] == false){
                                        continue;
                                    }
                                    if(empty($subitem_data["action"])){
                                        continue;
                                    }
                                    if(!empty($subitem_data["allowed"]) && !in_array($jwt_payload["userType"], $subitem_data["allowed"])) {
                                        continue;
                                    }
                                    unset($subitem_data["allowed"]);

                                    if(!empty($subitem_data["action"]["url"])){
                                        $subitem_data["actionType"] = "LINK";
                                        if(empty($subitem_data["action"]["target"])) $subitem_data["action"]["target"] = '_blank';

                                    }else if(!empty($subitem_data["action"]["path"]) && !empty($subitem_data["action"]["component"])){
                                        $subitem_data["actionType"] = "SECT";
                                        if(!in_array($subitem_data["action"], $routes)){
                                            $routes[] = $subitem_data["action"];
                                        }
                                    }else{
                                        $subitem_data["actionType"] = "FUNC";
                                    }

                                    $nav_element["subMenuItems"][] = $subitem_data;
                                }
                            }else if(!empty($item_data["divider"])){
                                //This menu item is a divider
                                if($item_data["divider"] && (!isset($item_data["visible"]) || $item_data["visible"] == true)){
                                    $item_data["type"] = "DIV";
                                    $nav_element = $item_data;
                                }
                            }else{
                                //This menu item is a single element
                                if(!empty($item_data["action"])){
                                    $item_data["type"] = "ELM";

                                    if(!empty($item_data["action"]["url"])){
                                        $item_data["actionType"] = "LINK";
                                        if(empty($item_data["action"]["target"])) $item_data["action"]["target"] = '_blank';
                                        $nav_element = $item_data;
                                        unset($nav_element["allowed"]);
                                    }else if(!empty($item_data["action"]["path"]) && !empty($item_data["action"]["component"])){
                                        $item_data["actionType"] = "SECT";
                                        $nav_element = $item_data;

                                        if(!in_array($item_data["action"], $routes)){
                                            $routes[] = $item_data["action"];
                                        }
                                        unset($nav_element["allowed"]);
                                    }else{
                                        $item_data["actionType"] = "FUNC";
                                        $nav_element = $item_data;
                                        unset($nav_element["allowed"]);
                                    }
                                }
                            }
                        }

                        if(!empty($nav_element)){
                            $navigation_map[] = $nav_element;
                        }
                    }
                }
            }

            if( !empty($app_config["navigation"]) && !empty($app_config["navigation"]["bootstrapPage"])){
                $bootstrapPage = $app_config["navigation"]["bootstrapPage"];
            }
        }

        $routeFounded = false;
        foreach($routes as $row_cnt => $row_data){
            if($bootstrapPage["route"] == $row_data["path"]){
                $routeFounded = true;
                break;
            }
        }
        if(!$routeFounded){
            $bootstrapPage = [];
        }



        $data["navMap"] = $navigation_map;
        $data["bootstrapPage"] = $bootstrapPage;
        $data["routes"] = $routes;

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response->withStatus(200)
                        ->withHeader("Content-Type", "application/json");
    });

});
