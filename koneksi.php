<?php
try {
    $conn = new PDO('sqlite:' . __DIR__ . '/db/jamu.db');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}