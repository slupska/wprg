<?php

function sito($n) {
    $A = array_fill(0, $n + 1, true);
    $A[0] = false;
    $A[1] = false;

    for ($i = 2; $i <= sqrt($n); $i++) {
        if ($A[$i]) {
            for ($j = $i * $i; $j <= $n; $j += $i) {
                $A[$j] = false;
            }
        }
    }

    $primes = [];
    for ($i = 2; $i <= $n; $i++) {
        if ($A[$i]) {
            $primes[] = $i;
        }
    }

    return $primes;
}

$primes = sito(500);
$primeSet = array_flip($primes);

echo "Liczby pierwsze [1–100] (bloki po 10):\n";
$do100 = array_filter($primes, function($p) { return $p <= 100; });
$bloki = array_chunk(array_values($do100), 10);
foreach ($bloki as $blok) {
    echo "[" . implode(", ", $blok) . "]\n";
}

echo "\nGęstość liczb pierwszych:\n";
$przedzialy = [
    [1, 100],
    [101, 200],
    [201, 300],
    [301, 400],
    [401, 500],
];

foreach ($przedzialy as $p) {
    $a = $p[0];
    $b = $p[1];
    $count = count(array_filter($primes, function($x) use ($a, $b) {
        return $x >= $a && $x <= $b;
    }));
    $srodek = ($a + $b) / 2;
    $teoretyczna = ($b - $a) / log($srodek);
    echo "Przedział [{$a}–{$b}]:   $count (teoretycznie: ~" . round($teoretyczna, 1) . ")\n";
}

// 3. Hipoteza Goldbacha
echo "\nGoldbach — najwięcej par w [4, 200]:\n";
$maxPary = 0;
$maxLiczba = 0;

for ($n = 4; $n <= 200; $n += 2) {
    $ile = 0;
    for ($p = 2; $p <= $n / 2; $p++) {
        if (isset($primeSet[$p]) && isset($primeSet[$n - $p])) {
            $ile++;
        }
    }
    if ($ile > $maxPary) {
        $maxPary = $ile;
        $maxLiczba = $n;
    }
}

echo "Liczba $maxLiczba ($maxPary par)\n";

echo "Pary Goldbacha dla 30: ";
$pary = [];
for ($p = 2; $p <= 15; $p++) {
    if (isset($primeSet[$p]) && isset($primeSet[30 - $p])) {
        $pary[] = "[$p+" . (30 - $p) . "]";
    }
}
echo implode(", ", $pary) . "\n";
