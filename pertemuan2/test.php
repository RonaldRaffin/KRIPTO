<?php

function fpb($m, $n) {

    while ($n != 0) {
        $kali = floor($m / $n);
        $sisa = $m % $n;

        //8- = 60 x 12 + 8
        echo "$m = $kali x $n  + $sisa <br>";

        $temp = $n;
        $n = $m % $n;
        $m = $temp;
    }
    return $m;
}

$m = 20;
$n = 3;
$hasilfpb = fpb($m, $n);

if($hasilfpb == 1) {
    echo "FPB dari $m dan $n adalah Relatif Prima";
} else {
    echo "FPB dari $m dan $n adalah Bukan Relatif Prima";
}
?>