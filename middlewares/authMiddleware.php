<?php
require_once __DIR__ . '/../config/response.php'; // Importa la función sendResponse()
require_once __DIR__ . '/../config/jwt_config.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verificarToken(): void {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? ($headers['authorization'] ?? '');

    if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $m)) {
        sendResponse(401, false, 'Token no enviado');
        exit;
    }

    $token = $m[1];

    try {
        JWT::decode($token, new Key(jwtSecret(), 'HS256'));
        // Si llega aquí, el token es válido.
    } catch (\Exception $e) {
        sendResponse(401, false, 'Token inválido o expirado');
        exit;
    }
}
