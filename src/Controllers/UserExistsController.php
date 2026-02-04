<?php
declare(strict_types=1);

class UserExistsController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;

        // If Bearer token provided, return user with tokens
        if ($this->access_token) {
            $user = new User($this->db);
            $result = $user->userExists('', '', '', $this->access_token);
            $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 404;
            return $result;
        }

        // Otherwise search by username, email, or phone (public search, no tokens)
        $user = new User($this->db);
        $result = $user->userExists(
            $this->param('username'),
            $this->param('email'),
            $this->param('phone')
        );

        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 404;
        return $result;
    }
}
