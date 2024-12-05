<?php
require_once __DIR__ . '/config/Database.php';


try {
    $db = Database::connect();
    echo "Connected to SQL Server successfully.";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
?>
