<?php
declare(strict_types=1);

class Router
{
    private mysqli $db;
    private array $request;
    private string $method;

    public function __construct(mysqli $db, array $request, string $method)
    {
        $this->db = $db;
        $this->request = $request;
        $this->method = $method;
    }

    public function dispatch(string $endpoint): array
    {
        $endpoint = strtolower(trim(str_replace("/", "", $endpoint)));
        
        return $this->callController($endpoint);
    }

    private function callController(string $endpoint): array
    {
        $controllerClass = $this->resolveController($endpoint);
        
        if ($controllerClass === null) {
            return ['status' => 'FAILED', 'msg' => 'Controller not found', 'code' => 404];
        }

        $handler = Closure::bind(function() use ($controllerClass) {
            $controller = new $controllerClass($this->db, $this->request, $this->method);
            return $controller->handle();
        }, $this, self::class);

        return $handler();
    }

    private function resolveController(string $endpoint): ?string
    {
        // Notes module is optional — only scan its controllers if the module is present
        $controllerDirs = [SRC_PATH . '/Controllers'];
        if (defined('NOTES_PATH') && is_dir(NOTES_PATH . '/Controllers')) {
            $controllerDirs[] = NOTES_PATH . '/Controllers';
        }

        foreach ($controllerDirs as $dir) {
            if (!is_dir($dir)) continue;
            foreach (glob($dir . '/*Controller.php') as $file) {
                $className = basename($file, '.php');
                $name = strtolower(str_replace('Controller', '', $className));
                if ($name === $endpoint) {
                    return $className;
                }
            }
        }
        return null;
    }

    public function __call(string $method, array $arguments): array
    {
        $endpoint = strtolower(basename($method));
        
        return $this->callController($endpoint);
    }
}
