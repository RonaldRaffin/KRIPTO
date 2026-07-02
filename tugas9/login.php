<?php
$host    = "localhost";
$user_db = "root";
$pass_db = "";
$db_name = "keamanan_db";

$conn = new mysqli($host, $user_db, $pass_db, $db_name);

$pesan = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input = $_POST['username'];
    $pass_input = $_POST['password'];

    // ✅ KODE AMAN (SECURE) - PREPARED STATEMENTS MYSQLI

    // 1. Siapkan cetakan Query dengan tanda tanya (?) sebagai tempat variabel
    $sql_aman = "SELECT * FROM users WHERE username = ? AND password = ?";

    // 2. Kirim kerangka query ke MySQL untuk di-prepare (dikunci strukturnya)
    $stmt = $conn->prepare($sql_aman);

    // 3. Binding Parameter: "ss" artinya dua inputan tersebut adalah String
    $stmt->bind_param("ss", $user_input, $pass_input);

    // 4. Eksekusi query dengan data aman yang baru disisipkan
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row   = $result->fetch_assoc();
        $pesan = "✅ Berhasil Login! Selamat datang, Role: " . $row['role'];
    } else {
        $pesan = "❌ Gagal Login! Sistem memblokir injeksi.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Login Perusahaan</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Inter:wght@400;600;700&display=swap');

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #0a0a0f;
            color: #e8e6f0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background-image: radial-gradient(ellipse 60% 40% at 50% 0%, rgba(67,160,71,0.07) 0%, transparent 70%);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(67,160,71,0.12);
            border: 1px solid #1b4d1b;
            color: #43a047;
            font-family: 'Share Tech Mono', monospace;
            font-size: 11px;
            letter-spacing: 0.12em;
            padding: 5px 14px;
            border-radius: 3px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .badge::before { content: '⬤'; font-size: 8px; }

        .card {
            width: 100%;
            max-width: 440px;
            background: #131a13;
            border: 1px solid #1a2e1a;
            border-top: 3px solid #43a047;
            border-radius: 6px;
            padding: 36px 32px 28px;
            box-shadow: 0 0 40px rgba(67,160,71,0.18), 0 8px 32px rgba(0,0,0,0.5);
        }

        h2 {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .subtitle {
            font-family: 'Share Tech Mono', monospace;
            font-size: 12px;
            color: #43a047;
            margin-bottom: 28px;
        }

        .pesan {
            padding: 12px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .pesan.success {
            background: rgba(76,175,80,0.1);
            border: 1px solid #2e7d32;
            color: #81c784;
        }
        .pesan.error {
            background: rgba(229,57,53,0.08);
            border: 1px solid #7c1010;
            color: #ef9a9a;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #7a7a9a;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 7px;
        }

        input[type="text"] {
            width: 100%;
            background: #0d0d14;
            border: 1px solid #2e1e1e;
            border-radius: 4px;
            color: #e8e6f0;
            font-family: 'Share Tech Mono', monospace;
            font-size: 14px;
            padding: 10px 14px;
            margin-bottom: 18px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input[type="text"]:focus {
            border-color: #43a047;
            box-shadow: 0 0 0 3px rgba(67,160,71,0.15);
        }

        button {
            width: 100%;
            background: #43a047;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s, box-shadow 0.2s;
        }
        button:hover { background: #2e7d32; box-shadow: 0 0 16px rgba(67,160,71,0.4); }

        .footer {
            margin-top: 28px;
            font-family: 'Share Tech Mono', monospace;
            font-size: 11px;
            color: #3a3a5a;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="badge">PREPARED STATEMENTS · PATCHED</div>

    <div class="card">
        <h2>Sistem Login Perusahaan</h2>
        <div class="subtitle">// login.php — SUDAH DIAMANKAN</div>

        <?php if ($pesan !== ""): ?>
            <div class="pesan <?= strpos($pesan, 'Berhasil') !== false ? 'success' : 'error' ?>">
                <?= $pesan ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" style="width:300px; padding:8px;">

            <label for="password">Password</label>
            <input type="text" id="password" name="password" style="width:300px; padding:8px;">

            <button type="submit">Masuk</button>
        </form>
    </div>

    <div class="footer">KEAMANAN WEB · EDUCATIONAL USE ONLY</div>

</body>
</html>