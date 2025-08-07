<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/passwordManager.php';
require_once __DIR__ . '/../config/jwt_config.php';
require_once __DIR__ . '/../config/response.php'; // Importa la función sendResponse()
require_once __DIR__ . '/../config/DataProvider.php';
require_once __DIR__ . '/../models/ModelLogin.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

DataProvider::validateRequestMethod();

$input = DataProvider::getDataLogin();

$passwordManager = new PasswordManager();

$usuario = $input['username'];
$clave = $input['password'];
$expiracion = isset($input['expiracion']) ? intval($input['expiracion']) : $_ENV['TIME_JWT']; // Valor en minutos (por defecto, 60 min)

$modelLogin = new ModelLogin();
$user = $modelLogin->getUser($usuario);

// 🔹 Validar que el usuario exista
if (!$user || !isset($user['PASSWORD'])) {
    sendResponse(401, false, "Usuario o clave incorrecta"); 
}else if (!$passwordManager->verifyPassword($clave, $user['PASSWORD'])) {
    sendResponse(401, false, "Clave no valida"); 
}

// 🔹 Configuración de expiración del token
define("TOKEN_EXPIRACION_MINUTOS", $_ENV['TIME_JWT']); 
$expTime = time() + (TOKEN_EXPIRACION_MINUTOS * 60);

// 🔹 Crear el token JWT
$payload = [
    "id" => $user["ID"],
    //"name" => $user["NAME"],
    //"email" => $user["EMAIL"],
    //"role" => $user["ROLE"],
    "iat" => time(),  // Tiempo de emisión
    "exp" => $expTime // Expiración fija de 1 hora
];

$jwt = JWT::encode($payload, jwtSecret(), 'HS256');

// 🔹 Respuesta con el token JWT
sendResponse(200, true, "Login exitoso", [
    "token" => $jwt,
    "expiracion" => $expTime, // Enviar el tiempo de expiración al frontend
    "user" => [
        "id" => $user["ID"]/*,
        "name" => $user["NAME"],
        "email" => $user["EMAIL"],
        "role" => $user["ROLE"]*/
    ]
]);
