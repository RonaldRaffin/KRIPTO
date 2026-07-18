<?php
session_start();
// --- KUNCI RAHASIA SERVER (JANGAN SAMPAI BOCOR) ---
$SECRET_KEY = 'Kunci_Super_Aman_Univ_Muh_PTK_2026';

// ==========================================================
// LANGKAH 1: Fungsi Helper Base64Url
// ==========================================================

// Fungsi 1: Base64Url Encode Khusus JWT
function base64url_encode($data) {
    $b64 = base64_encode($data);

    // Replace karakter '+', '/', dan hapus padding '='
    if ($b64 === false) return false;
    $url = strtr($b64, '+/', '-_');
    return rtrim($url, '=');
}

// Fungsi 2: Base64Url Decode Khusus JWT
function base64url_decode($data) {
    $b64 = strtr($data, '-_', '+/');

    // Kembalikan padding '=' yang hilang
    $b64_padded = str_pad($b64, strlen($b64) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($b64_padded);
}

// ==========================================================
// LANGKAH 2: Fungsi Generator Token
// ==========================================================

// Fungsi 3: Create JWT
function create_jwt($payload_data, $secret) {
    // 1. Header (Selalu sama: HS256)
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64UrlHeader = base64url_encode($header);

    // 2. Payload (Data Dinamis dari Parameter)
    $payload = json_encode($payload_data);
    $base64UrlPayload = base64url_encode($payload);

    // 3. Signature (HMAC SHA256)
    $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
    $base64UrlSignature = base64url_encode($signature);

    // Gabungkan ketiganya dengan titik
    return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
}

// ==========================================================
// LANGKAH 3: Fungsi Verifikasi Integritas & Tampering
// ==========================================================

// Fungsi 4: Verify JWT
function verify_jwt($jwt, $secret) {
    // Pecah token menjadi 3 bagian array
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) != 3) {
        return ['valid' => false, 'pesan' => 'Format Token Salah!'];
    }

    $header = $tokenParts[0];
    $payload = $tokenParts[1];
    $signature_provided = $tokenParts[2];

    // BUILD ULANG SIGNATURE di Server untuk pembuktian
    $signature_rebuilt = hash_hmac('sha256', $header . "." . $payload, $secret, true);
    $base64UrlSignature_rebuilt = base64url_encode($signature_rebuilt);

    // CEK TAMPERING (Peretasan/Pengubahan Payload)
    if (!hash_equals($base64UrlSignature_rebuilt, $signature_provided)) {
        return ['valid' => false, 'pesan' => 'Token Dimodifikasi / Palsu (Signature Invalid)!'];
    }

    // BACA ISI PAYLOAD UNTUK CEK KADALUWARSA (EXP)
    $payload_data = json_decode(base64url_decode($payload), true);
    $waktu_sekarang = time();

    if (isset($payload_data['exp']) && $waktu_sekarang > $payload_data['exp']) {
        return ['valid' => false, 'pesan' => 'Token Telah Kedaluwarsa (Expired)!'];
    }

    return ['valid' => true, 'pesan' => 'Token Sah!', 'data' => $payload_data];
}

// ==========================================================
// LANGKAH 4: Routing POST & Web Interface
// ==========================================================

// --- CONTROLLER FORM ---
$hasil_gen = ""; $hasil_ver = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $aksi = $_POST['aksi'];

    if ($aksi == 'generate') {
        // Susun data untuk Payload JSON
        $payload = [
            'iss' => 'web_universitas',
            'user_id' => $_POST['userid'],
            'role' => $_POST['role'],
            'exp' => time() + 3600 // Expired dalam 1 Jam (3600 detik)
        ];
        $hasil_gen = create_jwt($payload, $SECRET_KEY);

        // Simpan pilihan terakhir Box 1 ke session (biar tidak reset saat Box 2 disubmit)
        $_SESSION['last_userid'] = $_POST['userid'];
        $_SESSION['last_role'] = $_POST['role'];
        $_SESSION['last_token_gen'] = $hasil_gen;
    }
    else if ($aksi == 'verify') {
        $token_input = $_POST['token_jwt'];
        $cek = verify_jwt($token_input, $SECRET_KEY);

        if ($cek['valid']) {
            $hasil_ver = "<b style='color:#00ffa3;'>✅ ".$cek['pesan']."</b><br>Data: " . json_encode($cek['data']);
        } else {
            $hasil_ver = "<b style='color:#ff3d68;'>❌ ".$cek['pesan']."</b>";
        }

        // Simpan token yang baru dicek ke session (biar tidak reset saat Box 1 disubmit)
        $_SESSION['last_token_verify'] = $token_input;
    }
}

// Nilai yang dipakai untuk mengisi ulang form, prioritas: POST saat ini > session > default
$userid_tampil = $_POST['userid'] ?? $_SESSION['last_userid'] ?? 'U001';
$role_tampil = $_POST['role'] ?? $_SESSION['last_role'] ?? 'user';
$token_gen_tampil = $hasil_gen ?: ($_SESSION['last_token_gen'] ?? '');
$token_verify_tampil = $_POST['token_jwt'] ?? $_SESSION['last_token_verify'] ?? '';
?>
<!-- HTML UI -->
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>JWT Tool</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#fafaf9;
    --ink:#16181d;
    --muted:#6b7078;
    --line:#e4e5e7;
    --accent:#2b59ff;
    --ok:#15803d;
    --danger:#c0342a;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    background:var(--bg);
    color:var(--ink);
    font-family:'IBM Plex Sans', sans-serif;
    padding:64px 24px 80px;
  }
  .wrap{max-width:560px; margin:0 auto;}

  h1{
    font-family:'IBM Plex Mono', monospace;
    font-weight:600;
    font-size:22px;
    letter-spacing:-0.01em;
    margin:0 0 6px;
  }
  .sub{color:var(--muted); font-size:14px; line-height:1.6; margin:0 0 48px; max-width:44ch;}

  .step{
    display:flex; gap:20px;
    padding:32px 0;
    border-top:1px solid var(--line);
  }
  .step:last-of-type{border-bottom:1px solid var(--line);}
  .step-index{
    font-family:'IBM Plex Mono', monospace;
    font-size:13px; color:var(--muted);
    padding-top:2px;
    flex-shrink:0; width:20px;
  }
  .step-body{flex:1;}
  .step h2{
    font-size:15px; font-weight:600;
    margin:0 0 4px;
  }
  .step p.desc{
    color:var(--muted); font-size:13px; line-height:1.5;
    margin:0 0 20px;
  }

  label{
    display:block; font-size:12px; color:var(--muted);
    margin-bottom:6px;
  }
  .field-row{display:flex; gap:12px; margin-bottom:16px;}
  .field-row > div{flex:1;}

  input[type=text], textarea, select{
    width:100%;
    background:#fff;
    border:1px solid var(--line);
    color:var(--ink);
    font-family:'IBM Plex Mono', monospace;
    font-size:13px;
    padding:10px 12px;
    border-radius:6px;
    resize:vertical;
    transition:border-color .15s;
  }
  select{
    appearance:none;
    -webkit-appearance:none;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7078' stroke-width='1.5' fill='none' fill-rule='evenodd'/%3E%3C/svg%3E");
    background-repeat:no-repeat;
    background-position:right 12px center;
    padding-right:32px;
    cursor:pointer;
  }
  input[type=text]:focus, textarea:focus, select:focus{
    outline:none; border-color:var(--accent);
  }
  textarea[readonly]{
    color:var(--muted);
    background:#f5f5f4;
  }

  button{
    font-family:'IBM Plex Sans', sans-serif;
    font-weight:600; font-size:13px;
    background:var(--ink);
    color:#fff;
    border:none; border-radius:6px;
    padding:10px 18px;
    cursor:pointer;
    transition:opacity .15s;
  }
  button:hover{opacity:.85;}

  .verify-btn{background:var(--accent);}

  .copy-btn{
    font-family:'IBM Plex Mono', monospace;
    font-weight:500; font-size:11px;
    background:#fff; color:var(--muted);
    border:1px solid var(--line); border-radius:5px;
    padding:4px 10px;
    cursor:pointer;
  }
  .copy-btn:hover{border-color:var(--accent); color:var(--accent);}
  .copy-btn.copied{border-color:var(--ok); color:var(--ok);}

  .out-label{margin-top:16px;}

  .result{
    margin-top:16px; padding:12px 14px;
    border-radius:6px;
    font-family:'IBM Plex Mono', monospace;
    font-size:13px; line-height:1.6;
    background:#fff;
    border:1px solid var(--line);
    word-break:break-word;
  }
  .result b{font-family:'IBM Plex Sans', sans-serif;}

  footer{
    color:var(--muted); font-size:12px;
    margin-top:48px;
  }
</style>
</head>
<body>
<div class="wrap">

    <h1>JWT Tool</h1>
    <p class="sub">Generate token dengan HMAC-SHA256, lalu verifikasi keasliannya di sini.</p>

    <div class="step">
        <div class="step-index">01</div>
        <div class="step-body">
            <h2>Buat token</h2>
            <p class="desc">Isi identitas pengguna, server menyusun header, payload, dan signature.</p>
            <form method="POST">
                <input type="hidden" name="aksi" value="generate">
                <div class="field-row">
                    <div>
                        <label>User ID</label>
                        <input type="text" name="userid" value="<?= htmlspecialchars($userid_tampil) ?>">
                    </div>
                    <div>
                        <label>Role</label>
                        <select name="role">
                            <?php
                            $roles = ['user', 'admin', 'moderator'];
                            $role_terpilih = $role_tampil;
                            foreach ($roles as $r) {
                                $selected = ($r === $role_terpilih) ? 'selected' : '';
                                echo "<option value=\"$r\" $selected>$r</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <button type="submit">Generate JWT</button>
            </form>
            <div class="out-label" style="display:flex; align-items:center; justify-content:space-between;">
                <label style="margin:0;">Token</label>
                <button type="button" class="copy-btn" data-target="token-output" onclick="copyText(this)">Copy</button>
            </div>
            <textarea id="token-output" rows="3" readonly><?= htmlspecialchars($token_gen_tampil) ?></textarea>
        </div>
    </div>

    <div class="step">
        <div class="step-index">02</div>
        <div class="step-body">
            <h2>Verifikasi token</h2>
            <p class="desc">Server menghitung ulang signature untuk memastikan token belum diubah.</p>
            <form method="POST">
                <input type="hidden" name="aksi" value="verify">
                <label>Token JWT</label>
                <textarea name="token_jwt" rows="3" required placeholder="xxxxx.yyyyy.zzzzz" style="margin-bottom:16px;"><?= htmlspecialchars($token_verify_tampil) ?></textarea>
                <button type="submit" class="verify-btn">Cek Validitas</button>
            </form>
            <?php if ($hasil_ver): ?>
            <div class="result"><?= $hasil_ver ?></div>
            <?php endif; ?>
        </div>
    </div>

    <footer>HMAC-SHA256 &middot; Univ. Muhammadiyah Pontianak</footer>
</div>
<script>
function copyText(btn) {
    var el = document.getElementById(btn.dataset.target);
    if (!el.value) return;
    navigator.clipboard.writeText(el.value).then(function () {
        var original = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('copied');
        setTimeout(function () {
            btn.textContent = original;
            btn.classList.remove('copied');
        }, 1500);
    });
}
</script>
</body>
</html>