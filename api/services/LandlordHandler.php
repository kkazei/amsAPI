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

    public function createAnnouncement() {
        $data = json_decode(file_get_contents("php://input"));

        // Extract the data from the incoming JSON
        $title = $data->title ?? '';
        $content = $data->content ?? '';
        $landlord_id = $data->landlord_id;

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

        $table_name = 'posts';
        $query = "INSERT INTO " . $table_name . " (title, content, landlord_id) VALUES (:title, :content, :landlord_id)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':landlord_id', $landlord_id);

        if ($stmt->execute()) {
            return $this->sendSuccessResponse("Announcement created successfully", 201);
        } else {
            return $this->sendErrorResponse("Failed to create announcement", 500);
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