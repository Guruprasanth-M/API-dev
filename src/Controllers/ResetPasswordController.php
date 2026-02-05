<?php
declare(strict_types=1);

class ResetPasswordController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->requireParams(['token', 'password'])) {
            return ['status' => 'FAILED', 'msg' => 'POST parameters required: "token", "password"', 'code' => 400];
        }

        $auth = new Auth($this->db);
        $result = $auth->resetPassword(
            $this->param('token'),
            $this->param('password')
        );

        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 400;
        return $result;
    }
}
