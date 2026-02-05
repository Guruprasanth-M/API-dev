<?php
declare(strict_types=1);

class REST
{
    public array $_allow = [];
    public string $_content_type = 'application/json';
    public array $_request = [];

    private string $_method = '';
    private int $_code = 200;

    public function __construct()
    {
        $this->_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->inputs();
    }

    public function get_request_method(): string
    {
        return $this->_method;
    }

    public function response(mixed $data, int $status = 200): never
    {
        $this->_code = $status ?: 200;
        $this->set_headers();

        if ($this->_content_type === 'application/json' && !is_string($data)) {
            echo json_encode(
                $data,
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES
            );
        } else {
            echo $data;
        }

        exit;
    }

    private function get_status_message(): string
    {
        $status = [
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            415 => 'Unsupported Media Type',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
        ];

        return $status[$this->_code] ?? $status[500];
    }

    private function inputs(): void
    {
        switch ($this->get_request_method()) {
            case 'POST':
                $this->_request = $this->parseInput($_POST);
                break;

            case 'GET':
            case 'DELETE':
                $this->_request = $this->parseInput($_GET);
                break;

            case 'PUT':
            case 'PATCH':
                $raw = file_get_contents('php://input') ?: '';

                if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
                    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                } else {
                    parse_str($raw, $data);
                }

                $this->_request = $this->parseInput($data ?? []);
                break;

            default:
                $this->response(['error' => 'Not Acceptable'], 406);
        }
    }

    private function parseInput(mixed $data): mixed
    {
        if (is_array($data)) {
            $parsed = [];
            foreach ($data as $k => $v) {
                $parsed[$k] = $this->parseInput($v);
            }
            return $parsed;
        }

        if (is_string($data)) {
            return trim(str_replace("\0", '', $data));
        }

        return $data;
    }

    private function set_headers(): void
    {
        header(
            sprintf(
                'HTTP/1.1 %d %s',
                $this->_code,
                $this->get_status_message()
            )
        );

        header('Content-Type: ' . $this->_content_type . '; charset=utf-8');
    }
}
