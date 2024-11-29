<?php

require_once(__DIR__ . '/../config/database.php');

class TenantHandler{
    private $pdo;
    private $conn;

    public function __construct($pdo)
    {
        $databaseService = new DatabaseAccess();
        $this->conn = $databaseService->connect();
        $this->pdo = $pdo;
    }

    public function payInvoice($tenantId, $amount, $referenceNumber, $proofOfPaymentFile) {
        try {
            // Validate image file if provided
            $proofOfPaymentPath = null; // Default if no image is uploaded
            if ($proofOfPaymentFile && $proofOfPaymentFile['error'] == 0) {
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                $fileExtension = strtolower(pathinfo($proofOfPaymentFile['name'], PATHINFO_EXTENSION));
        
                if (!in_array($fileExtension, $allowedExtensions)) {
                    // Optionally, handle invalid file type
                }
        
                // Create uploads directory if it doesn't exist
                $uploadDir = __DIR__ . '/../uploads/proof_of_payment/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
        
                $proofOfPaymentPath = $uploadDir . uniqid() . '.' . $fileExtension;
                if (!move_uploaded_file($proofOfPaymentFile['tmp_name'], $proofOfPaymentPath)) {
                    // Optionally, handle file upload failure
                }
        
                // Save relative path for database storage
                $proofOfPaymentPath = 'uploads/proof_of_payment/' . basename($proofOfPaymentPath);
            }
        
            // Begin a transaction
            $this->conn->beginTransaction();
        
            // Fetch tenant details (tenant_fullname and room)
            $stmt = $this->conn->prepare("SELECT tenant_fullname, room FROM tenants WHERE tenant_id = :tenant_id");
            $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
            $stmt->execute();
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$tenant) {
                throw new Exception('Tenant not found.');
            }
    
            // Insert payment record in the `invoice` table
            $stmt = $this->conn->prepare("
                INSERT INTO invoice (tenant_id, amount, payment_date, reference_number, proof_of_payment, tenant_fullname, room)
                VALUES (:tenant_id, :amount, NOW(), :reference_number, :proof_of_payment, :tenant_fullname, :room)
            ");
            $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':reference_number', $referenceNumber, PDO::PARAM_STR);
            $stmt->bindParam(':proof_of_payment', $proofOfPaymentPath, PDO::PARAM_STR);
            $stmt->bindParam(':tenant_fullname', $tenant['tenant_fullname'], PDO::PARAM_STR);
            $stmt->bindParam(':room', $tenant['room'], PDO::PARAM_STR);
            $stmt->execute();
        
            // Update the tenant's due_date (add 30 days to the current due_date)
            $stmt = $this->conn->prepare("
                UPDATE tenants
                SET due_date = DATE_ADD(due_date, INTERVAL 30 DAY)
                WHERE tenant_id = :tenant_id
            ");
            $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
            $stmt->execute();
        
            // Commit the transaction
            $this->conn->commit();
        
            return [
                'status' => 'success',
                'message' => 'Payment recorded successfully. The due date has been updated.'
            ];
        } catch (Exception $e) {
            // Roll back the transaction if there was an error
            $this->conn->rollBack();
        
            return [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
        }
    }
    
    
    public function getPaymemtDetails() {
        // Prepare the SQL query to get posts data
        $query = "SELECT * FROM invoice";
    
        // Execute the query
        $stmt = $this->conn->prepare($query);
    
        if ($stmt->execute()) {
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $posts;
        } else {
            return $this->sendErrorResponse("Failed to retrieve posts", 500);
        }
    }

    private function sendErrorResponse($message, $statusCode) {
        http_response_code($statusCode);
        echo json_encode(['error' => $message]);
        exit;
    }

    private function sendSuccessResponse($message, $statusCode) {
        http_response_code($statusCode);
        echo json_encode(['message' => $message]);
        exit;
    }
}