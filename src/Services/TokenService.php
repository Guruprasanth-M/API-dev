<?php
declare(strict_types=1);


class TokenService
{

    public function generate(): string
    {
        return bin2hex(random_bytes(32));
    }


    public function getExpiry(int $seconds): string
    {
        return date('Y-m-d H:i:s', time() + $seconds);
    }


    public function isExpired(?string $expiresAt): bool
    {
        if (!$expiresAt) return true;
        return strtotime($expiresAt) < time();
    }
}
