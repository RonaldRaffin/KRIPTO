<?php
// ============================================================
//  RSA ENGINE
// ============================================================

function generateKeys() {
    $config = [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA
    ];

    $res = openssl_pkey_new($config);

    openssl_pkey_export($res, $privateKey);
    $pub = openssl_pkey_get_details($res);
    $publicKey = $pub['key'];

    return [$publicKey, $privateKey];
}

function encryptRSA($text, $publicKey) {
    if (!openssl_public_encrypt($text, $encrypted, $publicKey)) {
        return "❌ Gagal enkripsi (public key tidak valid)";
    }
    return base64_encode($encrypted);
}

function decryptRSA($cipher, $privateKey) {
    $decoded = base64_decode($cipher);

    if (!openssl_private_decrypt($decoded, $decrypted, $privateKey)) {
        return "❌ Gagal decrypt (pastikan PRIVATE KEY benar)";
    }

    return $decrypted;
}

// ============================================================
//  INIT
// ============================================================

$hasil = "";
$autoPublic = "";
$autoPrivate = "";

// GENERATE KEY
if (isset($_POST['generate'])) {
    list($autoPublic, $autoPrivate) = generateKeys();
}

// PROSES ENKRIPSI / DEKRIPSI
if (isset($_POST['proses'])) {

    $pesan = $_POST['pesan'] ?? '';
    $key   = $_POST['key'] ?? '';
    $aksi  = $_POST['aksi'] ?? '';

    if ($aksi == "encrypt") {

        if (empty($key)) {
            $hasil = "❌ Public key tidak boleh kosong";
        } else {
            $hasil = encryptRSA($pesan, $key);
        }

    } elseif ($aksi == "decrypt") {

        if (empty($key)) {
            $hasil = "❌ Private key tidak boleh kosong";
        } else {
            $hasil = decryptRSA($pesan, $key);
        }

    } else {
        $hasil = "❌ Aksi tidak valid";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Aplikasi Enkripsi & Dekripsi RSA</title>
    <style>
        body { font-family: Arial; background:#f5f5f5; }
        .box {
            width: 650px;
            margin: 40px auto;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        input, textarea, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 10px;
        }
        button {
            padding: 10px;
            margin-top: 5px;
        }
        textarea { height: 120px; }
    </style>
</head>
<body>

<div class="box">
    <h2>Aplikasi Enkripsi & Dekripsi RSA</h2>

    <!-- GENERATE KEY -->
    <form method="post">
        <button name="generate">Generate Key (Alice)</button>
    </form>

    <?php if (!empty($autoPublic)): ?>
        <b>Public Key (Alice):</b>
        <textarea readonly><?= htmlspecialchars($autoPublic) ?></textarea>

        <b>Private Key (Alice):</b>
        <textarea readonly><?= htmlspecialchars($autoPrivate) ?></textarea>
    <?php endif; ?>

    <hr>

    <!-- FORM UTAMA -->
    <form method="post">
        <label>Masukkan Pesan (Plain / Cipher):</label>
        <textarea name="pesan" required><?= $_POST['pesan'] ?? '' ?></textarea>

        <label>Kata Kunci (Key):</label>
        <textarea name="key" required placeholder="Paste Public Key (encrypt) atau Private Key (decrypt)"><?= $_POST['key'] ?? '' ?></textarea>

        <label>Pilih Aksi:</label>
        <select name="aksi">
            <option value="encrypt" <?= (($_POST['aksi'] ?? '') == 'encrypt') ? 'selected' : '' ?>>Enkripsi (Plain -> Cipher)</option>
            <option value="decrypt" <?= (($_POST['aksi'] ?? '') == 'decrypt') ? 'selected' : '' ?>>Dekripsi (Cipher -> Plain)</option>
        </select>

        <button name="proses">Proses Kriptografi</button>
    </form>

    <hr>

    <h3>Hasil Proses:</h3>
    <textarea readonly><?= htmlspecialchars($hasil) ?></textarea>

</div>

</body>
</html>