<?php

$dokumenty = [
    0 => "PHP jest językiem skryptowym używanym do tworzenia stron internetowych",
    1 => "Tablice w PHP mogą być indeksowane lub asocjacyjne i bardzo przydatne",
    2 => "Funkcje array_map i array_filter ułatwiają przetwarzanie tablic w PHP",
    3 => "PHP obsługuje tablice wielowymiarowe i zagnieżdżone struktury danych",
    4 => "Serwer Apache współpracuje z PHP do obsługi żądań HTTP i połączeń",
    5 => "Bazy danych MySQL są często używane razem z PHP do przechowywania",
    6 => "Funkcja usort sortuje tablice w PHP według różnych kryteriów i warunków",
    7 => "JavaScript i PHP razem tworzą dynamiczne aplikacje internetowe i serwisy",
    8 => "PHP posiada wbudowane funkcje do pracy z plikami tablicami i bazami",
    9 => "Bezpieczeństwo aplikacji PHP wymaga walidacji danych wejściowych i filtrów",
];

$stopWords = ['i', 'w', 'na', 'do', 'z', 'są', 'lub', 'być', 'może', 'jest', 'się'];

$index = [];

foreach ($dokumenty as $id => $tekst) {
    $tekst = strtolower($tekst);
    $tekst = preg_replace('/[^a-ząćęłńóśźż ]/', '', $tekst);
    $slowa = explode(' ', $tekst);

    foreach ($slowa as $slowo) {
        if (strlen($slowo) < 3 || in_array($slowo, $stopWords)) {
            continue;
        }
        if (!isset($index[$slowo][$id])) {
            $index[$slowo][$id] = 0;
        }
        $index[$slowo][$id]++;
    }
}

$czestosci = [];
foreach ($index as $slowo => $docs) {
    $czestosci[$slowo] = array_sum($docs);
}
arsort($czestosci);
$top5 = array_slice($czestosci, 0, 5, true);

echo "Top 5 najczęstszych słów:\n";
foreach ($top5 as $slowo => $ile) {
    echo "  '$slowo': {$ile}x\n";
}

function szukajAND($zapytanie, $index) {
    $listaIds = null;
    foreach ($zapytanie as $slowo) {
        if (!isset($index[$slowo])) {
            return [];
        }
        $ids = array_keys($index[$slowo]);
        if ($listaIds === null) {
            $listaIds = $ids;
        } else {
            $listaIds = array_intersect($listaIds, $ids);
        }
    }
    return $listaIds ?? [];
}

function szukajOR($zapytanie, $index) {
    $listaIds = [];
    foreach ($zapytanie as $slowo) {
        if (isset($index[$slowo])) {
            $listaIds = array_merge($listaIds, array_keys($index[$slowo]));
        }
    }
    return array_unique($listaIds);
}

function rankuj($ids, $zapytanie, $index) {
    $wyniki = [];
    foreach ($ids as $id) {
        $score = 0;
        $szczegoly = [];
        foreach ($zapytanie as $slowo) {
            $tf = isset($index[$slowo][$id]) ? $index[$slowo][$id] : 0;
            $score += $tf;
            if ($tf > 0) {
                $szczegoly[] = "$slowo:$tf";
            }
        }
        $wyniki[] = ['id' => $id, 'score' => $score, 'szczegoly' => $szczegoly];
    }
    usort($wyniki, function($a, $b) { return $b['score'] - $a['score']; });
    return $wyniki;
}

$zapytanieAND = ['php', 'tablice'];
$idsAND = szukajAND($zapytanieAND, $index);
$wynikAND = rankuj($idsAND, $zapytanieAND, $index);

echo "\nWyniki dla (php AND tablice):\n";
foreach ($wynikAND as $i => $w) {
    $nr = $i + 1;
    $szcz = implode(', ', $w['szczegoly']);
    echo "  $nr. Dokument ID:{$w['id']} | Score:{$w['score']} ($szcz)\n";
}

$zapytanieOR = ['mysql', 'javascript'];
$idsOR = szukajOR($zapytanieOR, $index);
$wynikOR = rankuj($idsOR, $zapytanieOR, $index);

echo "\nWyniki dla (mysql OR javascript):\n";
foreach ($wynikOR as $i => $w) {
    $nr = $i + 1;
    $szcz = implode(', ', $w['szczegoly']);
    echo "  $nr. Dokument ID:{$w['id']} | Score:{$w['score']} ($szcz)\n";
}