<?php
class PasswordManager {

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }

    public function verifyPassword($password, $passwordDb) {
        return password_verify($password, $passwordDb);
    }
}
?>