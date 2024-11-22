<?php

require_once(__DIR__ . '/../config/database.php');

class LandlordHandler 
{
    private $pdo;
    private $conn;

    public function __construct($pdo)
    {
        $databaseService = new DatabaseAccess();
        $this->conn = $databaseService->connect();
        $this->pdo = $pdo;
    }

    public function createAnnouncement($data) {
        // Extract the data from the incoming form data
        $title = $data['title'] ?? '';
        $content = $data['content'] ?? '';
        $landlord_id = $data['landlord_id'] ?? '';

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

        // Handle image upload
        if (isset($data['image']) && $data['image']['error'] == 0) {
            $image = $data['image'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = pathinfo($image['name'], PATHINFO_EXTENSION);

            if (!in_array($fileExtension, $allowedExtensions)) {
                return $this->sendErrorResponse('Invalid image format', 400);
            }

            $uploadDir = 'C:/xampp/htdocs/AMS/AMS/public/images/';
            $imagePath = $uploadDir . basename($image['name']);

            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                return $this->sendErrorResponse('Failed to upload image', 500);
            }

            $imagePath = '/images/' . basename($image['name']);
        } else {
            $imagePath = null;
        }

        $table_name = 'posts';
        $query = "INSERT INTO " . $table_name . " (title, content, landlord_id, image_path) VALUES (:title, :content, :landlord_id, :image_path)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':landlord_id', $landlord_id);
        $stmt->bindParam(':image_path', $imagePath);

        if ($stmt->execute()) {
            return $this->sendSuccessResponse('Announcement created successfully', 201);
        } else {
            return $this->sendErrorResponse('Failed to create announcement', 500);
        }
    }

    public function createApartment() {
        $data = json_decode(file_get_contents("php://input"));
    
        // Extract the data from the incoming JSON
        $room = $data->room ?? ''; // Make sure 'room' is being extracted
        $rent = $data->rent ?? '';
        $description = $data->description ?? '';
        $landlord_id = $data->landlord_id;
    
        // Validate if necessary
        $fields = [
            'room' => 'Apartment address cannot be empty',
            'rent' => 'Apartment rent cannot be empty',
            'description' => 'Apartment description cannot be empty',
        ];
    
        foreach ($fields as $field => $errorMessage) {
            if (empty($$field)) {
                return $this->sendErrorResponse($errorMessage, 400);
            }
        }
    
        // Insert the apartment into the database
        $query = "INSERT INTO apartments (room, rent, description, landlord_id) VALUES (:room, :rent, :description, :landlord_id)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':room', $room);
        $stmt->bindParam(':rent', $rent);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':landlord_id', $landlord_id);
    
        if ($stmt->execute()) {
            return $this->sendSuccessResponse("Apartment created successfully", 201);
        } else {
            return $this->sendErrorResponse("Failed to create apartment", 500);
        }
    }

    public function getPosts() {
        // Prepare the SQL query to get posts data
        $query = "SELECT * FROM posts";
    
        // Execute the query
        $stmt = $this->conn->prepare($query);
    
        if ($stmt->execute()) {
            $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $posts;
        } else {
            return $this->sendErrorResponse("Failed to retrieve posts", 500);
        }
    }
    
    public function getApartments() {
        // Prepare the SQL query to get apartments data
        $query = "SELECT * FROM apartments";
    
        // Execute the query
        $stmt = $this->conn->prepare($query);
    
        if ($stmt->execute()) {
            $apartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $apartments;
        } else {
            return $this->sendErrorResponse("Failed to retrieve apartments", 500);
        }
    }

    public function assignTenantToApartment() {
        $data = json_decode(file_get_contents("php://input"));
    
        // Extract the data from the incoming JSON
        $apartment_id = $data->apartment_id ?? null;
        $tenant_id = $data->tenant_id ?? null;
    
        // Validate input
        if (empty($apartment_id) || empty($tenant_id)) {
            return $this->sendErrorResponse("Apartment ID and Tenant ID are required.", 400);
        }
    
        // Check if the apartment exists and whether it already has a tenant assigned
        $queryCheckApartment = "SELECT tenant_id, room, rent FROM apartments WHERE apartment_id = :apartment_id";
        $stmtCheckApartment = $this->conn->prepare($queryCheckApartment);
        $stmtCheckApartment->bindParam(':apartment_id', $apartment_id);
    
        if (!$stmtCheckApartment->execute() || $stmtCheckApartment->rowCount() == 0) {
            return $this->sendErrorResponse("Apartment not found.", 404);
        }
    
        // Fetch apartment details
        $apartment = $stmtCheckApartment->fetch(PDO::FETCH_ASSOC);
        if (!empty($apartment['tenant_id'])) {
            return $this->sendErrorResponse("This apartment already has a tenant assigned.", 400);
        }
    
        $room = $apartment['room'];
        $rent = $apartment['rent'];
    
        // Check if the tenant exists
        $queryCheckTenant = "SELECT * FROM tenants WHERE tenant_id = :tenant_id";
        $stmtCheckTenant = $this->conn->prepare($queryCheckTenant);
        $stmtCheckTenant->bindParam(':tenant_id', $tenant_id);
    
        if (!$stmtCheckTenant->execute() || $stmtCheckTenant->rowCount() == 0) {
            return $this->sendErrorResponse("Tenant not found.", 404);
        }
    
        // Calculate next due date (one month from the current date)
        $dueDate = new DateTime();
        $dueDate->modify('+1 month');
        $formattedDueDate = $dueDate->format('Y-m-d');
    
        // Assign tenant to apartment (update apartments table)
        $queryAssignTenantToApartment = "UPDATE apartments SET tenant_id = :tenant_id WHERE apartment_id = :apartment_id";
        $stmtAssignTenantToApartment = $this->conn->prepare($queryAssignTenantToApartment);
        $stmtAssignTenantToApartment->bindParam(':tenant_id', $tenant_id);
        $stmtAssignTenantToApartment->bindParam(':apartment_id', $apartment_id);
    
        // Update tenants table to set apartment_id, set status as 'pending', copy room/rent from apartment, and set due date
        $queryAssignApartmentToTenant = "UPDATE tenants SET apartment_id = :apartment_id, status = 'pending', room = :room, rent = :rent, due_date = :due_date WHERE tenant_id = :tenant_id";
        $stmtAssignApartmentToTenant = $this->conn->prepare($queryAssignApartmentToTenant);
        $stmtAssignApartmentToTenant->bindParam(':apartment_id', $apartment_id);
        $stmtAssignApartmentToTenant->bindParam(':tenant_id', $tenant_id);
        $stmtAssignApartmentToTenant->bindParam(':room', $room);
        $stmtAssignApartmentToTenant->bindParam(':rent', $rent);
        $stmtAssignApartmentToTenant->bindParam(':due_date', $formattedDueDate);
    
        // Execute both queries inside a transaction to ensure data integrity
        try {
            $this->conn->beginTransaction();
    
            // Execute the queries
            if ($stmtAssignTenantToApartment->execute() && $stmtAssignApartmentToTenant->execute()) {
                $this->conn->commit();
                return $this->sendSuccessResponse("Tenant successfully assigned to apartment.", 200);
            } else {
                $this->conn->rollBack();
                return $this->sendErrorResponse("Failed to assign tenant to apartment.", 500);
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $this->sendErrorResponse("An error occurred: " . $e->getMessage(), 500);
        }
    }

    public function updateApartmentAndTenant() {
        $data = json_decode(file_get_contents("php://input"));
    
        // Extract the data from the incoming JSON
        $apartment_id = $data->apartment_id ?? null;
        $tenant_id = $data->tenant_id ?? null;
        $action = $data->action ?? null; // Specify the action: 'remove_tenant'
    
        // Validate input
        if (empty($apartment_id) || empty($action)) {
            return $this->sendErrorResponse("Apartment ID and action are required.", 400);
        }
    
        // Check if the apartment exists
        $queryCheckApartment = "SELECT tenant_id FROM apartments WHERE apartment_id = :apartment_id";
        $stmtCheckApartment = $this->conn->prepare($queryCheckApartment);
        $stmtCheckApartment->bindParam(':apartment_id', $apartment_id);
    
        if (!$stmtCheckApartment->execute() || $stmtCheckApartment->rowCount() == 0) {
            return $this->sendErrorResponse("Apartment not found.", 404);
        }
    
        $apartment = $stmtCheckApartment->fetch(PDO::FETCH_ASSOC);
        $current_tenant_id = $apartment['tenant_id'];
    
        // Start a transaction to ensure data consistency
        try {
            $this->conn->beginTransaction();
    
            if ($action === 'remove_tenant') {
                // Check if a tenant is assigned
                if (empty($current_tenant_id)) {
                    return $this->sendErrorResponse("No tenant is currently assigned to this apartment.", 400);
                }
    
                // Remove the tenant from the apartment
                $queryUpdateApartment = "UPDATE apartments SET tenant_id = NULL WHERE apartment_id = :apartment_id";
                $stmtUpdateApartment = $this->conn->prepare($queryUpdateApartment);
                $stmtUpdateApartment->bindParam(':apartment_id', $apartment_id);
    
                // Update the tenant's record
                $queryUpdateTenant = "UPDATE tenants SET apartment_id = NULL, status = 'inactive', rent = NULL, room = NULL, due_date = NULL WHERE tenant_id = :tenant_id";
                $stmtUpdateTenant = $this->conn->prepare($queryUpdateTenant);
                $stmtUpdateTenant->bindParam(':tenant_id', $current_tenant_id);
    
                // Execute both updates
                if ($stmtUpdateApartment->execute() && $stmtUpdateTenant->execute()) {
                    $this->conn->commit();
                    return $this->sendSuccessResponse("Tenant removed from apartment successfully.", 200);
                } else {
                    $this->conn->rollBack();
                    return $this->sendErrorResponse("Failed to remove tenant from apartment.", 500);
                }
            } else {
                $this->conn->rollBack();
                return $this->sendErrorResponse("Invalid action specified.", 400);
            }
        } catch (Exception $e) {
            $this->conn->rollBack();
            return $this->sendErrorResponse("An error occurred: " . $e->getMessage(), 500);
        }
    }
    
    
    
    
    
    
    public function getTenants() {
        // Prepare the SQL query to get tenants data
        $query = "SELECT * FROM tenants";

        // Execute the query
        $stmt = $this->conn->prepare($query);

        if ($stmt->execute()) {
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $tenants;
        } else {
            return $this->sendErrorResponse("Failed to retrieve tenants", 500);
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