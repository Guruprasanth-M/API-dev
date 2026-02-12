<?php
declare(strict_types=1);

class Session
{
    private mysqli $db;

    // Token prefixes for type identification (adopted from OAuth pattern)
    private const ACCESS_PREFIX  = 'a.';
    private const REFRESH_PREFIX = 'r.';

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    private function generateToken(string $prefix): string
    {
        return $prefix . bin2hex(random_bytes(32));
    }

    /**
     * Detect token type from prefix
     */
    public static function getTokenType(string $token): string
    {
        if (str_starts_with($token, self::ACCESS_PREFIX)) return 'access';
        if (str_starts_with($token, self::REFRESH_PREFIX)) return 'refresh';
        return 'unknown';
    }

    /**
     * Strip prefix from token for display/logging (never log full token)
     */
    public static function maskToken(string $token): string
    {
        return substr($token, 0, 6) . '...' . substr($token, -4);
    }

    public function create(int $user_id, string $reference_token = 'auth_grant'): array
    {
        $access_token  = $this->generateToken(self::ACCESS_PREFIX);
        $refresh_token = $this->generateToken(self::REFRESH_PREFIX);
        $expires_at = date('Y-m-d H:i:s', time() + (int)$_ENV['SESSION_TOKEN_EXPIRE']);

        $query = "INSERT INTO sessions (user_id, access_token, refresh_token, expires_at, valid, reference_token, created_at)
                  VALUES (?, ?, ?, ?, 1, ?, NOW())";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("issss", $user_id, $access_token, $refresh_token, $expires_at, $reference_token);

        if ($stmt->execute()) {
            $stmt->close();
            return [
                'status' => 'SUCCESS',
                'access_token'    => $access_token,
                'refresh_token'   => $refresh_token,
                'token_type'      => 'Bearer',
                'expires_at'      => $expires_at,
                'reference_token' => $reference_token
            ];
        } else {
            return ['status' => 'FAILED', 'error' => $this->db->error];
        }
    }

    public function validate(string $access_token): array
    {
        if (self::getTokenType($access_token) !== 'access') {
            return ['status' => 'FAILED', 'error' => 'Invalid token type. Expected access token (a.*)'];
        }

        $query = "SELECT id, user_id, expires_at FROM sessions 
                  WHERE access_token = ? AND valid = 1 AND expires_at > NOW() LIMIT 1";

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
            'status'     => 'SUCCESS',
            'session_id' => $session['id'],
            'user_id'    => $session['user_id'],
            'expires_at' => $session['expires_at']
        ];
    }

    public function refresh(string $refresh_token): array
    {
        if (self::getTokenType($refresh_token) !== 'refresh') {
            return ['status' => 'FAILED', 'error' => 'Invalid token type. Expected refresh token (r.*)'];
        }

        $query = "SELECT id, user_id, refresh_token FROM sessions 
                  WHERE refresh_token = ? AND valid = 1 LIMIT 1";

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

        // Generate ONLY a new access token — do NOT issue a new refresh token
        $new_access_token = $this->generateToken(self::ACCESS_PREFIX);
        $new_expires_at   = date('Y-m-d H:i:s', time() + (int)$_ENV['SESSION_TOKEN_EXPIRE']);

        // Update the existing session in-place with the new access token
        $update = "UPDATE sessions SET access_token = ?, expires_at = ? WHERE id = ? AND valid = 1";
        $stmt2 = $this->db->prepare($update);
        if (!$stmt2) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt2->bind_param("ssi", $new_access_token, $new_expires_at, $session['id']);
        if (!$stmt2->execute() || $stmt2->affected_rows === 0) {
            $stmt2->close();
            return ['status' => 'FAILED', 'error' => 'Failed to refresh session'];
        }
        $stmt2->close();

        return [
            'status'       => 'SUCCESS',
            'access_token' => $new_access_token,
            'token_type'   => 'Bearer',
            'expires_at'   => $new_expires_at
        ];
    }

    /**
     * Soft-delete: mark session as invalid instead of deleting (audit trail)
     */
    public function delete(string $access_token): array
    {
        $query = "UPDATE sessions SET valid = 0 WHERE access_token = ? AND valid = 1";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return ['status' => 'FAILED', 'error' => 'Database error'];
        }

        $stmt->bind_param("s", $access_token);

        if ($stmt->execute()) {
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected === 0) {
                return ['status' => 'FAILED', 'error' => 'Session not found or already invalidated'];
            }
            return ['status' => 'SUCCESS', 'msg' => 'Logged out successfully'];
        } else {
            return ['status' => 'FAILED', 'error' => $this->db->error];
        }
    }

    /**
     * Invalidate all sessions for a user (e.g., password change, security event)
     */
    public function invalidateAllByUserId(int $user_id): void
    {
        $query = "UPDATE sessions SET valid = 0 WHERE user_id = ? AND valid = 1";
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Hard delete — for old expired sessions cleanup only
     */
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

    /**
     * Invalidate a single session by ID
     */
    private function invalidate(int $session_id): void
    {
        $query = "UPDATE sessions SET valid = 0 WHERE id = ?";
        $stmt = $this->db->prepare($query);
        if ($stmt) {
            $stmt->bind_param("i", $session_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}
