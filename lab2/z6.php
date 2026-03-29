<?php

$zadania = [
    ["id"=>1,  "nazwa"=>"T01", "start"=>480,  "koniec"=>600],
    ["id"=>2,  "nazwa"=>"T02", "start"=>510,  "koniec"=>720],
    ["id"=>3,  "nazwa"=>"T03", "start"=>540,  "koniec"=>660],
    ["id"=>4,  "nazwa"=>"T04", "start"=>600,  "koniec"=>690],
    ["id"=>5,  "nazwa"=>"T05", "start"=>660,  "koniec"=>780],
    ["id"=>6,  "nazwa"=>"T06", "start"=>690,  "koniec"=>840],
    ["id"=>7,  "nazwa"=>"T07", "start"=>720,  "koniec"=>810],
    ["id"=>8,  "nazwa"=>"T08", "start"=>780,  "koniec"=>900],
    ["id"=>9,  "nazwa"=>"T09", "start"=>840,  "koniec"=>960],
    ["id"=>10, "nazwa"=>"T10", "start"=>480,  "koniec"=>540],
    ["id"=>11, "nazwa"=>"T11", "start"=>570,  "koniec"=>630],
    ["id"=>12, "nazwa"=>"T12", "start"=>750,  "koniec"=>870],
    ["id"=>13, "nazwa"=>"T13", "start"=>900,  "koniec"=>990],
    ["id"=>14, "nazwa"=>"T14", "start"=>495,  "koniec"=>555],
    ["id"=>15, "nazwa"=>"T15", "start"=>870,  "koniec"=>930],
];

function minutyNaCzas($m) {
    $h = (int)($m / 60);
    $min = $m % 60;
    return $h . ":" . str_pad($min, 2, "0", STR_PAD_LEFT);
}

$posortowane = $zadania;
usort($posortowane, function($a, $b) { return $a['koniec'] - $b['koniec']; });

$wybrane = [];
$ostatniKoniec = -1;

foreach ($posortowane as $z) {
    if ($z['start'] >= $ostatniKoniec) {
        $wybrane[] = $z;
        $ostatniKoniec = $z['koniec'];
    }
}

$nazwy = array_map(function($z) { return $z['nazwa']; }, $wybrane);
echo "Algorytm zachłanny (jedna sala):\n";
echo "  Wybrane zadania (" . count($wybrane) . "): " . implode(", ", $nazwy) . "\n";

$kolejnosc = array_map(function($z) {
    return $z['nazwa'] . "(" . minutyNaCzas($z['start']) . "–" . minutyNaCzas($z['koniec']) . ")";
}, $wybrane);
echo "  Kolejność decyzji: " . implode(" → ", $kolejnosc) . "\n";

echo "\nKonflikty:\n";
$konflikty = [];
foreach ($zadania as $a) {
    $ile = 0;
    foreach ($zadania as $b) {
        if ($a['id'] === $b['id']) continue;
        if (max($a['start'], $b['start']) < min($a['koniec'], $b['koniec'])) {
            $ile++;
        }
    }
    $konflikty[$a['nazwa']] = $ile;
}

$maxKonflikty = max($konflikty);
$najbardziej = array_search($maxKonflikty, $konflikty);
echo "  Najbardziej konfliktowe: $najbardziej ($maxKonflikty kolizji z innymi zadaniami)\n";

$posortowane2 = $zadania;
usort($posortowane2, function($a, $b) { return $a['start'] - $b['start']; });

$sale = [];

foreach ($posortowane2 as $z) {
    $przypisano = false;
    foreach ($sale as &$sala) {
        $ostatnie = $sala[count($sala) - 1];
        if ($ostatnie['koniec'] <= $z['start']) {
            $sala[] = $z;
            $przypisano = true;
            break;
        }
    }
    unset($sala);
    if (!$przypisano) {
        $sale[] = [$z];
    }
}

echo "\nMinimalna liczba sal: " . count($sale) . "\n";
foreach ($sale as $nr => $sala) {
    $zadaniaSali = array_map(function($z) {
        return $z['nazwa'] . "(" . minutyNaCzas($z['start']) . "–" . minutyNaCzas($z['koniec']) . ")";
    }, $sala);
    echo "  Sala " . ($nr + 1) . ": " . implode(", ", $zadaniaSali) . "\n";
}