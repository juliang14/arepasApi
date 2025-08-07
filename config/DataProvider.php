<?php

class DataProvider {

    /**
     * Valida que el método HTTP sea POST.
     */
    public static function validateRequestMethod() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse(405, false, "El método {$_SERVER['REQUEST_METHOD']} no es permitido");
        }
    }

    /**
     * Obtiene los datos JSON de la petición y valida los campos requeridos.
     * @param array $requiredFields Campos requeridos
     * @return array Datos decodificados
     */
    private static function getJsonInput(array $requiredFields = []) {
        $input = json_decode(file_get_contents("php://input"), true);

        if (!is_array($input)) {
            sendResponse(400, false, "JSON inválido.");
        }

        foreach ($requiredFields as $field) {
            if (!isset($input[$field]) || (is_string($input[$field]) && trim($input[$field]) === '')) {
                sendResponse(400, false, "Falta el parámetro requerido: $field");
            }
        }

        return $input;
    }

    public static function getDataLogin() {
        return self::getJsonInput(['username', 'password']);
    }

    public static function getDataGeneral() {
        return self::getJsonInput(['clave']);
    }

    public static function getDataProducts() {
        return self::getJsonInput(['type']);
    }
}
?>
