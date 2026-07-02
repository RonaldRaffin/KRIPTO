<?php
// =====================================================
// VERSI RENTAN (VULNERABLE) - JANGAN DIGUNAKAN DI PRODUKSI
// Tujuan: demonstrasi Stored XSS untuk praktikum
// =====================================================

$file_data = "komentar.txt";

// Fitur Bantuan: Tombol Hapus Semua Komentar
// (Berguna jika web terlanjur rusak karena XSS)
if (isset($_GET['reset'])) {
    file_put_contents($file_data, "");
    header("Location: buku_tamu_rentan.php");
    exit;
}

// Menangani Pengiriman Form Komentar Baru
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama  = $_POST['nama'] ?? 'Anonim';
    $pesan = $_POST['pesan'] ?? '';

    if (!empty($pesan)) {
        // Format Simpan: Nama|Pesan\n
        $data_baru = $nama . "|" . $pesan . "\n";

        // FILE_APPEND = menambahkan baris baru tanpa menghapus yang lama
        file_put_contents($file_data, $data_baru, FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tamu Rentan XSS</title>
    <style>
        body {font-family: sans-serif; padding: 20px;}
        .komentar {background: #fdf4ff; padding: 10px; margin-bottom: 10px; border-left: 4px solid #c026d3;}
        .warn {background:#fee2e2; color:#991b1b; padding:8px 12px; border-radius:6px; font-size:13px; margin-bottom:15px;}
    </style>
</head>
<body>
    <div class="warn">⚠️ Versi ini SENGAJA dibuat rentan untuk keperluan praktikum keamanan web. Jangan deploy ke server publik.</div>
    <h2>Buku Tamu Pengunjung (RENTAN)</h2>
    <form method="POST">
        <input type="text" name="nama" placeholder="Nama Anda..." required style="margin-bottom:10px; padding:5px;"><br>
        <textarea name="pesan" placeholder="Tulis pesan Anda..." rows="3" cols="50" required></textarea><br>
        <button type="submit">Kirim Pesan</button>
        <a href="?reset=1" style="color:red; margin-left:15px; font-size:12px;">[ Hapus Semua Komentar ]</a>
    </form>

    <hr style="margin-top:20px; margin-bottom:20px;">
    <h3>Daftar Komentar:</h3>

    <?php
    // Membaca dan menampilkan isi file komentar.txt
    if (file_exists($file_data)) {
        $isi_file = file($file_data, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($isi_file as $baris) {
            // Memisahkan format Nama|Pesan
            list($nama_user, $pesan_user) = explode("|", $baris, 2);

            // ⚠️ KODE RENTAN: langsung di-echo ke HTML tanpa filter/escaping
            echo "<div class='komentar'><b>$nama_user:</b> $pesan_user</div>";
        }
    } else {
        echo "<p><i>Belum ada komentar.</i></p>";
    }
    ?>
</body>
</html>