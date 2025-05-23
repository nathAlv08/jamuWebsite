<?php
require_once __DIR__ . '/../koneksi.php';

if (!isset($_GET['id'])) {
    die("ID racikan tidak ditemukan");
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM racikan WHERE id = ?");
$stmt->execute([$id]);
$racikan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$racikan) {
    die("Racikan tidak ditemukan");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $bahan = $_POST['bahan'];

    $conn->beginTransaction();

    $stmt = $conn->prepare("UPDATE racikan SET nama = ?, deskripsi = ? WHERE id = ?");
    $stmt->execute([$nama, $deskripsi, $id]);

    $conn->prepare("DELETE FROM racikan_bahan WHERE racikan_id = ?")->execute([$id]);

    $stmt2 = $conn->prepare("INSERT INTO racikan_bahan (racikan_id, bahan_id, jumlah) VALUES (?, ?, ?)");
    foreach ($bahan as $idBahan => $jumlah) {
        if ($jumlah > 0) {
            $stmt2->execute([$id, $idBahan, $jumlah]);
        }
    }

    $conn->commit();
    header("Location: /../index.php?lihat_racikan=$id");
    exit;
}

$bahanSemua = $conn->query("SELECT * FROM bahan")->fetchAll(PDO::FETCH_ASSOC);
$bahanDipakai = $conn->prepare("SELECT * FROM racikan_bahan WHERE racikan_id = ?");
$bahanDipakai->execute([$id]);
$bahanMap = [];
foreach ($bahanDipakai as $b) {
    $bahanMap[$b['bahan_id']] = $b['jumlah'];
}
?>

<h2>Edit Racikan</h2>
<form method="POST">
    <label>Nama Racikan: <input type="text" name="nama" value="<?= htmlspecialchars($racikan['nama']) ?>" required></label><br>
    <label>Deskripsi: <textarea name="deskripsi"><?= htmlspecialchars($racikan['deskripsi']) ?></textarea></label><br><br>

    <h3>Update Bahan:</h3>
    <?php foreach ($bahanSemua as $b): ?>
        <?php $jumlah = $bahanMap[$b['id']] ?? 0; ?>
        <label>
            <?= $b['nama'] ?> (Rp <?= number_format($b['harga']) ?>)
            <input type="number" name="bahan[<?= $b['id'] ?>]" value="<?= $jumlah ?>" min="0">
        </label><br>
    <?php endforeach; ?>

    <br><button type="submit">Simpan Perubahan</button>
</form>
<p><a href="/../index.php?lihat_racikan=<?= $id ?>">⬅ Kembali ke Racikan</a></p>