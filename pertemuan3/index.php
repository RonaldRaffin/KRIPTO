<?php
// ============================================================
//  CIPHER ENGINE
// ============================================================

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

// ============================================================
//  REQUEST HANDLER
// ============================================================
$output    = '';
$error     = '';
$plaintext = '';
$keyVal    = '';
$algorithm = 'caesar';
$action    = 'encrypt';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plaintext = $_POST['message']   ?? '';
    $keyVal    = $_POST['key']       ?? '';
    $algorithm = $_POST['algorithm'] ?? 'caesar';
    $action    = $_POST['action']    ?? 'encrypt';
    $isEncrypt = ($action === 'encrypt');

    if (trim($plaintext) === '') {
        $error = 'Message field cannot be empty.';
    } elseif ($algorithm === 'caesar') {
        if (!is_numeric($keyVal)) {
            $error = 'Caesar key must be a numeric integer.';
        } else {
            $output = caesar_cipher($plaintext, (int)$keyVal, $isEncrypt);
        }
    } else {
        $cleanKey = preg_replace('/\s/', '', $keyVal);
        if (trim($keyVal) === '' || !ctype_alpha($cleanKey)) {
            $error = 'Vigenère keyword must contain letters only.';
        } else {
            $output = vigenere_cipher($plaintext, $keyVal, $isEncrypt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Arcanum — Classical Cipher Suite</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=Instrument+Mono:ital@0;1&family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --obsidian:  #07080d;
  --void:      #0d0e15;
  --panel:     rgba(255,255,255,.032);
  --border:    rgba(255,255,255,.07);
  --border-hi: rgba(212,175,96,.35);
  --gold:      #d4af60;
  --gold-dim:  #a8893e;
  --gold-pale: rgba(212,175,96,.10);
  --silver:    #9eaab8;
  --mist:      rgba(158,170,184,.5);
  --white:     #e8eaf0;
  --ff-serif:   'Cormorant Garamond', Georgia, serif;
  --ff-display: 'Cinzel', serif;
  --ff-mono:    'Instrument Mono', monospace;
  --ease-lux:  cubic-bezier(.22,.68,0,1.2);
  --ease-silk: cubic-bezier(.4,0,.2,1);
}

*,*::before,*::after { box-sizing:border-box; margin:0; padding:0; }
html { font-size:16px; scroll-behavior:smooth; }

body {
  background: var(--obsidian);
  color: var(--white);
  font-family: var(--ff-serif);
  min-height: 100vh;
  overflow-x: hidden;
}

/* ── LAYERED BACKGROUND ── */
.bg-bloom {
  position: fixed; inset: 0; z-index: 0;
  pointer-events: none;
  overflow: hidden;
}
.bg-bloom::before {
  content: '';
  position: absolute;
  width: 1000px; height: 1000px;
  top: -35%; left: -20%;
  background: radial-gradient(circle, rgba(212,175,96,.055) 0%, transparent 62%);
  animation: breathe 9s ease-in-out infinite alternate;
}
.bg-bloom::after {
  content: '';
  position: absolute;
  width: 800px; height: 800px;
  bottom: -25%; right: -15%;
  background: radial-gradient(circle, rgba(55,80,155,.07) 0%, transparent 60%);
  animation: breathe 11s ease-in-out infinite alternate-reverse;
}
@keyframes breathe {
  from { transform: scale(1);    opacity: .7; }
  to   { transform: scale(1.1);  opacity: 1;  }
}

.bg-grid {
  position: fixed; inset: 0; z-index: 1;
  pointer-events: none;
  background-image:
    linear-gradient(rgba(212,175,96,.022) 1px, transparent 1px),
    linear-gradient(90deg, rgba(212,175,96,.022) 1px, transparent 1px);
  background-size: 72px 72px;
}

.bg-noise {
  position: fixed; inset: 0; z-index: 2;
  pointer-events: none; opacity: .03;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
  background-size: 200px;
}

/* ── PAGE ── */
.wrap {
  position: relative; z-index: 10;
  max-width: 800px;
  margin: 0 auto;
  padding: 0 2rem 6rem;
}

/* ── HEADER ── */
header {
  padding: 5.5rem 0 3.5rem;
  text-align: center;
  animation: rise .8s var(--ease-lux) .05s both;
}

.eyebrow {
  font-family: var(--ff-mono);
  font-size: .58rem;
  letter-spacing: .35em;
  text-transform: uppercase;
  color: var(--gold-dim);
  display: block;
  margin-bottom: 1.4rem;
}

.logo-orn {
  display: flex; align-items: center;
  justify-content: center; gap: 1.4rem;
  margin-bottom: 1.6rem;
}
.orn-line {
  height: 1px; width: 100px;
  background: linear-gradient(90deg, transparent, var(--gold-dim));
}
.orn-line.r { background: linear-gradient(90deg, var(--gold-dim), transparent); }
.orn-gem {
  width: 9px; height: 9px;
  border: 1px solid var(--gold);
  transform: rotate(45deg);
  position: relative;
}
.orn-gem::after {
  content: ''; position: absolute; inset: 2px;
  background: var(--gold);
}

h1 {
  font-family: var(--ff-display);
  font-size: clamp(3rem, 10vw, 5.6rem);
  font-weight: 400;
  letter-spacing: .22em;
  text-transform: uppercase;
  color: var(--white);
  line-height: 1;
  position: relative;
  display: inline-block;
}
/* Shimmer sweep */
h1::after {
  content: attr(data-t);
  position: absolute; inset: 0;
  background: linear-gradient(110deg, transparent 20%, rgba(212,175,96,.9) 50%, transparent 80%);
  -webkit-background-clip: text; background-clip: text;
  -webkit-text-fill-color: transparent;
  background-size: 300% 100%;
  animation: shimmer 3.5s ease .6s forwards;
}
@keyframes shimmer {
  0%   { background-position: 200% center; opacity:0; }
  15%  { opacity:1; }
  70%  { background-position: -80% center; opacity:1; }
  100% { opacity:0; }
}

.sub {
  margin-top: 1.2rem;
  font-family: var(--ff-serif);
  font-style: italic; font-weight: 300;
  font-size: 1rem;
  color: var(--mist);
  letter-spacing: .08em;
}

/* ── GLASS CARD ── */
.card {
  background: var(--panel);
  backdrop-filter: blur(20px) saturate(1.3);
  -webkit-backdrop-filter: blur(20px) saturate(1.3);
  border: 1px solid var(--border);
  border-radius: 2px;
  position: relative;
  overflow: hidden;
  transition: border-color .4s var(--ease-silk);
  margin-bottom: 1.2rem;
  animation: rise .7s var(--ease-lux) both;
}
.card:nth-of-type(2) { animation-delay: .18s; }
.card:nth-of-type(3) { animation-delay: .3s;  }

@keyframes rise {
  from { opacity:0; transform:translateY(28px); }
  to   { opacity:1; transform:translateY(0); }
}

/* Corner brackets */
.card::before, .card::after {
  content: ''; position: absolute;
  width: 16px; height: 16px;
  border-color: rgba(212,175,96,.4);
  border-style: solid;
}
.card::before { top:-1px; left:-1px; border-width:1px 0 0 1px; }
.card::after  { bottom:-1px; right:-1px; border-width:0 1px 1px 0; }

.card:hover { border-color: rgba(212,175,96,.18); }

.card-body { padding: 2.4rem 2.8rem; }

.section-label {
  font-family: var(--ff-mono);
  font-size: .56rem;
  letter-spacing: .3em;
  text-transform: uppercase;
  color: var(--gold-dim);
  display: flex; align-items: center; gap: .9rem;
  margin-bottom: 2rem;
}
.section-label::before {
  content: ''; width: 4px; height: 4px;
  background: var(--gold); border-radius: 50%;
}
.section-label::after {
  content: ''; flex:1; height:1px;
  background: linear-gradient(90deg, rgba(212,175,96,.3), transparent);
}

/* ── ALGO TABS ── */
.algo-wrap {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1px; background: var(--border);
  border: 1px solid var(--border);
  border-radius: 1px; overflow: hidden;
  margin-bottom: 2.2rem;
}
.algo-r { display: none; }
.algo-tab {
  padding: 1.1rem 1.4rem;
  background: rgba(0,0,0,.4);
  cursor: pointer;
  display: flex; flex-direction: column; gap: .25rem;
  position: relative; overflow: hidden;
  transition: background .3s var(--ease-silk);
}
.algo-tab::after {
  content: ''; position: absolute;
  bottom:0; left:0; right:0; height:1px;
  background: var(--gold);
  transform: scaleX(0); transform-origin: left;
  transition: transform .45s var(--ease-lux);
}
.algo-r:checked + .algo-tab { background: var(--gold-pale); }
.algo-r:checked + .algo-tab::after { transform: scaleX(1); }

.tab-name {
  font-family: var(--ff-display);
  font-size: .78rem; font-weight:400;
  letter-spacing: .12em; text-transform: uppercase;
  color: rgba(158,170,184,.6);
  transition: color .3s;
}
.algo-r:checked + .algo-tab .tab-name { color: var(--gold); }
.tab-hint {
  font-family: var(--ff-mono); font-size: .56rem;
  letter-spacing: .08em;
  color: rgba(158,170,184,.28);
  transition: color .3s;
}
.algo-r:checked + .algo-tab .tab-hint { color: rgba(212,175,96,.45); }
.tab-pip {
  position: absolute; top: .6rem; right: .8rem;
  font-family: var(--ff-mono); font-size: .48rem;
  letter-spacing: .1em; text-transform: uppercase;
  padding: .15rem .48rem;
  border: 1px solid rgba(158,170,184,.2);
  border-radius: 1px; color: rgba(158,170,184,.45);
  transition: all .3s;
}
.pip-adv { border-color: rgba(212,175,96,.22); color: var(--gold-dim); }
.algo-r:checked + .algo-tab .pip-adv {
  border-color: rgba(212,175,96,.55); color: var(--gold);
}

/* ── FIELDS ── */
.field { margin-bottom: 1.7rem; }
.fl {
  display: flex; align-items: baseline; gap:.6rem;
  margin-bottom: .65rem;
}
.fl .lbl {
  font-family: var(--ff-mono); font-size: .58rem;
  letter-spacing: .22em; text-transform: uppercase;
  color: var(--silver);
}
.fl .hint {
  font-family: var(--ff-serif); font-style: italic;
  font-size: .8rem; font-weight: 300; color: var(--mist);
}

textarea, input[type="number"], input[type="text"] {
  width: 100%;
  background: rgba(0,0,0,.38);
  border: 1px solid var(--border);
  border-radius: 1px;
  padding: .95rem 1.1rem;
  font-family: var(--ff-mono);
  font-size: .8rem;
  color: var(--white);
  outline: none; resize: vertical;
  transition: border-color .3s, box-shadow .3s, background .3s;
  letter-spacing: .04em; line-height: 1.85;
}
textarea { min-height: 140px; }
textarea::placeholder, input::placeholder {
  color: rgba(158,170,184,.22); font-style: italic;
}
textarea:focus, input:focus {
  border-color: rgba(212,175,96,.42);
  background: rgba(212,175,96,.025);
  box-shadow: 0 0 0 1px rgba(212,175,96,.08), 0 12px 40px rgba(0,0,0,.5);
}

/* ── DIVIDER ── */
.div {
  display: flex; align-items: center; gap: 1rem;
  margin: 2rem 0; opacity: .3;
}
.div::before,.div::after { content:''; flex:1; height:1px; background:var(--border); }
.div span {
  font-family: var(--ff-mono); font-size: .5rem;
  letter-spacing: .22em; text-transform: uppercase;
  color: var(--mist);
}

/* ── BUTTONS ── */
.btns { display: grid; grid-template-columns: 1fr 1fr; gap: .9rem; }

.btn {
  display: flex; align-items: center;
  justify-content: center; gap: .6rem;
  padding: 1.05rem 1.6rem;
  border: 1px solid; border-radius: 1px;
  font-family: var(--ff-display); font-size: .68rem;
  font-weight: 400; letter-spacing: .22em;
  text-transform: uppercase; cursor: pointer;
  position: relative; overflow: hidden;
  transition: color .35s, box-shadow .35s, transform .2s var(--ease-lux);
}
.btn::before {
  content: ''; position: absolute; inset:0;
  transform: scaleX(0); transform-origin: left;
  transition: transform .4s var(--ease-silk); z-index:0;
}
.btn:hover::before { transform: scaleX(1); }
.btn > * { position: relative; z-index:1; }
.btn:active { transform: scale(.98); }
.btn svg { width: 13px; height: 13px; opacity: .8; }

.btn-enc {
  background: var(--gold-pale);
  border-color: rgba(212,175,96,.3);
  color: var(--gold);
}
.btn-enc::before { background: var(--gold); }
.btn-enc:hover {
  color: var(--obsidian);
  border-color: var(--gold);
  box-shadow: 0 8px 40px rgba(212,175,96,.22), 0 0 0 1px var(--gold);
}
.btn-dec {
  background: transparent;
  border-color: var(--border);
  color: var(--silver);
}
.btn-dec::before { background: rgba(158,170,184,.1); }
.btn-dec:hover {
  color: var(--white); border-color: rgba(158,170,184,.38);
  box-shadow: 0 8px 40px rgba(0,0,0,.4);
}

/* ── ERROR ── */
.err {
  display: flex; align-items: center; gap: .8rem;
  padding: .85rem 1.1rem;
  background: rgba(130,50,60,.1);
  border: 1px solid rgba(160,60,70,.3);
  border-radius: 1px; margin-bottom: 1.4rem;
  animation: rise .3s var(--ease-silk) both;
}
.err svg { width:14px; height:14px; color:#c45560; flex-shrink:0; }
.err p { font-family: var(--ff-mono); font-size: .68rem; letter-spacing:.05em; color:#c46070; }

/* ── OUTPUT ── */
.out-card { animation: rise .5s var(--ease-lux) both !important; }

.out-top {
  display: flex; align-items: center;
  justify-content: space-between; margin-bottom: .8rem;
}

.out-badge {
  display: inline-flex; align-items: center; gap: .45rem;
  font-family: var(--ff-mono); font-size: .52rem;
  letter-spacing: .22em; text-transform: uppercase;
  padding: .28rem .7rem;
  border: 1px solid; border-radius: 1px;
}
.ob-enc { color: var(--gold);   border-color: rgba(212,175,96,.3); background: rgba(212,175,96,.06); }
.ob-dec { color: #8dd5b4; border-color: rgba(141,213,180,.28); background: rgba(141,213,180,.05); }

.badge-dot {
  width: 5px; height: 5px; border-radius: 50%;
  animation: blink 1.8s ease-in-out infinite;
}
.ob-enc .badge-dot { background: var(--gold); }
.ob-dec .badge-dot { background: #8dd5b4; }
@keyframes blink { 0%,100% { opacity:.35; } 50% { opacity:1; } }

.copy {
  font-family: var(--ff-mono); font-size: .52rem;
  letter-spacing: .2em; text-transform: uppercase;
  color: var(--mist); background: transparent;
  border: 1px solid var(--border); border-radius: 1px;
  padding: .28rem .85rem; cursor: pointer;
  display: flex; align-items: center; gap: .38rem;
  transition: all .25s var(--ease-silk);
}
.copy:hover { color: var(--gold); border-color: rgba(212,175,96,.3); }
.copy.ok { color:#8dd5b4; border-color:rgba(141,213,180,.3); background:rgba(141,213,180,.05); }
.copy svg { width: 11px; height: 11px; }

.out-box {
  background: rgba(0,0,0,.55);
  border: 1px solid var(--border);
  border-radius: 1px; padding: 1.5rem;
  font-family: var(--ff-mono); font-size: .85rem;
  line-height: 1.95; letter-spacing: .06em;
  color: var(--gold); word-break: break-all;
  min-height: 70px; position: relative;
  box-shadow: inset 0 0 50px rgba(0,0,0,.25);
}
.out-box::after {
  content: ''; position: absolute; inset:0; pointer-events:none;
  background: repeating-linear-gradient(
    0deg, transparent, transparent 30px,
    rgba(212,175,96,.018) 30px, rgba(212,175,96,.018) 31px);
}

/* ── REF GRID ── */
.ref-grid {
  display: grid; grid-template-columns: 1fr 1fr;
  gap: 1px; background: var(--border);
  border: 1px solid var(--border);
  border-radius: 2px; overflow: hidden;
  margin-top: 1.2rem;
  animation: rise .7s var(--ease-lux) .38s both;
}
.ref-cell {
  background: var(--void); padding: 1.8rem;
  position: relative; overflow: hidden;
  transition: background .35s;
}
.ref-cell:hover { background: rgba(212,175,96,.022); }
.ref-cell::before {
  content: ''; position: absolute;
  top:0; left:1.8rem; right:1.8rem; height:1px;
  background: linear-gradient(90deg, transparent, rgba(212,175,96,.3), transparent);
}
.ref-num {
  font-family: var(--ff-display); font-size: 3rem;
  font-weight: 400; color: rgba(212,175,96,.055);
  position: absolute; top:.6rem; right:1.1rem;
  line-height:1; pointer-events:none; letter-spacing:.1em;
}
.ref-name {
  font-family: var(--ff-display); font-size: .85rem;
  font-weight: 600; letter-spacing: .14em;
  text-transform: uppercase; color: var(--white);
  margin-bottom: .3rem;
}
.ref-name em {
  font-family: var(--ff-serif); font-style: italic;
  font-size: .78rem; color: var(--gold-dim);
  font-weight: 300; display: block;
  letter-spacing: .04em; margin-top: .15rem;
}
.ref-desc {
  font-family: var(--ff-serif); font-size: .84rem;
  font-weight: 300; color: var(--mist);
  line-height: 1.75; margin: .65rem 0 .9rem;
}
.ref-formula {
  font-family: var(--ff-mono); font-size: .58rem;
  color: var(--gold-dim);
  background: rgba(212,175,96,.055);
  border: 1px solid rgba(212,175,96,.1);
  padding: .55rem .85rem; border-radius: 1px;
  line-height: 2; letter-spacing: .06em;
}

/* ── FOOTER ── */
footer {
  position: relative; z-index: 10;
  text-align: center; padding: 3rem 0;
  display: flex; flex-direction: column;
  align-items: center; gap: .8rem;
}
.ft-orn { display:flex; align-items:center; gap:1rem; opacity:.2; }
.ft-orn::before,.ft-orn::after { content:''; width:50px; height:1px; background:var(--gold); }
footer p {
  font-family: var(--ff-mono); font-size: .52rem;
  letter-spacing: .25em; text-transform: uppercase;
  color: rgba(158,170,184,.25);
}

/* ── RESPONSIVE ── */
@media (max-width:560px) {
  .algo-wrap, .btns, .ref-grid { grid-template-columns: 1fr; }
  .card-body { padding: 1.5rem; }
  h1 { font-size: 2.6rem; }
}
</style>
</head>
<body>

<div class="bg-bloom"></div>
<div class="bg-grid"></div>
<div class="bg-noise"></div>

<div class="wrap">

  <!-- HEADER -->
  <header>
    <span class="eyebrow">Classical Cryptography Suite</span>
    <div class="logo-orn">
      <div class="orn-line"></div>
      <div class="orn-gem"></div>
      <div class="orn-line r"></div>
    </div>
    <h1 data-t="ARCANUM">ARCANUM</h1>
    <p class="sub">The art of concealment, rendered with precision</p>
  </header>

  <!-- ERROR -->
  <?php if ($error): ?>
  <div class="err">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
      <path d="M12 9v4m0 3.5h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
    </svg>
    <p><?= htmlspecialchars($error) ?></p>
  </div>
  <?php endif; ?>

  <!-- FORM CARD -->
  <div class="card">
    <div class="card-body">
      <div class="section-label">Cipher Configuration</div>

      <form method="POST" id="cf">

        <!-- Algorithm tabs -->
        <div class="algo-wrap">
          <input type="radio" name="algorithm" id="ac" value="caesar"
            class="algo-r" <?= $algorithm==='caesar'?'checked':'' ?>>
          <label for="ac" class="algo-tab">
            <span class="tab-pip">Classic</span>
            <span class="tab-name">Caesar</span>
            <span class="tab-hint">Monoalphabetic · Numeric key</span>
          </label>

          <input type="radio" name="algorithm" id="av" value="vigenere"
            class="algo-r" <?= $algorithm==='vigenere'?'checked':'' ?>>
          <label for="av" class="algo-tab">
            <span class="tab-pip pip-adv">Advanced</span>
            <span class="tab-name">Vigenère</span>
            <span class="tab-hint">Polyalphabetic · Keyword</span>
          </label>
        </div>

        <!-- Message -->
        <div class="field">
          <div class="fl">
            <span class="lbl">Message</span>
            <span class="hint">— spaces &amp; digits pass through unchanged</span>
          </div>
          <textarea id="msg" name="message"
            placeholder="Enter plaintext or ciphertext…"><?= htmlspecialchars($plaintext) ?></textarea>
        </div>

        <!-- Caesar key -->
        <div class="field" id="fC" style="<?= $algorithm==='vigenere'?'display:none':'' ?>">
          <div class="fl">
            <span class="lbl">Shift Key</span>
            <span class="hint">— integer, e.g. 13 for ROT-13</span>
          </div>
          <input type="number" id="kN" name="key" min="-25" max="25"
            placeholder="e.g. 3"
            value="<?= $algorithm==='caesar'?htmlspecialchars($keyVal):'' ?>">
        </div>

        <!-- Vigenère key -->
        <div class="field" id="fV" style="<?= $algorithm==='caesar'?'display:none':'' ?>">
          <div class="fl">
            <span class="lbl">Keyword</span>
            <span class="hint">— letters only, e.g. LEMON</span>
          </div>
          <input type="text" id="kW" name="key" autocomplete="off"
            placeholder="e.g. ARCANUM"
            value="<?= $algorithm==='vigenere'?htmlspecialchars($keyVal):'' ?>">
        </div>

        <div class="div"><span>execute</span></div>

        <div class="btns">
          <button type="submit" name="action" value="encrypt" class="btn btn-enc">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
            <span>Encrypt</span>
          </button>
          <button type="submit" name="action" value="decrypt" class="btn btn-dec">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <rect x="3" y="11" width="18" height="11" rx="2"/>
              <path d="M7 11V7a5 5 0 019.9-1"/>
            </svg>
            <span>Decrypt</span>
          </button>
        </div>

      </form>
    </div>
  </div>

  <!-- OUTPUT -->
  <?php if ($output !== '' && !$error): ?>
  <div class="card out-card">
    <div class="card-body">
      <div class="section-label">Output</div>
      <div class="out-top">
        <div class="out-badge <?= $action==='encrypt'?'ob-enc':'ob-dec' ?>">
          <span class="badge-dot"></span>
          <?= $action==='encrypt' ? 'Encrypted' : 'Decrypted' ?>
          &nbsp;·&nbsp;
          <?= $algorithm==='caesar' ? 'Caesar Cipher' : 'Vigenère Cipher' ?>
        </div>
        <button class="copy" id="cpBtn" onclick="doCopy(event)">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="9" y="9" width="13" height="13" rx="2"/>
            <path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/>
          </svg>
          Copy
        </button>
      </div>
      <div class="out-box" id="outTxt"><?= htmlspecialchars($output) ?></div>
    </div>
  </div>
  <?php endif; ?>

  <!-- REFERENCE CARDS -->
  <div class="ref-grid">
    <div class="ref-cell">
      <div class="ref-num">Ⅰ</div>
      <div class="ref-name">
        Caesar Cipher
        <em>Julius Caesar, 58 BC</em>
      </div>
      <p class="ref-desc">Each letter is shifted a fixed number of positions through the alphabet. Non-alphabetic characters remain untouched.</p>
      <div class="ref-formula">E(x) = (x + k) mod 26<br>D(x) = (x − k + 26) mod 26</div>
    </div>
    <div class="ref-cell">
      <div class="ref-num">Ⅱ</div>
      <div class="ref-name">
        Vigenère Cipher
        <em>Giovan Battista Bellaso, 1553</em>
      </div>
      <p class="ref-desc">A polyalphabetic cipher driven by a repeating keyword, defeating simple frequency analysis through variable shift lengths.</p>
      <div class="ref-formula">Eᵢ(x) = (xᵢ + kᵢ) mod 26<br>Dᵢ(x) = (xᵢ − kᵢ + 26) mod 26</div>
    </div>
  </div>

</div>

<footer>
  <div class="ft-orn">
    <div class="orn-gem" style="width:7px;height:7px"></div>
  </div>

  <p>Arcanum · Classical Cipher Suite · Caesar & Vigenère</p>

  <p style="margin-top:10px; font-size:12px; letter-spacing:1px;">
    Nama : Ronald Raffin <br>
    NIM  : 231220032
  </p>
</footer>
<script>
/* Algorithm toggle */
document.querySelectorAll('input[name="algorithm"]').forEach(r => {
  r.addEventListener('change', () => {
    const isCaesar = r.value === 'caesar';
    document.getElementById('fC').style.display = isCaesar ? '' : 'none';
    document.getElementById('fV').style.display = isCaesar ? 'none' : '';
    document.getElementById('kN').name = isCaesar ? 'key' : '_kn';
    document.getElementById('kW').name = isCaesar ? '_kw' : 'key';
  });
});

/* Copy */
function doCopy(e) {
  e.preventDefault();
  const t = document.getElementById('outTxt')?.innerText.trim();
  if (!t) return;
  navigator.clipboard.writeText(t).then(() => {
    const b = document.getElementById('cpBtn');
    b.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><path d="M20 6L9 17l-5-5"/></svg> Copied`;
    b.classList.add('ok');
    setTimeout(() => {
      b.innerHTML = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="11" height="11"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Copy`;
      b.classList.remove('ok');
    }, 2400);
  });
}
</script>
</body>
</html>