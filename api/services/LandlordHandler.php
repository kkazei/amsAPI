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
        $imagePath = null;
        if (isset($data['image']) && $data['image']['error'] == 0) {
            $image = $data['image'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $fileExtension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    
            if (!in_array($fileExtension, $allowedExtensions)) {
                return $this->sendErrorResponse('Invalid image format', 400);
            }
    
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
    
            $imagePath = $uploadDir . uniqid() . '.' . $fileExtension;
            if (!move_uploaded_file($image['tmp_name'], $imagePath)) {
                return $this->sendErrorResponse('Failed to upload image', 500);
            }
    
            $imagePath = 'uploads/' . basename($imagePath);
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

    public function deletePost($post_id) {
        $query = "DELETE FROM posts WHERE post_id = :post_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':post_id', $post_id, PDO::PARAM_INT);
        
        try {
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Post deleted successfully'
                ];
            } else {
                return $this->sendErrorResponse("Failed to delete post", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }

    public function addLease($file, $tenantId, $room) {
        $code = 0;
        $errmsg = "";
    
        // File upload logic
        $targetDir = "uploads/leases/";
    
        // Check if the directory exists, if not create it
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
    
        // Set the target file path and get file extension
        $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowedTypes = array("jpg", "png", "jpeg", "gif");
    
        // Check file type
        if (in_array($fileExtension, $allowedTypes)) {
            $targetFile = $targetDir . uniqid() . '.' . $fileExtension;
            // Move the file to the target directory
            if (move_uploaded_file($file["tmp_name"], $targetFile)) {
                try {
                    // Fetch tenant_fullname based on tenant_id
                    $sqlFetch = "SELECT tenant_fullname FROM tenants WHERE tenant_id = ?";
                    $stmtFetch = $this->pdo->prepare($sqlFetch);
                    $stmtFetch->execute([$tenantId]);
                    $tenant = $stmtFetch->fetch(PDO::FETCH_ASSOC);
    
                    if ($tenant) {
                        $tenantFullname = $tenant['tenant_fullname'];
    
                        // Prepare SQL to insert the lease record
                        $sqlInsert = "INSERT INTO leases (imgName, img, tenant_id, room, tenant_fullname) VALUES (?, ?, ?, ?, ?)";
                        $stmtInsert = $this->pdo->prepare($sqlInsert);
                        $stmtInsert->execute([
                            basename($targetFile),  // imgName
                            $targetFile,            // img (path to file)
                            $tenantId,              // tenant_id
                            $room,                  // room
                            $tenantFullname         // tenant_fullname
                        ]);
                    } else {
                        $errmsg = "Tenant not found.";
                        $code = 404;
                    }
                } catch (\PDOException $e) {
                    $errmsg = "Error inserting lease record: " . $e->getMessage();
                    $code = 500;
                }
            } else {
                $errmsg = "Failed to upload the file.";
                $code = 400;
            }
        } else {
            $errmsg = "Invalid file type.";
            $code = 400;
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
      
        // Fetch new apartment details
        $apartment = $stmtCheckApartment->fetch(PDO::FETCH_ASSOC);
        if (!empty($apartment['tenant_id'])) {
            return $this->sendErrorResponse("This apartment already has a tenant assigned.", 400);
        }
      
        $room = $apartment['room'];
        $rent = $apartment['rent'];
      
        // Check if the tenant exists and get their current apartment
        $queryCheckTenant = "SELECT apartment_id, tenant_fullname FROM tenants WHERE tenant_id = :tenant_id";
        $stmtCheckTenant = $this->conn->prepare($queryCheckTenant);
        $stmtCheckTenant->bindParam(':tenant_id', $tenant_id);
      
        if (!$stmtCheckTenant->execute() || $stmtCheckTenant->rowCount() == 0) {
            return $this->sendErrorResponse("Tenant not found.", 404);
        }
      
        // Fetch current apartment ID and fullname of the tenant
        $tenant = $stmtCheckTenant->fetch(PDO::FETCH_ASSOC);
        $currentApartmentId = $tenant['apartment_id'];
        $tenantFullname = $tenant['tenant_fullname'];
      
        // Check if the tenant is already assigned to an apartment
        if (!empty($currentApartmentId)) {
            return $this->sendErrorResponse("This tenant is already assigned to an apartment.", 400);
        }
      
        // Start a transaction to ensure data integrity
        try {
            $this->conn->beginTransaction();
      
            // Assign tenant to the new apartment
            $queryAssignTenantToApartment = "UPDATE apartments SET tenant_id = :tenant_id, tenant_fullname = :tenant_fullname WHERE apartment_id = :apartment_id";
            $stmtAssignTenantToApartment = $this->conn->prepare($queryAssignTenantToApartment);
            $stmtAssignTenantToApartment->bindParam(':tenant_id', $tenant_id);
            $stmtAssignTenantToApartment->bindParam(':tenant_fullname', $tenantFullname);
            $stmtAssignTenantToApartment->bindParam(':apartment_id', $apartment_id);
            $stmtAssignTenantToApartment->execute();
      
            // Update tenants table to reflect the new apartment and related details
            $dueDate = new DateTime();
            $dueDate->modify('+1 month');
            $formattedDueDate = $dueDate->format('Y-m-d');
      
            // Add the assigned_date field with the current timestamp
            $assignedDate = (new DateTime())->format('Y-m-d H:i:s');
      
            $queryAssignApartmentToTenant = "UPDATE tenants SET apartment_id = :apartment_id, status = 'pending', room = :room, rent = :rent, due_date = :due_date, assigned_date = :assigned_date WHERE tenant_id = :tenant_id";
            $stmtAssignApartmentToTenant = $this->conn->prepare($queryAssignApartmentToTenant);
            $stmtAssignApartmentToTenant->bindParam(':apartment_id', $apartment_id);
            $stmtAssignApartmentToTenant->bindParam(':tenant_id', $tenant_id);
            $stmtAssignApartmentToTenant->bindParam(':room', $room);
            $stmtAssignApartmentToTenant->bindParam(':rent', $rent);
            $stmtAssignApartmentToTenant->bindParam(':due_date', $formattedDueDate);
            $stmtAssignApartmentToTenant->bindParam(':assigned_date', $assignedDate);
            $stmtAssignApartmentToTenant->execute();
      
            // Commit the transaction
            $this->conn->commit();
            return $this->sendSuccessResponse("Tenant successfully assigned to the new apartment.", 200);
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
                $queryUpdateApartment = "UPDATE apartments SET tenant_id = NULL, tenant_fullname = NULL WHERE apartment_id = :apartment_id";
                $stmtUpdateApartment = $this->conn->prepare($queryUpdateApartment);
                $stmtUpdateApartment->bindParam(':apartment_id', $apartment_id);
    
                // Update the tenant's record, including removing the assigned_date
                $queryUpdateTenant = "UPDATE tenants SET apartment_id = NULL, status = 'inactive', rent = NULL, room = NULL, due_date = NULL, assigned_date = NULL WHERE tenant_id = :tenant_id";
                $stmtUpdateTenant = $this->conn->prepare($queryUpdateTenant);
                $stmtUpdateTenant->bindParam(':tenant_id', $current_tenant_id);
    
                // Remove the leases data for the tenant
                $queryDeleteLeases = "DELETE FROM leases WHERE tenant_id = :tenant_id";
                $stmtDeleteLeases = $this->conn->prepare($queryDeleteLeases);
                $stmtDeleteLeases->bindParam(':tenant_id', $current_tenant_id);
    
                // Execute all updates
                if ($stmtUpdateApartment->execute() && $stmtUpdateTenant->execute() && $stmtDeleteLeases->execute()) {
                    $this->conn->commit();
                    return $this->sendSuccessResponse("Tenant and associated leases removed from apartment successfully.", 200);
                } else {
                    $this->conn->rollBack();
                    return $this->sendErrorResponse("Failed to remove tenant and associated leases from apartment.", 500);
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

    public function getLeases() {
        try {
            // Prepare SQL statement to fetch images
            $sql = "SELECT * FROM leases";
            $stmt = $this->pdo->query($sql);
            
            // Fetch all rows as an associative array
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return the fetched images data
            return [
                "status" => "success",
                "message" => "Successfully retrieved images.",
                "data" => $result
            ];
        } catch(PDOException $e) {
            // Handle any potential errors
            return [
                "status" => "error",
                "message" => "Failed to retrieve images: " . $e->getMessage()
            ];
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

    public function addImage($file, $description, $landlord_id) {
        $code = 0;
        $errmsg = "";
    
        // Check if an image already exists for the landlord
        $sqlCheck = "SELECT COUNT(*) FROM images WHERE landlord_id = ?";
        try {
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([$landlord_id]);
            $imageCount = $stmtCheck->fetchColumn();
    
            if ($imageCount > 0) {
                $errmsg = "An image already exists for this landlord.";
                $code = 400;
                return [
                    'code' => $code,
                    'errmsg' => $errmsg
                ];
            }
        } catch (\PDOException $e) {
            $errmsg = "Error checking existing image: " . $e->getMessage();
            $code = 500;
            return [
                'code' => $code,
                'errmsg' => $errmsg
            ];
        }
    
        // File upload logic
        $targetDir = "uploads/";
    
        // Check if the directory exists, if not create it
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
    
        $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $allowedTypes = array("jpg", "png", "jpeg", "gif");
    
        if (in_array($fileExtension, $allowedTypes)) {
            $targetFile = $targetDir . uniqid() . '.' . $fileExtension;
            if (move_uploaded_file($file["tmp_name"], $targetFile)) {
                $sql = "INSERT INTO images (imgName, img, description, landlord_id) VALUES (?, ?, ?, ?)";
                try {
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([
                        basename($targetFile),
                        $targetFile,
                        $description,
                        $landlord_id
                    ]);
                } catch (\PDOException $e) {
                    $errmsg = "Error inserting image record: " . $e->getMessage();
                    $code = 500;
                }
            } else {
                $errmsg = "Failed to move uploaded file.";
                $code = 500;
            }
        } else {
            $errmsg = "Unsupported file type.";
            $code = 400;
        }
    
        return [
            'code' => $code,
            'errmsg' => $errmsg
        ];
    }

    public function deleteImage($landlord_id) {
        $code = 0;
        $errmsg = "";
    
        // Check if an image exists for the landlord
        $sqlCheck = "SELECT img FROM images WHERE landlord_id = ?";
        try {
            $stmtCheck = $this->pdo->prepare($sqlCheck);
            $stmtCheck->execute([$landlord_id]);
            $image = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
            if (!$image) {
                $errmsg = "No image found for this landlord.";
                $code = 404;
                return [
                    'code' => $code,
                    'errmsg' => $errmsg
                ];
            }
        } catch (\PDOException $e) {
            $errmsg = "Error checking existing image: " . $e->getMessage();
            $code = 500;
            return [
                'code' => $code,
                'errmsg' => $errmsg
            ];
        }
    
        // Delete the image file from the server
        if (file_exists($image['img'])) {
            unlink($image['img']);
        }
    
        // Delete the image record from the database
        $sqlDelete = "DELETE FROM images WHERE landlord_id = ?";
        try {
            $stmtDelete = $this->pdo->prepare($sqlDelete);
            $stmtDelete->execute([$landlord_id]);
            $code = 200;
            $errmsg = "Image successfully deleted.";
        } catch (\PDOException $e) {
            $errmsg = "Error deleting image record: " . $e->getMessage();
            $code = 500;
        }
    
        return [
            'code' => $code,
            'errmsg' => $errmsg
        ];
    }
    


    public function getImage() {
        try {
            // Prepare SQL statement to fetch images
            $sql = "SELECT * FROM images";
            $stmt = $this->pdo->query($sql);
            
            // Fetch all rows as an associative array
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Return the fetched images data
            return [
                "status" => "success",
                "message" => "Successfully retrieved images.",
                "data" => $result
            ];
        } catch(PDOException $e) {
            // Handle any potential errors
            return [
                "status" => "error",
                "message" => "Failed to retrieve images: " . $e->getMessage()
            ];
        }
    }

    public function addMaintenance($data) {
        // Extract properties from the $data object
        $apartment_id = $data->apartment_id ?? null;
        $landlord_id = $data->landlord_id ?? null;
        $start_date = $data->start_date ?? null;
        $end_date = $data->end_date ?? null;
        $description = $data->description ?? null;
        $expenses = $data->expenses ?? null;
        $status = $data->status ?? 'pending'; // Default status
    
        // Validate required fields
        $fields = [
            'apartment_id' => 'Apartment ID is required.',
            'landlord_id' => 'Landlord ID is required.',
            'start_date' => 'Start date is required.',
            'description' => 'Description is required.',
        ];
    
        foreach ($fields as $field => $errorMessage) {
            if (empty($$field)) {
                return $this->sendErrorResponse($errorMessage, 400);
            }
        }
    
        // Validate date format for start_date and end_date
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($datePattern, $start_date)) {
            return $this->sendErrorResponse("Invalid start date format. Use YYYY-MM-DD.", 400);
        }
        if ($end_date && !preg_match($datePattern, $end_date)) {
            return $this->sendErrorResponse("Invalid end date format. Use YYYY-MM-DD.", 400);
        }
    
        // Insert the maintenance record into the database
        $query = "INSERT INTO maintenance (apartment_id, landlord_id, start_date, end_date, description, expenses, status)
                  VALUES (:apartment_id, :landlord_id, :start_date, :end_date, :description, :expenses, :status)";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':apartment_id', $apartment_id);
            $stmt->bindParam(':landlord_id', $landlord_id);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':expenses', $expenses);
            $stmt->bindParam(':status', $status);
    
            if ($stmt->execute()) {
                return $this->sendSuccessResponse("Maintenance task added successfully.", 201);
            } else {
                return $this->sendErrorResponse("Failed to add maintenance task.", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }

    public function updateMaintenance($data) {
        // Extract properties from the $data object
        $maintenance_id = $data->maintenance_id ?? null;
        $apartment_id = $data->apartment_id ?? null;
        $landlord_id = $data->landlord_id ?? null;
        $start_date = $data->start_date ?? null;
        $end_date = $data->end_date ?? null;
        $description = $data->description ?? null;
        $expenses = $data->expenses ?? null;
        $status = $data->status ?? null;
    
        // Validate required fields
        $fields = [
            'maintenance_id' => 'Maintenance ID is required.',
            'apartment_id' => 'Apartment ID is required.',
            'landlord_id' => 'Landlord ID is required.',
            'start_date' => 'Start date is required.',
            'description' => 'Description is required.',
        ];
    
        foreach ($fields as $field => $errorMessage) {
            if (empty($$field)) {
                return $this->sendErrorResponse($errorMessage, 400);
            }
        }
    
        // Validate date format for start_date and end_date
        $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($datePattern, $start_date)) {
            return $this->sendErrorResponse("Invalid start date format. Use YYYY-MM-DD.", 400);
        }
        if ($end_date && !preg_match($datePattern, $end_date)) {
            return $this->sendErrorResponse("Invalid end date format. Use YYYY-MM-DD.", 400);
        }
    
        // Update the maintenance record in the database
        $query = "UPDATE maintenance 
                  SET apartment_id = :apartment_id, landlord_id = :landlord_id, start_date = :start_date, 
                      end_date = :end_date, description = :description, expenses = :expenses, status = :status
                  WHERE maintenance_id = :maintenance_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':maintenance_id', $maintenance_id);
            $stmt->bindParam(':apartment_id', $apartment_id);
            $stmt->bindParam(':landlord_id', $landlord_id);
            $stmt->bindParam(':start_date', $start_date);
            $stmt->bindParam(':end_date', $end_date);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':expenses', $expenses);
            $stmt->bindParam(':status', $status);
    
            if ($stmt->execute()) {
                return $this->sendSuccessResponse("Maintenance task updated successfully.", 200);
            } else {
                return $this->sendErrorResponse("Failed to update maintenance task.", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }

    public function getMaintenance() {
        $query = "SELECT * FROM maintenance WHERE isVisible = 1";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $maintenanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $maintenanceRecords
            ];
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }
    
    public function getArchivedMaintenance() {
        $query = "SELECT * FROM maintenance WHERE isVisible = 0";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $archivedMaintenanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return [
                'status' => 'success',
                'data' => $archivedMaintenanceRecords
            ];
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }

    public function archiveMaintenance($maintenanceId) {
        try {
            // Prepare the SQL query to update the isVisible field to 0
            $query = "UPDATE maintenance SET isVisible = 0 WHERE maintenance_id = :maintenance_id";
            $stmt = $this->conn->prepare($query);
            
            // Bind the maintenance ID parameter
            $stmt->bindParam(':maintenance_id', $maintenanceId, PDO::PARAM_INT);
            
            // Execute the query
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Maintenance record archived (isVisible = 0).'
                ];
            } else {
                return $this->sendErrorResponse("Failed to archive maintenance record", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }
    
    public function restoreMaintenance($maintenanceId) {
        try {
            // Prepare the SQL query to update the isVisible field to 1
            $query = "UPDATE maintenance SET isVisible = 1 WHERE maintenance_id = :maintenance_id";
            $stmt = $this->conn->prepare($query);
            
            // Bind the maintenance ID parameter
            $stmt->bindParam(':maintenance_id', $maintenanceId, PDO::PARAM_INT);
            
            // Execute the query
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Maintenance record restored (isVisible = 1).'
                ];
            } else {
                return $this->sendErrorResponse("Failed to restore maintenance record", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }

    public function deleteMaintenance($maintenanceId) {
        $query = "DELETE FROM maintenance WHERE maintenance_id = :maintenance_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':maintenance_id', $maintenanceId, PDO::PARAM_INT);
        
        try {
            if ($stmt->execute()) {
                return [
                    'status' => 'success',
                    'message' => 'Maintenance record deleted successfully'
                ];
            } else {
                return $this->sendErrorResponse("Failed to delete maintenance record", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
        }
    }

    public function updateConcern($data) {
        // Extract properties from the $data object
        $concern_id = $data->concern_id ?? null;
        $title = $data->title ?? '';
        $content = $data->content ?? '';
        $tenant_id = $data->tenant_id ?? '';
        $status = $data->status ?? 'pending'; // Default status
    
        // Validate required fields
        $fields = [
            'concern_id' => 'Concern ID is required.',
            'title' => 'Concern title cannot be empty',
            'content' => 'Concern content cannot be empty',
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
    
        // Update the concern record in the database
        $query = "UPDATE concerns 
                  SET title = :title, content = :content, tenant_id = :tenant_id, status = :status, image_path = COALESCE(:image_path, image_path)
                  WHERE concern_id = :concern_id";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':concern_id', $concern_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':tenant_id', $tenant_id);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':image_path', $imagePath);
    
            if ($stmt->execute()) {
                return $this->sendSuccessResponse("Concern updated successfully.", 200);
            } else {
                return $this->sendErrorResponse("Failed to update concern.", 500);
            }
        } catch (PDOException $e) {
            return $this->sendErrorResponse("Database error: " . $e->getMessage(), 500);
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