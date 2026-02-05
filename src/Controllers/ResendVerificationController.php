<?php
declare(strict_types=1);

class ResendVerificationController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->requireParams(['email'])) return $error;

        $auth = new Auth($this->db);
        $result = $auth->resendVerification($this->param('email'));

        if ($result['status'] === 'SUCCESS') {
            $result['code'] = 200;
        } else {
            $result['code'] = 400;
        }

        return $result;
    }
}
