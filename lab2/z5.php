<?php

$transakcje = [
    ["id"=>1,  "data"=>"2024-01-15","kategoria"=>"Elektronika","kwota"=>1200.00],
    ["id"=>2,  "data"=>"2024-01-22","kategoria"=>"Dom",        "kwota"=>350.00],
    ["id"=>3,  "data"=>"2024-02-03","kategoria"=>"Elektronika","kwota"=>800.00],
    ["id"=>4,  "data"=>"2024-02-14","kategoria"=>"Odzież",     "kwota"=>250.00],
    ["id"=>5,  "data"=>"2024-02-28","kategoria"=>"Dom",        "kwota"=>420.00],
    ["id"=>6,  "data"=>"2024-03-05","kategoria"=>"Elektronika","kwota"=>1500.00],
    ["id"=>7,  "data"=>"2024-03-12","kategoria"=>"Odzież",     "kwota"=>180.00],
    ["id"=>8,  "data"=>"2024-03-19","kategoria"=>"Dom",        "kwota"=>290.00],
    ["id"=>9,  "data"=>"2024-01-08","kategoria"=>"Odzież",     "kwota"=>310.00],
    ["id"=>10, "data"=>"2024-01-30","kategoria"=>"Elektronika","kwota"=>950.00],
    ["id"=>11, "data"=>"2024-02-10","kategoria"=>"Dom",        "kwota"=>600.00],
    ["id"=>12, "data"=>"2024-03-25","kategoria"=>"Odzież",     "kwota"=>430.00],
    ["id"=>13, "data"=>"2024-01-18","kategoria"=>"Elektronika","kwota"=>2100.00],
    ["id"=>14, "data"=>"2024-02-22","kategoria"=>"Dom",        "kwota"=>175.00],
    ["id"=>15, "data"=>"2024-03-08","kategoria"=>"Elektronika","kwota"=>670.00],
    ["id"=>16, "data"=>"2024-01-25","kategoria"=>"Odzież",     "kwota"=>520.00],
    ["id"=>17, "data"=>"2024-02-17","kategoria"=>"Elektronika","kwota"=>1350.00],
    ["id"=>18, "data"=>"2024-03-14","kategoria"=>"Dom",        "kwota"=>480.00],
    ["id"=>19, "data"=>"2024-01-12","kategoria"=>"Dom",        "kwota"=>230.00],
    ["id"=>20, "data"=>"2024-02-05","kategoria"=>"Odzież",     "kwota"=>390.00],
];

$pivot = [];
$kwotyKategorii = [];

foreach ($transakcje as $t) {
    $miesiac = substr($t['data'], 0, 7);
    $kat = $t['kategoria'];
    $kwota = $t['kwota'];

    if (!isset($pivot[$kat][$miesiac])) {
        $pivot[$kat][$miesiac] = 0;
    }
    $pivot[$kat][$miesiac] += $kwota;
    $kwotyKategorii[$kat][] = $kwota;
}

ksort($pivot);

$miesiace = ['2024-01' => 'Styczeń', '2024-02' => 'Luty', '2024-03' => 'Marzec'];

printf("%-14s | %8s | %8s | %8s\n", "Kategoria", "Styczeń", "Luty", "Marzec");
echo str_repeat("-", 46) . "\n";

foreach ($pivot as $kat => $dane) {
    $s = isset($dane['2024-01']) ? $dane['2024-01'] : 0;
    $l = isset($dane['2024-02']) ? $dane['2024-02'] : 0;
    $m = isset($dane['2024-03']) ? $dane['2024-03'] : 0;
    printf("%-14s | %8.2f | %8.2f | %8.2f\n", $kat, $s, $l, $m);
}

echo "\nOdchylenia standardowe (σ):\n";

$maxSigma = 0;
$maxKat = '';

foreach ($kwotyKategorii as $kat => $kwoty) {
    $n = count($kwoty);
    $avg = array_sum($kwoty) / $n;

    $suma = 0;
    foreach ($kwoty as $k) {
        $suma += ($k - $avg) * ($k - $avg);
    }
    $sigma = sqrt($suma / $n);

    printf("  %-13s: σ=%.2f (n=%d, avg=%.2f zł)\n", $kat, $sigma, $n, $avg);

    if ($sigma > $maxSigma) {
        $maxSigma = $sigma;
        $maxKat = $kat;
    }
}

printf("\nKategoria o największej zmienności: %s (σ=%.2f)\n", $maxKat, $maxSigma);
