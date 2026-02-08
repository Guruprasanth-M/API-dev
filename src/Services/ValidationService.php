<?php
declare(strict_types=1);

class ValidationService
{

    public function validateSignup(string $username, string $password, string $email, string $phone): ?string
    {
        if (empty($username) || empty($password) || empty($email) || empty($phone)) {
            return 'All fields are required';
        }

        if (strlen($username) < 3 || strlen($username) > 50) {
            return 'Username must be 3-50 characters';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return 'Username can only contain letters, numbers, and underscores';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }

        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters';
        }

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            return 'Phone must be 10 digits';
        }

        return null;
    }

    public function validateLogin(string $username, string $password): ?string
    {
        if (empty($username) || empty($password)) {
            return 'Username and password are required';
        }
        return null;
    }

    public function validateEmail(string $email): ?string
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Valid email is required';
        }
        return null;
    }


    public function validatePassword(string $password): ?string
    {
        if (strlen($password) < 6) {
            return 'Password must be at least 6 characters';
        }
        return null;
    }


    public function validateToken(string $token): ?string
    {
        if (empty($token)) {
            return 'Token is required';
        }
        return null;
    }
}
