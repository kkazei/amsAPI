<?php

/**
 * Post Class
 *
 * This PHP class provides methods for adding employees and jobs.
 *
 * Usage:
 * 1. Include this class in your project.
 * 2. Create an instance of the class to access the provided methods.
 * 3. Call the appropriate method to add new employees or jobs with the provided data.
 *
 * Example Usage:
 * ```
 * $post = new Post();
 * $employeeData = ... // prepare employee data as an associative array or object
 * $addedEmployee = $post->add_employees($employeeData);
 *
 * $jobData = ... // prepare job data as an associative array or object
 * $addedJob = $post->add_jobs($jobData);
 * ```
 *
 * Note: Customize the methods as needed to handle the addition of data to your actual data source (e.g., database, API).
 */

require_once "global.php"; 

class Post extends GlobalMethods{
    private $pdo;

    public function __construct(\PDO $pdo){
        $this->pdo = $pdo;
    }
    
   

    /**
     * Add a new employee with the provided data.
     *
     * @param array|object $data
     *   The data representing the new employee.
     *
     * @return array|object
     *   The added employee data.
     */
    public function add_tasks($data){
        $sql = "INSERT INTO tasks(id,title,description,
        status,due_date,created_at,updated_at) 
        VALUES (?,?,?,?,?,?,?)";
        try{
            $statement = $this->pdo->prepare($sql);
            $statement->execute(
                [
                    $data->id,
                    $data->title,
                    $data->description,
                    $data->status,
                    $data->due_date,
                    $data->created_at,
                    $data->updated_at
                  
                ]
            );
            return $this->sendPayload(null, "success", "Successfully created a new record.", 200);
    
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }
       
        return $this->sendPayload(null, "failed", $errmsg, $code);
    }

    public function edit_tasks($data, $id){
        $sql = "UPDATE tasks SET EMAIL=? WHERE EMPLOYEE_ID = ?";
        try{
            $statement = $this->pdo->prepare($sql);
            $statement->execute(
                [
                  $data->EMAIL,
                  $id
                ]
            );
            return $this->sendPayload(null, "success", "Successfully updated record.", 200);
    
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }
       
        return $this->sendPayload(null, "failed", $errmsg, $code);
    }

    public function delete_tasks($title){
        $sql = "DELETE FROM tasks WHERE title = ?";
        try{
            $statement = $this->pdo->prepare($sql);
            $statement->execute(
                [
                  $title
                ]
            );
            return $this->sendPayload(null, "success", "Successfully deleted record.", 200);
    
        }
        catch(\PDOException $e){
            $errmsg = $e->getMessage();
            $code = 400;
        }
       
        return $this->sendPayload(null, "failed", $errmsg, $code);
    }

    
}
