<?php
require_once __DIR__ . '/../config/database.php';

class ModelContact {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function createContact($data) {
        try {
            $stmt = $this->conn->prepare("
                CALL PR_CREATE_CONTACT_REQUEST(?, ?, ?)
            ");

            $stmt->bind_param(
                "sis",
                $data["name"],
                $data["phone"],
                $data["preferred_time"]
            );

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result) {
                $row = $result->fetch_assoc();
                return ["contact_request_id" => $row["NEW_CONTACT_REQUEST_ID"]];
            } else {
                return ["error" => "No result returned"];
            }

        } catch (mysqli_sql_exception $e) {
            return ["error" => $e->getMessage()];
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
