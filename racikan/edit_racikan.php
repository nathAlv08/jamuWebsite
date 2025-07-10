<?php
require_once __DIR__ . '/../koneksi.php'; // Koneksi database

if (!isset($_GET['id'])) {
    // Jika ID racikan tidak ditemukan, tampilkan pesan error dengan layout
    ob_start();
    require_once __DIR__ . '/../header.php';
    echo '<div class="main-area"><div class="container main-content no-sidebar">';
    echo "<h1>Kesalahan</h1><p>ID racikan tidak ditemukan.</p>";
    echo "<p><a href='/index.php?racikan=1'>⬅ Kembali ke Daftar Racikan</a></p>";
    echo '</div></div>';
    require_once __DIR__ . '/../footer.php';
    echo ob_get_clean();
    exit;
}

$id = intval($_GET['id']);

// Ambil data racikan dan bahan terpilih
$stmt = $conn->prepare("SELECT * FROM racikan WHERE id = ?");
$stmt->execute([$id]);
$racikan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$racikan) {
    // Jika racikan tidak ditemukan di database, tampilkan pesan error dengan layout
    ob_start();
    require_once __DIR__ . '/../header.php';
    echo '<div class="main-area"><div class="container main-contentr">';
    echo "<h1>Kesalahan</h1><p>Racikan tidak ditemukan.</p>";
    echo "<p><a href='/index.php?racikan=1'>⬅ Kembali ke Daftar Racikan</a></p>";
    echo '</div></div>';
    require_once __DIR__ . '/../footer.php';
    echo ob_get_clean();
    exit;
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
    header("Location: /index.php?lihat_racikan=$id"); // Redirect ke detail racikan yang baru diedit
    exit;
}

// Ambil semua bahan dari database untuk ditampilkan di form
$bahanSemua = $conn->query("SELECT * FROM bahan")->fetchAll(PDO::FETCH_ASSOC);
// Ambil bahan yang sudah dipakai di racikan ini beserta jumlahnya
$bahanDipakai = $conn->prepare("SELECT * FROM racikan_bahan WHERE racikan_id = ?");
$bahanDipakai->execute([$id]);
$bahanMap = [];
foreach ($bahanDipakai as $b) {
    $bahanMap[$b['bahan_id']] = $b['jumlah'];
}

ob_start(); // Memulai output buffering
require_once __DIR__ . '/../header.php'; // Memanggil header.php dari folder utama
?>
<div class="main-area"> <div class="container main-content"> <h2>Edit Racikan: <?= htmlspecialchars($racikan['nama']) ?></h2>
        <form method="POST">
            <label>Nama Racikan: <input type="text" name="nama" value="<?= htmlspecialchars($racikan['nama']) ?>" required></label><br>
            <label>Deskripsi: <textarea name="deskripsi"><?= htmlspecialchars($racikan['deskripsi']) ?></textarea></label><br><br>

            <h3>Update Bahan:</h3>
            <?php foreach ($bahanSemua as $b): ?>
                <?php $jumlah = $bahanMap[$b['id']] ?? 0; // Ambil jumlah jika bahan sudah dipakai, default 0 ?>
                <label>
                    <?= htmlspecialchars($b['nama']) ?> (Rp <?= number_format($b['harga']) ?>)
                    <input type="number" name="bahan[<?= $b['id'] ?>]" value="<?= $jumlah ?>" min="0">
                </label><br>
            <?php endforeach; ?>

            <br><button type="submit">Simpan Perubahan</button>
        </form>
        <p><a href="/index.php?lihat_racikan=<?= $id ?>">⬅ Kembali ke Racikan</a></p>
    </div> </div> <?php
require_once __DIR__ . '/../footer.php'; // Memanggil footer.php dari folder utama
echo ob_get_clean(); // Mengakhiri output buffering dan menampilkan semua konten
?>