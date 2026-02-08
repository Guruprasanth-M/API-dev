<?php
declare(strict_types=1);


class PasswordService
{
    private int $bcryptCost;

    public function __construct()
    {
        $this->bcryptCost = (int)($_ENV['BCRYPT_COST'] ?? 10);
    }


    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);
    }


    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }


    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => $this->bcryptCost]);
    }


    public function rehashIfNeeded(UserRepository $userRepo, int $userId, string $password, string $currentHash): void
    {
        if (!$this->needsRehash($currentHash)) {
            return;
        }

        $newHash = $this->hash($password);
        $userRepo->updatePasswordHash($userId, $newHash);
    }
}
