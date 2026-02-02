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
}
