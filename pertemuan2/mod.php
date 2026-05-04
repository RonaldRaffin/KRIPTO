<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Perhitungan Jam</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #0C447C, #378ADD, #85B7EB);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .clock-card {
            background: #ffffff;
            width: 320px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.25);
        }

        .clock-header {
            background: #0C447C;
            padding: 28px 28px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .clock-header::before {
            content: '';
            position: absolute;
            top: -30px; right: -30px;
            width: 100px; height: 100px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
        }

        .clock-header::after {
            content: '';
            position: absolute;
            bottom: -20px; left: -20px;
            width: 70px; height: 70px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
        }

        .clock-icon {
            width: 52px; height: 52px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 24px;
        }

        .clock-title {
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.65);
            letter-spacing: 2.5px;
            text-transform: uppercase;
        }

        .clock-body {
            padding: 24px 28px 28px;
        }

        .clock-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 13px 0;
            border-bottom: 0.5px solid #e5e7eb;
        }

        .clock-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .row-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .row-icon {
            width: 32px; height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
        }

        .icon-blue   { background: #E6F1FB; }
        .icon-amber  { background: #FAEEDA; }
        .icon-purple { background: #EEEDFE; }
        .icon-teal   { background: #E1F5EE; }

        .label-text {
            font-size: 13px;
            color: #6b7280;
        }

        .row-value {
            font-size: 18px;
            font-weight: 500;
            color: #111827;
        }

        .row-unit {
            font-size: 13px;
            color: #9ca3af;
            font-weight: 400;
        }

        .divider-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 16px 0 8px;
        }

        .divider-line {
            flex: 1;
            height: 0.5px;
            background: #e5e7eb;
        }

        .divider-text {
            font-size: 10px;
            color: #9ca3af;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .highlight-row {
            background: #E6F1FB;
            border-radius: 10px;
            padding: 16px !important;
            border-bottom: none !important;
        }

        .highlight-label {
            font-size: 13px;
            color: #185FA5;
            font-weight: 500;
        }

        .highlight-formula {
            font-size: 11px;
            color: #378ADD;
            margin-top: 2px;
        }

        .highlight-value {
            font-size: 28px;
            font-weight: 600;
            color: #0C447C;
        }

        .highlight-unit {
            font-size: 15px;
            color: #378ADD;
            margin-left: 2px;
        }

        .badge {
            display: inline-block;
            background: #E1F5EE;
            color: #0F6E56;
            font-size: 11px;
            font-weight: 500;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 3px;
        }
    </style>
</head>
<body>

<?php
    $jamAwal = 8;
    $durasi  = 50;
    $m       = 24;
    $jamAkhir = ($jamAwal + $durasi) % $m;
?>

<div class="clock-card">
    <div class="clock-header">
        <div class="clock-icon">&#128336;</div>
        <p class="clock-title">Perhitungan Jam</p>
    </div>

    <div class="clock-body">

        <div class="clock-row">
            <div class="row-label">
                <div class="row-icon icon-blue">&#9200;</div>
                <span class="label-text">Jam awal</span>
            </div>
            <div class="row-value"><?= sprintf('%02d', $jamAwal) ?>:00</div>
        </div>

        <div class="clock-row">
            <div class="row-label">
                <div class="row-icon icon-amber">&#9202;</div>
                <span class="label-text">Durasi</span>
            </div>
            <div class="row-value"><?= $durasi ?> <span class="row-unit">jam</span></div>
        </div>

        <div class="clock-row">
            <div class="row-label">
                <div class="row-icon icon-purple">&#8987;</div>
                <span class="label-text">Format</span>
            </div>
            <div style="text-align:right;">
                <div class="row-value"><?= $m ?> <span class="row-unit">jam</span></div>
                <div class="badge">Modulo</div>
            </div>
        </div>

        <div class="divider-wrap">
            <div class="divider-line"></div>
            <span class="divider-text">Hasil</span>
            <div class="divider-line"></div>
        </div>

        <div class="clock-row highlight-row">
            <div class="row-label">
                <div class="row-icon icon-teal">&#9989;</div>
                <div>
                    <div class="highlight-label">Jam akhir</div>
                    <div class="highlight-formula">(<?= $jamAwal ?> + <?= $durasi ?>) mod <?= $m ?></div>
                </div>
            </div>
            <div>
                <span class="highlight-value"><?= sprintf('%02d', $jamAkhir) ?></span>
                <span class="highlight-unit">:00</span>
            </div>
        </div>

    </div>
</div>

</body>
</html>