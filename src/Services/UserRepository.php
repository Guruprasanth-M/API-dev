<?php
declare(strict_types=1);

/**
 * UserRepository - Handles all user database operations
 */
class UserRepository
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }


    public function findByUsernameOrEmail(string $identifier): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, phone, blocked, verified, created_at 
             FROM users WHERE (username = ? OR email = ?) AND blocked = 0 LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("ss", $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }


    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, verified FROM users WHERE email = ? LIMIT 1"
        );
        if (!$stmt) return null;

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }


    public function findByVerificationToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, verified, token_expires_at 
             FROM users WHERE verification_token = ? LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }


    public function findByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, reset_token_expires_at 
             FROM users WHERE reset_token = ? LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }


    public function exists(string $username, string $email): ?string
    {
        $stmt = $this->db->prepare(
            "SELECT username, email FROM users WHERE username = ? OR email = ? LIMIT 1"
        );
        if (!$stmt) return 'Database error';

        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();

        if (!$existing) {
            return null;
        }

        if ($existing['username'] === $username) {
            return 'Username already exists';
        }
        return 'Email already registered';
    }


    public function create(
        string $username,
        string $hashedPassword,
        string $email,
        string $phone,
        string $verificationToken,
        string $tokenExpiresAt
    ): ?int {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, phone, blocked, verified, verification_token, token_expires_at, created_at, updated_at) 
             VALUES (?, ?, ?, ?, 0, 0, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) return null;

        $stmt->bind_param("ssssss", $username, $hashedPassword, $email, $phone, $verificationToken, $tokenExpiresAt);

        if (!$stmt->execute()) {
            return null;
        }

        $userId = $stmt->insert_id;
        $stmt->close();

        return $userId;
    }

    public function markAsVerified(int $userId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?"
        );

        if (!$stmt) return false;

        $stmt->bind_param("i", $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updateVerificationToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?"
        );

        if (!$stmt) return false;

        $stmt->bind_param("ssi", $token, $expiresAt, $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updateResetToken(int $userId, string $token, string $expiresAt): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?"
        );

        if (!$stmt) return false;

        $stmt->bind_param("ssi", $token, $expiresAt, $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updatePassword(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
        );

        if (!$stmt) return false;

        $stmt->bind_param("si", $hashedPassword, $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    public function updatePasswordHash(int $userId, string $hashedPassword): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");

        if (!$stmt) return false;

        $stmt->bind_param("si", $hashedPassword, $userId);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }
    public function isVerified(array $user): bool
    {
        return (int)($user['verified'] ?? 0) === 1;
    }


    public function findActiveSession(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT access_token, refresh_token, expires_at 
             FROM sessions WHERE user_id = ? AND expires_at > NOW() LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        return $session ?: null;
    }
}
