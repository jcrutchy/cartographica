<?php

/*

Response.php
============

Purpose:
Every service returns JSON.
You want consistent formatting and error handling.

Features:
- Automatic headers
- Automatic encoding

Responsibilities:
Response::json($data, $status = 200)
Response::error($message, $status = 400)
Response::success($data)

*/

namespace cartographica\share;

class Response {

    public static function json(array $data, int $status = 200): void {
        http_response_code($status);

        if (php_sapi_name() !== "cli") {
            header("Content-Type: application/json");
        }

        echo json_encode($data);
        exit;
    }

    public static function error(string $msg, int $status = 400): void {
        self::json([
            "ok"     => false,
            "status" => "error",
            "error"  => $msg
        ], $status);
    }

    public static function success(array $data, string $statusText = "ok"): void {
        $wrapped = array_merge([
            "ok"     => true,
            "status" => $statusText
        ], $data);

        self::json($wrapped, 200);
    }
}
