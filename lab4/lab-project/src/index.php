<?php
// etap 1 - ustawienie strefy czasowej na polską
date_default_timezone_set('Europe/Warsaw');

// etap 1 - funkcja wczytująca zadania z pliku JSON
// Jeśli plik nie istnieje (pierwsza wizyta), zwraca pustą tablicę
function loadTasks($file) {
    if (!file_exists($file)) {
        return [];
    }
    $json = file_get_contents($file);
    $tasks = json_decode($json, true);
    return is_array($tasks) ? $tasks : [];
}

// etap 1 - funkcja zapisująca zadania do pliku JSON
// Używa LOCK_EX do zapobiegania problemom z równoczesnym zapisem
// JSON_PRETTY_PRINT - formatuje czytelnie, JSON_UNESCAPED_UNICODE - zachowuje polskie znaki
function saveTasks($file, $tasks) {
    $json = json_encode($tasks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($file, $json, LOCK_EX);
}

// etap 1 - definicja pliku JSON do przechowywania zadań
$tasksFile = __DIR__ . '/tasks.json';

// etap 3 - konfiguracja katalogu dla załączników
$uploadDir = __DIR__ . '/uploads/';
// Automatyczne utworzenie katalogu uploads/ jeśli nie istnieje
// 0755 = uprawnienia (właściciel: rwx, grupa: rx, inni: rx)
// true = tworzy katalogi rekurencyjnie (w tym przypadku tylko jeden poziom)
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Sesja używana tylko dla komunikatów flash (tymczasowych)
session_start();

$errors  = [];
$success = false;
$post    = [];

// etap 1 - usunięcie wszystkich zadań
if (isset($_POST['usun_wszystkie'])) { //sprawdza kliknięcie przycisku
    // etap 4 - usuwanie załączników przed usunięciem zadań
    $zadania = loadTasks($tasksFile); // wczytaj wszystkie zadania
    foreach ($zadania as $z) {
        // jeśli zadanie ma załącznik, usuń plik z serwera
        if (!empty($z['attachment']) && file_exists($uploadDir . $z['attachment'])) {
            unlink($uploadDir . $z['attachment']); // kasuje plik z uploads/
        }
    }

    saveTasks($tasksFile, []); // zapisujemy pustą tablicę do pliku JSON
    header('Location: ' . $_SERVER['PHP_SELF']); //przeładowanie strony, eby nie było wysyłania formularza ponownie
    exit;//żeby nic więcej się nie wykonało po przekierowaniu
}

// etap 1 - usunięcie pojedynczego zadania
if (isset($_POST['usun_index'])) {
    $idx = (int) $_POST['usun_index'];
    $zadania = loadTasks($tasksFile); // wczytaj aktualne zadania z pliku

    // etap 4 - usuwanie załącznika przed usunięciem zadania
    if (isset($zadania[$idx]['attachment']) && !empty($zadania[$idx]['attachment'])) {
        $attachmentPath = $uploadDir . $zadania[$idx]['attachment'];
        // sprawdź czy plik istnieje i usuń go
        if (file_exists($attachmentPath)) {
            unlink($attachmentPath); // kasuje plik z uploads/
        }
    }

    array_splice($zadania, $idx, 1); // usuń zadanie o podanym indeksie
    saveTasks($tasksFile, $zadania); // zapisz zaktualizowaną listę
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Dodanie nowego zadania
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj'])) { //czy formularz został wysłany metodą POST i czy został kliknięty dodaj

    // Zebranie danych i zachowanie ich w zmiennej etap 2
    $post = [
        'tytul'          => trim($_POST['tytul']          ?? ''), //trim() function removes whitespace and other predefined characters (np \n) from both sides of a string
        'kategoria'      => trim($_POST['kategoria']      ?? ''), // A ?? B - jeśli A istnieje to go uzyj, jak nie to uzyj B
        'opis'           => trim($_POST['opis']           ?? ''),
        'priorytet'      => trim($_POST['priorytet']      ?? ''),
        'status'         => trim($_POST['status']         ?? ''),
        'data_wykonania' => trim($_POST['data_wykonania'] ?? ''),
        'czas'           => trim($_POST['czas']           ?? ''),
        'lokalizacja'    => trim($_POST['lokalizacja']    ?? ''),
        'osoba'          => trim($_POST['osoba']          ?? ''),
        'zasoby'         => $_POST['zasoby']              ?? [],
    ];

    // etap 2 walidacja
    if ($post['tytul']          === '') $errors[] = 'Tytuł zadania jest wymagany ⚠';
    if ($post['kategoria']      === '') $errors[] = 'Kategoria jest wymagana ⚠';
    if ($post['priorytet']      === '') $errors[] = 'Priorytet jest wymagany ⚠';
    if ($post['data_wykonania'] === '') $errors[] = 'Data wykonania jest wymagana ⚠';

    // etap 3 - obsługa przesyłania załącznika
    $attachmentFilename = ''; // domyślnie brak załącznika

    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        // Sprawdzenie czy plik został poprawnie przesłany
        $tmpPath = $_FILES['attachment']['tmp_name']; // tymczasowa lokalizacja pliku na serwerze
        $originalName = $_FILES['attachment']['name']; // oryginalna nazwa pliku

        // Generowanie unikalnej nazwy: timestamp + uniqid + oryginalna nazwa
        // time() - aktualny timestamp (sekundy od 1970)
        // uniqid() - unikalne ID bazujące na mikrosekundach
        // basename() - zabezpiecza przed atakami typu directory traversal (../../etc/passwd)
        $uniqueName = time() . '_' . uniqid() . '_' . basename($originalName);

        // Pełna ścieżka docelowa w katalogu uploads/
        $targetPath = $uploadDir . $uniqueName;

        // move_uploaded_file() bezpiecznie przenosi plik z tmp do docelowej lokalizacji
        // Działa TYLKO na pliki przesłane przez HTTP POST (zabezpieczenie)
        if (move_uploaded_file($tmpPath, $targetPath)) {
            $attachmentFilename = $uniqueName; // zapisujemy nazwę do bazy danych
        } else {
            $errors[] = 'Błąd podczas zapisywania załącznika ⚠';
        }
    } elseif (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
        // UPLOAD_ERR_NO_FILE = 4 (brak pliku jest OK, inne błędy nie)
        $errors[] = 'Błąd podczas przesyłania pliku ⚠';
    }

    if (empty($errors)) {
        // etap 2 - przygotowanie nowego zadania z timestampem utworzenia
        $noweZadanie = [
            'tytul'          => htmlspecialchars($post['tytul']), //htmlspecialchars chroni przed XSS (Cross-Site Scripting) - Zamienia specjalne znaki HTML na encje < becomes &lt;
            'kategoria'      => htmlspecialchars($post['kategoria']),
            'opis'           => htmlspecialchars($post['opis']),
            'priorytet'      => htmlspecialchars($post['priorytet']),
            'status'         => htmlspecialchars($post['status']),
            'data_wykonania' => htmlspecialchars($post['data_wykonania']),
            'czas'           => htmlspecialchars($post['czas']),
            'lokalizacja'    => htmlspecialchars($post['lokalizacja']),
            'osoba'          => htmlspecialchars($post['osoba']),
            'zasoby'         => array_map('htmlspecialchars', $post['zasoby']), //array_map() bierze każdy element tablicy i stosuje htmlspecialchars do każdego
            'created_at'     => time(), // etap 2 - timestamp utworzenia zadania (Unix timestamp = liczba sekund od 1 stycznia 1970)
            'attachment'     => $attachmentFilename, // etap 3 - nazwa pliku załącznika (pusty string jeśli brak)
        ];

        // etap 1 - wczytaj istniejące zadania z pliku JSON
        $zadania = loadTasks($tasksFile);

        // etap 1 - dodaj nowe zadanie do tablicy
        $zadania[] = $noweZadanie; //[] na końcu oznacza: dodaj nowy element na koniec tablicy

        // etap 1 - zapisz zaktualizowaną listę zadań do pliku JSON
        saveTasks($tasksFile, $zadania);

        // PRG (Post/Redirect/Get) – zapobiega ponownemu wysłaniu po F5
        $_SESSION['flash_success'] = true;//flash message — tymczasowy komunikat
        header('Location: ' . $_SERVER['PHP_SELF']);//wysyła nagłówek HTTP, który przekierowuje przeglądarkę; $_SERVER['PHP_SELF'] to ścieżka do aktualnie wykonywanego pliku PHP.
        exit;

// Jeśli wysłano formularz „dodaj zadanie" →
// pobierz dane → oczyść → sprawdź czy nie ma błedów →
// jeśli OK → zapisz w pliku JSON → odśwież stronę
    }
}

// Komunikat sukcesu (po przekierowaniu)
if (!empty($_SESSION['flash_success'])) {
    $success = true;
    unset($_SESSION['flash_success']); //unset() usuwa zmienna, dlatego komunikat jest tymczasowy
}

// etap 1 - wczytanie zadań z pliku JSON do wyświetlenia
$zadania = loadTasks($tasksFile);

// etap 2 - statystyki z licznikiem przeterminowanych zadań
$stat = [
    'lacznie' => count($zadania),
    'priorytet' => ['niski' => 0, 'sredni' => 0, 'wysoki' => 0],
    'status'    => ['nowe'  => 0, 'w_toku' => 0, 'zakonczone' => 0, '' => 0],
    'przeterminowane' => 0, // etap 2 - licznik przeterminowanych zadań
];

foreach ($zadania as $z) {
    if (isset($stat['priorytet'][$z['priorytet']])) $stat['priorytet'][$z['priorytet']]++; //czy mamy taki klucz; $stat['priorytet'] - licznik dla priorytetu,
    if (isset($stat['status'][$z['status']]))       $stat['status'][$z['status']]++;

    // etap 2 - wykrywanie przeterminowanych zadań
    // Przeterminowane = data wykonania < dziś AND status != zakończone
    // strtotime('today') zwraca timestamp północy dzisiejszego dnia (00:00:00)
    if ($z['status'] !== 'zakonczone' && strtotime($z['data_wykonania']) < strtotime('today')) {
        $stat['przeterminowane']++;
    }
}
?>

<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rozbudowany Menedżer Zadań</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            background-color: #e8eaed;
            color: #222;
        }

        header {
            background-color: #e8eaed;
            text-align: center;
            padding: 24px 30px 0;
        }

        .header-inner-blue-line {
            max-width: 1280px;
            margin: 0 auto;
            border-bottom: 3px solid #3a7bd5;
        }

        main {
            max-width: 1280px;
            margin: 30px auto;
            background: #fff;
            padding: 30px 36px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        h1 {
            font-size: 28px;
            font-weight: bold;
            color: #1a2e4a;
            padding-bottom: 16px;
        }

        h2 {
            font-size: 22px;
            font-weight: bold;
            color: #1a2e4a;
            margin-bottom: 22px;
        }
        /* Alerty */
        .alert-error, .alert-success {
            padding: 11px 16px; margin-bottom: 26px; font-size: 14px; border-left-width: 4px; border-left-style: solid; background: #fce8e8;
        }
        .alert-error   { background: #f7bfbf; border-color: #c0392b; color: #c0392b; }
        .alert-success { background: #caf3cd; border-color: #2e7d32; color: #2e7d32; }
        .alert-error ul { list-style: disc; padding-left: 18px; }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px 24px;
        }
        /* Formularz */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px 24px; }
        .full-width { grid-column: 1 / -1; }
        .row-2col   { grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        label { font-weight: bold; font-size: 13px; color: #111; }
        label .required { color: #c0392b; margin-left: 2px; }
        input[type="text"], input[type="number"], textarea, select {
            width: 100%; padding: 7px 10px; border: 1px solid #ccc; border-radius: 3px;
            font-size: 14px; font-family: Arial, sans-serif; background: #fff; color: #222;
        }
        input[type="file"] {
            width: 100%; padding: 5px; font-size: 13px;
        }
        input:focus, textarea:focus, select:focus {
            outline: none; border-color: #4a7cc7; box-shadow: 0 0 0 3px rgba(74,124,199,.18);
        }
        select   { background: #f7f7f7; cursor: pointer; }
        textarea { resize: vertical; min-height: 100px; }
        .checkbox-group { grid-column: 1 / -1; border-top: 1px solid #eee; padding-top: 16px; }
        .checkbox-group .group-label { display: block; font-weight: bold; font-size: 13px; margin-bottom: 10px; }
        .checkboxes { display: flex; flex-wrap: wrap; gap: 6px 20px; }
        .checkboxes label { display: flex; align-items: center; gap: 5px; font-weight: normal; font-size: 13px; cursor: pointer; }
        .checkboxes input[type="checkbox"] { width: 14px; height: 14px; cursor: pointer; }
        .btn { padding: 9px 22px; border: none; border-radius: 3px; font-size: 14px; cursor: pointer; color: #fff; }
        .btn-primary { background: #3a7bd5; margin-top: 24px; }
        .btn-primary:hover { background: #2f64b5; }
        .btn-danger  { background: #c0392b; }
        .btn-danger:hover  { background: #a32a1e; }
        .btn-small   { padding: 4px 12px; font-size: 12px; }

        /* etap 2 - statystyki z 4 kolumnami dla przeterminowanych */
        .stats-panel {
            display: grid; grid-template-columns: repeat(4, 1fr); /* etap 2 - 4 kolumny zamiast 3 */
            gap: 16px; background: #f0f4fa; border: 1px solid #d0daea;
            border-radius: 4px; padding: 20px; margin-bottom: 28px;
        }
        .stat-box { background: #fff; border: 1px solid #d8e2f0; border-radius: 4px; padding: 14px 16px; }
        .stat-box .stat-title { font-size: 12px; color: #666; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .5px; }
        .stat-box .stat-main  { font-size: 28px; font-weight: bold; color: #1a2e4a; }
        .stat-rows { display: flex; flex-direction: column; gap: 4px; }
        .stat-row  { display: flex; justify-content: space-between; font-size: 13px; }
        .stat-row span:last-child { font-weight: bold; }

        /* Tabela */
        .task-section { margin-top: 40px; border-top: 2px solid #e0e0e0; padding-top: 28px; }
        .task-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .task-section-header h2 { margin-bottom: 0; }
        .no-tasks { color: #666; font-style: italic; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th { background: #1a2e4a; color: #fff; padding: 9px 10px; text-align: left; }
        td { padding: 8px 10px; border-bottom: 1px solid #eee; vertical-align: top; }
        tr:nth-child(even) td { background: #f7f9fc; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
        .badge-niski     { background: #d4edda; color: #155724; }
        .badge-sredni    { background: #fff3cd; color: #856404; }
        .badge-wysoki    { background: #fde8d8; color: #7d3c00; }

        /* etap 2 - stylowanie przeterminowanych zadań */
        .overdue-cell {
            background-color: #fde8d8 !important; /* pomarańczowo-czerwone tło dla komórek przeterminowanych zadań */
        }
        .overdue-label {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            color: #c0392b; /* czerwony tekst */
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 600px) {
            .form-grid, .stats-panel { grid-template-columns: 1fr; }
            main { margin: 0; padding: 20px 16px; border-radius: 0; border-left: none; border-right: none; }
        }

    </style>
</head>

<body>

    <header>
        <div class="header-inner-blue-line">
            <h1>Rozbudowany Menedżer Zadań</h1>
        </div>
    </header>

    <main>
        <!-- FORMULARZ -->
        <h2>Dodaj nowe zadanie</h2>

        <!-- : — dwukropek zamiast { — alternatywna składnia PHP, czytelniejsza w HTML -->
        <?php if ($success): ?> 
            <div class="alert-success">Zadanie zostało pomyślnie dodane.</div>
        <?php endif; ?>

        <!-- etap 2 - walidacja cd -->
        <?php if (!empty($errors)): ?>
            <div class="alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <!-- ?= — skrót od ?php echo -->
                        <li><?= $e ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <!-- endif — zamknięcie bloku if (odpowiednik }) -->
        <?php endif; ?>

        <!-- $_SERVER['PHP_SELF']  - superglobalna zmienna PHP, zawiera ścieżkę do aktualnego pliku-->
        <!-- etap 3 - enctype umożliwia przesyłanie plików przez formularz -->
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" enctype="multipart/form-data">
            <div class="form-grid">

                <div class="row-2col">
                <div class="form-group">
                    <label for="tytul">Tytuł zadania: <span class="required">*</span></label>
                    <!-- value="?=  ?" — wstępnie wypełnia pole tym, co użytkownik wpisał przed chwilą (przy błędzie walidacji dane nie znikają) -->
                    <input type="text" id="tytul" name="tytul" value="<?= htmlspecialchars($post['tytul'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="kategoria">Kategoria: <span class="required">*</span></label>
                    <select id="kategoria" name="kategoria" >
                        <!-- option to element listy rozwijanej; value to wartość, która zostanie wysłana w formularzu -->
                        <option value="">Wybierz kategorię</option>
                        <?php foreach (['domowe'=>'Domowe','praca'=>'Praca','nauka'=>'Nauka','hobby'=>'Hobby','inne'=>'Inne'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= ($post['kategoria'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="opis">Opis zadania:</label>
                <textarea id="opis" name="opis"><?= htmlspecialchars($post['opis'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
                <label for="priorytet">Priorytet: <span class="required">*</span></label>
                <select id="priorytet" name="priorytet">
                    <option value="">Wybierz priorytet</option>
                    <?php foreach (['niski'=>'Niski','sredni'=>'Średni','wysoki'=>'Wysoki'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($post['priorytet'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="">Wybierz status</option>
                    <?php foreach (['nowe'=>'Nowe','w_toku'=>'W toku','zakonczone'=>'Zakończone'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($post['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="data_wykonania">Data wykonania: <span class="required">*</span></label>
                <input type="text" id="data_wykonania" name="data_wykonania"
                       placeholder="14.04.2026" value="<?= htmlspecialchars($post['data_wykonania'] ?? '') ?>" >
            </div>

            <div class="form-group">
                <label for="czas">Szacowany czas (minuty):</label>
                <input type="number" id="czas" name="czas" min="0" value="<?= htmlspecialchars($post['czas'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="lokalizacja">Lokalizacja:</label>
                <input type="text" id="lokalizacja" name="lokalizacja" value="<?= htmlspecialchars($post['lokalizacja'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="osoba">Osoba przypisana:</label>
                <input type="text" id="osoba" name="osoba" value="<?= htmlspecialchars($post['osoba'] ?? '') ?>">
            </div>

            <!-- etap 3 - pole do przesyłania załącznika -->
            <div class="form-group">
                <label for="attachment">Załącznik:</label>
                <input type="file" id="attachment" name="attachment">
                <small style="color:#666;">Opcjonalnie: dodaj plik do zadania</small>
            </div>

            <div class="checkbox-group">
                <span class="group-label">Potrzebne zasoby:</span>
                <div class="checkboxes">
                    <?php foreach (['komputer'=>'Komputer','internet'=>'Internet','telefon'=>'Telefon',
                                    'samochod'=>'Samochód','ksiazka'=>'Książka','narzedzia'=>'Narzędzia',
                                    'dokumenty'=>'Dokumenty','inne'=>'Inne'] as $v => $l): ?>
                        <label>
                            <input type="checkbox" name="zasoby[]" value="<?= $v ?>" <?= in_array($v, $post['zasoby'] ?? []) ? 'checked' : '' ?>>
                            <?= $l ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
        <button type="submit" name="dodaj" class="btn btn-primary">Dodaj zadanie</button>
    </form>

    <!-- Zadania -->
    <div class="task-section">
        <div class="task-section-header">
            <h2>Lista zadań</h2>
            <?php if (!empty($zadania)): ?>
                <!-- etap 4 - potwierdzenie usunięcia wszystkich zadań i załączników -->
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                      onsubmit="return confirm('Czy na pewno usunąć wszystkie zadania i załączniki?')">
                    <button type="submit" name="usun_wszystkie" class="btn btn-danger btn-small">
                        🗑 Usuń wszystkie
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Statystyki -->
        <div class="stats-panel">
            <div class="stat-box">
                <div class="stat-title">Łącznie zadań</div>
                <div class="stat-main"><?= $stat['lacznie'] ?></div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Według priorytetu</div>
                <div class="stat-rows">
                    <div class="stat-row"><span>Niski</span>     <span><?= $stat['priorytet']['niski'] ?></span></div>
                    <div class="stat-row"><span>Średni</span>    <span><?= $stat['priorytet']['sredni'] ?></span></div>
                    <div class="stat-row"><span>Wysoki</span>    <span><?= $stat['priorytet']['wysoki'] ?></span></div>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-title">Według statusu</div>
                <div class="stat-rows">
                    <div class="stat-row"><span>Nowe</span>       <span><?= $stat['status']['nowe'] ?></span></div>
                    <div class="stat-row"><span>W toku</span>     <span><?= $stat['status']['w_toku'] ?></span></div>
                    <div class="stat-row"><span>Zakończone</span> <span><?= $stat['status']['zakonczone'] ?></span></div>
                    <div class="stat-row"><span>Nieokreślony</span><span><?= $stat['status'][''] ?></span></div>
                </div>
            </div>
            <!-- etap 2 - statystyka przeterminowanych zadań -->
            <div class="stat-box">
                <div class="stat-title">Przeterminowane</div>
                <div class="stat-main" style="color: #c0392b;"><?= $stat['przeterminowane'] ?></div>
            </div>
        </div>

        <!-- Tabela lub komunikat,ze nie ma zadań -->
        <?php if (empty($zadania)): ?>
            <p class="no-tasks">Brak zadań. Dodaj pierwsze zadanie powyżej.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tytuł / Opis</th>
                        <th>Kategoria</th>
                        <th>Priorytet</th>
                        <th>Status</th>
                        <th>Data utworzenia</th>  <!-- etap 2 - nowa kolumna pokazująca kiedy zadanie zostało utworzone -->
                        <th>Data</th>
                        <th>Czas</th>
                        <th>Osoba</th>
                        <th>Lokalizacja</th>
                        <th>Zasoby</th>
                        <th>Załącznik</th>  <!-- etap 3 - kolumna z linkiem do pobrania załącznika -->
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($zadania as $i => $z): ?>
                        <tr>
                            <!-- zawartość wierszy, po kolei -->
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?= $z['tytul'] ?>
                                <?php if ($z['opis'] !== ''): ?>
                                    <br><small style="color:#666"><?= $z['opis'] ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= $z['kategoria'] ?></td>
                            <td><span class="badge badge-<?= $z['priorytet'] ?>"><?= $z['priorytet'] ?></span></td>
                            <td><?= $z['status'] !== '' ? $z['status'] : '—' ?></td>

                            <!-- etap 2 - wyświetlanie daty utworzenia z obsługą starych zadań bez tego pola -->
                            <td>
                                <?php if (isset($z['created_at'])): ?>
                                    <?= date('d.m.Y H:i', $z['created_at']) ?>
                                <?php else: ?>
                                    <small style="color:#999;">—</small>
                                <?php endif; ?>
                            </td>

                            <!-- etap 2 - wykrywanie przeterminowania i wizualne oznaczenie -->
                            <?php
                            // Zadanie przeterminowane gdy: data < dziś I status != zakończone
                            // strtotime() konwertuje tekstową datę (np. "14.04.2026") na Unix timestamp
                            $isPrzeterminowane = ($z['status'] !== 'zakonczone' && strtotime($z['data_wykonania']) < strtotime('today'));
                            ?>
                            <td class="<?= $isPrzeterminowane ? 'overdue-cell' : '' ?>">
                                <?= $z['data_wykonania'] ?>
                                <?php if ($isPrzeterminowane): ?>
                                    <br><span class="overdue-label">Po terminie!</span>
                                <?php endif; ?>
                            </td>

                            <td><?= $z['czas'] !== '' ? $z['czas'] : '—' ?></td>
                            <td><?= $z['osoba'] !== '' ? $z['osoba'] : '—' ?></td>
                            <td><?= $z['lokalizacja'] !== '' ? $z['lokalizacja'] : '—' ?></td>
                            <!-- implode łączy zasoby w jeden string -->
                            <td><?= !empty($z['zasoby']) ? implode(', ', $z['zasoby']) : '—' ?></td>

                            <!-- etap 3 - wyświetlanie linku do pobrania załącznika -->
                            <td>
                                <?php if (!empty($z['attachment']) && file_exists($uploadDir . $z['attachment'])): ?>
                                    <a href="uploads/<?= htmlspecialchars($z['attachment']) ?>"
                                       download
                                       style="color:#3a7bd5; text-decoration:none;">
                                        📎 Pobierz
                                    </a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>

                            <td>
                                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                                      onsubmit="return confirm('Usunąć to zadanie?')">
                                    <input type="hidden" name="usun_index" value="<?= $i ?>">
                                    <!-- hidden dostępne w źródło strony -->
                                    <button type="submit" class="btn btn-danger btn-small">Usuń</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<!-- ###### -->
</main>
</body>
</html>