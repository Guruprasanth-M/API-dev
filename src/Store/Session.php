<?php
declare(strict_types=1);

class Session
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function create(int $user_id): array
    {
        $access_token = $this->generateToken();
        $refresh_token = $this->generateToken();
        $expires_at = date('Y-m-d H:i:s', time() + (int)$_ENV['SESSION_TOKEN_EXPIRE']);

        $query = "INSERT INTO sessions (user_id, access_token, refresh_token, expires_at, created_at)
                  VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("isss", $user_id, $access_token, $refresh_token, $expires_at);
        
        if ($stmt->execute()) {
            $stmt->close();
            return [
                'status' => 'SUCCESS',
                'access_token' => $access_token,
                'refresh_token' => $refresh_token,
                'expires_at' => $expires_at
            ];
        } else {
            return ['status' => 'FAILED', 'error' => $this->db->error];
        }
    }

    public function validate(string $access_token): array
    {
        $query = "SELECT id, user_id, expires_at FROM sessions 
                  WHERE access_token = ? AND expires_at > NOW() LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("s", $access_token);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            return ['status' => 'FAILED', 'error' => 'Invalid or expired token'];
        }

        $session = $result->fetch_assoc();
        return [
            'status' => 'SUCCESS',
            'session_id' => $session['id'],
            'user_id' => $session['user_id'],
            'expires_at' => $session['expires_at']
        ];
    }

    public function refresh(string $refresh_token): array
    {
        $query = "SELECT id, user_id FROM sessions 
                  WHERE refresh_token = ? AND expires_at > NOW() LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("s", $refresh_token);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();

        if ($result->num_rows === 0) {
            return ['status' => 'FAILED', 'error' => 'Invalid or expired refresh token'];
        }

        $session = $result->fetch_assoc();
        $new_access_token = $this->generateToken();
        $expires_at = date('Y-m-d H:i:s', time() + (int)$_ENV['SESSION_TOKEN_EXPIRE']);

        $update_query = "UPDATE sessions SET access_token = ?, expires_at = ? WHERE id = ?";
        $update_stmt = $this->db->prepare($update_query);
        if (!$update_stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $update_stmt->bind_param("ssi", $new_access_token, $expires_at, $session['id']);
        
        if ($update_stmt->execute()) {
            $update_stmt->close();
            return [
                'status' => 'SUCCESS',
                'access_token' => $new_access_token,
                'expires_at' => $expires_at
            ];
        } else {
            return ['status' => 'FAILED', 'error' => $this->db->error];
        }
    }

    public function delete(string $access_token): array
    {
        $query = "DELETE FROM sessions WHERE access_token = ?";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("s", $access_token);
        
        if ($stmt->execute()) {
            $stmt->close();
            return ['status' => 'SUCCESS', 'msg' => 'Logged out successfully'];
        } else {
            return ['status' => 'FAILED', 'error' => $this->db->error];
        }
    }

    public function deleteByUserId(int $user_id): void
    {
        $query = "DELETE FROM sessions WHERE user_id = ?";
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>
