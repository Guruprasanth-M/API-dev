<?php
error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/../src/load.php';

class API extends REST
{
    private ?mysqli $db = null;

    public function __construct()
    {
        parent::__construct();
        $this->dbConnect();
        
        if (!$this->checkDatabaseConnection()) {
            return; 
        }
        
        Migration::run($this->db);
    }

    private function dbConnect(): void
    {
        $this->db = Database::getConnection();
    }

    private function checkDatabaseConnection(): bool
    {
        if ($this->db === null || !$this->db->ping()) {
            $error = [
                'status' => 'DATABASE_ERROR',
                'msg' => 'Database connection failed. Service temporarily unavailable.',
                'code' => 'DB_CONNECTION_FAILED'
            ];
            $this->response($this->json($error), 503);
            return false;
        }
        return true;
    }

    public function processApi(): void
    {
        $func = strtolower(trim(str_replace("/", "", $_REQUEST['request'] ?? '')));
        if (method_exists($this, $func)) {
            $this->$func();
        } else {
            $this->response('', 400);
        }
    }

    private function about(): void
    {
        if ($this->get_request_method() != "POST") {
            $error = ['status' => 'WRONG_CALL', 'msg' => 'The type of call cannot be accepted by our servers.'];
            $this->response($this->json($error), 406);
        }
        $data = ['version' => '0.1', 'desc' => 'This API is created by GURUPRASANTH. For learning purpose.'];
        $this->response($this->json($data), 200);
    }

    private function verify(): void
    {
        $username = $this->_request['Username'] ?? '';
        $password = $this->_request['Password'] ?? '';
        
        $user = new User($this->db);
        $result = $user->verify($username, $password);
        
        $status = ($result['status'] === 'SUCCESS') ? 200 : 401;
        $this->response($this->json($result), $status);
    }

    private function userexists(): void
    {
        if ($this->get_request_method() != "POST") {
            $error = ['status' => 'FAILED', 'msg' => 'Only POST method allowed'];
            $this->response($this->json($error), 406);
            return;
        }

        $searchData = $this->_request['data'] ?? '';
        
        if (empty($searchData)) {
            $error = ['status' => 'FAILED', 'msg' => 'Search parameter "data" is required'];
            $this->response($this->json($error), 400);
            return;
        }

        $user = new User($this->db);
        $result = $user->userExists($searchData);
        
        $status = ($result['status'] === 'SUCCESS') ? 200 : 404;
        $this->response($this->json($result), $status);
    }

    private function signup(): void
    {
        if ($this->get_request_method() != "POST") {
            $error = ['status' => 'FAILED', 'msg' => 'Only POST method allowed'];
            $this->response($this->json($error), 405);
            return;
        }

        $username = $this->_request['username'] ?? '';
        $password = $this->_request['password'] ?? '';
        $email = $this->_request['email'] ?? '';
        $phone = $this->_request['phone'] ?? '';

        if (empty($username) || empty($password) || empty($email) || empty($phone)) {
            $error = ['status' => 'FAILED', 'msg' => 'POST parameters required: "username", "password", "email", "phone"'];
            $this->response($this->json($error), 400);
            return;
        }

        $auth = new Auth($this->db);
        $result = $auth->signup($username, $password, $email, $phone);
        
        $status = ($result['status'] === 'SUCCESS') ? 201 : 400;
        $this->response($this->json($result), $status);
    }

    private function login(): void
    {
        if ($this->get_request_method() != "POST") {
            $error = ['status' => 'FAILED', 'msg' => 'Only POST method allowed'];
            $this->response($this->json($error), 405);
            return;
        }

        $username = $this->_request['username'] ?? '';
        $password = $this->_request['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = ['status' => 'FAILED', 'msg' => 'POST parameters required: "username", "password"'];
            $this->response($this->json($error), 400);
            return;
        }

        $auth = new Auth($this->db);
        $result = $auth->login($username, $password);
        
        $status = ($result['status'] === 'SUCCESS') ? 200 : 401;
        $this->response($this->json($result), $status);
    }

    private function logout(): void
    {
        if ($this->get_request_method() != "POST") {
            $error = ['status' => 'FAILED', 'msg' => 'Only POST method allowed'];
            $this->response($this->json($error), 405);
            return;
        }

        $access_token = $this->_request['access_token'] ?? '';

        if (empty($access_token)) {
            $error = ['status' => 'FAILED', 'msg' => 'POST parameter required: "access_token"'];
            $this->response($this->json($error), 400);
            return;
        }

        $session = new Session($this->db);
        $result = $session->delete($access_token);
        
        $status = ($result['status'] === 'SUCCESS') ? 200 : 401;
        $this->response($this->json($result), $status);
    }

    private function refresh(): void
    {
        if ($this->get_request_method() != "POST") {
            $error = ['status' => 'FAILED', 'msg' => 'Only POST method allowed'];
            $this->response($this->json($error), 405);
            return;
        }

        $refresh_token = $this->_request['refresh_token'] ?? '';

        if (empty($refresh_token)) {
            $error = ['status' => 'FAILED', 'msg' => 'POST parameter required: "refresh_token"'];
            $this->response($this->json($error), 400);
            return;
        }

        $session = new Session($this->db);
        $result = $session->refresh($refresh_token);
        
        $status = ($result['status'] === 'SUCCESS') ? 200 : 401;
        $this->response($this->json($result), $status);
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

$api = new API();
$api->processApi();
?>