<?php

/*

Http.php
========

Purpose:
Your HTTP client helpers.
Identity, island, and island‑directory all need to make HTTP requests.
You want consistent error handling and consistent POST/GET behavior.

Features:
- Proper headers
- Proper error reporting
- Returning a consistent structure

Responsibilities:
http_get($url)
http_post($url,$data)

*/

namespace cartographica\share;

class Http {
    public static function get(string $url): array {
        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "header" => "Accept: application/json\r\n"
            ]
        ]);

        $body = file_get_contents($url, false, $context);
        $status = self::extractStatus($http_response_header ?? []);

        return ["status" => $status, "body" => $body];
    }

    public static function post(string $url, array $data): array {
        $context = stream_context_create([
            "http" => [
                "method"  => "POST",
                "header"  => "Content-Type: application/x-www-form-urlencoded\r\n",
                "content" => http_build_query($data)
            ]
        ]);

        $body = file_get_contents($url, false, $context);
        $status = self::extractStatus($http_response_header ?? []);

        return ["status" => $status, "body" => $body];
    }

    private static function extractStatus(array $headers): int {
        if (!isset($headers[0])) return 0;
        return intval(substr($headers[0], 9, 3));
    }
}
