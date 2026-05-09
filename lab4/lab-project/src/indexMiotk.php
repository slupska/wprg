<?php
declare(strict_types=1);

$dbHost     = getenv('DB_HOST')     ?: 'db';
$dbPort     = getenv('DB_PORT')     ?: '3306';
$dbName     = getenv('DB_NAME')     ?: 'devdb';
$dbUser     = getenv('DB_USER')     ?: 'devuser';
$dbPassword = getenv('DB_PASSWORD') ?: 'devpassword';

$dbStatus  = false;
$dbMessage = '';
$dbVersion = '';

try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPassword, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);

    $stmt      = $pdo->query('SELECT VERSION() AS version');
    $row       = $stmt->fetch();
    $dbVersion = $row['version'] ?? 'nieznana';
    $dbStatus  = true;
    $dbMessage = 'Połączenie z bazą danych działa poprawnie.';
} catch (PDOException $e) {
    $dbMessage = 'Błąd połączenia z bazą: ' . htmlspecialchars($e->getMessage());
}

$phpVersion = phpversion();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Docker Dev Stack – Status</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2.5rem;
            max-width: 560px;
            width: 100%;
            box-shadow: 0 25px 50px rgba(0,0,0,.4);
        }

        .logo { font-size: 2.5rem; margin-bottom: .5rem; }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: .25rem;
        }

        .subtitle {
            font-size: .9rem;
            color: #94a3b8;
            margin-bottom: 2rem;
        }

        .status-row {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .status-row.ok    { background: #052e16; border-color: #166534; }
        .status-row.error { background: #2d0a0a; border-color: #991b1b; }
        .status-row.info  { background: #0c1a2e; border-color: #1e3a5f; }

        .badge {
            flex-shrink: 0;
            font-size: .75rem;
            font-weight: 700;
            padding: .25rem .6rem;
            border-radius: 9999px;
            margin-top: 2px;
        }

        .badge.ok    { background: #166534; color: #bbf7d0; }
        .badge.error { background: #991b1b; color: #fecaca; }
        .badge.info  { background: #1e3a5f; color: #bae6fd; }

        .status-content strong {
            display: block;
            font-size: .95rem;
            color: #f1f5f9;
            margin-bottom: .2rem;
        }

        .status-content span { font-size: .85rem; color: #94a3b8; }

        .footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #334155;
            font-size: .8rem;
            color: #64748b;
            text-align: center;
        }

        .footer a { color: #38bdf8; text-decoration: none; }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">🐳</div>
    <h1>Docker Dev Stack</h1>
    <p class="subtitle">Lokalny zamiennik XAMPP &mdash; PHP + MySQL + phpMyAdmin</p>

    <div class="status-row ok">
        <span class="badge ok">OK</span>
        <div class="status-content">
            <strong>Aplikacja PHP działa</strong>
            <span>PHP <?= htmlspecialchars($phpVersion) ?> &middot; Apache</span>
        </div>
    </div>

    <div class="status-row <?= $dbStatus ? 'ok' : 'error' ?>">
        <span class="badge <?= $dbStatus ? 'ok' : 'error' ?>"><?= $dbStatus ? 'OK' : 'BŁĄD' ?></span>
        <div class="status-content">
            <strong><?= htmlspecialchars($dbMessage) ?></strong>
            <?php if ($dbStatus): ?>
                <span>MySQL <?= htmlspecialchars($dbVersion) ?> &middot; host: <?= htmlspecialchars($dbHost) ?> &middot; baza: <?= htmlspecialchars($dbName) ?></span>
            <?php else: ?>
                <span>Sprawdź logi: <code>docker compose logs db</code></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="status-row info">
        <span class="badge info">INFO</span>
        <div class="status-content">
            <strong>Środowisko deweloperskie</strong>
            <span>
                Kod źródłowy: <code>./src</code> &rarr; <code>/var/www/html</code><br>
                phpMyAdmin: <a href="http://localhost:8081" target="_blank">http://localhost:8081</a>
            </span>
        </div>
    </div>

    <div class="footer">
        Wygenerowano: <?= date('Y-m-d H:i:s') ?> &nbsp;|&nbsp;
        <a href="http://localhost:8081" target="_blank">Otwórz phpMyAdmin</a>
    </div>
</div>
</body>
</html>

