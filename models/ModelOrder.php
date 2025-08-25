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
            $paypal = $this->getPayPalClientId();

            return [
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'paypal' => $paypal,
                    'order_id' => $orderId,
                    'total'    => $totalAmount
                ]
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
        $timestamp = date('YmdHis');
        $dynamicString = $clientId . '|' . $timestamp;

        return $this->passwordManager->encrypt($dynamicString);
    }

    /**
     * Obtiene las órdenes por id_order o id_user
     */
    public function getOrders($id_order = null, $id_user = null) {
        try {
            $query = "SELECT * FROM view_customer_orders_with_details WHERE 1=1";
            $params = [];
            $types  = "";

            if ($id_order) {
                $query .= " AND id_order = ?";
                $params[] = $id_order;
                $types .= "i";
            }

            if ($id_user) {
                $query .= " AND id_user = ?";
                $params[] = $id_user;
                $types .= "i";
            }

            $stmt = $this->conn->prepare($query);

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }

            $stmt->close();

            return $orders;

        } catch (mysqli_sql_exception $e) {
            return ['error' => $e->getMessage()];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function updateOrderStatusFromPaypal($id_order, $paypalOrderId) {
        try {
            // ---------------------------
            // 1️⃣ Obtener token de PayPal
            // ---------------------------
            $clientId = $_ENV['PAYPAL_CLIENT_ID'] ?? '';
            $secret   = $_ENV['PAYPAL_SECRET'] ?? '';

            $ch = curl_init("https://api-m.sandbox.paypal.com/v1/oauth2/token");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERPWD, "$clientId:$secret");
            curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
            $responseToken = curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['success' => false, 'message' => "Error cURL token: $error"];
            }
            curl_close($ch);

            $dataToken = json_decode($responseToken, true);
            $accessToken = $dataToken['access_token'] ?? null;
            if (!$accessToken) {
                return ['success' => false, 'message' => "No se obtuvo access_token"];
            }

            // ---------------------------
            // 2️⃣ Consultar estado de la orden
            // ---------------------------
            $url = "https://api-m.sandbox.paypal.com/v2/checkout/orders/$paypalOrderId";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer $accessToken"
            ]);
            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['success' => false, 'message' => "Error cURL order: $error"];
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return ['success' => false, 'message' => "PayPal API returned HTTP $httpCode: $response"];
            }

            $orderData = json_decode($response, true);
            $paypalStatusRaw = $orderData['status'] ?? 'UNKNOWN';

            // ---------------------------
            // 3️⃣ Mapear estado PayPal a DB
            // ---------------------------
            switch ($paypalStatusRaw) {
                case 'CREATED':
                case 'SAVED':
                case 'APPROVED':
                case 'PAYER_ACTION_REQUIRED':
                    $paypalStatus = 'pending';
                    break;
                case 'COMPLETED':
                    $paypalStatus = 'paid';
                    break;
                case 'VOIDED':
                case 'FAILED':
                    $paypalStatus = 'cancelled';
                    break;
                default:
                    $paypalStatus = 'pending';
            }

            // ---------------------------
            // 4️⃣ Actualizar estado en DB
            // ---------------------------
            $stmt = $this->conn->prepare("CALL PR_UPDATE_ORDER_STATUS(?, ?)");
            $stmt->bind_param("is", $id_order, $paypalStatus);
            $stmt->execute();
            $stmt->close();

            return [
                'success' => true,
                'message' => 'Orden actualizada correctamente',
                'paypal_status' => $paypalStatus
            ];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

}
