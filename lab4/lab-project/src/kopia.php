<?php
session_start();

if (!isset($_SESSION['zadania'])) {
    $_SESSION['zadania'] = [];
}

$errors  = [];
$success = false;
$post    = [];

// ── Usunięcie wszystkich zadań ──────────────────────────────────────────────
if (isset($_POST['usun_wszystkie'])) {
    $_SESSION['zadania'] = [];
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── Usunięcie pojedynczego zadania ──────────────────────────────────────────
if (isset($_POST['usun_index'])) {
    $idx = (int) $_POST['usun_index'];
    array_splice($_SESSION['zadania'], $idx, 1);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── Dodanie nowego zadania ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dodaj'])) {

    // Zebranie danych
    $post = [
        'tytul'          => trim($_POST['tytul']          ?? ''),
        'kategoria'      => trim($_POST['kategoria']      ?? ''),
        'opis'           => trim($_POST['opis']           ?? ''),
        'priorytet'      => trim($_POST['priorytet']      ?? ''),
        'status'         => trim($_POST['status']         ?? ''),
        'data_wykonania' => trim($_POST['data_wykonania'] ?? ''),
        'czas'           => trim($_POST['czas']           ?? ''),
        'lokalizacja'    => trim($_POST['lokalizacja']    ?? ''),
        'osoba'          => trim($_POST['osoba']          ?? ''),
        'zasoby'         => $_POST['zasoby']              ?? [],
    ];

    // Walidacja
    if ($post['tytul']          === '') $errors[] = 'Tytuł zadania jest wymagany ⚠';
    if ($post['kategoria']      === '') $errors[] = 'Kategoria jest wymagana ⚠';
    if ($post['priorytet']      === '') $errors[] = 'Priorytet jest wymagany ⚠';
    if ($post['data_wykonania'] === '') $errors[] = 'Data wykonania jest wymagana ⚠';

    if (empty($errors)) {
        $_SESSION['zadania'][] = [
            'tytul'          => htmlspecialchars($post['tytul']),
            'kategoria'      => htmlspecialchars($post['kategoria']),
            'opis'           => htmlspecialchars($post['opis']),
            'priorytet'      => htmlspecialchars($post['priorytet']),
            'status'         => htmlspecialchars($post['status']),
            'data_wykonania' => htmlspecialchars($post['data_wykonania']),
            'czas'           => htmlspecialchars($post['czas']),
            'lokalizacja'    => htmlspecialchars($post['lokalizacja']),
            'osoba'          => htmlspecialchars($post['osoba']),
            'zasoby'         => array_map('htmlspecialchars', $post['zasoby']),
        ];

        // PRG – zapobiega ponownemu wysłaniu po F5
        $_SESSION['flash_success'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Komunikat sukcesu (po przekierowaniu)
if (!empty($_SESSION['flash_success'])) {
    $success = true;
    unset($_SESSION['flash_success']);
}

$zadania = $_SESSION['zadania'];

// ── Statystyki ──────────────────────────────────────────────────────────────
$stat = [
    'lacznie' => count($zadania),
    'priorytet' => ['niski' => 0, 'sredni' => 0, 'wysoki' => 0, 'krytyczny' => 0],
    'status'    => ['nowe'  => 0, 'w_toku' => 0, 'zakonczone' => 0, '' => 0],
];
foreach ($zadania as $z) {
    if (isset($stat['priorytet'][$z['priorytet']])) $stat['priorytet'][$z['priorytet']]++;
    $s = $z['status'] !== '' ? $z['status'] : '';
    if (array_key_exists($s, $stat['status'])) $stat['status'][$s]++;
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rozbudowany Menedżer Zadań</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif;
         font-size: 14px; background-color: #e8eaed; color: #222; }

        header { background-color: #e8eaed;
         text-align: center; padding: 24px 30px 0; }
        .header-inner-blue-line { max-width: 960px;
         margin: 0 auto; border-bottom: 3px solid #3a7bd5; }
        h1 { font-size: 28px; font-weight: bold; color: #1a2e4a; padding-bottom: 16px; }
        h2 { font-size: 22px; font-weight: bold; color: #1a2e4a; margin-bottom: 22px; }

        main { max-width: 960px; margin: 30px auto;
         background: #fff; padding: 30px 36px;
               border: 1px solid #ddd; border-radius: 4px; }

        /* Alerty */
        .alert-error, .alert-success {
            padding: 11px 16px; margin-bottom: 26px; font-size: 14px; border-left-width: 4px; border-left-style: solid;
        }
        .alert-error   { background: #fce8e8; border-color: #c0392b; color: #c0392b; }
        .alert-success { background: #e8f5e9; border-color: #2e7d32; color: #2e7d32; }
        .alert-error ul { list-style: disc; padding-left: 18px; }

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

        /* Statystyki */
        .stats-panel {
            display: grid; grid-template-columns: repeat(3, 1fr);
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
        .badge-krytyczny { background: #fce8e8; color: #c0392b; }

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

    <!-- ── FORMULARZ ─────────────────────────────────────────────────────── -->
    <h2>Dodaj nowe zadanie</h2>

    <?php if ($success): ?> 
        <div class="alert-success">✅ Zadanie zostało pomyślnie dodane.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= $e ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST">
        <div class="form-grid">

            <div class="row-2col">
                <div class="form-group">
                    <label for="tytul">Tytuł zadania: <span class="required">*</span></label>
                    <input type="text" id="tytul" name="tytul" value="<?= htmlspecialchars($post['tytul'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="kategoria">Kategoria: <span class="required">*</span></label>
                    <select id="kategoria" name="kategoria" required>
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
                <select id="priorytet" name="priorytet" required>
                    <option value="">Wybierz priorytet</option>
                    <?php foreach (['niski'=>'Niski','sredni'=>'Średni','wysoki'=>'Wysoki','krytyczny'=>'Krytyczny'] as $v => $l): ?>
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
                       placeholder="14.04.2025" value="<?= htmlspecialchars($post['data_wykonania'] ?? '') ?>" required>
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

    <!-- ── SEKCJA ZADAŃ ───────────────────────────────────────────────────── -->
    <div class="task-section">
        <div class="task-section-header">
            <h2>Lista zadań</h2>
            <?php if (!empty($zadania)): ?>
                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                      onsubmit="return confirm('Usunąć wszystkie zadania?')">
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
                    <div class="stat-row"><span>Krytyczny</span> <span><?= $stat['priorytet']['krytyczny'] ?></span></div>
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
        </div>

        <!-- Tabela lub komunikat -->
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
                        <th>Data</th>
                        <th>Czas</th>
                        <th>Osoba</th>
                        <th>Lokalizacja</th>
                        <th>Zasoby</th>
                        <th>Akcja</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($zadania as $i => $z): ?>
                        <tr>
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
                            <td><?= $z['data_wykonania'] ?></td>
                            <td><?= $z['czas'] !== '' ? $z['czas'] : '—' ?></td>
                            <td><?= $z['osoba'] !== '' ? $z['osoba'] : '—' ?></td>
                            <td><?= $z['lokalizacja'] !== '' ? $z['lokalizacja'] : '—' ?></td>
                            <td><?= !empty($z['zasoby']) ? implode(', ', $z['zasoby']) : '—' ?></td>
                            <td>
                                <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>"
                                      onsubmit="return confirm('Usunąć to zadanie?')">
                                    <input type="hidden" name="usun_index" value="<?= $i ?>">
                                    <button type="submit" class="btn btn-danger btn-small">Usuń</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>
</body>
</html>