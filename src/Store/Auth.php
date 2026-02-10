<?php
declare(strict_types=1);

class Auth
{
    private mysqli $db;
    private UserRepository $userRepo;
    private PasswordService $passwordService;
    private TokenService $tokenService;
    private ValidationService $validator;
    private int $verificationExpiry;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
        $this->userRepo = new UserRepository($db);
        $this->passwordService = new PasswordService();
        $this->tokenService = new TokenService();
        $this->validator = new ValidationService();
        $this->verificationExpiry = (int)($_ENV['VERIFICATION_TOKEN_EXPIRE'] ?? 86400);
    }

    public function signup(string $username, string $password, string $email, string $phone): array
    {
        // Validate input
        if ($error = $this->validator->validateSignup($username, $password, $email, $phone)) {
            return ResponseBuilder::error($error);
        }

        // Check if user exists
        if ($error = $this->userRepo->exists($username, $email)) {
            return ResponseBuilder::error($error);
        }

        // Create user
        $hashedPassword = $this->passwordService->hash($password);
        $verificationToken = $this->tokenService->generate();
        $tokenExpiresAt = $this->tokenService->getExpiry($this->verificationExpiry);

        $userId = $this->userRepo->create(
            $username,
            $hashedPassword,
            $email,
            $phone,
            $verificationToken,
            $tokenExpiresAt
        );

        if (!$userId) {
            return ResponseBuilder::error('Failed to create user');
        }

        // Send verification email
        $emailSent = $this->sendVerificationEmail($email, $username, $verificationToken);

        return ResponseBuilder::signupResponse($userId, $username, $emailSent, $tokenExpiresAt);
    }

    public function login(string $username, string $password): array
    {
        // Validate input
        if ($error = $this->validator->validateLogin($username, $password)) {
            return ResponseBuilder::error($error);
        }

        // Find user
        $user = $this->userRepo->findByUsernameOrEmail($username);
        if (!$user) {
            return ResponseBuilder::error('Invalid username or email');
        }

        // Verify password
        if (!$this->passwordService->verify($password, $user['password'])) {
            return ResponseBuilder::error('Invalid password');
        }

        // Check verification status
        if (!$this->userRepo->isVerified($user)) {
            return ResponseBuilder::verificationNeeded();
        }

        // Rehash password if needed
        $this->passwordService->rehashIfNeeded(
            $this->userRepo,
            $user['id'],
            $password,
            $user['password']
        );

        // Get or create session
        return $this->getOrCreateSession($user);
    }

    public function verifyEmail(string $token): array
    {
        if ($error = $this->validator->validateToken($token)) {
            return ResponseBuilder::error($error);
        }

        $user = $this->userRepo->findByVerificationToken($token);
        if (!$user) {
            return ResponseBuilder::error('Invalid verification token');
        }

        if ($this->userRepo->isVerified($user)) {
            return ResponseBuilder::success('Email already verified', ['already_verified' => true]);
        }

        if ($this->tokenService->isExpired($user['token_expires_at'])) {
            return ResponseBuilder::error('Verification token has expired. Please request a new one.');
        }

        if (!$this->userRepo->markAsVerified($user['id'])) {
            return ResponseBuilder::error('Failed to verify email');
        }

        return ResponseBuilder::success('Email verified successfully! You can now log in.', [
            'username' => $user['username'],
            'email' => $user['email']
        ]);
    }

    public function resendVerification(string $email): array
    {
        if ($error = $this->validator->validateEmail($email)) {
            return ResponseBuilder::error($error);
        }

        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            return ResponseBuilder::error('Email not found');
        }

        if ($this->userRepo->isVerified($user)) {
            return ResponseBuilder::success('Email is already verified', ['already_verified' => true]);
        }

        // Generate new token
        $verificationToken = $this->tokenService->generate();
        $tokenExpiresAt = $this->tokenService->getExpiry($this->verificationExpiry);

        if (!$this->userRepo->updateVerificationToken($user['id'], $verificationToken, $tokenExpiresAt)) {
            return ResponseBuilder::error('Failed to resend verification');
        }

        $emailSent = $this->sendVerificationEmail($user['email'], $user['username'], $verificationToken);

        return [
            'status' => 'SUCCESS',
            'msg' => 'Verification email sent. Please check your inbox.',
            'email_sent' => $emailSent,
            'expires_at' => $tokenExpiresAt
        ];
    }

    public function requestPasswordReset(string $email): array
    {
        if ($error = $this->validator->validateEmail($email)) {
            return ResponseBuilder::error($error);
        }

        $user = $this->userRepo->findByEmail($email);
        if (!$user) {
            return ResponseBuilder::error('Email not found');
        }

        // Generate reset token (1 hour expiry)
        $resetToken = $this->tokenService->generate();
        $tokenExpiresAt = $this->tokenService->getExpiry(3600);

        if (!$this->userRepo->updateResetToken($user['id'], $resetToken, $tokenExpiresAt)) {
            return ResponseBuilder::error('Failed to send password reset email');
        }

        $emailSent = $this->sendPasswordResetEmail($user['email'], $user['username'], $resetToken);

        return [
            'status' => 'SUCCESS',
            'msg' => 'Password reset token sent to your email.',
            'token' => $resetToken,
            'email_sent' => $emailSent,
            'expires_at' => $tokenExpiresAt
        ];
    }

    public function resetPassword(string $token, string $newPassword): array
    {
        if ($error = $this->validator->validateToken($token)) {
            return ResponseBuilder::error('Reset token is required');
        }

        if ($error = $this->validator->validatePassword($newPassword)) {
            return ResponseBuilder::error($error);
        }

        $user = $this->userRepo->findByResetToken($token);
        if (!$user) {
            return ResponseBuilder::error('Invalid reset token');
        }

        if ($this->tokenService->isExpired($user['reset_token_expires_at'])) {
            return ResponseBuilder::error('Reset token has expired. Please request a new one.');
        }

        $hashedPassword = $this->passwordService->hash($newPassword);

        if (!$this->userRepo->updatePassword($user['id'], $hashedPassword)) {
            return ResponseBuilder::error('Failed to reset password');
        }

        // Invalidate all existing sessions for security (password changed)
        $session = new Session($this->db);
        $session->invalidateAllByUserId($user['id']);

        return ResponseBuilder::success('Password reset successfully! You can now log in with your new password.', [
            'username' => $user['username'],
            'email' => $user['email']
        ]);
    }

    private function getOrCreateSession(array $user): array
    {
        $existingSession = $this->userRepo->findActiveSession($user['id']);
        if ($existingSession) {
            return ResponseBuilder::loginResponse($user, $existingSession, 'Already logged in');
        }

        $session = new Session($this->db);
        $sessionResult = $session->create($user['id']);

        if ($sessionResult['status'] !== 'SUCCESS') {
            return ResponseBuilder::error('Failed to create session');
        }

        return ResponseBuilder::loginResponse($user, $sessionResult, 'Login successful');
    }

    private function sendVerificationEmail(string $email, string $username, string $token): bool
    {
        $emailService = new Email();
        $result = $emailService->sendVerificationEmail($email, $username, $token);
        return $result['status'] === 'SUCCESS';
    }

    private function sendPasswordResetEmail(string $email, string $username, string $token): bool
    {
        $emailService = new Email();
        $result = $emailService->sendPasswordResetEmail($email, $username, $token);
        return $result['status'] === 'SUCCESS';
    }
}
