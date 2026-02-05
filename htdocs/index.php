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
        $endpoint = $_REQUEST['request'] ?? '';
        
        $router = new Router($this->db, $this->_request, $this->get_request_method());
        $result = $router->dispatch($endpoint);
        
        $code = $result['code'] ?? 200;
        unset($result['code']);
        
        $this->response($this->json($result), $code);
    }

    private function json(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT);
    }
}

$api = new API();
$api->processApi();