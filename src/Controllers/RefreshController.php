<?php
declare(strict_types=1);

class RefreshController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->requireParams(['refresh_token'])) return $error;

        $session = new Session($this->db);
        $result = $session->refresh($this->param('refresh_token'));

        $result['code'] = ($result['status'] === 'SUCCESS') ? 200 : 401;
        return $result;
    }
}
