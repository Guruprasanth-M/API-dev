<?php
declare(strict_types=1);

class Database
{
    private static ?mysqli $conn = null;

    public static function getConnection(): mysqli
    {
        if (self::$conn instanceof mysqli) {
            return self::$conn;
        }

        $host = $_ENV['DB_SERVER'] ?? '127.0.0.1';
        $user = $_ENV['DB_USERNAME'] ?? 'root';
        $pass = $_ENV['DB_PASSWORD'] ?? '';
        $name = $_ENV['DB_NAME'] ?? '';
        $timeout = $_ENV['DB_CONNECT_TIMEOUT'] ?? 5;

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli('p:' . $host, $user, $pass, $name);
            $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, $timeout);
            
            if (defined('MYSQLI_OPT_READ_TIMEOUT')) {
                $conn->options(MYSQLI_OPT_READ_TIMEOUT, 10);
            }
            if (defined('MYSQLI_OPT_WRITE_TIMEOUT')) {
                $conn->options(MYSQLI_OPT_WRITE_TIMEOUT, 10);
            }
            
            $conn->set_charset('utf8mb4');
            $conn->autocommit(true);
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException(
                'Database connection failed: ' . $e->getMessage(),
                500,
                $e
            );
        }

        self::$conn = $conn;
        return self::$conn;
    }

    public static function close(): void
    {
        if (self::$conn instanceof mysqli) {
            self::$conn->close();
            self::$conn = null;
        }
    }

    private function __construct() {}
    private function __clone() {}
}
