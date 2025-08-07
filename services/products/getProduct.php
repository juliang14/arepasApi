<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/passwordManager.php';
require_once __DIR__ . '/../../config/jwt_config.php';
require_once __DIR__ . '/../../config/response.php'; // Importa la función sendResponse()
require_once __DIR__ . '/../../config/DataProvider.php';
require_once __DIR__ . '/../../models/ModelProduct.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

DataProvider::validateRequestMethod();

$input = DataProvider::getDataProducts();

$type = $input['type'];
$modelProduct = new ModelProduct();
$products = $modelProduct->getProducts();

if (isset($products['error'])) {
    sendResponse(401, false, "Error al obtener productos", $products);
} else {
    sendResponse(200, true, "Productos disponibles", [
        "products" => $products
    ]);
}
