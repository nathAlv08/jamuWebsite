<?php 
session_start();
$sql = "SELECT * FROM bahan";

try {
    $conn = new \PDO('sqlite:./db/jamu.db');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage();
}