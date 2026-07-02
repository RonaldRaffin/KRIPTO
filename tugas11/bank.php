<?php
// 1. Aktifkan Engine Sesi (WAJIB di baris paling atas)
session_start();

// 2. Simulasi Login: Jika belum ada data user di sesi, buat baru
if (!isset($_SESSION['user'])) {
    $_SESSION['user']  = 'Bapak Budi';
    $_SESSION['saldo'] = 50000000; // Saldo awal Rp 50 Juta
}

// ✅ PATCH 1: Generate Token Acak (setelah session_start)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$notifikasi = "";

// ✅ PATCH 2: Verifikasi token sebelum memproses transfer
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $token_form = $_POST['csrf_token'] ?? '';

    if (hash_equals($_SESSION['csrf_token'], $token_form)) {

        // Token Valid! Proses transfer dilanjutkan...
        $tujuan = $_POST['rekening_tujuan'] ?? '';
        $jumlah = (int)($_POST['jumlah'] ?? 0);

        if ($jumlah > 0 && $_SESSION['saldo'] >= $jumlah) {
            $_SESSION['saldo'] -= $jumlah;
            $notifikasi = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:5px;'>
                ✅ Sukses Transfer Rp " . number_format($jumlah) . " ke Rekening $tujuan!</div>";
        }

    } else {
        // Token Tidak Valid → Serangan CSRF Terdeteksi!
        die("<h2 style='color:red; text-align:center; margin-top:50px;'>
            ⛔ ERROR: SERANGAN CSRF TERDETEKSI! Akses Ditolak.</h2>");
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head><title>Bank Nasional - eBanking</title>
<style>
body { font-family: Arial; padding: 20px; background: #f4f4f5; }
.kartu { background: white; padding: 25px; border-radius: 12px;
         max-width: 450px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: auto; }
input, button { width: 95%; padding: 10px; margin-top: 5px; margin-bottom: 15px; }
button { background: #2563eb; color: white; border: none;
         border-radius: 6px; cursor: pointer; font-weight: bold; }
</style></head>
<body>
<div class="kartu">
    <h2>Selamat Datang, <?php echo $_SESSION['user']; ?></h2>
    <h3 style="color:#2563eb;">
        Saldo: Rp <?php echo number_format($_SESSION['saldo']); ?>
    </h3>
    <?php echo $notifikasi; ?>
    <hr style="margin:20px 0;">
    <h4>Form Transfer Cepat:</h4>
    <form method="POST" action="bank.php">
        <!-- ✅ PATCH 3: Sisipkan token rahasia ke dalam form -->
        <input type="hidden" name="csrf_token"
               value="<?php echo $_SESSION['csrf_token']; ?>">

        <label>No. Rekening Tujuan:</label><br>
        <input type="text" name="rekening_tujuan" required><br>
        <label>Jumlah Transfer (Rp):</label><br>
        <input type="number" name="jumlah" required><br>
        <button type="submit">KIRIM DANA SEKARANG</button>
    </form>
</div></body></html>