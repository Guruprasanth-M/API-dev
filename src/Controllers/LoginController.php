<?php
declare(strict_types=1);

class LoginController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->requireParams(['username', 'password'])) return $error;

        $auth = new Auth($this->db);
        $result = $auth->login(
            $this->param('username'),
            $this->param('password')
        );

        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 401;
        return $result;
    }
}
