<?php
class PasswordManager {

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword($password, $passwordDb) {
        return password_verify($password, $passwordDb);
    }

    /**
     * Encripta un texto usando AES-256-CBC y devuelve Base64 (IV + ciphertext)
     * @param string $plaintext Texto a encriptar
     * @return string Base64 del IV concatenado con el ciphertext
     */
    public static function encrypt($plaintext) {
        $key = $_ENV['ENCRYPT_SECRET'] ?? '';
        if (strlen($key) !== 32) {
            throw new Exception("La clave de encriptación debe tener 32 caracteres");
        }

        // Generar IV de 16 bytes
        $iv = random_bytes(16);

        // Cifrar el texto
        $ciphertext = openssl_encrypt(
            $plaintext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA, // clave exacta, sin padding extra
            $iv
        );

        // Concatenar IV + ciphertext y codificar en Base64
        return base64_encode($iv . $ciphertext);
    }

    /**
     * Desencripta un Base64 generado con encrypt()
     * @param string $base64 Encriptado (IV + ciphertext)
     * @return string Texto original
     */
    public static function decrypt($base64) {
        $key = $_ENV['ENCRYPT_SECRET'] ?? '';
        if (strlen($key) !== 32) {
            throw new Exception("La clave de encriptación debe tener 32 caracteres");
        }

        $data = base64_decode($base64);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);

        return openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    }

}
?>