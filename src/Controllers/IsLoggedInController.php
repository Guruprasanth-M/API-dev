<?php
declare(strict_types=1);

class IsLoggedInController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;

        // Check if Bearer token is provided
        if (empty($this->access_token)) {
            return [
                'status' => 'NOT_LOGGED_IN',
                'logged_in' => false,
                'code' => 401
            ];
        }

        // Validate token
        if ($error = $this->validateBearerToken()) {
            return [
                'status' => 'NOT_LOGGED_IN',
                'logged_in' => false,
                'msg' => $error['msg'],
                'code' => 401
            ];
        }

        // Get authenticated user
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return [
                'status' => 'NOT_LOGGED_IN',
                'logged_in' => false,
                'code' => 401
            ];
        }

        return [
            'status' => 'LOGGED_IN',
            'logged_in' => true,
            'username' => $user['username'],
            'email' => $user['email'],
            'user_id' => $user['id'],
            'code' => 200
        ];
    }
}
