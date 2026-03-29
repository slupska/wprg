<?php

$oceny = [
    "Anna"    => [5, 4, null, 2, null, 3, 4, 5],
    "Bartek"  => [4, 5, 3, null, 2, 4, null, 4],
    "Celina"  => [5, 3, null, 3, null, 4, 5, null],
    "Dawid"   => [2, null, 4, 5, 3, null, 2, 3],
    "Ewa"     => [null, 4, 3, null, 5, 3, 4, 2],
    "Filip"   => [3, 5, 4, 2, null, 5, null, 4],
    "Grażyna" => [5, null, 2, 4, 3, 2, 5, null],
];
$produkty = ["Laptop", "Monitor", "Klawiatura", "Mysz", "Słuchawki", "Kamera", "Tablet", "Głośnik"];

function pearson($a, $b) {
    $wspolne = [];
    for ($i = 0; $i < 8; $i++) {
        if ($a[$i] !== null && $b[$i] !== null) {
            $wspolne[] = $i;
        }
    }

    if (count($wspolne) < 2) {
        return 0;
    }

    $avgA = 0;
    $avgB = 0;
    foreach ($wspolne as $i) {
        $avgA += $a[$i];
        $avgB += $b[$i];
    }
    $avgA /= count($wspolne);
    $avgB /= count($wspolne);

    $licznik = 0;
    $sumA = 0;
    $sumB = 0;
    foreach ($wspolne as $i) {
        $dA = $a[$i] - $avgA;
        $dB = $b[$i] - $avgB;
        $licznik += $dA * $dB;
        $sumA += $dA * $dA;
        $sumB += $dB * $dB;
    }

    $mianownik = sqrt($sumA * $sumB);
    if ($mianownik == 0) {
        return 0;
    }

    return $licznik / $mianownik;
}

$anna = $oceny["Anna"];
$podobienstwa = [];

foreach ($oceny as $nazwa => $ocena) {
    if ($nazwa === "Anna") continue;
    $podobienstwa[$nazwa] = pearson($anna, $ocena);
}

arsort($podobienstwa);

echo "Podobieństwo Pearsona dla Anny:\n";
foreach ($podobienstwa as $nazwa => $r) {
    printf("  %-10s %s\n", $nazwa . ":", round($r, 4));
}

$k = 3;
$sasiedzi = array_slice($podobienstwa, 0, $k, true);

$sasStr = [];
foreach ($sasiedzi as $nazwa => $r) {
    $sasStr[] = $nazwa . "(" . round($r, 4) . ")";
}
echo "\nk=3 sąsiedzi Anny: " . implode(", ", $sasStr) . "\n";

echo "\nRekomendacje dla Anny (produkty nieocenione):\n";
$rekomendacje = [];

for ($p = 0; $p < 8; $p++) {
    if ($anna[$p] !== null) continue;

    $licznik = 0;
    $mianownik = 0;

    foreach ($sasiedzi as $nazwa => $sim) {
        $ocena = $oceny[$nazwa][$p];
        if ($ocena === null) continue;
        $licznik += $sim * $ocena;
        $mianownik += abs($sim);
    }

    if ($mianownik > 0) {
        $rekomendacje[$produkty[$p]] = $licznik / $mianownik;
    }
}

arsort($rekomendacje);
$nr = 1;
foreach ($rekomendacje as $produkt => $pred) {
    printf("  %d. %-14s — przewidywana ocena: %.2f\n", $nr, $produkt, $pred);
    $nr++;
}

echo "\nZimny start (Hania, 1 ocena):\n";
echo "  Za mało wspólnych ocen z innymi użytkownikami — brak wiarygodnych korelacji.\n";
echo "  Strategia: rekomenduj najpopularniejsze produkty (najwyższa średnia ocen wśród wszystkich).\n";