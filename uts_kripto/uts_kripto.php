<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);

function caesar_cipher(string $text, int $key, bool $is_encrypt): string {
    $key = (($key % 26) + 26) % 26;
    if (!$is_encrypt) $key = 26 - $key;
    $result = '';
    for ($i = 0; $i < strlen($text); $i++) {
        $c = $text[$i];
        if (ctype_alpha($c)) {
            $base   = ctype_upper($c) ? ord('A') : ord('a');
            $result .= chr($base + (ord($c) - $base + $key) % 26);
        } else {
            $result .= $c;
        }
    }
    return $result;
}

function vigenere_cipher(string $text, string $key, bool $is_encrypt): string {
    $key  = strtoupper(preg_replace('/[^a-zA-Z]/', '', $key));
    if (strlen($key) === 0) return $text;
    $result = '';
    $ki     = 0;
    $kLen   = strlen($key);
    for ($i = 0; $i < strlen($text); $i++) {
        $c = $text[$i];
        if (ctype_alpha($c)) {
            $base  = ctype_upper($c) ? ord('A') : ord('a');
            $shift = ord($key[$ki % $kLen]) - ord('A');
            if (!$is_encrypt) $shift = (26 - $shift) % 26;
            $result .= chr($base + (ord($c) - $base + $shift) % 26);
            $ki++;
        } else {
            $result .= $c;
        }
    }
    return $result;
}

function hitungFPB($a, $b, &$langkah) {
    $langkah = [];
    while ($b != 0) {
        $sisa      = $a % $b;
        $langkah[] = "$a mod $b = $sisa";
        $temp = $b;
        $b    = $sisa;
        $a    = $temp;
    }
    return $a;
}

function generateKeys() {
    $config = [
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    $res = openssl_pkey_new($config);
    if (!$res) return ['error' => 'OpenSSL gagal generate key.'];
    openssl_pkey_export($res, $privateKey);
    $pub       = openssl_pkey_get_details($res);
    $publicKey = $pub['key'];
    return [$publicKey, $privateKey];
}

function encryptRSA($text, $publicKey) {
    if (!openssl_public_encrypt($text, $encrypted, $publicKey))
        return null;
    return base64_encode($encrypted);
}

function decryptRSA($cipher, $privateKey) {
    $decoded = base64_decode($cipher);
    if (!openssl_private_decrypt($decoded, $decrypted, $privateKey))
        return null;
    return $decrypted;
}

function sign_document($data, $private_key_pem) {
    $pk = openssl_pkey_get_private(trim($private_key_pem));
    if (!$pk) return false;
    openssl_sign($data, $signature, $pk, OPENSSL_ALGO_SHA256);
    return base64_encode($signature);
}

function verify_document($dokumen, $signature_b64, $public_key_string) {
    $pub    = openssl_pkey_get_public(trim($public_key_string));
    if (!$pub) return 'ERROR: Public Key tidak valid.';
    $sigBin = base64_decode(trim($signature_b64), true);
    if ($sigBin === false) return 'ERROR: Format Signature bukan Base64.';
    $status = openssl_verify($dokumen, $sigBin, $pub, OPENSSL_ALGO_SHA256);
    if ($status === 1)  return 'VERIFIED';
    if ($status === 0)  return 'INVALID';
    return 'ERROR: Kesalahan saat verifikasi.';
}

function generateSSL($dn, $days = 365) {
    $configFile = __DIR__ . '/openssl.cnf';
    $configArgs = file_exists($configFile) ? ['config' => $configFile] : [];

    $privkey = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ] + $configArgs);
    if (!$privkey) return ['error' => 'Gagal membuat Private Key.'];

    $csr  = openssl_csr_new($dn, $privkey, $configArgs);
    if (!$csr) return ['error' => 'Gagal membuat CSR.'];

    $x509 = openssl_csr_sign($csr, null, $privkey, $days, $configArgs);
    if (!$x509) return ['error' => 'Gagal menandatangani sertifikat.'];

    openssl_x509_export($x509, $cert_out);
    openssl_pkey_export($privkey, $pkey_out, null, $configArgs);
    return ['cert' => $cert_out, 'key' => $pkey_out];
}

$tool   = $_POST['tool']   ?? $_GET['tool']   ?? 'caesar';
$action = $_POST['action'] ?? '';
$result = null;
$error  = null;

switch ($action) {

    case 'caesar_encrypt':
    case 'caesar_decrypt':
        $text  = $_POST['caesar_text'] ?? '';
        $shift = (int)($_POST['caesar_shift'] ?? 3);
        $enc   = ($action === 'caesar_encrypt');
        $result = ['tool'=>'caesar','mode'=>$enc?'Enkripsi':'Dekripsi','output'=>caesar_cipher($text,$shift,$enc),'input'=>$text,'shift'=>$shift];
        break;

    case 'vigenere_encrypt':
    case 'vigenere_decrypt':
        $text = $_POST['vig_text'] ?? '';
        $key  = $_POST['vig_key']  ?? '';
        if (empty(trim($key))) { $error='Keyword tidak boleh kosong.'; $tool='vigenere'; break; }
        $enc  = ($action === 'vigenere_encrypt');
        $result = ['tool'=>'vigenere','mode'=>$enc?'Enkripsi':'Dekripsi','output'=>vigenere_cipher($text,$key,$enc),'input'=>$text,'key'=>$key];
        break;

    case 'fpb_hitung':
        $a = trim($_POST['fpb_a'] ?? '');
        $b = trim($_POST['fpb_b'] ?? '');
        if (!is_numeric($a)||!is_numeric($b)||intval($a)<=0||intval($b)<=0) {
            $error='Masukkan angka bulat positif.'; $tool='fpb'; break;
        }
        $langkah = [];
        $fpb = hitungFPB(intval($a), intval($b), $langkah);
        $result = ['tool'=>'fpb','fpb'=>$fpb,'a'=>$a,'b'=>$b,'langkah'=>$langkah,'prima'=>($fpb==1)];
        break;

    case 'rsa_generate':
        $keys = generateKeys();
        if (isset($keys['error'])) { $error=$keys['error']; $tool='rsa'; break; }
        $result = ['tool'=>'rsa','mode'=>'generate','public'=>$keys[0],'private'=>$keys[1]];
        break;

    case 'rsa_encrypt':
        $pesan = $_POST['rsa_pesan'] ?? '';
        $key   = $_POST['rsa_key']   ?? '';
        $aksi  = $_POST['rsa_aksi']  ?? 'encrypt';
        if (empty($key)) { $error='Key tidak boleh kosong.'; $tool='rsa'; break; }
        if ($aksi === 'encrypt') {
            $out = encryptRSA($pesan, $key);
            if ($out === null) { $error='Enkripsi gagal. Periksa Public Key.'; $tool='rsa'; break; }
            $result = ['tool'=>'rsa','mode'=>'Enkripsi','output'=>$out];
        } else {
            $out = decryptRSA($pesan, $key);
            if ($out === null) { $error='Dekripsi gagal. Periksa Private Key.'; $tool='rsa'; break; }
            $result = ['tool'=>'rsa','mode'=>'Dekripsi','output'=>$out];
        }
        break;

    case 'ds_sign':
        $dok = $_POST['ds_dokumen'] ?? '';
        $pk  = $_POST['ds_privkey'] ?? '';
        if (empty(trim($dok))||empty(trim($pk))) { $error='Dokumen dan Private Key harus diisi.'; $tool='signature'; break; }
        $sig = sign_document($dok, $pk);
        if (!$sig) { $error='Gagal signing. Periksa Private Key.'; $tool='signature'; break; }
        $result = ['tool'=>'signature','mode'=>'sign','signature'=>$sig];
        break;

    case 'ds_verify':
        $dok  = $_POST['dv_dokumen']   ?? '';
        $sig  = $_POST['dv_signature'] ?? '';
        $pub  = $_POST['dv_pubkey']    ?? '';
        if (empty(trim($dok))||empty(trim($sig))||empty(trim($pub))) { $error='Semua field harus diisi.'; $tool='signature'; break; }
        $status = verify_document($dok, $sig, $pub);
        $result = ['tool'=>'signature','mode'=>'verify','status'=>$status,'valid'=>($status==='VERIFIED')];
        break;

    case 'ssl_generate':
        $dn = [
            'countryName'         => strtoupper(substr($_POST['ssl_country'] ?? 'ID', 0, 2)),
            'stateOrProvinceName' => $_POST['ssl_state']  ?? '',
            'localityName'        => $_POST['ssl_city']   ?? '',
            'organizationName'    => $_POST['ssl_org']    ?? '',
            'commonName'          => $_POST['ssl_domain'] ?? 'localhost',
        ];
        $ssl = generateSSL($dn);
        if (isset($ssl['error'])) { $error=$ssl['error']; $tool='ssl'; break; }
        $result = ['tool'=>'ssl','cert'=>$ssl['cert'],'key'=>$ssl['key']];
        break;
}

if ($result) $tool = $result['tool'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CryptoVault — Web Tools Kriptografi</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --bg:      #080b12;
  --bg2:     #0d1117;
  --surface: #111827;
  --border:  #1e2a3a;
  --accent:  #38bdf8;
  --accent2: #818cf8;
  --green:   #34d399;
  --red:     #f87171;
  --yellow:  #fbbf24;
  --text:    #e2e8f0;
  --muted:   #64748b;
  --sans:    'Syne', sans-serif;
  --mono:    'JetBrains Mono', monospace;
}

html { scroll-behavior: smooth; }

body {
  background: var(--bg);
  color: var(--text);
  font-family: var(--sans);
  min-height: 100vh;
  display: flex;
  background-image:
    radial-gradient(ellipse 70% 40% at 10% 0%, rgba(56,189,248,.06), transparent),
    radial-gradient(ellipse 60% 40% at 90% 100%, rgba(129,140,248,.05), transparent);
}

.sidebar {
  width: 230px; min-height: 100vh;
  background: var(--bg2);
  border-right: 1px solid var(--border);
  display: flex; flex-direction: column;
  position: fixed; top:0; left:0; bottom:0;
  z-index: 100;
}

.sb-brand {
  padding: 1.5rem 1.25rem;
  border-bottom: 1px solid var(--border);
}
.sb-logo {
  font-size: 1.3rem; font-weight: 800;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
  letter-spacing: -0.5px;
}
.sb-sub { font-size: .65rem; color: var(--muted); font-family: var(--mono); margin-top: .2rem; }

.sb-section {
  font-size: .6rem; letter-spacing: .15em; text-transform: uppercase;
  color: var(--muted); padding: 1.2rem 1.25rem .5rem;
}

.sb-nav { padding: 0 .6rem; flex: 1; }

.sb-item {
  display: flex; align-items: center; gap: .65rem;
  padding: .65rem .75rem; border-radius: 8px;
  cursor: pointer; color: var(--muted);
  font-size: .82rem; font-weight: 600;
  transition: all .15s; border: none;
  background: none; width: 100%; text-align: left;
  font-family: var(--sans);
  text-decoration: none;
}
.sb-item:hover { background: rgba(255,255,255,.04); color: var(--text); }
.sb-item.active { background: rgba(56,189,248,.1); color: var(--accent); }
.sb-item .icon { font-size: 1rem; width: 1.2rem; text-align: center; }

.sb-badge {
  margin-left: auto; font-size: .55rem; padding: .15rem .5rem;
  border-radius: 99px; background: rgba(129,140,248,.15);
  color: var(--accent2); border: 1px solid rgba(129,140,248,.25);
  font-family: var(--mono);
}

.sb-footer {
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--border);
  font-family: var(--mono); font-size: .65rem; color: var(--muted);
  line-height: 1.8;
}

.main { margin-left: 230px; flex: 1; min-height: 100vh; padding: 2rem 2.5rem 3rem; }

.page-hdr { margin-bottom: 1.75rem; }
.page-hdr h1 { font-size: 1.6rem; font-weight: 800; letter-spacing: -.5px; }
.page-hdr h1 span { color: var(--accent); }
.page-hdr p { font-size: .78rem; color: var(--muted); margin-top: .3rem; font-family: var(--mono); }

.chips { display: flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1.75rem; }
.chip {
  padding: .4rem 1rem; border-radius: 99px;
  font-size: .72rem; font-family: var(--mono);
  border: 1px solid var(--border); color: var(--muted);
  background: var(--surface);
}
.chip span { color: var(--accent); }

.card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 12px; overflow: hidden;
  margin-bottom: 1.5rem;
  animation: fadeUp .3s ease both;
}
@keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:none} }

.card-hdr {
  padding: .9rem 1.4rem;
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; gap: .75rem;
  background: rgba(255,255,255,.02);
}
.card-hdr h2 { font-size: .9rem; font-weight: 700; }
.badge {
  font-family: var(--mono); font-size: .6rem; padding: .2rem .6rem;
  border-radius: 99px; background: rgba(56,189,248,.1);
  color: var(--accent); border: 1px solid rgba(56,189,248,.2);
}
.badge-v { background: rgba(129,140,248,.1); color: var(--accent2); border-color: rgba(129,140,248,.2); }
.badge-g { background: rgba(52,211,153,.1);  color: var(--green);   border-color: rgba(52,211,153,.2); }

.card-body { padding: 1.4rem; }

.fg { margin-bottom: 1.1rem; }
label {
  display: block; font-size: .7rem; font-family: var(--mono);
  text-transform: uppercase; letter-spacing: .07em;
  color: var(--muted); margin-bottom: .35rem;
}
input[type=text], input[type=number], textarea, select {
  width: 100%; padding: .65rem 1rem;
  background: rgba(0,0,0,.3); border: 1px solid var(--border);
  border-radius: 8px; color: var(--text);
  font-family: var(--mono); font-size: .82rem;
  transition: border-color .2s, box-shadow .2s; outline: none; resize: vertical;
}
input:focus, textarea:focus, select:focus {
  border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56,189,248,.1);
}
textarea { min-height: 85px; }
.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
@media(max-width:640px){.row2{grid-template-columns:1fr}}

.btns { display: flex; flex-wrap: wrap; gap: .6rem; margin-top: .25rem; }
.btn {
  padding: .6rem 1.3rem; border: none; border-radius: 8px;
  cursor: pointer; font-family: var(--mono); font-size: .8rem;
  font-weight: 500; transition: all .18s;
}
.btn-p { background: var(--accent); color: var(--bg); box-shadow: 0 0 18px rgba(56,189,248,.25); }
.btn-p:hover { transform: translateY(-1px); box-shadow: 0 0 26px rgba(56,189,248,.4); }
.btn-s { background: transparent; color: var(--accent); border: 1px solid rgba(56,189,248,.3); }
.btn-s:hover { background: rgba(56,189,248,.07); }
.btn-v { background: var(--accent2); color: #fff; box-shadow: 0 0 18px rgba(129,140,248,.25); }
.btn-v:hover { transform: translateY(-1px); box-shadow: 0 0 26px rgba(129,140,248,.4); }
.btn-g { background: var(--green); color: var(--bg); box-shadow: 0 0 18px rgba(52,211,153,.2); }
.btn-g:hover { transform: translateY(-1px); }

.result {
  background: rgba(56,189,248,.04); border: 1px solid rgba(56,189,248,.18);
  border-radius: 10px; padding: 1.1rem 1.4rem; margin-top: 1.4rem;
  animation: fadeUp .3s ease both;
}
.result-title { font-size: .65rem; font-family: var(--mono); color: var(--accent); text-transform: uppercase; letter-spacing: .08em; margin-bottom: .6rem; }
.result-val { font-family: var(--mono); font-size: .8rem; word-break: break-all; line-height: 1.7; color: #f0f9ff; }
.result-val.pre-wrap { white-space: pre-wrap; font-size: .72rem; max-height: 180px; overflow-y: auto; background: rgba(0,0,0,.3); padding: .75rem; border-radius: 6px; }
.copy-btn {
  margin-top: .5rem; display: inline-block; padding: .28rem .7rem;
  font-size: .68rem; font-family: var(--mono); cursor: pointer;
  background: transparent; color: var(--muted);
  border: 1px solid var(--border); border-radius: 5px; transition: all .2s;
}
.copy-btn:hover { color: var(--accent); border-color: var(--accent); }

.result-verified { color: var(--green); font-size: 1.05rem; font-weight: 700; }
.result-invalid  { color: var(--red);   font-size: 1.05rem; font-weight: 700; }

.err {
  background: rgba(248,113,113,.07); border: 1px solid rgba(248,113,113,.25);
  border-radius: 8px; padding: .85rem 1.2rem; color: #fca5a5;
  font-family: var(--mono); font-size: .8rem; margin-bottom: 1.25rem;
}

.steps { margin-top: .75rem; display: flex; flex-direction: column; gap: .3rem; }
.step  { font-family: var(--mono); font-size: .75rem; color: var(--muted); display: flex; align-items: center; gap: .6rem; }
.step::before { content:''; width:5px; height:5px; border-radius:50%; background: var(--border); flex-shrink:0; }
.step:last-child { color: var(--accent); }
.step:last-child::before { background: var(--accent); }

.ssl-table { width:100%; border-collapse:collapse; margin-top:.5rem; }
.ssl-table td { padding: .9rem 1rem; border-bottom: 1px solid var(--border); vertical-align:top; font-size:.78rem; }
.ssl-table tr:last-child td { border-bottom:none; }
.ssl-table .lbl { color: var(--muted); font-family: var(--mono); font-size:.68rem; white-space:nowrap; }
.ssl-table .val { font-family: var(--mono); font-size:.7rem; color: var(--green); word-break:break-all; white-space:pre-wrap; max-height:140px; overflow-y:auto; display:block; }

hr { border:none; border-top:1px solid var(--border); margin: 1.2rem 0; }

@media(max-width:768px){
  .sidebar{ width:100%; min-height:auto; position:relative; flex-direction:row; flex-wrap:wrap; }
  .main{ margin-left:0; padding:1rem; }
  .sb-nav{ display:flex; flex-wrap:wrap; padding:.5rem; }
}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sb-brand">
    <div class="sb-logo"> CryptoVault</div>
    <div class="sb-sub">
  </div>

  <div class="sb-section">Cipher Klasik</div>
  <nav class="sb-nav">
    <a class="sb-item <?= $tool==='caesar' ?'active':'' ?>" href="?tool=caesar">
      <span class="icon"></span> Caesar Cipher
    </a>
    <a class="sb-item <?= $tool==='vigenere' ?'active':'' ?>" href="?tool=vigenere">
      <span class="icon"></span> Vigenère Cipher
    </a>
    <a class="sb-item <?= $tool==='fpb' ?'active':'' ?>" href="?tool=fpb">
      <span class="icon"></span> FPB Euclidean
    </a>
  </nav>

  <div class="sb-section">Kriptografi Modern</div>
  <nav class="sb-nav">
    <a class="sb-item <?= $tool==='rsa' ?'active':'' ?>" href="?tool=rsa">
      <span class="icon"></span> RSA Enkripsi
    </a>
    <a class="sb-item <?= $tool==='signature' ?'active':'' ?>" href="?tool=signature">
      <span class="icon"></span> Digital Signature <span class="sb-badge">+10</span>
    </a>
    <a class="sb-item <?= $tool==='ssl' ?'active':'' ?>" href="?tool=ssl">
      <span class="icon"></span> SSL Generator
    </a>
  </nav>

  <div class="sb-footer">
    Nama : Ronald Raffin<br>
    NIM  : 231220032<br>
    UTS Kriptografi <?= date('Y') ?>
  </div>
</aside>

<div class="main">

<?php if ($error): ?>
  <div class="err"> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($tool === 'caesar'): ?>
<div class="page-hdr">
  <h1>Caesar <span>Cipher</span></h1>
  <p>
</div>
<div class="chips">
  <div class="chip">Pertemuan <span>3</span></div>
  <div class="chip">Algoritma <span>Substitusi</span></div>
  <div class="chip">E(x) = (x + k) <span>mod 26</span></div>
</div>
<div class="card">
  <div class="card-hdr"><span></span><h2>Caesar Cipher</h2><span class="badge">Pertemuan 3</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="caesar">
      <div class="fg">
        <label>Teks Input</label>
        <textarea name="caesar_text" placeholder="Masukkan teks..."><?= htmlspecialchars($_POST['caesar_text'] ?? '') ?></textarea>
      </div>
      <div class="fg" style="max-width:200px">
        <label>Shift Key (1–25)</label>
        <input type="number" name="caesar_shift" min="1" max="25" value="<?= (int)($_POST['caesar_shift'] ?? 3) ?>">
      </div>
      <div class="btns">
        <button class="btn btn-p" name="action" value="caesar_encrypt"> Enkripsi</button>
        <button class="btn btn-s" name="action" value="caesar_decrypt"> Dekripsi</button>
      </div>
    </form>
    <?php if ($result && $result['tool']==='caesar'): ?>
    <div class="result">
      <div class="result-title">▸ Hasil <?= $result['mode'] ?> — Shift: <?= $result['shift'] ?></div>
      <div class="result-val" id="r-caesar"><?= htmlspecialchars($result['output']) ?></div>
      <button class="copy-btn" onclick="doCopy('r-caesar',this)"> Salin</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tool === 'vigenere'): ?>
<div class="page-hdr">
  <h1>Vigenère <span>Cipher</span></h1>
  <p>
</div>
<div class="chips">
  <div class="chip">Pertemuan <span>3</span></div>
  <div class="chip">Algoritma <span>Polyalphabetik</span></div>
  <div class="chip">Eᵢ(x) = (xᵢ + kᵢ) <span>mod 26</span></div>
</div>
<div class="card">
  <div class="card-hdr"><span></span><h2>Vigenère Cipher</h2><span class="badge badge-v">Pertemuan 3</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="vigenere">
      <div class="fg">
        <label>Teks Input</label>
        <textarea name="vig_text" placeholder="Masukkan teks..."><?= htmlspecialchars($_POST['vig_text'] ?? '') ?></textarea>
      </div>
      <div class="fg">
        <label>Keyword (huruf saja, misal: LEMON)</label>
        <input type="text" name="vig_key" placeholder="Contoh: ARCANUM" value="<?= htmlspecialchars($_POST['vig_key'] ?? '') ?>">
      </div>
      <div class="btns">
        <button class="btn btn-p" name="action" value="vigenere_encrypt"> Enkripsi</button>
        <button class="btn btn-s" name="action" value="vigenere_decrypt"> Dekripsi</button>
      </div>
    </form>
    <?php if ($result && $result['tool']==='vigenere'): ?>
    <div class="result">
      <div class="result-title">▸ Hasil <?= $result['mode'] ?> — Key: "<?= htmlspecialchars($result['key']) ?>"</div>
      <div class="result-val" id="r-vig"><?= htmlspecialchars($result['output']) ?></div>
      <button class="copy-btn" onclick="doCopy('r-vig',this)"> Salin</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tool === 'fpb'): ?>
<div class="page-hdr">
  <h1>Kalkulator <span>FPB</span></h1>
  <p>
</div>
<div class="chips">
  <div class="chip">Tugas <span>1</span></div>
  <div class="chip">Algoritma <span>Euclidean</span></div>
  <div class="chip">FPB = 1 → <span>Relatif Prima</span></div>
</div>
<div class="card">
  <div class="card-hdr"><span></span><h2>FPB Euclidean</h2><span class="badge badge-g">Tugas</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="fpb">
      <div class="row2">
        <div class="fg"><label>Nilai A</label><input type="number" name="fpb_a" placeholder="misal: 48" value="<?= htmlspecialchars($_POST['fpb_a'] ?? '') ?>"></div>
        <div class="fg"><label>Nilai B</label><input type="number" name="fpb_b" placeholder="misal: 18" value="<?= htmlspecialchars($_POST['fpb_b'] ?? '') ?>"></div>
      </div>
      <button class="btn btn-g" name="action" value="fpb_hitung"> Hitung FPB</button>
    </form>
    <?php if ($result && $result['tool']==='fpb'): ?>
    <div class="result">
      <div class="result-title">▸ FPB dari <?= $result['a'] ?> dan <?= $result['b'] ?></div>
      <div class="result-val" style="font-size:1.5rem;font-weight:700;color:var(--<?= $result['prima']?'green':'yellow' ?>)">
        <?= $result['fpb'] ?>
        <span style="font-size:.75rem;margin-left:.5rem;color:var(--muted)"><?= $result['prima'] ? ' Relatif Prima' : ' Tidak Relatif Prima' ?></span>
      </div>
      <div class="steps">
        <?php foreach($result['langkah'] as $step): ?>
          <div class="step"><?= htmlspecialchars($step) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tool === 'rsa'): ?>
<div class="page-hdr">
  <h1>RSA <span>Enkripsi</span></h1>
  <p>
</div>
<div class="chips">
  <div class="chip">Tugas <span>5</span></div>
  <div class="chip">RSA <span>2048-bit</span></div>
  <div class="chip">OpenSSL <span>PHP</span></div>
</div>

<div class="card">
  <div class="card-hdr"><span></span><h2>Generate RSA Key Pair</h2><span class="badge">Langkah 1</span></div>
  <div class="card-body">
    <p style="font-size:.8rem;color:var(--muted);margin-bottom:1rem">Klik tombol di bawah untuk membuat pasangan Public Key & Private Key baru.</p>
    <form method="POST">
      <input type="hidden" name="tool" value="rsa">
      <button class="btn btn-v" name="action" value="rsa_generate"> Generate Key Pair (Alice)</button>
    </form>
    <?php if ($result && $result['tool']==='rsa' && $result['mode']==='generate'): ?>
    <div class="result">
      <div class="result-title">▸ Public Key (Alice)</div>
      <div class="result-val pre-wrap" id="r-pub"><?= htmlspecialchars($result['public']) ?></div>
      <button class="copy-btn" onclick="doCopy('r-pub',this)"> Salin Public Key</button>
      <hr>
      <div class="result-title" style="margin-top:.5rem">▸ Private Key (Alice) <small style="color:var(--red)">— Rahasia!</small></div>
      <div class="result-val pre-wrap" id="r-priv"><?= htmlspecialchars($result['private']) ?></div>
      <button class="copy-btn" onclick="doCopy('r-priv',this)"> Salin Private Key</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-hdr"><span></span><h2>Enkripsi / Dekripsi RSA</h2><span class="badge">Langkah 2</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="rsa">
      <div class="fg">
        <label>Pesan (Plaintext untuk enkripsi / Ciphertext Base64 untuk dekripsi)</label>
        <textarea name="rsa_pesan" placeholder="Teks atau ciphertext Base64..."><?= htmlspecialchars($_POST['rsa_pesan'] ?? '') ?></textarea>
      </div>
      <div class="fg">
        <label>Key (Public Key untuk enkripsi / Private Key untuk dekripsi)</label>
        <textarea name="rsa_key" placeholder="Paste Public Key atau Private Key PEM di sini..."><?= htmlspecialchars($_POST['rsa_key'] ?? '') ?></textarea>
      </div>
      <div class="fg" style="max-width:260px">
        <label>Pilih Aksi</label>
        <select name="rsa_aksi">
          <option value="encrypt" <?= (($_POST['rsa_aksi']??'encrypt')==='encrypt')?'selected':'' ?>>Enkripsi (Plain → Cipher)</option>
          <option value="decrypt" <?= (($_POST['rsa_aksi']??'')==='decrypt')?'selected':'' ?>>Dekripsi (Cipher → Plain)</option>
        </select>
      </div>
      <button class="btn btn-p" name="action" value="rsa_encrypt"> Proses Kriptografi</button>
    </form>
    <?php if ($result && $result['tool']==='rsa' && $result['mode']!=='generate'): ?>
    <div class="result">
      <div class="result-title">▸ Hasil <?= $result['mode'] ?></div>
      <div class="result-val pre-wrap" id="r-rsa"><?= htmlspecialchars($result['output']) ?></div>
      <button class="copy-btn" onclick="doCopy('r-rsa',this)"> Salin</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tool === 'signature'): ?>
<div class="page-hdr">
  <h1>Digital <span>Signature</span></h1>
  <p>
</div>
<div class="chips">
  <div class="chip">Tugas <span>6</span></div>
  <div class="chip">RSA + <span>SHA-256</span></div>
  <div class="chip">Bonus <span>+10</span></div>
</div>

<div class="card">
  <div class="card-hdr"><span></span><h2>Sign Dokumen</h2><span class="badge badge-v">Tanda Tangan</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="signature">
      <div class="fg">
        <label>Isi Dokumen</label>
        <textarea name="ds_dokumen" placeholder="Masukkan isi dokumen yang akan ditandatangani..."><?= htmlspecialchars($_POST['ds_dokumen'] ?? '') ?></textarea>
      </div>
      <div class="fg">
        <label>Private Key (PEM)</label>
        <textarea name="ds_privkey" placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;...&#10;-----END RSA PRIVATE KEY-----"><?= htmlspecialchars($_POST['ds_privkey'] ?? '') ?></textarea>
      </div>
      <button class="btn btn-v" name="action" value="ds_sign"> Tanda Tangani</button>
    </form>
    <?php if ($result && $result['tool']==='signature' && $result['mode']==='sign'): ?>
    <div class="result">
      <div class="result-title">▸ Signature (Base64)</div>
      <div class="result-val pre-wrap" id="r-sig"><?= htmlspecialchars($result['signature']) ?></div>
      <button class="copy-btn" onclick="doCopy('r-sig',this)"> Salin Signature</button>
    </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card-hdr"><span></span><h2>Verifikasi Dokumen</h2><span class="badge badge-v">Verifikasi</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="signature">
      <div class="fg">
        <label>Isi Dokumen Asli</label>
        <textarea name="dv_dokumen" placeholder="Isi dokumen yang akan diverifikasi..."><?= htmlspecialchars($_POST['dv_dokumen'] ?? '') ?></textarea>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Signature (Base64)</label>
          <textarea name="dv_signature" placeholder="Paste signature Base64..."><?= htmlspecialchars($_POST['dv_signature'] ?? '') ?></textarea>
        </div>
        <div class="fg">
          <label>Public Key (PEM)</label>
          <textarea name="dv_pubkey" placeholder="-----BEGIN PUBLIC KEY-----&#10;...&#10;-----END PUBLIC KEY-----"><?= htmlspecialchars($_POST['dv_pubkey'] ?? '') ?></textarea>
        </div>
      </div>
      <button class="btn btn-p" name="action" value="ds_verify"> Verifikasi Dokumen</button>
    </form>
    <?php if ($result && $result['tool']==='signature' && $result['mode']==='verify'): ?>
    <div class="result">
      <div class="result-title">▸ Hasil Verifikasi</div>
      <div class="<?= $result['valid'] ? 'result-verified' : 'result-invalid' ?>">
        <?= $result['valid'] ? ' VERIFIED — Tanda tangan sah, dokumen asli & tidak dimodifikasi.' : ' INVALID — Tanda tangan tidak cocok atau dokumen telah diubah.' ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tool === 'ssl'): ?>
<div class="page-hdr">
  <h1>SSL <span>Generator</span></h1>
  <p>
</div>
<div class="chips">
  <div class="chip">Tugas <span>7</span></div>
  <div class="chip">X.509 <span>Self-Signed</span></div>
  <div class="chip">RSA <span>2048-bit</span></div>
  <div class="chip">Valid <span>365 hari</span></div>
</div>

<div class="card">
  <div class="card-hdr"><span></span><h2>Informasi Sertifikat</h2><span class="badge badge-g">Tugas 7</span></div>
  <div class="card-body">
    <form method="POST">
      <input type="hidden" name="tool" value="ssl">
      <div class="row2">
        <div class="fg">
          <label>Negara (2 Digit)</label>
          <input type="text" name="ssl_country" maxlength="2" placeholder="ID" value="<?= htmlspecialchars($_POST['ssl_country'] ?? 'ID') ?>">
        </div>
        <div class="fg">
          <label>Provinsi</label>
          <input type="text" name="ssl_state" placeholder="Kalimantan Barat" value="<?= htmlspecialchars($_POST['ssl_state'] ?? '') ?>">
        </div>
        <div class="fg">
          <label>Kota</label>
          <input type="text" name="ssl_city" placeholder="Pontianak" value="<?= htmlspecialchars($_POST['ssl_city'] ?? '') ?>">
        </div>
        <div class="fg">
          <label>Organisasi</label>
          <input type="text" name="ssl_org" placeholder="UM Pontianak" value="<?= htmlspecialchars($_POST['ssl_org'] ?? '') ?>">
        </div>
      </div>
      <div class="fg">
        <label>Common Name (Domain)</label>
        <input type="text" name="ssl_domain" placeholder="webku.local" value="<?= htmlspecialchars($_POST['ssl_domain'] ?? '') ?>">
      </div>
      <button class="btn btn-g" name="action" value="ssl_generate"> Generate SSL Certificate</button>
    </form>

    <?php if ($result && $result['tool']==='ssl'): ?>
    <div class="result" style="border-color:rgba(52,211,153,.25);background:rgba(52,211,153,.04)">
      <div class="result-title" style="color:var(--green)">▸ SSL Certificate — Berhasil dibuat!</div>
      <table class="ssl-table">
        <tr>
          <td class="lbl"> Certificate (.CRT)</td>
          <td>
            <span class="val" id="r-cert"><?= htmlspecialchars($result['cert']) ?></span>
            <button class="copy-btn" onclick="doCopy('r-cert',this)"> Salin</button>
            <a style="display:inline-block;margin-top:.4rem;padding:.28rem .7rem;font-size:.68rem;font-family:var(--mono);color:var(--green);border:1px solid rgba(52,211,153,.3);border-radius:5px;text-decoration:none"
               href="data:application/x-pem-file;charset=utf-8,<?= rawurlencode($result['cert']) ?>" download="server.crt"> Download .CRT</a>
          </td>
        </tr>
        <tr>
          <td class="lbl"> Private Key (.KEY)</td>
          <td>
            <span class="val" id="r-sslkey"><?= htmlspecialchars($result['key']) ?></span>
            <button class="copy-btn" onclick="doCopy('r-sslkey',this)"> Salin</button>
            <a style="display:inline-block;margin-top:.4rem;padding:.28rem .7rem;font-size:.68rem;font-family:var(--mono);color:var(--green);border:1px solid rgba(52,211,153,.3);border-radius:5px;text-decoration:none"
               href="data:application/x-pem-file;charset=utf-8,<?= rawurlencode($result['key']) ?>" download="server.key"> Download .KEY</a>
          </td>
        </tr>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php endif; ?>

</div>

<script>
function doCopy(id, btn) {
  const text = document.getElementById(id).textContent.trim();
  navigator.clipboard.writeText(text).then(() => {
    const orig = btn.textContent;
    btn.textContent = ' Tersalin!';
    setTimeout(() => btn.textContent = orig, 1600);
  });
}
</script>
</body>
</html>
