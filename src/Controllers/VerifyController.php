<?php
declare(strict_types=1);

class VerifyController extends Controller
{
    public function handle(): array
    {
        $token = $this->param('token');

        if (empty($token)) {
            return ['status' => 'FAILED', 'error' => 'POST parameter required: "token"', 'code' => 400];
        }

        $auth = new Auth($this->db);
        $result = $auth->verifyEmail($token);

        if ($result['status'] === 'SUCCESS') {
            $result['code'] = 200;
        } else {
            $result['code'] = 400;
        }

        return $result;
    }
}
