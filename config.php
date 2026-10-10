<?php
define('DB_HOST', getenv('FLORAX_DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('FLORAX_DB_NAME') ?: 'flower_exchange');
define('DB_USER', getenv('FLORAX_DB_USER') ?: 'root');
define('DB_PASS', getenv('FLORAX_DB_PASS') ?: '');
define('DB_PORT', getenv('FLORAX_DB_PORT') ?: '3308');

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $ports = array_values(array_unique([DB_PORT, '3306', '3308']));
    $last = null;

    foreach ($ports as $port) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';port='.$port.';dbname='.DB_NAME.';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            return $pdo;
        } catch (Throwable $e) {
            $last = $e;
        }
    }

    // Database may not have been imported yet. Create it automatically if
    // the MySQL server itself is reachable.
    foreach ($ports as $port) {
        try {
            $server = new PDO(
                'mysql:host='.DB_HOST.';port='.$port.';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::MYSQL_ATTR_MULTI_STATEMENTS => true]
            );
            $server->exec('CREATE DATABASE IF NOT EXISTS `'.str_replace('`','``',DB_NAME).'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

            $pdo = new PDO(
                'mysql:host='.DB_HOST.';port='.$port.';dbname='.DB_NAME.';charset=utf8mb4',
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );

            $hasUsers = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=". $pdo->quote(DB_NAME) ." AND table_name='users'")->fetchColumn();
            if ($hasUsers === 0) {
                $schemaFile = __DIR__.'/database/flower_exchange.sql';
                if (is_file($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $sql = preg_replace('/^\s*CREATE DATABASE IF NOT EXISTS.*?;\s*/is','',$sql,1);
                    $sql = preg_replace('/^\s*USE\s+flower_exchange\s*;\s*/im','',$sql,1);
                    $pdo->exec($sql);
                }
            }
            return $pdo;
        } catch (Throwable $e) {
            $last = $e;
        }
    }

    throw new RuntimeException('Cannot connect to MySQL. Tried ports: '.implode(', ', $ports).'. Start MySQL in XAMPP.');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function require_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        echo json_encode(['ok'=>false,'message'=>'Security token expired. Refresh the page and try again.']);
        exit;
    }
}
?>