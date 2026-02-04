<?php
declare(strict_types=1);

class Auth
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }
    public function signup(string $username, string $password, string $email, string $phone): array
    {
        if (empty($username) || empty($password) || empty($email) || empty($phone)) {
            return ['status' => 'FAILED', 'error' => 'All fields are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['status' => 'FAILED', 'error' => 'Invalid email format'];
        }

        if (strlen($password) < 6) {
            return ['status' => 'FAILED', 'error' => 'Password must be at least 6 characters'];
        }

        $check_query = "SELECT id FROM users WHERE username = ? LIMIT 1";
        $stmt = $this->db->prepare($check_query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['status' => 'FAILED', 'error' => 'Username already exists'];
        }
        $stmt->close();

        $check_query = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($check_query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return ['status' => 'FAILED', 'error' => 'Email already registered'];
        }
        $stmt->close();

        $options = ['cost' => (int)$_ENV['BCRYPT_COST']];
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, $options);

        $insert_query = "INSERT INTO users (username, password, email, phone, blocked, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, 0, NOW(), NOW())";
        $stmt = $this->db->prepare($insert_query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("ssss", $username, $hashed_password, $email, $phone);
        
        if ($stmt->execute()) {
            $user_id = $stmt->insert_id;
            $stmt->close();
            
            return [
                'status' => 'SUCCESS',
                'msg' => 'User registered successfully',
                'user_id' => $user_id,
                'username' => $username
            ];
        } else {
            return ['status' => 'FAILED', 'error' => $this->db->error];
        }
    }

    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return ['status' => 'FAILED', 'error' => 'Username and password are required'];
        }

        $query = "SELECT id, username, email, password, phone, blocked, created_at FROM users 
                  WHERE (username = ? OR email = ?) AND blocked = 0 LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['status' => 'FAILED', 'error' => 'Invalid username or email'];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        if (!password_verify($password, $user['password'])) {
            return ['status' => 'FAILED', 'error' => 'Invalid password'];
        }

        $bcrypt_cost = (int)$_ENV['BCRYPT_COST'];
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => $bcrypt_cost])) {
            $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $bcrypt_cost]);
            $update_query = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $this->db->prepare($update_query);
            if ($update_stmt) {
                $update_stmt->bind_param("si", $new_hash, $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }

        // Check if user already has active session
        $session_check_query = "SELECT access_token, refresh_token, expires_at FROM sessions 
                                WHERE user_id = ? AND expires_at > NOW() LIMIT 1";
        $session_check_stmt = $this->db->prepare($session_check_query);
        if ($session_check_stmt) {
            $session_check_stmt->bind_param("i", $user['id']);
            $session_check_stmt->execute();
            $session_result = $session_check_stmt->get_result();
            
            if ($session_result->num_rows > 0) {
                $existing_session = $session_result->fetch_assoc();
                $session_check_stmt->close();
                
                return [
                    'status' => 'SUCCESS',
                    'msg' => 'Already logged in',
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'created_at' => $user['created_at']
                    ],
                    'access_token' => $existing_session['access_token'],
                    'refresh_token' => $existing_session['refresh_token'],
                    'expires_at' => $existing_session['expires_at']
                ];
            }
            $session_check_stmt->close();
        }

        $session = new Session($this->db);
        $session_result = $session->create($user['id']);

        if ($session_result['status'] !== 'SUCCESS') {
            return ['status' => 'FAILED', 'error' => 'Failed to create session'];
        }

        return [
            'status' => 'SUCCESS',
            'msg' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'created_at' => $user['created_at']
            ],
            'access_token' => $session_result['access_token'],
            'refresh_token' => $session_result['refresh_token'],
            'expires_at' => $session_result['expires_at']
        ];
    }
}
?>
