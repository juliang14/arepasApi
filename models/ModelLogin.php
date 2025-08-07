<?php
require_once __DIR__ . '/../config/database.php';

class ModelLogin {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getUser($usuario) {
        try {

            $stmt = $this->conn->prepare("CALL PR_GET_USER_LOGIN(?)");
            $stmt->bind_param("s",$usuario);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            return $user ? $user : null;
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
?>
