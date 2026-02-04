<?php
declare(strict_types=1);

class LogoutController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;

        $session = new Session($this->db);
        $result = $session->delete($this->access_token);

        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 401;
        return $result;
    }
}
