<?php

function sito($n)
{
    $A = [];
    for ($i = 0; $i <= $n; $i++) {
        array_push($A, true);
    }
    $A[0] = $A[1] = false;
    for ($i = 2; $i <= sqrt($n); $i++) {
        if ($A[$i]) {
            for ($j = $i * $i; $j <= $n; $j += $i) {
                $A[$j] = false;
            }
        }
    }
    $result = [];
    foreach ($A as $i => $value) {
        if ($value) {
            $result[] = $i;
        }
    }
    return $result;
}

$array = sito(100);
echo "Liczby pierwsze [1-100] bloki po 10:<br>[ ";
foreach ($array as $i => $value) {
    if ($i % 9 == 0 && $i != 0) {
        echo $value;
        echo " ]<br>[ ";
    } elseif ($i == sizeof($array) - 1) {
        echo $value . " ]<br>";
    } else {
        echo $value . ", ";
    }
}

function density($a, $b)
{
    $mid = ($a + $b) / 2;
    $density = ($b - $a) / log($mid);
    return $density;
}

function countFirstNums($a, $b)
{
    $primesAll = sito($b);
    $primes = [];
    foreach ($primesAll as $value) {
        if ($value >= $a) {
            $primes[] = $value;
        }
    }
    return count($primes);
}

function goldbachNum($a)
{
    if ($a % 2) {
        return [];
    }
    $primes = sito($a);
    $sums = [];
    foreach ($primes as $value1) {
        if ($value1 <= $a / 2) {
            foreach ($primes as $value2) {
                if ($value1 + $value2 == $a) {
                    $sums[] = "[$value1 + $value2]";
                }
            }
        }
    }
    return $sums;
}

function biggestGoldbachInRange($a, $b, &$biggest, &$biggestArr)
{
    $biggest = 0;
    $biggestArr = [];
    for ($i = $a; $i <= $b; $i++) {
        $currentSums = goldbachNum($i);
        if ($currentSums) {
            if (sizeof($currentSums) > sizeof($biggestArr)) {
                $biggest = $i;
                $biggestArr = $currentSums;
            }
        }
    }
};

echo "<br> Gęstość liczb pierwszych:<br>";
echo "Przedział: [1-100]: " . countFirstNums(1, 100)
 . " | teoretycznie: ~" . density(1, 100) . "<br>";
echo "Przedział: [101-200]: " . countFirstNums(101, 200)
 . " | teoretycznie: ~" . density(101, 200) . "<br>";
echo "Przedział: [201-300]: " . countFirstNums(201, 300)
 . " | teoretycznie: ~" . density(201, 300) . "<br>";
echo "Przedział: [301-400]: " . countFirstNums(301, 400)
 . " | teoretycznie: ~" . density(301, 400) . "<br>";
echo "Przedział: [401-500]: " . countFirstNums(401, 500)
 . " | teoretycznie: ~" . density(401, 500) . "<br>";

biggestGoldbachInRange(4, 200, $biggest, $biggestArr);
echo "<br> Goldbach - najwięcej par w [4, 200]: liczba "
 . $biggest . " (" . sizeof($biggestArr) . " par) <br>";
echo "Pary goldbacha dla 30: " . implode(", ", goldbachNum(30));