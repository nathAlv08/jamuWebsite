<?php
require_once __DIR__ . '/../koneksi.php';

if (!isset($_GET['id'])) {
    die("ID racikan tidak ditemukan");
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("DELETE FROM racikan_bahan WHERE racikan_id = ?");
$stmt->execute([$id]);

$stmt = $conn->prepare("DELETE FROM racikan WHERE id = ?");
$stmt->execute([$id]);

header("Location: /../index.php?racikan=1");
exit;
