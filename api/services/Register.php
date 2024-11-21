<?php

require_once(__DIR__ . '/../config/database.php');

class RegisterUser {
    private $conn;

    public function __construct() {
        $databaseService = new DatabaseAccess();
        $this->conn = $databaseService->connect();

        header("Access-Control-Allow-Origin: * ");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    }

    public function registerUser() {
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
    
        // Check if all required fields are provided and not empty
        if (
            empty($data->user_email) ||
            empty($data->password) ||
            empty($data->fullname) ||
            empty($data->user_phone)
        ) {
            http_response_code(400);
            echo json_encode(array("message" => "All fields are required."));
            return;
        }
    
        // Extract data
        $email = $data->user_email;
        $password = $data->password;
        $fullName = $data->fullname;
        $phone = $data->user_phone;
    
        // Determine user role
        $userRole = isset($data->user_role) && $data->user_role === 'admin' ? 'admin' : 'user';
    
        // Choose table based on user role
        $table_name = $userRole === 'admin' ? 'users' : 'tenants';
    
        // SQL query to insert user/tenant data
        $query = "INSERT INTO " . $table_name . "
        SET " . ($userRole === 'admin' ? "user_email" : "tenant_email") . " = :email,
            password = :password,
            " . ($userRole === 'admin' ? "user_fullname" : "tenant_fullname") . " = :fullname,
            " . ($userRole === 'admin' ? "user_phone" : "tenant_phone") . " = :phone" . 
            ($userRole === 'admin' ? ", user_role = :user_role" : "");
    
        // Prepare the SQL statement
        $stmt = $this->conn->prepare($query);
    
        // Bind parameters
        $stmt->bindParam(':email', $email);
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':fullname', $fullName);
        $stmt->bindParam(':phone', $phone);
        if ($userRole === 'admin') {
            $stmt->bindParam(':user_role', $userRole);
        }
    
        // Execute the statement
        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "User was created."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create user."));
        }
    }
}

?>
