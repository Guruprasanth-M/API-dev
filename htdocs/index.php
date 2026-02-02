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
        
        // ✅ CHECK IF DATABASE IS CONNECTED BEFORE PROCESSING
        if (!$this->checkDatabaseConnection()) {
            return;  // Error already sent, stop processing
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

    private function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

$api = new API();
$api->processApi();
?>