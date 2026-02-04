<?php
declare(strict_types=1);

class AboutController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;

        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return ['status' => 'FAILED', 'msg' => 'User not found', 'code' => 404];
        }

        return [
            'status' => 'SUCCESS',
            'msg' => 'Welcome Admin',
            'admin' => [
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'created_at' => $user['created_at']
            ],
            'api' => [
                'name' => 'Guruprasanth API',
                'version' => '0.1.0',
                'description' => 'REST API for backend and security concepts'
            ],
            'code' => 200
        ];
    }
}
