<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/DataProvider.php';
require_once __DIR__ . '/../../models/ModelUser.php';

// Validar método HTTP
DataProvider::validateRequestMethod("POST");

// Obtener datos del request (JSON)
$input = DataProvider::getCreateUser();

// Validar campos requeridos
$requiredFields = [
    "first_name", "middle_name", "first_surname", "second_surname",
    "id_document", "document_number", "age", "phone", "address",
    "email", "password", "status", "id_role"
];

foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        sendResponse(400, false, "Missing required field: $field");
        exit;
    }
}

$modelUser = new ModelUser();
$result = $modelUser->createUser($input);

if (isset($result["error"])) {
    sendResponse(400, false, "Error creating user", $result);
} else {
    sendResponse(201, true, "User created successfully", $result);
}