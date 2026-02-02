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

        // Enable mysqli exceptions (IMPORTANT)
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $conn = new mysqli($host, $user, $pass, $name);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            throw new RuntimeException(
                'Database connection failed',
                500,
                $e
            );
        }

        self::$conn = $conn;
        return self::$conn;
    }

    // Prevent instantiation
    private function __construct() {}
    private function __clone() {}
}
