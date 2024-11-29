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
                }
    
                // Create uploads directory if it doesn't exist
                $uploadDir = __DIR__ . '/../uploads/proof_of_payment/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
    
                $proofOfPaymentPath = $uploadDir . uniqid() . '.' . $fileExtension;
                if (!move_uploaded_file($proofOfPaymentFile['tmp_name'], $proofOfPaymentPath)) {                }
    
                // Save relative path for database storage
                $proofOfPaymentPath = 'uploads/proof_of_payment/' . basename($proofOfPaymentPath);
            }
    
            // Begin a transaction
            $this->conn->beginTransaction();
    
            // Insert payment record in the `invoice` table
            $stmt = $this->conn->prepare("
                INSERT INTO invoice (tenant_id, amount, payment_date, reference_number, proof_of_payment)
                VALUES (:tenant_id, :amount, NOW(), :reference_number, :proof_of_payment)
            ");
            $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
            $stmt->bindParam(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindParam(':reference_number', $referenceNumber, PDO::PARAM_STR);
            $stmt->bindParam(':proof_of_payment', $proofOfPaymentPath, PDO::PARAM_STR);
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
    
    public function getPaymentDetails($tenantId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT tenant_id, amount, payment_date, reference_number, proof_of_payment
                FROM invoice
                WHERE tenant_id = :tenant_id
            ");
            $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
            $stmt->execute();
            
            $paymentDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $paymentDetails
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
        }
    }
}