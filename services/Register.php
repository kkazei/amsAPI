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
            empty($data->user_lastname) ||
            empty($data->user_firstname)
        ) {
            http_response_code(400);
            echo json_encode(array("message" => "All fields are required."));
            return;
        }

        // Extract data
        $email = $data->user_email;
        $password = $data->password;
        $lastName = $data->user_lastname;
        $firstName = $data->user_firstname;

        // Set default user role
        $userRole = 'user';

        // Database table name
        $table_name = 'users';

        // SQL query to insert user data
        $query = "INSERT INTO " . $table_name . "
                    SET user_email = :email,
                        password = :password,
                        user_lastname = :lastname,
                        user_firstname = :firstname,
                        user_role = :user_role";

        // Prepare the SQL statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':email', $email);
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bindParam(':password', $password_hash);
        $stmt->bindParam(':firstname', $firstName);
        $stmt->bindParam(':lastname', $lastName);
        $stmt->bindParam(':user_role', $userRole);

        // Execute the query
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(array("message" => "User was successfully registered."));
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Unable to register the user."));
        }
    }
}

?>