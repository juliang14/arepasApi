<?php
use Dotenv\Dotenv;


require __DIR__.'/vendor/autoload.php';

Dotenv::createImmutable(__DIR__)->load();

// CORS y middleware
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/middlewares/authMiddleware.php';


// Manejador global de excepciones
set_exception_handler(function ($exception) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error interno del servidor: " . $exception->getMessage(),
        "file"    => $exception->getFile(),
        "line"    => $exception->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// Manejador global de errores (notices, warnings, etc.)
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Error en el servidor: [$errno] $errstr en $errfile línea $errline"
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

// Captura de errores fatales al finalizar la ejecución del script
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && ($error['type'] === E_ERROR || $error['type'] === E_PARSE)) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Error fatal: {$error['message']} en {$error['file']} línea {$error['line']}"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});


$requestUri = $_GET['route'] ?? '';

$basePublicPath = '/services/';
$baseProtectedPath = '/services/'; // Si cambia, puedes separarlos

// Mapa de rutas públicas (sin repetir la base)
$publicRoutes = [
    "login" => "login.php",
    "general" => "general.php",
    "products/get" => "products/getProduct.php"
];

// Mapa de rutas protegidas
$protectedRoutes = [
    //"finance/create" => "finance/create.php",
    //"user/update" => "user/update.php"
];

if (array_key_exists($requestUri, $protectedRoutes)) {
    verificarToken();
    require_once __DIR__ . $baseProtectedPath . $protectedRoutes[$requestUri];
} elseif (array_key_exists($requestUri, $publicRoutes)) {
    require_once __DIR__ . $basePublicPath . $publicRoutes[$requestUri];
} else {
    http_response_code(404);
    echo json_encode(["success" => false, "message" => "Ruta no encontrada"], JSON_UNESCAPED_UNICODE);
}