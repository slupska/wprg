<?php

$dane = [];
$historia = [];

function pokazTablice($dane) {
    echo "[" . implode(", ", $dane) . "]\n";
}

function dodajHistorie(&$historia, $linia) {
    $historia[] = $linia;
    $historia = array_slice($historia, -10);
}

while (true) {
    $linia = readline(">> ");
    if ($linia === false || trim($linia) === '') continue;

    $czesci = explode(' ', trim($linia), 3);
    $polecenie = strtolower($czesci[0]);

    switch ($polecenie) {
        case 'push':
            if (!isset($czesci[1])) { echo "Brak argumentu dla: push\n"; break; }
            $dane[] = is_numeric($czesci[1]) ? $czesci[1] + 0 : $czesci[1];
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'pop':
            if (count($dane) === 0) { echo "Tablica jest pusta\n"; break; }
            $val = array_pop($dane);
            echo "Usunięto: $val\n";
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'insert':
            if (!isset($czesci[1]) || !isset($czesci[2])) { echo "Brak argumentu dla: insert\n"; break; }
            $idx = (int)$czesci[1];
            $val = is_numeric($czesci[2]) ? $czesci[2] + 0 : $czesci[2];
            array_splice($dane, $idx, 0, [$val]);
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'delete':
            if (!isset($czesci[1])) { echo "Brak argumentu dla: delete\n"; break; }
            $idx = (int)$czesci[1];
            array_splice($dane, $idx, 1);
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'sort':
            sort($dane);
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'rsort':
            rsort($dane);
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'filter':
            if (!isset($czesci[1]) || !isset($czesci[2])) { echo "Brak argumentu dla: filter\n"; break; }
            $op = $czesci[1];
            $val = (float)$czesci[2];
            $dane = array_values(array_filter($dane, function($x) use ($op, $val) {
                if ($op === '>') return $x > $val;
                if ($op === '<') return $x < $val;
                if ($op === '>=') return $x >= $val;
                if ($op === '<=') return $x <= $val;
                if ($op === '==') return $x == $val;
                return false;
            }));
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'unique':
            $dane = array_values(array_unique($dane));
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'reverse':
            $dane = array_reverse($dane);
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'chunk':
            if (!isset($czesci[1])) { echo "Brak argumentu dla: chunk\n"; break; }
            $n = (int)$czesci[1];
            $chunks = array_chunk($dane, $n);
            foreach ($chunks as $i => $chunk) {
                echo "Chunk " . ($i + 1) . ": [" . implode(", ", $chunk) . "]\n";
            }
            dodajHistorie($historia, trim($linia));
            break;

        case 'slice':
            if (!isset($czesci[1]) || !isset($czesci[2])) { echo "Brak argumentu dla: slice\n"; break; }
            $od = (int)$czesci[1];
            $ile = (int)$czesci[2];
            $fragment = array_slice($dane, $od, $ile);
            pokazTablice($fragment);
            dodajHistorie($historia, trim($linia));
            break;

        case 'stats':
            if (count($dane) === 0) { echo "Tablica jest pusta\n"; break; }
            $suma = 0;
            $min = $dane[0];
            $max = $dane[0];
            foreach ($dane as $v) {
                $suma += $v;
                if ($v < $min) $min = $v;
                if ($v > $max) $max = $v;
            }
            $avg = $suma / count($dane);
            echo "Suma: $suma | Średnia: $avg | Min: $min | Max: $max\n";
            dodajHistorie($historia, trim($linia));
            break;

        case 'show':
            pokazTablice($dane);
            dodajHistorie($historia, trim($linia));
            break;

        case 'reset':
            $dane = [];
            echo "Tablica wyczyszczona\n";
            dodajHistorie($historia, trim($linia));
            break;

        case 'save':
            echo json_encode(["dane" => $dane]) . "\n";
            dodajHistorie($historia, trim($linia));
            break;

        case 'history':
            foreach ($historia as $i => $h) {
                echo ($i + 1) . ": $h\n";
            }
            break;

        case 'help':
            echo "Dostępne polecenia:\n";
            echo "  push <v>       — dodaj wartość na koniec\n";
            echo "  pop            — usuń ostatni element\n";
            echo "  insert <i> <v> — wstaw na pozycję i\n";
            echo "  delete <i>     — usuń element na pozycji i\n";
            echo "  sort           — posortuj rosnąco\n";
            echo "  rsort          — posortuj malejąco\n";
            echo "  filter <op> <v>— filtruj (op: > < >= <= ==)\n";
            echo "  unique         — usuń duplikaty\n";
            echo "  reverse        — odwróć kolejność\n";
            echo "  chunk <n>      — podziel na grupy po n\n";
            echo "  slice <od> <ile>— wypisz fragment\n";
            echo "  stats          — suma, średnia, min, max\n";
            echo "  show           — wypisz tablicę\n";
            echo "  reset          — wyczyść tablicę\n";
            echo "  save           — zapisz jako JSON\n";
            echo "  history        — ostatnie 10 komend\n";
            echo "  help           — lista poleceń\n";
            echo "  exit           — zakończ\n";
            break;

        case 'exit':
            echo "Do widzenia!\n";
            exit(0);

        default:
            echo "Nieznane polecenie: $polecenie\n";
    }
}