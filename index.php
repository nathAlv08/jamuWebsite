<?php 

try {
    $conn = new \PDO('sqlite:./db/jamu.db');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $method = $_GET['method'] ?? null;
    $lihat = $_GET['lihat'] ?? null;
  
    if ($lihat) {
      
      $sql = 'SELECT * FROM bahan WHERE id = :id';
      $stmt = $conn->prepare($sql);
      $stmt->bindParam(':id', $lihat, PDO::PARAM_INT);
      $stmt->execute();
      $bahanDetail = $stmt->fetch(PDO::FETCH_ASSOC);
  
      // Jika tugas ditemukan, tampilkan detail
      if ($bahanDetail) {
        echo renderDetailBahan($bahanDetail);
      } else {
        echo "<p>ERROR.</p>";
      }
    } else {
      // Menampilkan daftar tugas
      $sql = 'SELECT * FROM bahan';
      $stmt = $conn->query($sql);
      $bahan = $stmt->fetchAll(PDO::FETCH_ASSOC);
      echo renderListingBahan($bahan);
    }
  }
  
  function renderListingBahan($daftarBahan) {
    $list = "<ol>";
  
    foreach ($daftarBahan as $b) {
      $list .= <<<HTML
      <li>
        <strong>{$b['nama']}</strong> ({$b['jenis']}) 
        <p>{$b['deskripsi']} </p> 
        <p>(Rp. {$b['harga']})</p>
        <p><a href="?lihat={$b['id']}">[Lihat Detail]</a> </p>
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
      <h2>Daftar Bahan</h2>
      {$list}
    
    </body>
    </html>
  HTML;
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
?>