<?php
declare(strict_types=1);

class Router
{
    private mysqli $db;
    private array $request;
    private string $method;
    private array $routes = [];

    public function __construct(mysqli $db, array $request, string $method)
    {
        $this->db = $db;
        $this->request = $request;
        $this->method = $method;
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        $this->routes = [
            'about' => 'AboutController',
            'test' => 'TestController',
            'signup' => 'SignupController',
            'login' => 'LoginController',
            'logout' => 'LogoutController',
            'refresh' => 'RefreshController',
            'userexists' => 'UserExistsController',
        ];
    }

    public function addRoute(string $endpoint, string $controller): void
    {
        $this->routes[$endpoint] = $controller;
    }

    public function dispatch(string $endpoint): array
    {
        $endpoint = strtolower(trim(str_replace("/", "", $endpoint)));
        
        if (!isset($this->routes[$endpoint])) {
            return ['status' => 'FAILED', 'msg' => 'Endpoint not found', 'code' => 404];
        }

        $controllerClass = $this->routes[$endpoint];
        
        if (!class_exists($controllerClass)) {
            return ['status' => 'FAILED', 'msg' => 'Controller not found', 'code' => 500];
        }

        $controller = new $controllerClass($this->db, $this->request, $this->method);
        return $controller->handle();
    }

    public function getRoutes(): array
    {
        return array_keys($this->routes);
    }
}
