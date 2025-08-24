<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/passwordManager.php'; // Para encriptar datos

class ModelOrder {
    private $conn;
    private $passwordManager;

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->passwordManager = new PasswordManager();
    }

    /**
     * Crea un nuevo pedido en la base de datos
     */
    public function createOrder($data) {
        try {
            $stmt = $this->conn->prepare("CALL PR_CREATE_UPDATE_ORDER(?, ?, ?)");

            $id_order = 0; // siempre crear nuevo
            $user_id  = $data['user_id'];
            $products_json = json_encode($data['items']); // convertir items a JSON

            $stmt->bind_param("iis", $id_order, $user_id, $products_json);
            $stmt->execute();

            // Obtener resultado del procedimiento
            $result = $stmt->get_result();
            if (!$result) {
                $stmt->close();
                return [
                    'success' => false,
                    'message' => 'Failed to create order'
                ];
            }

            $row = $result->fetch_assoc();
            $orderId = $row['ORDER_ID'];

            // Liberar resultados pendientes y cerrar statement
            $result->free();
            while ($this->conn->more_results() && $this->conn->next_result()) {
                if ($extraResult = $this->conn->store_result()) {
                    $extraResult->free();
                }
            }
            $stmt->close();

            // Calcular total exacto desde los items
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $stmtProd = $this->conn->prepare("SELECT price FROM product WHERE id_product = ?");
                $stmtProd->bind_param("i", $item['id_product']);
                $stmtProd->execute();
                $resProd = $stmtProd->get_result()->fetch_assoc();
                $totalAmount += $resProd['price'] * $item['quantity'];
                $stmtProd->close();
            }

            // Convertir total a entero (sin decimales)
            $totalAmount = (int) round($totalAmount);

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order_id' => $orderId,
                    'total'    => $totalAmount
                ],
                'paypal' => $this->getPayPalClientId()
            ];

        } catch (mysqli_sql_exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Retorna solo el client_id de PayPal encriptado para frontend
     */
    public function getPayPalClientId() {
        $clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
        $timestamp = date('YmdHis'); // fecha y hora con segundos
        $dynamicString = $clientId . '|' . $timestamp;
        return $this->passwordManager->encrypt($dynamicString);
    }
}
