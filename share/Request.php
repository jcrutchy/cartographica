<?php

/*

Request.php
===========

Purpose:
Every service receives HTTP requests.
You want a consistent way to access:
- GET params
- POST params
- JSON bodies
- Headers
- IP address
- Method

Features:
- Keeps controllers clean and readable

Responsibilities:
Request::method()
Request::get($key)
Request::post($key)
Request::json()
Request::header($name)
Request::ip()

*/

namespace cartographica\share;

class Request {
    public function method(): string {
        return $_SERVER["REQUEST_METHOD"] ?? "GET";
    }

    public function get(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    public function json(): array {
        $raw = file_get_contents("php://input");
        return json_decode($raw, true) ?? [];
    }
}
