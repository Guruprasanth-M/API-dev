<?php
declare(strict_types=1);

class User
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    private function getUserById(int $user_id): ?array
    {
        $stmt = $this->db->prepare("SELECT id, username, email, phone, blocked, created_at FROM users WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $user ?: null;
    }

    private function getTokensByUserId(int $user_id): ?array
    {
        $stmt = $this->db->prepare("SELECT access_token, refresh_token, expires_at FROM sessions WHERE user_id = ? LIMIT 1");
        if (!$stmt) return null;
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $tokens = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $tokens ?: null;
    }

    private function getUserByUsername(string $username): ?array
    {
        $stmt = $this->db->prepare("SELECT id, username, email, phone, blocked, created_at FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) return null;
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $user ?: null;
    }

    private function getUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT id, username, email, phone, blocked, created_at FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) return null;
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $user ?: null;
    }

    private function getUserByPhone(string $phone): array
    {
        $stmt = $this->db->prepare("SELECT id, username, email, phone, blocked, created_at FROM users WHERE phone = ?");
        if (!$stmt) return [];
        
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = $result->fetch_all(MYSQLI_ASSOC) ?: [];
        $stmt->close();
        
        return $users;
    }

    private function getUserByToken(string $token_field, string $token): ?array
    {
        $stmt = $this->db->prepare("SELECT user_id FROM sessions WHERE $token_field = ? LIMIT 1");
        if (!$stmt) return null;
        
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $session = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        return $session ? $this->getUserById($session['user_id']) : null;
    }

    public function userExists(string $username = '', string $email = '', string $phone = '', string $access_token = '', string $refresh_token = ''): array
    {
        $user_data = null;

        // Check by tokens first
        if ($access_token) {
            $user_data = $this->getUserByToken('access_token', $access_token);
        } elseif ($refresh_token) {
            $user_data = $this->getUserByToken('refresh_token', $refresh_token);
        }
        // Check by credentials
        elseif ($username) {
            $user_data = $this->getUserByUsername($username);
        } elseif ($email) {
            $user_data = $this->getUserByEmail($email);
        } elseif ($phone) {
            $users = $this->getUserByPhone($phone);
            
            if (count($users) > 1) {
                return ['status' => 'SUCCESS', 'count' => count($users), 'users' => $users];
            }
            
            $user_data = $users[0] ?? null;
        } else {
            return ['status' => 'FAILED', 'error' => 'POST parameter required: username, email, phone, access_token, or refresh_token'];
        }

        if (!$user_data) {
            return ['status' => 'FAILED', 'error' => 'User not found'];
        }

        $response = ['status' => 'SUCCESS', 'user' => $user_data];
        
        // Add tokens if available
        $tokens = $this->getTokensByUserId($user_data['id']);
        if ($tokens) {
            $response = array_merge($response, $tokens);
        }
        
        return $response;
    }
}