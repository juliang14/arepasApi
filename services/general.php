<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/DataProvider.php';
require_once __DIR__ . '/../config/passwordManager.php';
require_once __DIR__ . '/../config/response.php'; // Importa la función sendResponse()
require_once __DIR__ . '/../vendor/autoload.php';

DataProvider::validateRequestMethod();

$input = DataProvider::getDataGeneral();
$passwordManager = new PasswordManager();
$clave = $input['clave'];

$newPassword = $passwordManager->hashPassword($clave);

sendResponse(200, true, "success", [
    "newPassword" => $newPassword
]);

?>