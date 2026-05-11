<?php
// Sembunyikan warning PHP agar layout tidak rusak
error_reporting(E_ALL);
ini_set('display_errors', 0);

function generate_rsa_keys() {
    $opensslConfig = 'D:/laragon/bin/php/php-8.2.30-Win32-vs16-x64/extras/ssl/openssl.cnf';

    $config = [
        "digest_alg"       => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
        "config"           => $opensslConfig,
    ];

    $res = openssl_pkey_new($config);

    if (!$res) {
        $errors = [];
        while ($msg = openssl_error_string()) {
            $errors[] = $msg;
        }
        return ['error' => 'Gagal generate: ' . implode(', ', $errors)];
    }

    // Export private key
    if (!openssl_pkey_export($res, $private_key, null, $config)) {
        return ['error' => 'Gagal export private key.'];
    }

    // Ambil public key
    $key_details = openssl_pkey_get_details($res);

    if (!$key_details || !isset($key_details['key'])) {
        return ['error' => 'Gagal mengambil public key.'];
    }

    return [
        'public'  => $key_details['key'],
        'private' => $private_key
    ];
}

function sign_document($data, $private_key_pem) {
    // Bersihkan spasi di awal/akhir
    $private_key_pem = trim($private_key_pem);

    // Konversi PEM string menjadi OpenSSL key object
    $private_key = openssl_pkey_get_private($private_key_pem);

    // Jika private key tidak valid
    if (!$private_key) {
        return false;
    }

    $signature = '';

    // Proses signing
    $success = openssl_sign(
        $data,
        $signature,
        $private_key,
        OPENSSL_ALGO_SHA256
    );

    // Bebaskan resource
    if (function_exists('openssl_free_key')) {
        openssl_free_key($private_key);
    }

    // Return signature dalam Base64
    return $success ? base64_encode($signature) : false;
}

function verify_document($dokumen, $signature_b64, $public_key_string) {
    // Bersihkan spasi
    $public_key_string = trim($public_key_string);
    $signature_b64 = trim($signature_b64);

    // Ambil public key
    $public_key = openssl_pkey_get_public($public_key_string);

    if (!$public_key) {
        return "ERROR: Public Key tidak valid.";
    }

    // Decode signature Base64
    $signature_biner = base64_decode($signature_b64, true);

    if ($signature_biner === false) {
        return "ERROR: Format Signature bukan Base64.";
    }

    // Verifikasi signature
    $status = openssl_verify(
        $dokumen,
        $signature_biner,
        $public_key,
        OPENSSL_ALGO_SHA256
    );

    // Bebaskan resource
    if (function_exists('openssl_free_key')) {
        openssl_free_key($public_key);
    }

    if ($status === 1) {
        return "VERIFIED";
    } elseif ($status === 0) {
        return "INVALID";
    } else {
        return "ERROR: Terjadi kesalahan saat verifikasi.";
    }
}

$hasil = "";
$hasil_type = "";
$aksi = $_POST['aksi'] ?? 'generate';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $dok   = $_POST['dokumen']   ?? '';
    $sign  = $_POST['signature'] ?? '';
    $kunci = $_POST['kunci']     ?? '';

    switch ($aksi) {
        case 'generate':
            // Generate boleh langsung diproses
            $keys = generate_rsa_keys();

            if (isset($keys['error'])) {
                $hasil = $keys['error'];
                $hasil_type = "error";
            } else {
                $hasil =
                    "--- PRIVATE KEY ---\n" .
                    $keys['private'] .
                    "\n--- PUBLIC KEY ---\n" .
                    $keys['public'];
                $hasil_type = "generate";
            }
            break;

        case 'sign':
            // Jika menu baru dibuka tanpa input, jangan proses
            if (trim($dok) === '' || trim($kunci) === '') {
                break;
            }

            $r = sign_document($dok, $kunci);

            if ($r !== false) {
                $hasil = $r;
                $hasil_type = "sign";
            } else {
                $hasil = "Gagal signing. Periksa Private Key.";
                $hasil_type = "error";
            }
            break;

        case 'verify':
            // Jika menu baru dibuka tanpa input, jangan proses
            if (
                trim($dok) === '' ||
                trim($sign) === '' ||
                trim($kunci) === ''
            ) {
                break;
            }

            $hasil = verify_document($dok, $sign, $kunci);

            if ($hasil === "VERIFIED") {
                $hasil_type = "verified";
            } elseif ($hasil === "INVALID") {
                $hasil_type = "invalid";
            } else {
                $hasil_type = "error";
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SigSystem — Digital Signature</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700&family=DM+Sans:wght@300;400;500&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #0a0a10;
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            display: flex;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 220px;
            background: #11111b;
            border-right: 1px solid #1e1e2e;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
        }

        .sb-logo {
            display: flex;
            align-items: center;
            gap: 11px;
            padding: 22px 18px;
            border-bottom: 1px solid #1e1e2e;
        }

        .sb-logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #7c6cf0, #a78bf5);
            border-radius: 9px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
        }

        .sb-logo-icon svg {
            width: 20px;
            height: 20px;
        }

        @keyframes float {
            0%,100% { transform: translateY(0px); box-shadow: 0 4px 14px rgba(124,108,240,0.3); }
            50%      { transform: translateY(-6px); box-shadow: 0 14px 28px rgba(124,108,240,0.5); }
        }

        .sb-logo-name {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: #e8e8f0;
        }

        .sb-section { padding: 18px 0 8px; }

        .sb-label {
            font-size: 0.6rem;
            font-weight: 500;
            letter-spacing: 0.15em;
            color: #363660;
            text-transform: uppercase;
            padding: 0 18px 8px;
            display: block;
        }

        .sb-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 18px;
            cursor: pointer;
            color: #525280;
            font-size: 0.84rem;
            font-weight: 400;
            position: relative;
            transition: all 0.15s;
            border-left: 3px solid transparent;
            text-decoration: none;
        }

        .sb-item:hover {
            background: #17172a;
            color: #9090c0;
            border-left-color: #2e2e50;
        }

        .sb-item.active {
            background: #191930;
            color: #a78bf5;
            border-left-color: #7c6cf0;
        }

        /* ── MAIN ── */
        .main {
            margin-left: 220px;
            flex: 1;
            min-height: 100vh;
            background: #0d0d14;
            display: flex;
            flex-direction: column;
        }

        .main-hdr {
            padding: 28px 36px 20px;
            border-bottom: 1px solid #1a1a2e;
        }

        .main-hdr h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            color: #e0e0f0;
            letter-spacing: -0.01em;
        }

        .main-hdr h1 span { color: #a78bf5; }

        .main-hdr p {
            font-size: 0.78rem;
            color: #404060;
            margin-top: 4px;
        }

        /* ── STAT CARDS ── */
        .stat-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            padding: 20px 36px;
        }

        .stat {
            background: #11111b;
            border: 1px solid #1e1e2e;
            border-radius: 9px;
            padding: 16px;
        }

        .stat-lbl {
            font-size: 0.62rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #363660;
            margin-bottom: 7px;
        }

        .stat-val {
            font-family: 'Syne', sans-serif;
            font-size: 1.05rem;
            font-weight: 600;
            color: #c8c8e8;
        }

        .stat-tag {
            display: inline-block;
            margin-top: 6px;
            font-size: 0.62rem;
            background: #181830;
            color: #6060a0;
            padding: 2px 9px;
            border-radius: 20px;
            border: 1px solid #242444;
        }

        /* ── PANEL ── */
        .content { padding: 0 36px 36px; }

        .panel {
            background: #11111b;
            border: 1px solid #1e1e2e;
            border-radius: 11px;
            overflow: hidden;
        }

        .pnl-hdr {
            padding: 15px 20px;
            border-bottom: 1px solid #1a1a2e;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .pnl-title {
            font-family: 'Syne', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            color: #b0b0d8;
        }

        .pnl-pills { display: flex; gap: 6px; }

        .pill {
            font-size: 0.62rem;
            padding: 3px 11px;
            border-radius: 20px;
            background: #181830;
            color: #6060a0;
            border: 1px solid #242444;
            letter-spacing: 0.08em;
        }

        /* ── FORM ── */
        .form-body { padding: 20px; }

        .field { margin-bottom: 16px; }

        .field-lbl {
            display: block;
            font-size: 0.62rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #404060;
            margin-bottom: 8px;
        }

        textarea {
            width: 100%;
            padding: 12px 14px;
            background: #0a0a12;
            border: 1px solid #1e1e2e;
            border-radius: 8px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.78rem;
            color: #b0b0d0;
            resize: vertical;
            outline: none;
            line-height: 1.65;
            transition: border-color 0.2s, background 0.2s;
        }

        textarea:focus {
            border-color: #4a3a7a;
            background: #0d0d18;
        }

        textarea::placeholder {
            color: #282848;
            font-family: 'DM Sans', sans-serif;
            font-style: italic;
            font-size: 0.8rem;
        }

        /* ── BUTTONS ── */
        .btn-row {
            display: flex;
            justify-content: flex-end;
            padding: 0 20px 20px;
        }

        .gen-hint {
            margin: 20px;
            background: #0a0a12;
            border: 1.5px dashed #1e1e2e;
            border-radius: 11px;
            padding: 32px 20px;
            text-align: center;
        }

        .gen-hint-title {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            color: #484868;
            margin-bottom: 7px;
        }

        .gen-hint-sub {
            font-size: 0.78rem;
            color: #282840;
            margin-bottom: 22px;
        }

        .gen-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #7c6cf0;
            color: #fff;
            border: none;
            padding: 12px 26px;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            letter-spacing: 0.02em;
        }

        .gen-btn:hover { background: #9b8ef5; transform: translateY(-1px); }
        .gen-btn:active { transform: scale(0.98); }

        .act-btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: #7c6cf0;
            color: #fff;
            border: none;
            padding: 11px 24px;
            border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .act-btn:hover { background: #9b8ef5; }
        .act-btn:active { transform: scale(0.98); }

        /* ── RESULTS ── */
        .result-wrap { margin: 0 20px 20px; }

        .result-verified {
            background: #071410;
            border: 1px solid #1a4030;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
        }

        .rv-icon {
            width: 48px; height: 48px;
            background: #0f3020;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4ade80;
        }

        .result-verified h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            color: #4ade80;
            margin-bottom: 4px;
        }

        .result-verified p { font-size: 0.75rem; color: #1d6040; }

        .result-invalid {
            background: #140707;
            border: 1px solid #401a1a;
            border-radius: 10px;
            padding: 24px;
            text-align: center;
        }

        .ri-icon {
            width: 48px; height: 48px;
            background: #300f0f;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 12px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #f87171;
        }

        .result-invalid h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1rem;
            color: #f87171;
            margin-bottom: 4px;
        }

        .result-invalid p { font-size: 0.75rem; color: #602020; }

        .result-output {
            background: #080810;
            border: 1px solid #1a1a2e;
            border-radius: 10px;
            overflow: hidden;
        }

        .result-output-hdr {
            background: #0f0f1e;
            padding: 10px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #1a1a2e;
        }

        .result-output-hdr span {
            font-size: 0.62rem;
            font-weight: 500;
            letter-spacing: 0.12em;
            color: #363660;
            text-transform: uppercase;
        }

        .ro-dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: #7c6cf0;
        }

        .result-output pre {
            padding: 14px 18px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.74rem;
            line-height: 1.8;
            color: #6868a0;
            white-space: pre-wrap;
            word-break: break-all;
            max-height: 300px;
            overflow-y: auto;
        }

        .result-error {
            background: #150e00;
            border: 1px solid #3a2800;
            border-radius: 10px;
            padding: 16px 20px;
            font-size: 0.8rem;
            color: #c08040;
            line-height: 1.6;
        }

        /* ── PAGE VISIBILITY ── */
        .page { display: none; }
        .page.active { display: block; }

        /* ── SCROLLBAR ── */
        ::-webkit-scrollbar { width: 4px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #2e2e50; border-radius: 2px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sb-logo">
        <div class="sb-logo-icon">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
                      fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                <path d="M9 12L11 14L15 10" stroke="white" stroke-width="1.8"
                      stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <span class="sb-logo-name">SigSystem</span>
    </div>

    <div class="sb-section">
        <span class="sb-label">Menu</span>
        <form method="POST" style="display:contents">
            <button type="submit" name="aksi" value="generate"
                class="sb-item <?= $aksi === 'generate' ? 'active' : '' ?>"
                style="background:none;border:none;width:100%;text-align:left;font-family:'DM Sans',sans-serif;cursor:pointer">
                Generate Key
            </button>
            <button type="submit" name="aksi" value="sign"
                class="sb-item <?= $aksi === 'sign' ? 'active' : '' ?>"
                style="background:none;border:none;width:100%;text-align:left;font-family:'DM Sans',sans-serif;cursor:pointer">
                Sign Dokumen
            </button>
            <button type="submit" name="aksi" value="verify"
                class="sb-item <?= $aksi === 'verify' ? 'active' : '' ?>"
                style="background:none;border:none;width:100%;text-align:left;font-family:'DM Sans',sans-serif;cursor:pointer">
                Verifikasi
            </button>
        </form>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <?php if ($aksi === 'generate'): ?>
    <!-- PAGE: Generate Key -->
    <div class="main-hdr">
        <h1>Generate <span>RSA Key</span></h1>
        <p>Buat pasangan Private Key &amp; Public Key baru</p>
    </div>
    <div class="stat-row">
        <div class="stat">
            <div class="stat-lbl">Algoritma</div>
            <div class="stat-val">RSA</div>
            <div class="stat-tag">2048-bit</div>
        </div>
        <div class="stat">
            <div class="stat-lbl">Hash</div>
            <div class="stat-val">SHA-256</div>
            <div class="stat-tag">digest</div>
        </div>
        <div class="stat">
            <div class="stat-lbl">Format</div>
            <div class="stat-val">PEM</div>
            <div class="stat-tag">openssl</div>
        </div>
    </div>
    <div class="content">
        <div class="panel">
            <div class="pnl-hdr">
                <span class="pnl-title">RSA Keypair Generator</span>
                <div class="pnl-pills">
                    <span class="pill">RSA</span>
                    <span class="pill">SHA-256</span>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="generate">
                <div class="gen-hint">
                    <div class="gen-hint-title">Siap Generate RSA Keypair</div>
                    <div class="gen-hint-sub">Klik tombol di bawah untuk membuat pasangan kunci kriptografi baru</div>
                    <button type="submit" class="gen-btn">Generate Keypair</button>
                </div>
            </form>
            <?php if ($hasil): ?>
            <div class="result-wrap">
                <?php if ($hasil_type === 'error'): ?>
                    <div class="result-error"><strong>Error:</strong> <?= htmlspecialchars($hasil) ?></div>
                <?php else: ?>
                    <div class="result-output">
                        <div class="result-output-hdr">
                            <span>RSA Keypair Generated</span>
                            <div class="ro-dot"></div>
                        </div>
                        <pre><?= htmlspecialchars($hasil) ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($aksi === 'sign'): ?>
    <!-- PAGE: Sign Dokumen -->
    <div class="main-hdr">
        <h1>Sign <span>Dokumen</span></h1>
        <p>Tanda tangani dokumen menggunakan Private Key</p>
    </div>
    <div class="content" style="padding-top:20px">
        <div class="panel">
            <div class="pnl-hdr">
                <span class="pnl-title">Form Penandatanganan</span>
                <div class="pnl-pills">
                    <span class="pill">PKCS#1</span>
                    <span class="pill">SHA-256</span>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="sign">
                <div class="form-body">
                    <div class="field">
                        <label class="field-lbl">Isi Dokumen</label>
                        <textarea name="dokumen" rows="4"
                            placeholder="Masukkan teks dokumen yang akan ditandatangani..."><?= htmlspecialchars($_POST['dokumen'] ?? '') ?></textarea>
                    </div>
                    <div class="field">
                        <label class="field-lbl">Private Key (PEM)</label>
                        <textarea name="kunci" rows="5"
                            placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;Paste private key PEM di sini...&#10;-----END RSA PRIVATE KEY-----"><?= htmlspecialchars($_POST['kunci'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="submit" class="act-btn">Tanda Tangani</button>
                </div>
            </form>
            <?php if ($hasil): ?>
            <div class="result-wrap">
                <?php if ($hasil_type === 'error'): ?>
                    <div class="result-error"><strong>Error:</strong> <?= htmlspecialchars($hasil) ?></div>
                <?php else: ?>
                    <div class="result-output">
                        <div class="result-output-hdr">
                            <span>Signature Base64</span>
                            <div class="ro-dot"></div>
                        </div>
                        <pre><?= htmlspecialchars($hasil) ?></pre>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php elseif ($aksi === 'verify'): ?>
    <!-- PAGE: Verifikasi -->
    <div class="main-hdr">
        <h1>Verifikasi <span>Dokumen</span></h1>
        <p>Periksa keaslian dokumen menggunakan Public Key</p>
    </div>
    <div class="content" style="padding-top:20px">
        <div class="panel">
            <div class="pnl-hdr">
                <span class="pnl-title">Form Verifikasi</span>
                <div class="pnl-pills">
                    <span class="pill">openssl_verify</span>
                </div>
            </div>
            <form method="POST">
                <input type="hidden" name="aksi" value="verify">
                <div class="form-body">
                    <div class="field">
                        <label class="field-lbl">Isi Dokumen</label>
                        <textarea name="dokumen" rows="3"
                            placeholder="Masukkan teks dokumen yang akan diverifikasi..."><?= htmlspecialchars($_POST['dokumen'] ?? '') ?></textarea>
                    </div>
                    <div class="field">
                        <label class="field-lbl">Signature (Base64)</label>
                        <textarea name="signature" rows="3"
                            placeholder="Paste signature Base64 di sini..."><?= htmlspecialchars($_POST['signature'] ?? '') ?></textarea>
                    </div>
                    <div class="field">
                        <label class="field-lbl">Public Key (PEM)</label>
                        <textarea name="kunci" rows="5"
                            placeholder="-----BEGIN PUBLIC KEY-----&#10;Paste public key PEM di sini...&#10;-----END PUBLIC KEY-----"><?= htmlspecialchars($_POST['kunci'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="btn-row">
                    <button type="submit" class="act-btn">Verifikasi Dokumen</button>
                </div>
            </form>
            <?php if ($hasil): ?>
            <div class="result-wrap">
                <?php if ($hasil_type === 'verified'): ?>
                    <div class="result-verified">
                        <div class="rv-icon">OK</div>
                        <h3>Dokumen Terverifikasi</h3>
                        <p>Tanda tangan digital sah. Dokumen tidak mengalami modifikasi.</p>
                    </div>
                <?php elseif ($hasil_type === 'invalid'): ?>
                    <div class="result-invalid">
                        <div class="ri-icon">X</div>
                        <h3>Dokumen Tidak Sah</h3>
                        <p>Tanda tangan tidak cocok. Dokumen telah dimodifikasi atau dipalsukan.</p>
                    </div>
                <?php else: ?>
                    <div class="result-error"><strong>Error:</strong> <?= htmlspecialchars($hasil) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
</body>
</html>