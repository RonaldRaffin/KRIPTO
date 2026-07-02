<?php
// =====================================================
// VERSI AMAN (PATCHED) - sudah ditambal dari Stored XSS
// Perbaikan: htmlspecialchars() pada setiap output ke HTML
// =====================================================

$file_data = "komentar.txt";

// Fitur Bantuan: Tombol Hapus Semua Komentar
if (isset($_GET['reset'])) {
    file_put_contents($file_data, "");
    header("Location: buku_tamu_aman.php");
    exit;
}

// Menangani Pengiriman Form Komentar Baru
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama  = $_POST['nama'] ?? 'Anonim';
    $pesan = $_POST['pesan'] ?? '';

    if (!empty($pesan)) {
        // Format Simpan: Nama|Pesan\n
        // Catatan: data mentah tetap disimpan apa adanya di file.
        // Filtering dilakukan saat MENAMPILKAN (output), bukan saat menyimpan.
        $data_baru = $nama . "|" . $pesan . "\n";
        file_put_contents($file_data, $data_baru, FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buku Tamu Aman (Anti-XSS)</title>
    <style>
        body {font-family: sans-serif; padding: 20px;}
        .komentar {background: #f0fdf4; padding: 10px; margin-bottom: 10px; border-left: 4px solid #16a34a;}
        .ok {background:#dcfce7; color:#166534; padding:8px 12px; border-radius:6px; font-size:13px; margin-bottom:15px;}
    </style>
</head>
<body>
    <div class="ok">✅ Versi ini sudah dipatch menggunakan htmlspecialchars() untuk mencegah Stored XSS.</div>
    <h2>Buku Tamu Pengunjung (AMAN)</h2>
    <form method="POST">
        <input type="text" name="nama" placeholder="Nama Anda..." required style="margin-bottom:10px; padding:5px;"><br>
        <textarea name="pesan" placeholder="Tulis pesan Anda..." rows="3" cols="50" required></textarea><br>
        <button type="submit">Kirim Pesan</button>
        <a href="?reset=1" style="color:red; margin-left:15px; font-size:12px;">[ Hapus Semua Komentar ]</a>
    </form>

    <hr style="margin-top:20px; margin-bottom:20px;">
    <h3>Daftar Komentar:</h3>

    <?php
    if (file_exists($file_data)) {
        $isi_file = file($file_data, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($isi_file as $baris) {
            list($nama_user, $pesan_user) = explode("|", $baris, 2);

            // ✅ KODE AMAN: setiap data yang berasal dari input pengguna
            // di-escape dulu sebelum dicetak ke HTML.
            // ENT_QUOTES -> mengubah kutip tunggal (') dan ganda (") juga.
            // 'UTF-8'    -> memastikan karakter non-ASCII tetap terbaca benar.
            $nama_aman  = htmlspecialchars($nama_user, ENT_QUOTES, 'UTF-8');
            $pesan_aman = htmlspecialchars($pesan_user, ENT_QUOTES, 'UTF-8');

            echo "<div class='komentar'><b>$nama_aman:</b> $pesan_aman</div>";
        }
    } else {
        echo "<p><i>Belum ada komentar.</i></p>";
    }
    ?>
</body>
</html> 