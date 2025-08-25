<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/DataProvider.php';
require_once __DIR__ . '/../../models/ModelOrder.php';

DataProvider::validateRequestMethod();

$input = DataProvider::getPaypalOrderData();

if (!$input['id_order'] || !$input['paypal_order_id']) {
    sendResponse(400, false, "Faltan parámetros: id_order o paypal_order_id");
}

$modelOrder = new ModelOrder();
$response = $modelOrder->updateOrderStatusFromPaypal($input['id_order'], $input['paypal_order_id']);

sendResponse($response['success'] ? 200 : 400, $response['success'], $response['message'], $response);
