<?php

/*

Router.php
==========

Purpose:
Every service needs routing.
You want a consistent, readable pattern.

Features:
- Replaces giant switch/case blocks
- Keeps index.php files tiny and elegant

Responsibilities:
Router::get($action, $controllerClass)
Router::post($action, $controllerClass)
Router::dispatch()

*/

namespace cartographica\share;

class Router {
    private array $routes = [];
    private Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function post(string $action, string $controller): void {
        $this->routes["POST"][$action] = $controller;
    }

    public function get(string $action, string $controller): void {
        $this->routes["GET"][$action] = $controller;
    }

    public function dispatch(): void {
        $method = $this->request->method();
        $action = $this->request->get("action");

        if (!isset($this->routes[$method][$action])) {
            Response::error("Unknown action", 404);
        }

        $controller = $this->routes[$method][$action];
        (new $controller($this->request))->handle();
    }
}
