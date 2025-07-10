<?php
session_start();
require 'koneksi.php';

/**
 * Fungsi untuk merender daftar bahan.
 * Mengembalikan string HTML konten spesifik halaman.
 */
function renderListingBahan($daftarBahan) {
    ob_start();
    ?>
    <h1>Selamat datang di Jamuku</h1>
    <h2>Daftar Bahan</h2>
    <ol>
    <?php if (empty($daftarBahan)): ?>
        <p>Tidak ada bahan yang ditemukan untuk kategori ini.</p>
    <?php else: ?>
        <?php foreach ($daftarBahan as $b): ?>
        <li>
            <div class="bahan-item-content"> <?php
                // Logika nama file gambar: nama_bahan (huruf kecil, spasi jadi underscore).png
                $image_name = strtolower($b['nama']) . '.png';
                $image_path = 'imageAsset/' . $image_name; // Menggunakan folder 'imageAsset'
                ?>
                <img src="<?= htmlspecialchars($image_path) ?>" alt="<?= htmlspecialchars($b['nama']) ?>" class="bahan-image">

                <div class="bahan-details">
                    <strong><?= htmlspecialchars($b['nama']) ?></strong> (<?= htmlspecialchars($b['jenis']) ?>)
                    <p><?= htmlspecialchars($b['deskripsi']) ?> </p>
                    <p>(Rp. <?= number_format($b['harga']) ?>)</p>
                    <p>
                        <a href="?lihat=<?= $b['id'] ?>">[Lihat Detail]</a>
                        <form method="POST" style="display:inline-block; margin-left: 10px;">
                            <input type="hidden" name="bahan_id" value="<?= $b['id'] ?>">
                            <label>Jumlah: <input type="number" name="jumlah" value="1" min="1" required></label>
                            <button type="submit" name="tambah">Tambah ke Keranjang</button>
                        </form>
                    </p>
                </div>
            </div> </li>
        <?php endforeach; ?>
    <?php endif; ?>
    </ol>
    <?php
    return ob_get_clean();
}

/**
 * Fungsi untuk merender detail bahan.
 * Mengembalikan string HTML konten spesifik halaman.
 */
function renderDetailBahan($bahan) {
    ob_start();
    ?>
    <h1>Detail Bahan</h1>
    <p><strong>Nama Bahan :</strong> <?= htmlspecialchars($bahan['nama']) ?></p>
    <p><strong>Manfaat :</strong> <?= htmlspecialchars($bahan['deskripsi']) ?></p>
    <p><strong>Harga :</strong> (Rp. <?= number_format($bahan['harga']) ?>)</p>
    <p><a href="{$_SERVER['SCRIPT_NAME']}">⬅ Kembali ke Daftar Bahan</a></p>
    <?php
    return ob_get_clean();
}

/**
 * Fungsi untuk merender keranjang belanja.
 * Mengembalikan string HTML konten spesifik halaman.
 */
function renderKeranjang() {
    $keranjang = $_SESSION['keranjang'] ?? [];

    ob_start();
    ?>
    <h2>Keranjang Belanja</h2>
    <?php
    if (empty($keranjang)) {
        echo '<p>Keranjang Kosong</p>';
        echo '<p><a href="' . $_SERVER['SCRIPT_NAME'] . '">⬅ Kembali ke Daftar</a></p>';
    } else {
    ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Nama</th>
                <th>Harga Satuan</th>
                <th>Jumlah</th>
                <th>Total</th>
                <th>Aksi</th>
            </tr>
            <?php
            $totalKeseluruhan = 0;
            foreach ($_SESSION['keranjang'] as $id => $item):
                $subtotal = $item['harga'] * $item['jumlah'];
                $totalKeseluruhan += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['nama']) ?></td>
                    <td>Rp <?= number_format($item['harga']) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="ubah_id" value="<?= $id ?>">
                            <input type="hidden" name="aksi" value="kurang">
                            <button type="submit">−</button>
                        </form>
                        <?= $item['jumlah'] ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="ubah_id" value="<?= $id ?>">
                            <input type="hidden" name="aksi" value="tambah">
                            <button type="submit">+</button>
                        </form>
                    </td>
                    <td>Rp <?= number_format($subtotal) ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="hapus" value="<?= $id ?>">
                            <button type="submit" onclick="return confirm('Hapus item ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3"><strong>Total Keseluruhan</strong></td>
                <td colspan="2"><strong>Rp <?= number_format($totalKeseluruhan) ?></strong></td>
            </tr>
        </table>
        <form method="POST" style="margin-top: 20px; display:inline-block; margin-right:10px;">
            <input type="hidden" name="aksi" value="kosongkan">
            <button type="submit" onclick="return confirm('Kosongkan semua keranjang?')">Kosongkan Keranjang</button>
        </form>
        <form method="POST" onsubmit="return confirm('Yakin ingin memproses pembayaran?');" style="display:inline-block;">
            <input type="hidden" name="aksi" value="bayar">
            <button type="submit" name="bayar">Bayar Sekarang</button>
        </form>
        <p><a href="{$_SERVER['SCRIPT_NAME']}" style="display:block; margin-top:15px;">⬅ Kembali ke Daftar</a></p>
    <?php
    }
    return ob_get_clean();
}

/**
 * Fungsi untuk merender halaman pembayaran.
 * Mengembalikan string HTML konten spesifik halaman.
 */
function renderPembayaran() {
    $rincian = $_SESSION['rincian_bayar'] ?? [];

    ob_start();
    ?>
    <h2>Rincian Pembayaran</h2>
    <?php if (empty($rincian)): ?>
        <p>Tidak ada item untuk dibayar.</p>
        <p><a href='{$_SERVER['SCRIPT_NAME']}'>⬅ Kembali ke Daftar</a></p>
    <?php else: ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Nama</th>
                <th>Harga</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
            <?php
            $total = 0;
            foreach ($rincian as $item):
                $subtotal = $item['harga'] * $item['jumlah'];
                $total += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['nama']) ?></td>
                    <td>Rp <?= number_format($item['harga']) ?></td>
                    <td><?= $item['jumlah'] ?></td>
                    <td>Rp <?= number_format($subtotal) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total) ?></strong></td>
            </tr>
        </table>
        <p>Pembayaran berhasil diproses! Terima kasih atas pembelian Anda.</p>
        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="aksi" value="selesai">
            <button type="submit">Selesai</button>
        </form>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}

/**
 * Fungsi untuk merender daftar racikan.
 * Mengembalikan string HTML konten spesifik halaman.
 */
function renderRacikanList() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM racikan");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_start();
    ?>
    <h2>Daftar Racikan</h2>
    <p><a href="racikan/tambah_racikan.php">➕ Tambah Racikan</a></p>
    <ul>
    <?php if (empty($data)): ?>
        <p>Belum ada racikan yang ditambahkan.</p>
    <?php else: ?>
        <?php foreach ($data as $r): ?>
           <li><strong><?= htmlspecialchars($r['nama']) ?></strong> - <a href="/index.php?lihat_racikan=<?= $r['id'] ?>">Lihat Detail</a></li>
        <?php endforeach; ?>
    <?php endif; ?>
    </ul>
    <p><a href='{$_SERVER['SCRIPT_NAME']}'>⬅ Kembali ke Daftar Bahan</a></p>
    <?php
    return ob_get_clean();
}

/**
 * Fungsi untuk merender detail racikan.
 * Mengembalikan string HTML konten spesifik halaman.
 */
function renderDetailRacikan($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM racikan WHERE id = ?");
    $stmt->execute([$id]);
    $racikan = $stmt->fetch(PDO::FETCH_ASSOC);

    ob_start();
    ?>
    <?php if (!$racikan): ?>
        <p>Racikan tidak ditemukan.</p>
        <p><a href='/index.php?racikan=1'>⬅ Kembali ke Daftar Racikan</a></p>
    <?php else: ?>
        <h2>Detail Racikan: <?= htmlspecialchars($racikan['nama']) ?></h2>
        <p><?= htmlspecialchars($racikan['deskripsi']) ?></p>

        <?php
        $stmt = $conn->prepare("SELECT b.nama, b.harga, rb.jumlah FROM racikan_bahan rb JOIN bahan b ON rb.bahan_id = b.id WHERE rb.racikan_id = ?");
        $stmt->execute([$id]);
        $bahanList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table border='1'>
            <tr>
                <th>Nama Bahan</th>
                <th>Jumlah</th>
                <th>Subtotal</th>
            </tr>
            <?php
            $total = 0;
            foreach ($bahanList as $b):
                $subtotal = $b['harga'] * $b['jumlah'];
                $total += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($b['nama']) ?></td>
                    <td><?= $b['jumlah'] ?></td>
                    <td>Rp <?= number_format($subtotal) ?></td>
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan='2'><strong>Total</strong></td>
                <td><strong>Rp <?= number_format($total) ?></strong></td>
            </tr>
        </table>

        <form method="POST" style="margin-top: 20px;">
            <input type="hidden" name="racikan_id" value="<?= $id ?>">
            <button type="submit" name="tambah_racikan_keranjang">Tambahkan ke Keranjang</button>
        </form>

        <p style="margin-top: 20px;">
            <a href="racikan/edit_racikan.php?id=<?= $id ?>">✏️ Edit Racikan</a> |
            <a href="racikan/hapus_racikan.php?id=<?= $id ?>" onclick="return confirm('Hapus racikan ini?')">🗑️ Hapus</a>
        </p>

        <p><a href='/index.php?racikan=1'>⬅ Kembali ke Daftar Racikan</a></p>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}


// --- BAGIAN UTAMA LOGIKA INDEX.PHP (POST & GET) ---

$pageContent = ''; // Variabel untuk menyimpan konten HTML halaman yang akan dirender
$showSidebar = false; // Flag untuk menentukan apakah sidebar akan ditampilkan

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Logika POST request Anda
    // Karena semua POST request Anda sudah ada `header("Location: ...")` atau `exit;`,
    // eksekusi script akan berhenti dan me-redirect browser, sehingga bagian rendering di bawah tidak akan tercapai.

    if (isset($_POST['tambah'])) {
        $idBahan = $_POST['bahan_id'];
        $jumlah = max(1, intval($_POST['jumlah']));
        $stmt = $conn->prepare('SELECT * FROM bahan WHERE id = :id');
        $stmt->bindParam(':id', $idBahan, PDO::PARAM_INT);
        $stmt->execute();
        $bahan = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($bahan) {
            if (!isset($_SESSION['keranjang'])) {
                $_SESSION['keranjang'] = [];
            }

            if (isset($_SESSION['keranjang'][$idBahan])) {
                $_SESSION['keranjang'][$idBahan]['jumlah'] += $jumlah;
            } else {
                $_SESSION['keranjang'][$idBahan] = [
                    'nama' => $bahan['nama'],
                    'harga' => $bahan['harga'],
                    'jumlah' => $jumlah
                ];
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
    if (isset($_POST['aksi'])) {
        $aksi = $_POST['aksi'];

        switch ($aksi) {
            case 'kosongkan':
                unset($_SESSION['keranjang']);
                break;

            case 'bayar':
                $_SESSION['rincian_bayar'] = $_SESSION['keranjang'] ?? [];
                header("Location: " . $_SERVER['PHP_SELF'] . "?bayar=1");
                exit;

            case 'selesai':
                unset($_SESSION['keranjang']);
                unset($_SESSION['rincian_bayar']);
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;

            case 'tambah':
            case 'kurang':
                if (isset($_POST['ubah_id'])) {
                    $idUbah = $_POST['ubah_id'];
                    if (isset($_SESSION['keranjang'][$idUbah])) {
                        if ($aksi === 'tambah') {
                            $_SESSION['keranjang'][$idUbah]['jumlah'] += 1;
                        } elseif ($aksi === 'kurang') {
                            if ($_SESSION['keranjang'][$idUbah]['jumlah'] > 1) {
                                $_SESSION['keranjang'][$idUbah]['jumlah'] -= 1;
                            } else {
                                unset($_SESSION['keranjang'][$idUbah]);
                            }
                        }
                    }
                }
            break;
        }
        if (!in_array($aksi, ['bayar', 'selesai'])) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?keranjang=1");
            exit;
        }
    }
    if (isset($_POST['hapus'])) {
        $idHapus = $_POST['hapus'];
        if (isset($_SESSION['keranjang'][$idHapus])) {
            unset($_SESSION['keranjang'][$idHapus]);
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?keranjang=1");
        exit;
    }

    if (isset($_POST['tambah_racikan_keranjang'])) {
        $id = $_POST['racikan_id'];

        $stmt = $conn->prepare("SELECT * FROM racikan_bahan rb JOIN bahan b ON rb.bahan_id = b.id WHERE rb.racikan_id = ?");
        $stmt->execute([$id]);
        $bahanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }

        foreach ($bahanList as $bahan) {
            $idBahan = $bahan['bahan_id'];
            $jumlah = $bahan['jumlah'];

            if (isset($_SESSION['keranjang'][$idBahan])) {
                $_SESSION['keranjang'][$idBahan]['jumlah'] += $jumlah;
            } else {
                $_SESSION['keranjang'][$idBahan] = [
                    'nama' => $bahan['nama'],
                    'harga' => $bahan['harga'],
                    'jumlah' => $jumlah
                ];
            }
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?keranjang=1");
        exit;
    }
}


// Logika penanganan GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lihat = $_GET['lihat'] ?? null;
    $keranjang = $_GET['keranjang'] ?? null;
    $racikan = $_GET['racikan'] ?? null;
    $lihat_racikan = $_GET['lihat_racikan'] ?? null;
    $bayar = $_GET['bayar'] ?? null;
    $selesai = $_GET['selesai'] ?? null;

    $jenis_bahan = $_GET['jenis'] ?? null;

    if ($lihat) {
      $sql = 'SELECT * FROM bahan WHERE id = :id';
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $lihat, PDO::PARAM_INT);
      $stmt->execute();
      $bahanDetail = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($bahanDetail) {
        $pageContent = renderDetailBahan($bahanDetail);
      } else {
        $pageContent = "<h1>Kesalahan</h1><p>Bahan tidak ditemukan.</p><p><a href='{$_SERVER['SCRIPT_NAME']}'>⬅ Kembali ke Daftar Bahan</a></p>";
      }
      $showSidebar = true; // Detail bahan tetap menampilkan sidebar untuk navigasi bahan
    } elseif ($keranjang) {
        $pageContent = renderKeranjang();
        $showSidebar = false; // Keranjang tidak menampilkan sidebar
    } elseif ($racikan) { // Cek jika parameter racikan ada (daftar racikan)
        $pageContent = renderRacikanList();
        $showSidebar = false; // Daftar racikan tidak menampilkan sidebar
    } elseif ($lihat_racikan) { // Cek jika parameter lihat_racikan ada (detail racikan)
        $id = intval($lihat_racikan);
        $pageContent = renderDetailRacikan($id);
        $showSidebar = false; // Detail racikan tidak menampilkan sidebar
    } elseif ($bayar) {
        $pageContent = renderPembayaran();
        $showSidebar = false; // Pembayaran tidak menampilkan sidebar
    } elseif ($selesai) {
        unset($_SESSION['rincian_bayar']);
        $pageContent = '<p>Proses selesai. <a href="/">Kembali ke beranda</a></p>'; // Pesan fallback
        $showSidebar = false; // Selesai tidak menampilkan sidebar
    } else { // Default view: daftar bahan
      $sql = 'SELECT * FROM bahan';
      if ($jenis_bahan) {
          $sql .= ' WHERE jenis = :jenis';
      }
      $stmt = $conn->prepare($sql);
      if ($jenis_bahan) {
          $stmt->bindParam(':jenis', $jenis_bahan, PDO::PARAM_STR);
      }
      $stmt->execute();
      $bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);
      $pageContent = renderListingBahan($bahan);
      $showSidebar = true; // Halaman daftar bahan menampilkan sidebar
    }
}


// --- BAGIAN TERAKHIR: TAMPILKAN HALAMAN LENGKAP ---
// Ini akan selalu dieksekusi setelah semua logika POST/GET di atas
// dan $pageContent sudah diisi dengan HTML yang relevan.
require_once 'header.php'; // Menyertakan header, navbar, dan pembuka div layout-wrapper, header-wrapper
?>

<div class="main-area">
    <?php if ($showSidebar): ?>
        <aside class="sidebar">
            <h3>Kategori Bahan</h3>
            <ul>
                <li><a href="/index.php" class="<?= (!isset($_GET['jenis']) || $_GET['jenis'] == '') ? 'active' : '' ?>">Semua Bahan</a></li>
                <li><a href="/index.php?jenis=Bahan utama" class="<?= (isset($_GET['jenis']) && $_GET['jenis'] == 'Bahan utama') ? 'active' : '' ?>">Bahan Utama</a></li>
                <li><a href="/index.php?jenis=Rempah tambahan" class="<?= (isset($_GET['jenis']) && $_GET['jenis'] == 'Rempah tambahan') ? 'active' : '' ?>">Rempah Tambahan</a></li>
                <li><a href="/index.php?jenis=Bahan tambahan" class="<?= (isset($_GET['jenis']) && $_GET['jenis'] == 'Bahan tambahan') ? 'active' : '' ?>">Bahan Tambahan</a></li>
                <li><a href="/index.php?jenis=Pemanis" class="<?= (isset($_GET['jenis']) && $_GET['jenis'] == 'Pemanis') ? 'active' : '' ?>">Pemanis</a></li>
            </ul>
        </aside>
        <div class="container main-content">
    <?php else: ?>
        <div class="container main-content">
    <?php endif; ?>

    <?php echo $pageContent; ?>

</div> </div> <?php
require_once 'footer.php'; // Menyertakan penutup div.layout-wrapper, body, html
?>