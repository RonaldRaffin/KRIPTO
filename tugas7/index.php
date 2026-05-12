<?php
// Inisialisasi variabel default
$cert_out = null;
$pkey_out  = null;
$error     = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $opensslConfig = __DIR__ . '/openssl.cnf';

        if (!file_exists($opensslConfig)) {
            throw new Exception("File konfigurasi tidak ditemukan: $opensslConfig");
        }

        $configArgs = array("config" => $opensslConfig);

        // A. Menyusun Array DN
        $dn = array(
            "countryName"            => $_POST['country'] ?? 'ID',
            "stateOrProvinceName"    => $_POST['state']   ?? '',
            "localityName"           => $_POST['city']    ?? '',
            "organizationName"       => $_POST['org']     ?? '',
            "commonName"             => $_POST['domain']  ?? 'localhost',
        );

        // B. Membuat Private Key RSA 2048
        $privkey = openssl_pkey_new(array(
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ) + $configArgs);
        if (!$privkey) throw new Exception("Gagal membuat Private Key.");

        // C. Membuat CSR
        $csr = openssl_csr_new($dn, $privkey, $configArgs);
        if (!$csr) throw new Exception("Gagal membuat CSR.");

        // D. Menandatangani Sertifikat (365 Hari)
        $x509 = openssl_csr_sign($csr, null, $privkey, 365, $configArgs);
        if (!$x509) throw new Exception("Gagal menandatangani sertifikat.");

        // E. Ekspor ke variabel string
        openssl_x509_export($x509, $cert_out);
        openssl_pkey_export($privkey, $pkey_out, null, $configArgs);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSL Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50%       { transform: translateY(-6px); }
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-base:     #0d0f14;
            --bg-sidebar:  #111318;
            --bg-card:     #161a22;
            --border:      rgba(255,255,255,0.07);
            --accent:      #7c6dfa;
            --accent-soft: rgba(124,109,250,0.15);
            --text-main:   #e8eaf2;
            --text-muted:  #6b7280;
            --text-dim:    #9ca3af;
            --green:       #4fc48d;
            --red:         #f87171;
            --yellow:      #fbbf24;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg-base);
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 210px;
            min-height: 100vh;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 22px 20px;
            border-bottom: 1px solid var(--border);
        }

        .brand-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: float 3s ease-in-out infinite;
            flex-shrink: 0;
        }

        .brand-name {
            font-family: 'Space Mono', monospace;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-main);
        }

        .sidebar-section {
            padding: 18px 20px 8px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0 10px;
        }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-dim);
            font-size: 14px;
            font-weight: 500;
            transition: background 0.15s, color 0.15s;
        }

        .sidebar-menu li a:hover {
            background: var(--accent-soft);
            color: var(--text-main);
        }

        .sidebar-menu li.active a {
            background: var(--accent-soft);
            color: var(--accent);
        }

        /* ── MAIN ── */
        .main {
            flex: 1;
            padding: 36px 40px;
            overflow-y: auto;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 32px;
        }

        .page-title h1 {
            font-family: 'Space Mono', monospace;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.1;
        }

        .page-title h1 span { color: var(--accent); }

        .page-title p {
            color: var(--text-muted);
            font-size: 13px;
            margin-top: 5px;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--accent);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.1s;
        }

        .btn-primary:hover { opacity: 0.88; transform: translateY(-1px); }
        .btn-primary:active { transform: translateY(0); }

        /* ── STAT CARDS ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 22px 24px;
        }

        .stat-label {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: 'Space Mono', monospace;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
            line-height: 1;
        }

        .stat-value.green  { color: var(--green); }
        .stat-value.yellow { color: var(--yellow); }

        .stat-badge {
            display: inline-block;
            margin-top: 8px;
            background: rgba(79,196,141,0.12);
            color: var(--green);
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }

        .stat-badge.yellow { background: rgba(251,191,36,0.12); color: var(--yellow); }
        .stat-badge.purple { background: rgba(124,109,250,0.12); color: var(--accent); }

        /* ── SECTION BOX ── */
        .section-box {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-main);
        }

        .section-body { padding: 28px 24px; }

        /* ── FORM ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full { grid-column: 1 / -1; }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        .form-group input {
            background: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 9px;
            padding: 10px 14px;
            font-family: 'DM Sans', sans-serif;
            font-size: 14px;
            color: var(--text-main);
            outline: none;
            transition: border-color 0.15s;
            width: 100%;
        }

        .form-group input::placeholder { color: var(--text-muted); }

        .form-group input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(124,109,250,0.12);
        }

        .form-actions {
            margin-top: 22px;
            display: flex;
            justify-content: flex-end;
        }

        /* ── ALERT ERROR ── */
        .alert-error {
            background: rgba(248,113,113,0.08);
            border: 1px solid rgba(248,113,113,0.25);
            border-radius: 10px;
            padding: 14px 18px;
            color: var(--red);
            font-size: 14px;
            margin-bottom: 20px;
        }

        /* ── OUTPUT TABLE ── */
        .output-table {
            width: 100%;
            border-collapse: collapse;
        }

        .output-table thead tr {
            border-bottom: 1px solid var(--border);
        }

        .output-table thead th {
            padding: 12px 24px;
            text-align: left;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.3px;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        .output-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: background 0.12s;
        }

        .output-table tbody tr:last-child { border-bottom: none; }
        .output-table tbody tr:hover { background: rgba(255,255,255,0.025); }

        .output-table td {
            padding: 16px 24px;
            font-size: 13px;
            vertical-align: top;
        }

        .row-id {
            width: 32px;
            height: 32px;
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Space Mono', monospace;
            font-size: 12px;
            font-weight: 700;
        }

        .row-label {
            font-weight: 600;
            color: var(--text-main);
            font-size: 14px;
        }

        .row-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .row-val {
            font-family: 'Space Mono', monospace;
            font-size: 11px;
            color: var(--green);
            word-break: break-all;
            max-width: 500px;
            white-space: pre-wrap;
        }

        .action-cell {
            display: flex;
            gap: 8px;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-dim);
            padding: 6px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.12s, color 0.12s, border-color 0.12s;
            white-space: nowrap;
        }

        .copy-btn:hover {
            background: var(--accent-soft);
            color: var(--accent);
            border-color: var(--accent);
        }

        .download-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: transparent;
            border: 1px solid rgba(79,196,141,0.3);
            color: var(--green);
            padding: 6px 12px;
            border-radius: 7px;
            font-size: 12px;
            font-weight: 600;
            font-family: 'DM Sans', sans-serif;
            transition: background 0.12s;
            text-decoration: none;
            white-space: nowrap;
        }

        .download-btn:hover { background: rgba(79,196,141,0.1); }

        .tag {
            display: inline-block;
            background: rgba(124,109,250,0.12);
            color: var(--accent);
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
            padding: 2px 8px;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .tag.green { background: rgba(79,196,141,0.12); color: var(--green); }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">
            <svg width="38" height="42" viewBox="0 0 38 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                <!-- Shield shape -->
                <path d="M19 2L4 8V20C4 29.5 10.8 38.3 19 40C27.2 38.3 34 29.5 34 20V8L19 2Z"
                      stroke="#7c6dfa" stroke-width="2.2" stroke-linejoin="round" fill="rgba(124,109,250,0.08)"/>
                <!-- SSL text -->
                <text x="19" y="25" text-anchor="middle"
                      font-family="'Space Mono', monospace"
                      font-size="9.5" font-weight="700"
                      fill="#7c6dfa" letter-spacing="0.5">SSL</text>
            </svg>
        </div>
        <span class="brand-name">SSL Generator</span>
    </div>
    <div class="sidebar-section">Menu</div>
    <ul class="sidebar-menu">
        <li class="active">
            <a href="#">SSL Generator</a>
        </li>
    </ul>
</aside>

<!-- MAIN -->
<main class="main">

    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <h1>SSL <span>Generator</span></h1>
            <p>Buat sertifikat SSL self-signed untuk domain kamu</p>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Algoritma</div>
            <div class="stat-value">RSA</div>
            <span class="stat-badge purple">2048-bit</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Masa Berlaku</div>
            <div class="stat-value green">365</div>
            <span class="stat-badge">hari</span>
        </div>
        <div class="stat-card">
            <div class="stat-label">Status</div>
            <div class="stat-value yellow">Self-Signed</div>
            <span class="stat-badge yellow">lokal</span>
        </div>
    </div>

    <!-- Error Alert -->
    <?php if ($error): ?>
    <div class="alert-error">
        ⚠️ <strong>Error:</strong> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Form Section -->
    <div class="section-box" id="form-section">
        <div class="section-header">
            <span class="section-title">Informasi Sertifikat</span>
            <span class="tag">Form</span>
        </div>
        <div class="section-body">
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Negara (Country Code 2 Digit)</label>
                        <input type="text" name="country" value="<?= htmlspecialchars($_POST['country'] ?? 'ID') ?>" placeholder="ID" maxlength="2" required>
                    </div>
                    <div class="form-group">
                        <label>Provinsi</label>
                        <input type="text" name="state" value="<?= htmlspecialchars($_POST['state'] ?? '') ?>" placeholder="Kalimantan Barat" required>
                    </div>
                    <div class="form-group">
                        <label>Kota</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>" placeholder="Pontianak" required>
                    </div>
                    <div class="form-group">
                        <label>Organisasi</label>
                        <input type="text" name="org" value="<?= htmlspecialchars($_POST['org'] ?? '') ?>" placeholder="UM Pontianak" required>
                    </div>
                    <div class="form-group full">
                        <label>Common Name (Domain Utama)</label>
                        <input type="text" name="domain" value="<?= htmlspecialchars($_POST['domain'] ?? '') ?>" placeholder="webku.local" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Generate SSL</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Output Section -->
    <?php if ($cert_out): ?>
    <div class="section-box">
        <div class="section-header">
            <span class="section-title">Hasil Sertifikat</span>
            <span class="tag green">Berhasil</span>
        </div>
        <table class="output-table">
            <thead>
                <tr>
                    <th style="width:56px;">ID</th>
                    <th style="width:180px;">Tipe</th>
                    <th>Isi</th>
                    <th style="width:160px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><div class="row-id">1</div></td>
                    <td>
                        <div class="row-label">Server Certificate</div>
                        <div class="row-sub">.CRT file</div>
                    </td>
                    <td>
                        <div class="row-val" id="cert-text"><?= htmlspecialchars($cert_out) ?></div>
                    </td>
                    <td>
                        <div class="action-cell">
                            <button class="copy-btn" onclick="copyText('cert-text', this)">Salin</button>
                            <a class="download-btn" href="data:application/x-pem-file;charset=utf-8,<?= rawurlencode($cert_out) ?>" download="server.crt">⬇ .CRT</a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td><div class="row-id">2</div></td>
                    <td>
                        <div class="row-label">Private Key</div>
                        <div class="row-sub">.KEY file</div>
                    </td>
                    <td>
                        <div class="row-val" id="key-text"><?= htmlspecialchars($pkey_out) ?></div>
                    </td>
                    <td>
                        <div class="action-cell">
                            <button class="copy-btn" onclick="copyText('key-text', this)">Salin</button>
                            <a class="download-btn" href="data:application/x-pem-file;charset=utf-8,<?= rawurlencode($pkey_out) ?>" download="server.key">⬇ .KEY</a>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</main>

<script>
function copyText(elementId, btn) {
    const text = document.getElementById(elementId).textContent;
    navigator.clipboard.writeText(text).then(() => {
        const original = btn.innerHTML;
        btn.innerHTML = 'Disalin';
        btn.style.color = 'var(--green)';
        btn.style.borderColor = 'var(--green)';
        setTimeout(() => {
            btn.innerHTML = original;
            btn.style.color = '';
            btn.style.borderColor = '';
        }, 2000);
    });
}
</script>

</body>
</html>