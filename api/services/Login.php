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



    
    public function loginUserAsLandlord($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user || !password_verify($password, $user['password'])) {
            return [
                'status' => 401,
                'message' => 'Invalid email or password'
            ];
        }

        // Create JWT payload for landlord
        $payload = [
            'iss' => 'localhost',
            'aud' => 'localhost',
            'exp' => time() + 3600,
            'data' => [
                'id' => $user['user_id'],
                'fullname' => $user['user_fullname'],
                'email' => $user['user_email'],
                'usertype' => 'landlord'  // Set usertype as landlord
            ],
        ];

        $jwt = JWT::encode($payload, $this->secretKey, 'HS256');
    
        return [
            'status' => 200,
            'jwt' => $jwt,  
            'message' => 'Login Successful'
        ];
    }

    // Login for tenants
    public function loginUserAsTenant($email, $password) {
        $stmt = $this->conn->prepare("SELECT * FROM tenants WHERE tenant_email = ?");
        $stmt->execute([$email]);
        $tenant = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$tenant || !password_verify($password, $tenant['password'])) {
            return [
                'status' => 401,
                'message' => 'Invalid email or password'
            ];
        }

        // Create JWT payload for tenant
        $payload = [
            'iss' => 'localhost',
            'aud' => 'localhost',
            'exp' => time() + 3600,
            'data' => [
                'id' => $tenant['tenant_id'],
                'fullname' => $tenant['tenant_fullname'],
                'email' => $tenant['tenant_email'],
                'usertype' => 'tenant'  // Set usertype as tenant
            ],
        ];

        $jwt = JWT::encode($payload, $this->secretKey, 'HS256');
    
        return [
            'status' => 200,
            'jwt' => $jwt,  
            'message' => 'Login Successful'
        ];
    }
    
    // General login function that delegates to specific login methods
    public function loginUser($email, $password) {
        if ($this->isLandlord($email)) {
            return $this->loginUserAsLandlord($email, $password);
        } else {
            return $this->loginUserAsTenant($email, $password);
        }
    }

    // Check if the user is a landlord
    private function isLandlord($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user !== false;
    }

    // Logout function
    public function logoutUser() {
        setcookie("jwt", "", time() - 3600, '/');
        http_response_code(200);
    }
}
?>
