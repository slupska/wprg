<?php

$rekordy = [
    ["id"=>1,  "imie"=>"anna",    "wiek"=>"25",  "email"=>"anna@test.com",   "wynik"=>92.5],
    ["id"=>2,  "imie"=>"Bartosz", "wiek"=>"abc", "email"=>"bartosz@test.com","wynik"=>78.0],
    ["id"=>3,  "imie"=>"celina",  "wiek"=>"31",  "email"=>"celina@test.com", "wynik"=>105.0],
    ["id"=>4,  "imie"=>"Dawid",   "wiek"=>"45",  "email"=>"",               "wynik"=>66.5],
    ["id"=>5,  "imie"=>"EWA",     "wiek"=>"28",  "email"=>"ewa@test.com",    "wynik"=>88.0],
    ["id"=>6,  "imie"=>"filip",   "wiek"=>"130", "email"=>"filip@test.com",  "wynik"=>74.0],
    ["id"=>7,  "imie"=>"Grażyna", "wiek"=>"52",  "email"=>"anna@test.com",   "wynik"=>91.0],
    ["id"=>8,  "imie"=>"Henryk",  "wiek"=>"19",  "email"=>"henryk@test.com", "wynik"=>-5.0],
    ["id"=>9,  "imie"=>"irena",   "wiek"=>"37",  "email"=>"irena@test.com",  "wynik"=>83.5],
    ["id"=>10, "imie"=>"JANEK",   "wiek"=>"22",  "email"=>"janek@test.com",  "wynik"=>55.0],
    ["id"=>11, "imie"=>"Kasia",   "wiek"=>"29",  "email"=>"kasia@test.com",  "wynik"=>97.0],
    ["id"=>12, "imie"=>"Leon",    "wiek"=>"41",  "email"=>"leon@test.com",   "wynik"=>62.0],
    ["id"=>13, "imie"=>"Marta",   "wiek"=>"0",   "email"=>"marta@test.com",  "wynik"=>79.5],
    ["id"=>14, "imie"=>"norbert", "wiek"=>"33",  "email"=>"norbert@test.com","wynik"=>86.0],
    ["id"=>15, "imie"=>"Ola",     "wiek"=>"26",  "email"=>"ola@test.com",    "wynik"=>91.0],
];

function waliduj(array $dane): array {
    $valid = [];
    $rejected = [];
    $emaile = [];

    foreach ($dane as $r) {
        $wiek = $r['wiek'];
        $wynik = $r['wynik'];
        $email = trim($r['email']);

        if (!is_numeric($wiek) || (int)$wiek != $wiek || (int)$wiek < 1 || (int)$wiek > 120) {
            $rejected[] = ["rekord" => $r, "powod" => "nieprawidłowy wiek '{$wiek}'"];
        } elseif ($wynik < 0.0 || $wynik > 100.0) {
            $rejected[] = ["rekord" => $r, "powod" => "wynik poza zakresem [0–100]: {$wynik}"];
        } elseif ($email === '') {
            $rejected[] = ["rekord" => $r, "powod" => "pusty email"];
        } elseif (in_array($email, $emaile)) {
            $rejected[] = ["rekord" => $r, "powod" => "duplikat email '{$email}'"];
        } else {
            $emaile[] = $email;
            $valid[] = $r;
        }
    }

    return ['valid' => $valid, 'rejected' => $rejected];
}

function transformuj(array $dane): array {
    $wynik = [];
    foreach ($dane as $r) {
        $r['imie'] = ucfirst(strtolower($r['imie']));
        $r['wiek'] = (int)$r['wiek'];
        $r['wynik'] = (float)$r['wynik'];
        $r['email'] = trim($r['email']);
        $wynik[] = $r;
    }
    return $wynik;
}

function ocena($wynik) {
    if ($wynik >= 90) return 'A';
    if ($wynik >= 75) return 'B';
    if ($wynik >= 60) return 'C';
    return 'D';
}

echo "=== Etap E: Walidacja ===\n";
$etapE = waliduj($rekordy);

echo "Odrzucone rekordy (" . count($etapE['rejected']) . "):\n";
foreach ($etapE['rejected'] as $r) {
    printf("  - ID %-3d (%s): %s\n", $r['rekord']['id'], $r['rekord']['imie'], $r['powod']);
}

$etapT = transformuj($etapE['valid']);

echo "\n=== Etap L: Finalna baza (" . count($etapT) . " rekordów) ===\n";
printf("%-14s| %-5s| %-27s| %-6s| %s\n", "Imię", "Wiek", "Email", "Wynik", "Ocena");
echo str_repeat("-", 65) . "\n";

$rozklad = ['A' => [], 'B' => [], 'C' => [], 'D' => []];

foreach ($etapT as $r) {
    $o = ocena($r['wynik']);
    $rozklad[$o][] = $r['wynik'];
    printf("%-14s| %4d | %-27s| %5.1f | %s\n", $r['imie'], $r['wiek'], $r['email'], $r['wynik'], $o);
}

echo "\nRozkład ocen:\n";
foreach ($rozklad as $o => $wyniki) {
    $ile = count($wyniki);
    $avg = $ile > 0 ? array_sum($wyniki) / $ile : 0;
    printf("  %s: %d studentów, średnia: %.1f%%\n", $o, $ile, $avg);
}