<?php
declare(strict_types=1);

class Auth
{
    private mysqli $db;
    private int $bcryptCost;
    private int $verificationExpiry;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->bcryptCost = (int)($_ENV['BCRYPT_COST'] ?? 10);
        $this->verificationExpiry = (int)($_ENV['VERIFICATION_TOKEN_EXPIRE'] ?? 86400);
    }

    public function signup(string $username, string $password, string $email, string $phone): array
    {
        if ($error = $this->validateSignupInput($username, $password, $email, $phone)) {
            return $error;
        }

        if ($error = $this->checkUserExists($username, $email)) {
            return $error;
        }

        return $this->createUser($username, $password, $email, $phone);
    }

    public function login(string $username, string $password): array
    {
        if (empty($username) || empty($password)) {
            return $this->error('Username and password are required');
        }

        $user = $this->findUserByUsernameOrEmail($username);
        if (!$user) {
            return $this->error('Invalid username or email');
        }

        if (!password_verify($password, $user['password'])) {
            return $this->error('Invalid password');
        }

        if (!$this->isVerified($user)) {
            return [
                'status' => 'FAILED',
                'error' => 'Please verify your email before logging in. Check your inbox for the verification link.',
                'verified' => false
            ];
        }

        $this->rehashPasswordIfNeeded($user['id'], $password, $user['password']);

        return $this->getOrCreateSession($user);
    }

    public function verifyEmail(string $token): array
    {
        if (empty($token)) {
            return $this->error('Verification token is required');
        }

        $user = $this->findUserByToken($token);
        if (!$user) {
            return $this->error('Invalid verification token');
        }

        if ($this->isVerified($user)) {
            return $this->success('Email already verified', ['already_verified' => true]);
        }

        if ($this->isTokenExpired($user['token_expires_at'])) {
            return $this->error('Verification token has expired. Please request a new one.');
        }

        return $this->markAsVerified($user);
    }

    public function resendVerification(string $email): array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Valid email is required');
        }

        $user = $this->findUserByEmail($email);
        if (!$user) {
            return $this->error('Email not found');
        }

        if ($this->isVerified($user)) {
            return $this->success('Email is already verified', ['already_verified' => true]);
        }

        return $this->generateAndSendVerification($user);
    }

    public function requestPasswordReset(string $email): array
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Valid email is required');
        }

        $user = $this->findUserByEmail($email);
        if (!$user) {
            return $this->error('Email not found');
        }

        return $this->generateAndSendPasswordReset($user);
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        if (empty($token)) {
            return $this->error('Reset token is required');
        }

        if (strlen($newPassword) < 6) {
            return $this->error('Password must be at least 6 characters');
        }

        $user = $this->findUserByResetToken($token);
        if (!$user) {
            return $this->error('Invalid reset token');
        }

        if ($this->isTokenExpired($user['reset_token_expires_at'])) {
            return $this->error('Reset token has expired. Please request a new one.');
        }

        return $this->updatePassword($user, $newPassword);
    }

    private function validateSignupInput(string $username, string $password, string $email, string $phone): ?array
    {
        if (empty($username) || empty($password) || empty($email) || empty($phone)) {
            return $this->error('All fields are required');
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            return $this->error('Username must be 3-50 characters');
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return $this->error('Username can only contain letters, numbers, and underscores');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('Invalid email format');
        }

        if (strlen($password) < 6) {
            return $this->error('Password must be at least 6 characters');
        }

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            return $this->error('Phone must be 10 digits');
        }

        return null;
    }

    private function checkUserExists(string $username, string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        if (!$stmt) return $this->error('Database error');

        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $existing = $result->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Check which one exists
            $stmt2 = $this->db->prepare("SELECT username, email FROM users WHERE id = ?");
            $stmt2->bind_param("i", $existing['id']);
            $stmt2->execute();
            $user = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();

            if ($user['username'] === $username) {
                return $this->error('Username already exists');
            }
            return $this->error('Email already registered');
        }

        return null;
    }

    private function createUser(string $username, string $password, string $email, string $phone): array
    {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);
        $verificationToken = $this->generateToken();
        $tokenExpiresAt = $this->getExpiryTime($this->verificationExpiry);

        $stmt = $this->db->prepare(
            "INSERT INTO users (username, password, email, phone, blocked, verified, verification_token, token_expires_at, created_at, updated_at) 
             VALUES (?, ?, ?, ?, 0, 0, ?, ?, NOW(), NOW())"
        );

        if (!$stmt) return $this->error('Database error');

        $stmt->bind_param("ssssss", $username, $hashedPassword, $email, $phone, $verificationToken, $tokenExpiresAt);

        if (!$stmt->execute()) {
            return $this->error($this->db->error);
        }

        $userId = $stmt->insert_id;
        $stmt->close();

        $emailSent = $this->sendVerificationEmail($email, $username, $verificationToken);

        return [
            'status' => 'SUCCESS',
            'msg' => 'User registered successfully. Please check your email to verify your account.',
            'user_id' => $userId,
            'username' => $username,
            'email_sent' => $emailSent,
            'verification_expires_at' => $tokenExpiresAt
        ];
    }

    private function findUserByUsernameOrEmail(string $identifier): ?array
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

    private function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT id, username, email, verified FROM users WHERE email = ? LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    private function findUserByToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, verified, token_expires_at FROM users WHERE verification_token = ? LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    private function isVerified(array $user): bool
    {
        return (int)$user['verified'] === 1;
    }

    private function isTokenExpired(?string $expiresAt): bool
    {
        if (!$expiresAt) return true;
        return strtotime($expiresAt) < time();
    }

    private function markAsVerified(array $user): array
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = ?"
        );

        if (!$stmt) return $this->error('Database error');

        $stmt->bind_param("i", $user['id']);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->success('Email verified successfully! You can now log in.', [
                'username' => $user['username'],
                'email' => $user['email']
            ]);
        }

        return $this->error('Failed to verify email');
    }

    private function generateAndSendVerification(array $user): array
    {
        $verificationToken = $this->generateToken();
        $tokenExpiresAt = $this->getExpiryTime($this->verificationExpiry);

        $stmt = $this->db->prepare(
            "UPDATE users SET verification_token = ?, token_expires_at = ? WHERE id = ?"
        );

        if (!$stmt) return $this->error('Database error');

        $stmt->bind_param("ssi", $verificationToken, $tokenExpiresAt, $user['id']);

        if ($stmt->execute()) {
            $stmt->close();
            $emailSent = $this->sendVerificationEmail($user['email'], $user['username'], $verificationToken);

            return [
                'status' => 'SUCCESS',
                'msg' => 'Verification email sent. Please check your inbox.',
                'email_sent' => $emailSent,
                'expires_at' => $tokenExpiresAt
            ];
        }

        return $this->error('Failed to resend verification');
    }

    private function sendVerificationEmail(string $email, string $username, string $token): bool
    {
        $emailService = new Email();
        $result = $emailService->sendVerificationEmail($email, $username, $token);
        return $result['status'] === 'SUCCESS';
    }

    private function getOrCreateSession(array $user): array
    {
        $existingSession = $this->findActiveSession($user['id']);
        if ($existingSession) {
            return $this->buildLoginResponse($user, $existingSession, 'Already logged in');
        }

        $session = new Session($this->db);
        $sessionResult = $session->create($user['id']);

        if ($sessionResult['status'] !== 'SUCCESS') {
            return $this->error('Failed to create session');
        }

        return $this->buildLoginResponse($user, $sessionResult, 'Login successful');
    }

    private function findActiveSession(int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT access_token, refresh_token, expires_at FROM sessions WHERE user_id = ? AND expires_at > NOW() LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $session = $result->fetch_assoc();
        $stmt->close();

        return $session ?: null;
    }

    private function buildLoginResponse(array $user, array $session, string $message): array
    {
        return [
            'status' => 'SUCCESS',
            'msg' => $message,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'created_at' => $user['created_at']
            ],
            'access_token' => $session['access_token'],
            'refresh_token' => $session['refresh_token'],
            'expires_at' => $session['expires_at']
        ];
    }

    private function rehashPasswordIfNeeded(int $userId, string $password, string $currentHash): void
    {
        if (!password_needs_rehash($currentHash, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost])) {
            return;
        }

        $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);
        $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("si", $newHash, $userId);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function getExpiryTime(int $seconds): string
    {
        return date('Y-m-d H:i:s', time() + $seconds);
    }

    private function error(string $message): array
    {
        return ['status' => 'FAILED', 'error' => $message];
    }

    private function success(string $message, array $extra = []): array
    {
        return array_merge(['status' => 'SUCCESS', 'msg' => $message], $extra);
    }

    private function findUserByResetToken(string $token): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, reset_token_expires_at FROM users WHERE reset_token = ? LIMIT 1"
        );

        if (!$stmt) return null;

        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        return $user ?: null;
    }

    private function generateAndSendPasswordReset(array $user): array
    {
        $resetToken = $this->generateToken();
        $tokenExpiresAt = $this->getExpiryTime(3600);

        $stmt = $this->db->prepare(
            "UPDATE users SET reset_token = ?, reset_token_expires_at = ? WHERE id = ?"
        );

        if (!$stmt) return $this->error('Database error');

        $stmt->bind_param("ssi", $resetToken, $tokenExpiresAt, $user['id']);

        if ($stmt->execute()) {
            $stmt->close();
            $emailSent = $this->sendPasswordResetEmail($user['email'], $user['username'], $resetToken);

            return [
                'status' => 'SUCCESS',
                'msg' => 'Password reset token sent to your email. Use this token with the API to reset your password.',
                'token' => $resetToken,
                'email_sent' => $emailSent,
                'expires_at' => $tokenExpiresAt
            ];
        }

        return $this->error('Failed to send password reset email');
    }

    private function sendPasswordResetEmail(string $email, string $username, string $token): bool
    {
        $emailService = new Email();
        $result = $emailService->sendPasswordResetEmail($email, $username, $token);
        return $result['status'] === 'SUCCESS';
    }

    private function updatePassword(array $user, string $newPassword): array
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);

        $stmt = $this->db->prepare(
            "UPDATE users SET password = ?, reset_token = NULL, reset_token_expires_at = NULL WHERE id = ?"
        );

        if (!$stmt) return $this->error('Database error');

        $stmt->bind_param("si", $hashedPassword, $user['id']);

        if ($stmt->execute()) {
            $stmt->close();
            return $this->success('Password reset successfully! You can now log in with your new password.', [
                'username' => $user['username'],
                'email' => $user['email']
            ]);
        }

        return $this->error('Failed to reset password');
    }
}
