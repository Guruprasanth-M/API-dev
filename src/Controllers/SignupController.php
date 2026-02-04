<?php
declare(strict_types=1);

class SignupController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->requireParams(['username', 'password', 'email', 'phone'])) return $error;

        $auth = new Auth($this->db);
        $result = $auth->signup(
            $this->param('username'),
            $this->param('password'),
            $this->param('email'),
            $this->param('phone')
        );

        $result['code'] = ($result['status'] === 'SUCCESS') ? 201 : 400;
        return $result;
    }
}
