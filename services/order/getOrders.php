<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/response.php';
require_once __DIR__ . '/../../config/DataProvider.php';
require_once __DIR__ . '/../../models/ModelOrder.php';

// Validar método HTTP
DataProvider::validateRequestMethod("POST");

// Obtener datos del request
$input = DataProvider::getOrders();

$id_order = isset($input['id_order']) ? intval($input['id_order']) : null;
$id_user  = isset($input['id_user']) ? intval($input['id_user']) : null;

if (!$id_order && !$id_user) {
    sendResponse(400, false, "Debe proporcionar id_order o id_user");
    exit;
}

$modelOrder = new ModelOrder();
$orders = $modelOrder->getOrders($id_order, $id_user);

if (isset($orders['error'])) {
    sendResponse(500, false, "Error al obtener órdenes", $orders);
} else {
    sendResponse(200, true, "Órdenes obtenidas correctamente", $orders);
}
