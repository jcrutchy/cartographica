<?php
function json_ok($data) {
    echo json_encode(["ok" => true, "data" => $data]);
    exit;
}

function json_error($msg) {
    echo json_encode(["ok" => false, "error" => $msg]);
    exit;
}
