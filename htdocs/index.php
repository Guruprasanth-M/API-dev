<?php
error_reporting(E_ALL ^ E_DEPRECATED);

// --- CORS: Allow React / frontend apps to call this API ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Authorization, Content-Type, Accept");
header("Access-Control-Max-Age: 3600");

// Handle preflight OPTIONS request — respond immediately, skip all logic
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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
        // Notes module is optional — only run its migrations if the module is present
        if (is_dir(BASE_PATH . '/notes/Database/migrations')) {
            Migration::run($this->db, BASE_PATH . '/notes/Database/migrations');
        }
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