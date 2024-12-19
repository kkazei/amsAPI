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
    
            // Fetch tenant details (tenant_fullname, room)
            $stmt = $this->conn->prepare("SELECT tenant_fullname, room FROM tenants WHERE tenant_id = :tenant_id");
            $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
            $stmt->execute();
            $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if (!$tenant) {
                throw new Exception('Tenant not found.');
            }
    
            // Check if the tenant has a room assigned
            if (empty($tenant['room'])) {
                throw new Exception('No room assigned to the tenant. Payment cannot be processed.');
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
    
    public function createConcern($data) {
        // Extract the data from the incoming form data
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $tenant_id = $data['tenant_id'] ?? '';
        $status = 'pending'; // Default status
    
        // Validate if necessary
        $fields = [
            'title' => 'Announcement title cannot be empty',
            'content' => 'Announcement content cannot be empty',
        ];
    
        foreach ($fields as $field => $errorMessage) {
            if (empty($$field)) {
                return $this->sendErrorResponse($errorMessage, 400);
            }
        }
    
        // Handle image upload (from $_FILES)
        $imagePath = null;
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] == 0) {
            $image = $_FILES['attachment']; // Accessing file from $_FILES superglobal
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    
            if (!in_array($fileExtension, $allowedExtensions)) {
                return $this->sendErrorResponse('Invalid image format', 400);
            }
    
            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/concerns/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
    
            // Generate a unique file path
            $imagePath = $uploadDir . uniqid() . '.' . $fileExtension;
            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                return $this->sendErrorResponse('Failed to upload image', 500);
            }
    
            // Store relative image path in the database
            $imagePath = 'uploads/concerns/' . basename($imagePath);
        }
    
        // Insert the concern record into the database
        $query = "INSERT INTO concerns (title, content, tenant_id, status, image_path)
                  VALUES (:title, :content, :tenant_id, :status, :image_path)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':tenant_id', $tenant_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':image_path', $imagePath);
    
            if ($stmt->execute()) {
                return $this->sendSuccessResponse("Concern created successfully.", 201);
            } else {
                return $this->sendErrorResponse("Failed to create concern.", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }
    
    
    public function getConcern() {
        // Prepare the SQL query to get posts data
        $query = "SELECT * FROM concerns";
    
        // Execute the query
        $stmt = $this->conn->prepare($query);
    
        if ($stmt->execute()) {
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $posts;
        } else {
            return $this->sendErrorResponse("Failed to retrieve posts", 500);
        }
    }
    
    
    public function getPaymentDetails() {
        // Prepare the SQL query to get invoice data where isVisible = 1
        $query = "SELECT * FROM invoice WHERE isVisible = 1";
        
        // Execute the query
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute()) {
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $payments;
        } else {
            return $this->sendErrorResponse("Failed to retrieve payment details", 500);
        }
    }

    public function getArchivedPayments() {
        // Prepare the SQL query to get invoice data where isVisible = 1
        $query = "SELECT * FROM invoice WHERE isVisible = 0";
        
        // Execute the query
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute()) {
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $payments;
        } else {
            return $this->sendErrorResponse("Failed to retrieve payment details", 500);
        }
    }

    public function updatePaymentVisibility($invoiceId) {
        try {
            // Prepare the SQL query to update the isVisible field
            $query = "UPDATE invoice SET isVisible = 0 WHERE invoice_id = :invoice_id";
            $stmt = $this->conn->prepare($query);
            
            // Bind the invoice ID parameter
            $stmt->bindParam(':invoice_id', $invoiceId, PDO::PARAM_INT);
            
            // Execute the query
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Payment visibility updated to archived (isVisible = 0).'
                ];
            } else {
                return $this->sendErrorResponse("Failed to update payment visibility", 500);
            }
        } catch (Exception $e) {
            return $this->sendErrorResponse("An error occurred: " . $e->getMessage(), 500);
        }
    }
    
    public function restorePaymentVisibility($invoiceId) {
        try {
            // Prepare the SQL query to update the isVisible field
            $query = "UPDATE invoice SET isVisible = 1 WHERE invoice_id = :invoice_id";
            $stmt = $this->conn->prepare($query);
            
            // Bind the invoice ID parameter
            $stmt->bindParam(':invoice_id', $invoiceId, PDO::PARAM_INT);
            
            // Execute the query
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Payment visibility restored (isVisible = 1).'
                ];
            } else {
                return $this->sendErrorResponse("Failed to restore payment visibility", 500);
            }
        } catch (Exception $e) {
            return $this->sendErrorResponse("An error occurred: " . $e->getMessage(), 500);
        }
    }
    
    

    public function deleteTenant($tenantId) {
        $query = "DELETE FROM tenants WHERE tenant_id = :tenantId";
        $stmt = $this->pdo->prepare($query);
        $stmt->bindParam(':tenantId', $tenantId);
        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Tenant deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete tenant'];
        }
    }

    public function deleteInvoice($invoiceId) {
        $query = "DELETE FROM invoice WHERE invoice_id = :invoice_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':invoice_id', $invoiceId, PDO::PARAM_INT);
        
        try {
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Invoice deleted successfully'
                ];
            } else {
                return $this->sendErrorResponse("Failed to delete invoice", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }
    
    public function importPayments($payments) {
        try {
            $this->conn->beginTransaction();
    
            foreach ($payments as $payment) {
                $query = "INSERT INTO invoice (tenant_id, tenant_fullname, room, amount, payment_date)
                          VALUES (:tenant_id, :tenant_fullname, :room, :amount, :payment_date)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindValue(':tenant_id', $payment['tenant_id'], PDO::PARAM_INT);
                $stmt->bindValue(':tenant_fullname', $payment['tenant_fullname']);
                $stmt->bindValue(':room', $payment['room']);
                $stmt->bindValue(':amount', $payment['amount']);
                $stmt->bindValue(':payment_date', $payment['payment_date']);
                $stmt->execute();
            }
    
            $this->conn->commit();
            return ['status' => 'success', 'message' => 'Payments imported successfully'];
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
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