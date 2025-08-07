<?php
define('JWT_SECRET', $_ENV['JWT_SECRET']);  // Cambia esto por una clave segura
define('JWT_EXPIRE_TIME', 3600);  // Expira en 1 hora (3600 segundos)

function jwtSecret(): string {
    return JWT_SECRET;
}
?>
