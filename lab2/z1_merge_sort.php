<?php

function merge($left, $right, &$comparisons) {
    $result = [];
    $i = 0;
    $j = 0;

    while ($i < count($left) && $j < count($right)) {
        $comparisons++;
        if ($left[$i] <= $right[$j]) {
            $result[] = $left[$i];
            $i++;
        } else {
            $result[] = $right[$j];
            $j++;
        }
    }

    while ($i < count($left)) {
        $result[] = $left[$i];
        $i++;
    }
    while ($j < count($right)) {
        $result[] = $right[$j];
        $j++;
    }

    return $result;
}

function mergeSort($arr, &$comparisons) {
    $n = count($arr);
    if ($n <= 1) {
        return $arr;
    }

    $mid = (int)($n / 2);
    $left = mergeSort(array_slice($arr, 0, $mid), $comparisons);
    $right = mergeSort(array_slice($arr, $mid), $comparisons);

    return merge($left, $right, $comparisons);
}

$tablice = [
    [5, 3, 8, 1, 9, 2],
    [38, 27, 43, 3, 9, 82, 10, 15],
    [64, 25, 12, 22, 11, 90, 3, 47, 71, 38, 55, 8],
    [25, 24, 23, 22, 21, 20, 19, 18, 17, 16, 15, 14, 13, 12, 11, 10, 9, 8, 7, 6, 5, 4, 3, 2, 1],
];

$allMatch = true;

foreach ($tablice as $arr) {
    $n = count($arr);
    $comparisons = 0;

    $sorted = mergeSort($arr, $comparisons);
    $k = $comparisons / ($n * log($n, 2));

    $control = $arr;
    sort($control);
    if ($sorted !== $control) {
        $allMatch = false;
    }

    echo "n=$n | Wejście: [" . implode(', ', $arr) . "]\n";
    echo "     | Wyjście: [" . implode(', ', $sorted) . "]\n";
    echo "     | Porównania: $comparisons | K: " . round($k, 3) . "\n\n";
}

echo "Weryfikacja z sort(): " . ($allMatch ? "ZGODNA" : "NIEZGODNA") . "\n";