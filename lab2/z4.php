<?php

function s_push(array &$stos, $val): void {
    array_splice($stos, count($stos), 0, [$val]);
}

function s_pop(array &$stos) {
    $top = $stos[count($stos) - 1];
    array_splice($stos, -1, 1);
    return $top;
}

function s_peek(array $stos) {
    return $stos[count($stos) - 1];
}

function walidujNawiasy($napis) {
    $stos = [];
    $pary = [')' => '(', ']' => '[', '}' => '{'];

    for ($i = 0; $i < strlen($napis); $i++) {
        $znak = $napis[$i];
        if (in_array($znak, ['(', '[', '{'])) {
            s_push($stos, $znak);
        } elseif (isset($pary[$znak])) {
            if (count($stos) === 0 || s_pop($stos) !== $pary[$znak]) {
                return false;
            }
        }
    }

    return count($stos) === 0;
}

function obliczONP($wyrazenie) {
    $stos = [];
    $tokeny = explode(' ', $wyrazenie);

    foreach ($tokeny as $token) {
        if (is_numeric($token)) {
            s_push($stos, (float)$token);
        } else {
            $b = s_pop($stos);
            $a = s_pop($stos);
            if ($token === '+') s_push($stos, $a + $b);
            if ($token === '-') s_push($stos, $a - $b);
            if ($token === '*') s_push($stos, $a * $b);
            if ($token === '/') s_push($stos, $a / $b);
        }
    }

    return s_pop($stos);
}

$wyrazenia_ONP = [
    "5 2 + 3 *",
    "15 7 1 1 + - / 3 * 2 1 1 + + -",
    "4 13 5 / +",
    "2 3 + 4 * 5 -",
    "100 50 25 / -",
];

$napisy_nawiasy = [
    "[({()})]",
    "((())",
    "{[()]}",
    "([)]",
    "",
];

$bufor = array_fill(0, 5, null);
$pos = 0;

for ($i = 0; $i < count($wyrazenia_ONP); $i++) {
    $napis = $napisy_nawiasy[$i];
    $wyrazenie = $wyrazenia_ONP[$i];

    $nawiasyOK = walidujNawiasy($napis) ? "OK" : "BŁĄD";
    $wynik = obliczONP($wyrazenie);

    $bufor[$pos % 5] = $wynik;
    $pos++;

    $wynikStr = ($wynik == (int)$wynik) ? (int)$wynik : $wynik;
    $napisStr = $napis === "" ? '""' : "\"$napis\"";

    echo "[" . ($i + 1) . "] Nawiasy $napisStr: $nawiasyOK | ONP \"$wyrazenie\" = $wynikStr\n";
}

$buforStr = implode(', ', array_map(function($x) {
    return ($x == (int)$x) ? (int)$x : $x;
}, $bufor));

echo "Bufor cykliczny (ostatnie 5 wyników): [$buforStr]\n";