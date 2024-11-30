<?php

require_once(__DIR__ . '/../config/database.php');

class AnalyticsHandler {
    private $pdo;
    private $conn;

    public function __construct($pdo)
    {
        $databaseService = new DatabaseAccess();
        $this->conn = $databaseService->connect();
        $this->pdo = $pdo;
    }

    public function getMonthlyIncome($month, $year) {
        try {
            // Validate the month and year parameters
            if (!is_numeric($month) || !is_numeric($year) || $month < 1 || $month > 12 || $year < 1900 || $year > date('Y')) {
                throw new Exception('Invalid month or year.');
            }

            // Prepare SQL query to calculate the sum of payments for the given month and year
            $query = "
                SELECT IFNULL(SUM(amount), 0) AS total_income
                FROM invoice
                WHERE MONTH(payment_date) = :month AND YEAR(payment_date) = :year
            ";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':month', $month, PDO::PARAM_INT);
            $stmt->bindParam(':year', $year, PDO::PARAM_INT);
            $stmt->execute();

            // Fetch the result
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if we have a valid result
            if ($result) {
                return [
                    'status' => 'success',
                    'total_income' => $result['total_income']
                ];
            } else {
                throw new Exception('No income data found for the specified month and year.');
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'An error occurred: ' . $e->getMessage()
            ];
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