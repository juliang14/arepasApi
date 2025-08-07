<?php
require_once __DIR__ . '/../config/database.php';

class ModelProduct {
    private $conn;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getProducts() {
         try {
            $stmt = $this->conn->prepare("SELECT * FROM view_available_products");
            $stmt->execute();

            $result = $stmt->get_result();
            $products = [];

            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            return $products;
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }
}
?>
