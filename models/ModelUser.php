<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/passwordManager.php';

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

    public function createUser($data) {
        $passwordManager = new PasswordManager();
        $clave = $passwordManager->hashPassword($data['password']);
        try {
            $stmt = $this->conn->prepare("
                CALL PR_CREATE_USER(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "ssssisiissssi",
                $data["first_name"],
                $data["middle_name"],
                $data["first_surname"],
                $data["second_surname"],
                $data["id_document"],
                $data["document_number"],
                $data["age"],
                $data["phone"],
                $data["address"],
                $data["email"],
                $clave,
                $data["status"],
                $data["id_role"]
            );

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result) {
                $row = $result->fetch_assoc();
                return ["user_id" => $row["NEW_USER_ID"]];
            } else {
                return ["error" => "No result returned"];
            }

        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
    
}
?>
