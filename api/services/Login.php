<?php

declare(strict_types=1);

use Firebase\JWT\JWT;

require_once(__DIR__ . '/../config/database.php');
require_once(__DIR__ . '/../config/secretKey.php');
require_once(__DIR__ . '/../vendor/autoload.php');

class Login {
    private $conn;
    private $secretKey;

    public function __construct() {
        $databaseService = new DatabaseAccess();
        $this->conn = $databaseService->connect();

        $keys = new Secret();
        $this->secretKey = $keys->generateSecretKey();
    }



    
    public function loginUser($email, $password) {
        // First, check in the users table
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            // If no user found in the users table, check the tenants table
            $stmt = $this->conn->prepare("SELECT * FROM tenants WHERE tenant_email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            // If no user or tenant is found, return an error
            if (!$user) {
                return [
                    'status' => 401,
                    'message' => 'Invalid email or password'
                ];
            }
        }
    
        // If password verification fails, return an error
        if (!password_verify($password, $user['password'])) {
            return [
                'status' => 401,
                'message' => 'Invalid email or password'
            ];
        }
    
        // Create the payload for the JWT token
        $payload = [
            'iss' => 'localhost',
            'aud' => 'localhost',
            'exp' => time() + 3600,
            'data' => [
                'id' => $user['user_id'] ?? $user['tenant_id'],  // Adjust field based on table
                'fullname' => $user['user_fullname'] ?? $user['tenant_fullname'],  // Adjust field based on table
                'email' => $user['user_email'] ?? $user['tenant_email'],  // Adjust field based on table
                'usertype' => $user['user_role'] ?? 'tenant'  // Default to 'tenant' if not found
            ],
        ];
    
        // Encode the payload and return the JWT token
        $jwt = JWT::encode($payload, $this->secretKey, 'HS256');
    
        return [
            'status' => 200,
            'jwt' => $jwt,  
            'message' => 'Login Successful'
        ];
    }
    




    public function logoutUser() {

        setcookie("jwt", "", time() - 3600, '/');


        http_response_code(200);
    }
}
?>
