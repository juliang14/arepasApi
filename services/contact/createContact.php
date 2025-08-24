<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/DataProvider.php';
require_once __DIR__ . '/../../models/ModelContact.php';

// Validar método HTTP
DataProvider::validateRequestMethod("POST");

// Obtener datos del request (JSON)
$input = DataProvider::getCreateContact();

// Validar campos requeridos
$requiredFields = ["name", "phone", "preferred_time"];

foreach ($requiredFields as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        sendResponse(400, false, "Missing required field: $field");
        exit;
    }
}

$modelContact = new ModelContact();
$result = $modelContact->createContact($input);

if (isset($result["error"])) {
    sendResponse(400, false, "Error creating contact request", $result);
} else {
    sendResponse(201, true, "Contact request created successfully", $result);
}
