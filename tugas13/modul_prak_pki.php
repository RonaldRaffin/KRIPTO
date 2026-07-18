<?php
$hasil_ssl = null;
$error = null;
$domain_input = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Bersihkan input domain (buang http:// atau karakter miring)
    $domain_target = trim($_POST['domain']);
    $domain_input = $domain_target;
    $domain_bersih = preg_replace('#^https?://#', '', $domain_target);
    $domain_bersih = explode('/', rtrim($domain_bersih, '/'))[0];

    if (!empty($domain_bersih)) {
        try {
            // 1. Buat Konteks Koneksi Stream
            $streamContext = stream_context_create([
                "ssl" => [
                    "capture_peer_cert" => true, // Tangkap Sertifikat Target!
                    "verify_peer"       => false, // Abaikan validasi CA lokal kita
                    "verify_peer_name"  => false
                ]
            ]);

            // 2. Buka Socket TCP ke Port 443 (HTTPS) dengan Timeout 10 detik
            $client = @stream_socket_client(
                "ssl://".$domain_bersih.":443",
                $errorNumber, $errorString, 10,
                STREAM_CLIENT_CONNECT, $streamContext
            );

            if ($client) {
                // 3. Ambil data dari parameter koneksi yang sukses
                $params = stream_context_get_params($client);
                $cert_resource = $params['options']['ssl']['peer_certificate'];

                // 4. Eksekusi Parsing: Ubah objek Sertifikat jadi Array PHP (X.509)
                $hasil_ssl = openssl_x509_parse($cert_resource);

                // 5. Ekspor format .PEM mentah untuk referensi tampilan
                openssl_x509_export($cert_resource, $pem_format);
                $hasil_ssl['pem_raw'] = $pem_format;

                // 6. Fingerprint SHA-256 (untuk strip identitas / MRZ)
                $hasil_ssl['fingerprint_sha256'] = openssl_x509_fingerprint($cert_resource, 'sha256');

                fclose($client);
            } else {
                $error = "Gagal menghubungi ".htmlspecialchars($domain_bersih).". Pastikan domain aktif dan mendukung HTTPS di port 443. (".$errorString.")";
            }
        } catch (Throwable $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    } else {
        $error = "Masukkan alamat domain terlebih dahulu.";
    }
}

// Helper tampilan
function nilai($arr, $key, $fallback = null) {
    return (isset($arr[$key]) && $arr[$key] !== '') ? $arr[$key] : $fallback;
}

$hari_tersisa = null;
if ($hasil_ssl && isset($hasil_ssl['validTo_time_t'])) {
    $hari_tersisa = (int) floor(($hasil_ssl['validTo_time_t'] - time()) / 86400);
}

$is_ov_ev = $hasil_ssl ? !empty($hasil_ssl['subject']['O']) : false;
$san_raw = $hasil_ssl ? nilai($hasil_ssl['extensions'] ?? [], 'subjectAltName', '') : '';
$san_list = $san_raw ? array_map('trim', explode(',', $san_raw)) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inspektur Sertifikat &mdash; SSL/TLS X.509</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=IBM+Plex+Mono:wght@400;500;600&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  :root{
    --bg:#0A0D12;
    --surface:#12161D;
    --surface-2:#181D26;
    --hairline:#262D3A;
    --gold:#C9A227;
    --gold-soft:rgba(201,162,39,0.14);
    --teal:#2DD4A6;
    --teal-soft:rgba(45,212,166,0.12);
    --red:#E5484D;
    --red-soft:rgba(229,72,77,0.12);
    --amber:#E0A64B;
    --text:#E8EAED;
    --text-muted:#8B95A5;
    --text-dim:#5B6472;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    background:
      radial-gradient(1200px 500px at 15% -10%, rgba(201,162,39,0.06), transparent 60%),
      var(--bg);
    color:var(--text);
    font-family:'Inter', sans-serif;
    padding:48px 20px 80px;
    min-height:100vh;
  }
  .wrap{max-width:880px; margin:0 auto;}

  /* ---- Header ---- */
  .eyebrow{
    font-family:'IBM Plex Mono', monospace;
    font-size:11px;
    letter-spacing:0.22em;
    text-transform:uppercase;
    color:var(--gold);
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:14px;
  }
  .eyebrow::before{
    content:'';
    width:16px; height:1px;
    background:var(--gold);
    display:inline-block;
  }
  h1{
    font-family:'Fraunces', serif;
    font-weight:600;
    font-size:clamp(28px, 4vw, 40px);
    line-height:1.15;
    margin:0 0 10px;
    letter-spacing:-0.01em;
  }
  .sub{
    color:var(--text-muted);
    font-size:15px;
    max-width:56ch;
    line-height:1.6;
    margin:0 0 36px;
  }

  /* ---- Form ---- */
  form{
    display:flex;
    gap:10px;
    background:var(--surface);
    border:1px solid var(--hairline);
    border-radius:12px;
    padding:8px 8px 8px 20px;
    align-items:center;
    flex-wrap:wrap;
  }
  form label{display:none;}
  input[name="domain"]{
    flex:1;
    min-width:180px;
    background:transparent;
    border:none;
    color:var(--text);
    font-family:'IBM Plex Mono', monospace;
    font-size:15px;
    padding:14px 8px;
    outline:none;
  }
  input[name="domain"]::placeholder{color:var(--text-dim);}
  button[type="submit"]{
    font-family:'Inter', sans-serif;
    font-weight:600;
    font-size:14px;
    color:#0A0D12;
    background:var(--gold);
    border:none;
    border-radius:8px;
    padding:14px 22px;
    cursor:pointer;
    transition:filter 0.15s ease, transform 0.15s ease;
    white-space:nowrap;
  }
  button[type="submit"]:hover{filter:brightness(1.08); transform:translateY(-1px);}
  button[type="submit"]:focus-visible, input:focus-visible{
    outline:2px solid var(--gold);
    outline-offset:2px;
  }

  /* ---- Error ---- */
  .error-box{
    margin-top:24px;
    background:var(--red-soft);
    border:1px solid rgba(229,72,77,0.35);
    color:#FCA5A6;
    padding:16px 18px;
    border-radius:10px;
    font-size:14px;
    line-height:1.55;
    display:flex;
    gap:12px;
  }
  .error-box b{color:var(--red); font-family:'IBM Plex Mono', monospace; font-size:12px; letter-spacing:0.05em;}

  /* ---- Result: ID Card ---- */
  .card{
    margin-top:36px;
    background:var(--surface);
    border:1px solid var(--hairline);
    border-radius:16px;
    overflow:hidden;
  }
  .card-top{
    display:grid;
    grid-template-columns:220px 1fr;
    border-bottom:1px dashed var(--hairline);
  }
  @media (max-width:640px){ .card-top{grid-template-columns:1fr;} }

  .seal{
    padding:32px 24px;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;
    gap:14px;
    background:var(--surface-2);
    border-right:1px dashed var(--hairline);
  }
  @media (max-width:640px){ .seal{border-right:none; border-bottom:1px dashed var(--hairline);} }

  .seal-icon{
    width:56px; height:56px;
    border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    background:var(--teal-soft);
    color:var(--teal);
    border:1px solid rgba(45,212,166,0.35);
  }
  .status-pill{
    font-family:'IBM Plex Mono', monospace;
    font-size:10.5px;
    letter-spacing:0.14em;
    text-transform:uppercase;
    color:var(--teal);
    background:var(--teal-soft);
    border:1px solid rgba(45,212,166,0.3);
    padding:5px 10px;
    border-radius:100px;
  }
  .status-pill.warn{color:var(--amber); background:rgba(224,166,75,0.12); border-color:rgba(224,166,75,0.3);}
  .cn-big{
    font-family:'Fraunces', serif;
    font-size:19px;
    font-weight:600;
    line-height:1.3;
    word-break:break-word;
  }
  .validation-tag{
    font-size:12px;
    color:var(--text-muted);
  }

  .detail-grid{padding:28px 28px;}
  .drow{
    display:grid;
    grid-template-columns:170px 1fr;
    gap:16px;
    padding:12px 0;
    border-bottom:1px solid var(--hairline);
  }
  .drow:last-child{border-bottom:none;}
  .dlabel{
    font-size:11px;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:var(--text-dim);
    font-weight:600;
    padding-top:2px;
  }
  .dval{font-size:14.5px; line-height:1.6; word-break:break-word;}
  .dval.mono{font-family:'IBM Plex Mono', monospace; font-size:13px;}
  .san-list{display:flex; flex-wrap:wrap; gap:6px;}
  .san-chip{
    font-family:'IBM Plex Mono', monospace;
    font-size:11.5px;
    background:var(--surface-2);
    border:1px solid var(--hairline);
    padding:3px 9px;
    border-radius:6px;
    color:var(--text-muted);
  }

  /* ---- MRZ strip ---- */
  .mrz{
    position:relative;
    margin:0 28px 28px;
    background:#05070A;
    border:1px solid var(--hairline);
    border-radius:10px;
    padding:18px 22px;
    font-family:'IBM Plex Mono', monospace;
    font-size:12.5px;
    letter-spacing:0.06em;
    color:var(--teal);
    line-height:1.9;
    overflow-x:auto;
    white-space:pre;
  }
  .mrz::before, .mrz::after{
    content:'';
    position:absolute;
    width:10px; height:10px;
    border:1.5px solid var(--gold);
    opacity:0.6;
  }
  .mrz::before{top:6px; left:6px; border-right:none; border-bottom:none;}
  .mrz::after{bottom:6px; right:6px; border-left:none; border-top:none;}
  .mrz-caption{
    font-family:'IBM Plex Mono', monospace;
    font-size:10px;
    letter-spacing:0.18em;
    text-transform:uppercase;
    color:var(--text-dim);
    margin:0 28px 8px;
  }

  /* ---- PEM terminal ---- */
  details{margin:0 28px 28px;}
  summary{
    cursor:pointer;
    font-family:'IBM Plex Mono', monospace;
    font-size:12px;
    letter-spacing:0.05em;
    color:var(--text-muted);
    padding:10px 0;
    list-style:none;
    display:flex;
    align-items:center;
    gap:8px;
  }
  summary::-webkit-details-marker{display:none;}
  summary::before{content:'▸'; color:var(--gold); transition:transform 0.15s ease;}
  details[open] summary::before{transform:rotate(90deg);}
  .pem-box{
    background:#05070A;
    border:1px solid var(--hairline);
    border-radius:10px;
    padding:16px 18px;
    color:var(--teal);
    font-family:'IBM Plex Mono', monospace;
    font-size:11.5px;
    line-height:1.7;
    max-height:220px;
    overflow:auto;
    white-space:pre-wrap;
    word-break:break-all;
  }
</style>
</head>
<body>
<div class="wrap">

  <div class="eyebrow">Digital Identity Inspector</div>
  <h1>Inspeksi Sertifikat SSL/TLS</h1>
  <p class="sub">Buka koneksi langsung ke port 443 domain target, tangkap sertifikat X.509 dari sesi SSL handshake, lalu urai identitas dan penerbitnya &mdash; tanpa mengirim satu pun request HTTP.</p>

  <form method="POST">
    <label for="domain">Domain</label>
    <input type="text" id="domain" name="domain" required placeholder="contoh: google.com" value="<?= htmlspecialchars($domain_input) ?>">
    <button type="submit">Analisis &rarr;</button>
  </form>

  <?php if ($error): ?>
    <div class="error-box">
      <div>
        <b>KONEKSI GAGAL</b><br>
        <?= $error ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($hasil_ssl): ?>
    <div class="card">
      <div class="card-top">
        <div class="seal">
          <div class="seal-icon">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l8 3v6c0 5-3.5 8.5-8 11-4.5-2.5-8-6-8-11V5l8-3z"/><path d="M9 12l2 2 4-4"/></svg>
          </div>
          <?php if ($hari_tersisa !== null && $hari_tersisa < 30): ?>
            <span class="status-pill warn">Kedaluwarsa &lt; 30 hari</span>
          <?php else: ?>
            <span class="status-pill">Sertifikat Aktif</span>
          <?php endif; ?>
          <div class="cn-big"><?= htmlspecialchars(nilai($hasil_ssl['subject'], 'CN', '(tanpa CN)')) ?></div>
          <div class="validation-tag"><?= $is_ov_ev ? 'Organization Validated (OV/EV)' : 'Domain Validated (DV)' ?></div>
        </div>

        <div class="detail-grid">
          <div class="drow">
            <div class="dlabel">Organisasi Pemilik</div>
            <div class="dval"><?= htmlspecialchars(nilai($hasil_ssl['subject'], 'O', 'Tidak terdaftar (DV)')) ?></div>
          </div>
          <div class="drow">
            <div class="dlabel">Diterbitkan Oleh</div>
            <div class="dval"><?= htmlspecialchars(nilai($hasil_ssl['issuer'], 'O', nilai($hasil_ssl['issuer'], 'CN', '-'))) ?></div>
          </div>
          <div class="drow">
            <div class="dlabel">Berlaku Hingga</div>
            <div class="dval">
              <?= date('d M Y, H:i', $hasil_ssl['validTo_time_t']) ?> UTC
              <?php if ($hari_tersisa !== null): ?>
                <span style="color:var(--text-dim)"> &middot; <?= $hari_tersisa ?> hari lagi</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="drow">
            <div class="dlabel">Algoritma Tanda Tangan</div>
            <div class="dval mono"><?= htmlspecialchars(nilai($hasil_ssl, 'signatureTypeSN', '-')) ?></div>
          </div>
          <?php if (!empty($san_list)): ?>
          <div class="drow">
            <div class="dlabel">Domain Tambahan (SAN)</div>
            <div class="dval">
              <div class="san-list">
                <?php foreach (array_slice($san_list, 0, 12) as $san): ?>
                  <span class="san-chip"><?= htmlspecialchars(str_replace('DNS:', '', $san)) ?></span>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <p class="mrz-caption">Zona Identitas &mdash; Serial &amp; Fingerprint SHA-256</p>
      <div class="mrz"><?= 'SERIAL  ' . htmlspecialchars(nilai($hasil_ssl, 'serialNumberHex', nilai($hasil_ssl, 'serialNumber', '-'))) ?>
<?= 'SHA256  ' . htmlspecialchars(nilai($hasil_ssl, 'fingerprint_sha256', '-')) ?></div>

      <details>
        <summary>Tampilkan data sertifikat mentah (.PEM)</summary>
        <div class="pem-box"><?= htmlspecialchars($hasil_ssl['pem_raw']) ?></div>
      </details>
    </div>
  <?php endif; ?>

</div>
</body>
</html>