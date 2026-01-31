
<?php

class Database
{
    public static $conn = null;
    public static function getConnection()
    {
        if (Database::$conn == null) {
            $servername = $_ENV['DB_SERVER'] ?: '127.0.0.1';
            $username = $_ENV['DB_USERNAME'] ?: 'root';
            $password = $_ENV['DB_PASSWORD'] ?: '';
            $dbname = $_ENV['DB_NAME'] ?: '';
            try {
                $connection = new mysqli($servername, $username, $password, $dbname);
            } catch (mysqli_sql_exception $e) {
                throw new Exception('Database connection error: ' . $e->getMessage());
            }
            if ($connection->connect_error) {
                throw new Exception("Connection failed: " . $connection->connect_error);
            } else {
                Database::$conn = $connection;
                return Database::$conn;
            }
        } else {
                return Database::$conn;
        }
    }
}