<?php
function sendResponse($code, $success, $message, $data = null) {
    header("Access-Control-Allow-Origin: *"); // Permitir acceso desde cualquier origen
    header("Content-Type: application/json; charset=UTF-8"); // Asegurar formato JSON

    http_response_code($code);

    $response = [
        "success" => $success,
        "message" => $message
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}
