<?php
class PasswordManager {

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword($password, $passwordDb) {
        return password_verify($password, $passwordDb);
    }

    /**
     * Encripta un texto usando AES-256-CBC
     */
    public function encrypt($plaintext) {
        $iv = random_bytes(16); // vector de inicialización de 16 bytes
        $cipher = openssl_encrypt($plaintext, 'AES-256-CBC', $_ENV['ENCRYPT_SECRET'], 0, $iv);
        // Retornar IV + texto cifrado codificado en base64 
        return base64_encode($iv . $cipher);
    }

    /**
     * Desencripta un texto previamente cifrado
     */
    public function decrypt($ciphertext) {
        $data = base64_decode($ciphertext);
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        return openssl_decrypt($cipher, 'AES-256-CBC', $_ENV['ENCRYPT_SECRET'], 0, $iv);
    }

}
?>