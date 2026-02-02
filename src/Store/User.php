<?php
declare(strict_types=1);

class User
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function verify(string $username, string $password): array
    {
        if ($username === "admin" && $password === "admin123") {
            return ['status' => 'SUCCESS', 'msg' => 'User verified successfully.'];
        }
        return ['status' => 'FAILED', 'msg' => 'Invalid credentials.'];
    }

    public function userExists(string $searchData): array
    {
        if (empty($searchData)) {
            return ['status' => 'FAILED', 'error' => 'Search data is required'];
        }
        $query = "SELECT id, username, email, phone, blocked, created_at FROM users 
                  WHERE id = ? OR username = ? OR email = ? OR phone = ? LIMIT 1";       
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Query preparation failed'];
        }
        $stmt->bind_param("ssss", $searchData, $searchData, $searchData, $searchData);        
        if (!$stmt->execute()) {
            return ['status' => 'FAILED', 'error' => 'Query execution failed'];
        }
        $result = $stmt->get_result();        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return ['status' => 'SUCCESS', 'user' => $user];
        }        
        return ['status' => 'FAILED', 'error' => 'User not found'];
    }
}
