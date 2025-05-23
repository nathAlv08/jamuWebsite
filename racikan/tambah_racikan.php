<?php
require_once __DIR__ . '/../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $bahan = $_POST['bahan'];

    $conn->beginTransaction();

    $stmt = $conn->prepare("INSERT INTO racikan (nama, deskripsi) VALUES (?, ?)");
    $stmt->execute([$nama, $deskripsi]);
    $racikanId = $conn->lastInsertId();

    $stmt2 = $conn->prepare("INSERT INTO racikan_bahan (racikan_id, bahan_id, jumlah) VALUES (?, ?, ?)");
    foreach ($bahan as $id => $jumlah) {
        if ($jumlah > 0) {
            $stmt2->execute([$racikanId, $id, $jumlah]);
        }
    }

    $conn->commit();
    header("Location: /../index.php?racikan=1");
    exit;
}

$bahan = $conn->query("SELECT * FROM bahan")->fetchAll(PDO::FETCH_ASSOC);
?>
<h2>Buat Racikan Baru</h2>
<form method="POST">
    <label>Nama Racikan: <input type="text" name="nama" required></label><br>
    <label>Deskripsi: <textarea name="deskripsi"></textarea></label><br><br>

    <h3>Pilih Bahan:</h3>
    <?php foreach ($bahan as $b): ?>
        <label>
            <?= $b['nama'] ?> (Rp <?= number_format($b['harga']) ?>)
            <input type="number" name="bahan[<?= $b['id'] ?>]" value="0" min="0">
        </label><br>
    <?php endforeach; ?>

    <br><button type="submit">Simpan Racikan</button>
</form>
<p><a href="/../index.php?racikan=1">⬅ Kembali</a></p>
