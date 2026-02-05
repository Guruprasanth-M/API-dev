<?php
declare(strict_types=1);

abstract class Controller
{
    protected mysqli $db;
    protected array $request;
    protected string $method;
    protected ?string $access_token = null;

    public function __construct(mysqli $db, array $request, string $method)
    {
        $this->db = $db;
        $this->request = $request;
        $this->method = $method;
        $this->extractBearerToken();
    }

    protected function param(string $key, mixed $default = ''): mixed
    {
        return $this->request[$key] ?? $default;
    }

    private function extractBearerToken(): void
    {
        $authorization = '';
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authorization = $headers['Authorization'] ?? '';
        } elseif (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if (preg_match('/Bearer\s+(.+)/', $authorization, $matches)) {
            $this->access_token = $matches[1];
        }
    }

    protected function getBearerToken(): ?string
    {
        return $this->access_token;
    }

    protected function requirePost(): ?array
    {
        if ($this->method !== 'POST') {
            return ['status' => 'FAILED', 'msg' => 'Only POST method allowed', 'code' => 405];
        }
        return null;
    }

    protected function requireParams(array $params): ?array
    {
        $missing = [];
        foreach ($params as $param) {
            if (empty($this->request[$param])) {
                $missing[] = '"' . $param . '"';
            }
        }
        
        if (!empty($missing)) {
            return [
                'status' => 'FAILED',
                'msg' => 'POST parameters required: ' . implode(', ', $missing),
                'code' => 400
            ];
        }
        return null;
    }

    protected function validateBearerToken(): ?array
    {
        if (empty($this->access_token)) {
            return ['status' => 'UNAUTHORIZED', 'msg' => 'Authorization header required: Bearer <access_token>', 'code' => 401];
        }

        $session = new Session($this->db);
        $validation = $session->validate($this->access_token);

        if ($validation['status'] !== 'SUCCESS') {
            return ['status' => 'UNAUTHORIZED', 'msg' => 'Invalid or expired access token', 'code' => 401];
        }

        $user = $this->getAuthenticatedUser();
        if ($user && isset($user['verified']) && (int)$user['verified'] === 0) {
            return ['status' => 'FAILED', 'error' => 'Please verify your email first', 'verified' => false, 'code' => 403];
        }

        return null;
    }

    protected function getAuthenticatedUser(): ?array
    {
        if (empty($this->access_token)) {
            return null;
        }

        $user = new User($this->db);
        $result = $user->userExists('', '', '', $this->access_token);
        
        if ($result['status'] === 'SUCCESS') {
            return $result['user'];
        }
        return null;
    }

    abstract public function handle(): array;
}

