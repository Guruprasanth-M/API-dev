<?php
include 'libs/load.php';

?>
<h1>
<?php
echo $_ENV['DB_SERVER'] ?? '127.0.0.1';
echo ' | ';
echo $_ENV['DB_USERNAME'] ?? 'root';
echo ' | ';
echo $_ENV['DB_PASSWORD'] ?? '(hidden)';
echo ' | ';
echo $_ENV['DB_NAME'] ?? '(none)';
?>
</h1>
