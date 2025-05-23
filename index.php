<?php 
session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $keranjang = $_GET['keranjang'] ?? null;
    $lihat = $_GET['lihat'] ?? null;
  
    if ($lihat) {
      
      $sql = 'SELECT * FROM bahan WHERE id = :id';
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $lihat, PDO::PARAM_INT);
      $stmt->execute();
      $bahanDetail = $stmt->fetch(PDO::FETCH_ASSOC);
  
    
      if ($bahanDetail) {
        echo renderDetailBahan($bahanDetail);
      } else {
        echo "<p>ERROR.</p>";
      }

    } elseif ($keranjang) {
        echo renderKeranjang();
    } elseif (isset($_GET['racikan'])) {
        echo renderRacikanList();
    } elseif (isset($_GET['lihat_racikan'])) {
        $id = intval($_GET['lihat_racikan']);
        echo renderDetailRacikan($id); 
    } elseif (isset($_GET['bayar'])) {
        echo renderPembayaran();
    } elseif (isset($_GET['selesai'])) {
        unset($_SESSION['rincian_bayar']);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
    
      $sql = 'SELECT * FROM bahan';
      $stmt = $conn->query($sql);
      $bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo renderListingBahan($bahan);
    }
  }

function renderKeranjang() {
    $keranjang = $_SESSION['keranjang'] ?? [];

    if (empty($keranjang)) {
        return <<<HTML
        <h1>Keranjang Kosong</h1>
        <p><a href="{$_SERVER['SCRIPT_NAME']}">⬅️ Kembali ke Daftar</a></p>
        HTML;
    }

    ob_start(); ?>
    <h2>Keranjang Belanja</h2>
    <table border="1" cellpadding="5" cellspacing="0">
        <tr>
            <th>Nama</th>
            <th>Harga Satuan</th>
            <th>Jumlah</th>
            <th>Total</th>
            <th> </th>
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
            <td><strong>Rp <?= number_format($totalKeseluruhan) ?></strong></td>
        </tr>
    </table>
    <form method="POST">
        <input type="hidden" name="aksi" value="kosongkan">
        <button type="submit" onclick="return confirm('Kosongkan semua keranjang?')">Kosongkan Keranjang</button>
    </form>
    <p><a href="{$_SERVER['SCRIPT_NAME']}">⬅Kembali ke Daftar</a></p>
    <form method="POST" onsubmit="return confirm('Yakin ingin memproses pembayaran?');">
        <input type="hidden" name="aksi" value="bayar">
        <button type="submit" name="bayar">Bayar Sekarang</button>
    </form>
    
    <?php return ob_get_clean();
}

function renderListingBahan($daftarBahan) {
    $list = "<ol>";
  
    foreach ($daftarBahan as $b) {
      $list .= <<<HTML
      <li>
        <strong>{$b['nama']}</strong> ({$b['jenis']}) 
        <p>{$b['deskripsi']} </p> 
        <p>(Rp. {$b['harga']})</p>
        <p><a href="?lihat={$b['id']}">[Lihat Detail]</a> 
              <form method="POST">
                    <input type="hidden" name="bahan_id" value="{$b['id']}">
                    <label>Jumlah: <input type="number" name="jumlah" value="1" min="1" required></label>
                    <button type="submit" name="tambah">Tambah ke Keranjang</button>
              </form>
        </p>
      </li>
      HTML;
    }
    $list .= "</ol>";
    $list .= <<<HTML
    HTML;
    return <<<HTML
    <!DOCTYPE html>
    <html lang="id">
    <head>
      <meta charset="UTF-8">
      <title>Jamuku Official</title>
    </head>
    <body>
      <h1>Selamat datang di Jamuku</h1>
      <p><a href="?keranjang=1">Lihat Keranjang</a></p>
      <a href="?racikan=1">Lihat Racikan</a>
      <h2>Daftar Bahan</h2>
      {$list}
    
    </body>
    </html>
  HTML;
  $list .= renderListingRacikan($GLOBALS['conn']);
}
  

function renderDetailBahan($bahan) {
    return <<<HTML
    <h1>Detail Bahan</h1>
    <p><strong>Nama Bahan :</strong> {$bahan['nama']}</p>
    <p><strong>Manfaat :</strong> {$bahan['deskripsi']}</p>
    <p><strong>Harga :</strong> (Rp. {$bahan['harga']})</p>
    <a href="{$_SERVER['SCRIPT_NAME']}">Kembali ke Daftar Bahan</a>
  HTML;
  }
function renderPembayaran() {
    $rincian = $_SESSION['rincian_bayar'] ?? [];

    if (empty($rincian)) {
        return "<p>Tidak ada item untuk dibayar.</p><p><a href='{$_SERVER['SCRIPT_NAME']}'>⬅ Kembali ke Daftar</a></p>";
    }

    ob_start(); ?>
    <h2>Rincian Pembayaran</h2>
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
    <form method="POST">
        <input type="hidden" name="aksi" value="selesai">
        <button type="submit">Selesai</button>
    </form>
    <?php return ob_get_clean();
}
function renderRacikanList() {
    global $conn;
    $stmt = $conn->query("SELECT * FROM racikan");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = "<h2>Daftar Racikan</h2><a href='racikan/tambah_racikan.php'>➕ Tambah Racikan</a><ul>";
    foreach ($data as $r) {
       $html .= "<li><strong>{$r['nama']}</strong> - <a href='/index.php?lihat_racikan={$r['id']}'>Lihat Detail</a></li>";
    }
    $html .= "</ul><p><a href='{$_SERVER['SCRIPT_NAME']}'>⬅ Kembali ke Daftar</a></p>";
    return $html;
}

function renderDetailRacikan($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM racikan WHERE id = ?");
    $stmt->execute([$id]);
    $racikan = $stmt->fetch(PDO::FETCH_ASSOC);

    $html = "<h2>Detail Racikan: {$racikan['nama']}</h2><p>{$racikan['deskripsi']}</p>";

    $stmt = $conn->prepare("SELECT b.nama, b.harga, rb.jumlah FROM racikan_bahan rb JOIN bahan b ON rb.bahan_id = b.id WHERE rb.racikan_id = ?");
    $stmt->execute([$id]);
    $bahanList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = 0;
    $html .= "<table border='1'><tr><th>Nama Bahan</th><th>Jumlah</th><th>Subtotal</th></tr>";
    foreach ($bahanList as $b) {
        $subtotal = $b['harga'] * $b['jumlah'];
        $total += $subtotal;
        $html .= "<tr><td>{$b['nama']}</td><td>{$b['jumlah']}</td><td>Rp " . number_format($subtotal) . "</td></tr>";
    }
    $html .= "<tr><td colspan='2'><strong>Total</strong></td><td><strong>Rp " . number_format($total) . "</strong></td></tr></table>";

    $html .= <<<HTML
        <form method="POST">
            <input type="hidden" name="racikan_id" value="{$id}">
            <button type="submit" name="tambah_racikan_keranjang">Tambahkan ke Keranjang</button>
        </form>
        <p><a href="racikan/edit_racikan.php?id={$id}">Edit Racikan</a> | 
           <a href="racikan/hapus_racikan.php?id={$id}" onclick="return confirm('Hapus racikan ini?')">🗑️ Hapus</a></p>
    HTML;

    $html .= "<p><a href='/index.php'>⬅ Kembali ke Daftar Racikan</a></p>";
    return $html;
}

?>