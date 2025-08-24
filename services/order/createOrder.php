<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/DataProvider.php';
require_once __DIR__ . '/../../models/ModelOrder.php';

// Validar método HTTP
DataProvider::validateRequestMethod("POST");

// Obtener datos del request
$input = DataProvider::getCreateOrder();

// Preparar datos para el modelo
$data = [
    'user_id' => $input['user_id'] ?? null,
    'items'   => $input['items'] ?? [],
    'total'   => $input['total'] ?? 0
];

// Validaciones básicas
if (!$data['user_id']) {
    sendResponse(401, false, "User not authenticated");
    exit;
}

if (empty($data['items'])) {
    sendResponse(400, false, "No products in order");
    exit;
}

$model = new ModelOrder();
$response = $model->createOrder($data);

sendResponse(
    $response['success'] ? 201 : 400,
    $response['success'],
    $response['message'],
    $response['data'] ?? []
);
