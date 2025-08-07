<?php
require_once __DIR__ . '/../config/database.php';

class ModelUser {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function manageFinance($action, $idFinance, $idUser, $idCategory, $idProvider, $value, $paymentDate, $courtDate) {
        try {

            $stmt = $this->conn->prepare("CALL PR_MANAGE_FINANCE(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss",$action, $idFinance, $idUser, $idCategory, $idProvider, $value, $paymentDate, $courtDate);
            $stmt->execute();
            $result = $stmt->get_result();
    
            $finances = [];
            while ($row = $result->fetch_assoc()) {
                $finances[] = $row;
            }
            return $finances;
        } catch (PDOException $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public function getFinanceByUser($idUser) {
        try {
            $stmt = $this->conn->prepare("SELECT * FROM VW_FINANCE WHERE ID_USER = ?");
            $stmt->bind_param("s", $idUser);
            $stmt->execute();
            $result = $stmt->get_result();
    
            $finances = [];
            while ($row = $result->fetch_assoc()) {
                $finances[] = $row;
            }
    
            return $finances;
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
    
}
?>
