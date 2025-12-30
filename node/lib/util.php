<?php

function http_post(string $url, array $fields): array {
    $opts = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/x-www-form-urlencoded\r\n",
            "content" => http_build_query($fields)
        ]
    ];

    $ctx = stream_context_create($opts);
    $res = file_get_contents($url, false, $ctx);

    if ($res === false) {
        throw new Exception("HTTP POST failed to $url");
    }

    #echo "RAW RESPONSE:\n$res\n\n";

    $json = json_decode($res, true);
    if (!$json) {
        throw new Exception("Invalid JSON from $url");
    }
    return $json;
}
