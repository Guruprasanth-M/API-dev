<?php
declare(strict_types=1);


class ResponseBuilder
{

    public static function error(string $message): array
    {
        return ['status' => 'FAILED', 'error' => $message];
    }

 
    public static function success(string $message, array $extra = []): array
    {
        return array_merge(['status' => 'SUCCESS', 'msg' => $message], $extra);
    }


    public static function loginResponse(array $user, array $session, string $message): array
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


    public static function signupResponse(int $userId, string $username, bool $emailSent, string $expiresAt): array
    {
        return [
            'status' => 'SUCCESS',
            'msg' => 'User registered successfully. Please check your email to verify your account.',
            'user_id' => $userId,
            'username' => $username,
            'email_sent' => $emailSent,
            'verification_expires_at' => $expiresAt
        ];
    }

    public static function verificationNeeded(): array
    {
        return [
            'status' => 'FAILED',
            'error' => 'Please verify your email before logging in. Check your inbox for the verification link.',
            'verified' => false
        ];
    }
}
