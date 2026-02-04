<?php
declare(strict_types=1);

class TestController extends Controller
{
    public function handle(): array
    {
        if ($error = $this->requirePost()) return $error;
        if ($error = $this->validateBearerToken()) return $error;

        $headers = getallheaders();
        $user = $this->getAuthenticatedUser();
        
        return [
            'status' => 'SUCCESS',
            'msg' => 'Request Headers & Auth Info',
            'authenticated_user' => $user,
            'bearer_token' => $this->access_token ? substr($this->access_token, 0, 20) . '...' : null,
            'headers' => $headers,
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'remote_addr' => $_SERVER['REMOTE_ADDR'],
            'code' => 200
        ];
    }
}
