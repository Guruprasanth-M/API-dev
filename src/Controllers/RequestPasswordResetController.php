<?php
declare(strict_types=1);

class RequestPasswordResetController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->requireParams(['email'])) return $error;

        $auth = new Auth($this->db);
        $result = $auth->requestPasswordReset($this->param('email'));

        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 400;
        return $result;
    }
}
