<?php

/**
 * PHP Environment Diagnostics
 *
 * Checks: PHP runtime, extensions, MySQL connectivity, Xdebug status.
 */

header('Content-Type: text/html; charset=utf-8');

function status(bool $ok): string
{
    return $ok ? '<span style="color:#22c55e;font-weight:bold">OK</span>'
        : '<span style="color:#ef4444;font-weight:bold">FAIL</span>';
}

function row(string $label, bool $ok, string $detail = ''): string
{
    $status = status($ok);
    $detail = $detail ? " &mdash; <code>$detail</code>" : '';
    return "<tr><td>$label</td><td>$status$detail</td></tr>";
}

// ── Nginx / Web Server ──────────────────────────────────────────────────
$serverSoftware  = $_SERVER['SERVER_SOFTWARE'] ?? 'unknown';
$isNginx         = stripos($serverSoftware, 'nginx') !== false;
$documentRoot    = $_SERVER['DOCUMENT_ROOT'] ?? '';
$serverName      = $_SERVER['SERVER_NAME'] ?? '';
$serverPort      = $_SERVER['SERVER_PORT'] ?? '';
$requestScheme   = $_SERVER['REQUEST_SCHEME'] ?? ($_SERVER['HTTPS'] ?? 'off') !== 'off' ? 'https' : 'http';
$fastcgiParam    = $_SERVER['SCRIPT_FILENAME'] ?? '';
$isFpm           = php_sapi_name() === 'fpm-fcgi';

// ── PHP info ────────────────────────────────────────────────────────────
$phpVersion   = phpversion();
$sapi         = php_sapi_name();
$os           = PHP_OS;
$memoryLimit  = ini_get('memory_limit');

// ── Extensions ──────────────────────────────────────────────────────────
$requiredExtensions = [
    'pdo_mysql',
    'mbstring',
    'exif',
    'pcntl',
    'bcmath',
    'gd',
    'zip',
    'xdebug',
];

$extensionResults = [];
foreach ($requiredExtensions as $ext) {
    $extensionResults[$ext] = extension_loaded($ext);
}

// ── Xdebug ──────────────────────────────────────────────────────────────
$xdebugLoaded  = extension_loaded('xdebug');
$xdebugVersion = $xdebugLoaded ? phpversion('xdebug') : null;
$xdebugMode    = $xdebugLoaded ? ini_get('xdebug.mode') : null;
$xdebugClient  = $xdebugLoaded ? ini_get('xdebug.client_host') . ':' . ini_get('xdebug.client_port') : null;
$xdebugIdeKey  = $xdebugLoaded ? ini_get('xdebug.idekey') : null;
$xdebugStart   = $xdebugLoaded ? ini_get('xdebug.start_with_request') : null;

// ── Composer & Autoloader ───────────────────────────────────────────────
$composerBin     = trim(shell_exec('which composer 2>/dev/null') ?: '');
$composerInstalled = $composerBin !== '';
$composerVersion   = $composerInstalled ? trim(shell_exec('composer --version 2>/dev/null') ?: '') : null;

$composerJsonPath  = __DIR__ . '/composer.json';
$composerJsonExists = file_exists($composerJsonPath);

$autoloaderPath  = __DIR__ . '/vendor/autoload.php';
$autoloaderExists = file_exists($autoloaderPath);
$autoloaderWorks  = false;
$autoloaderError  = '';

if ($autoloaderExists) {
    try {
        $loader = require $autoloaderPath;
        $autoloaderWorks = ($loader !== false);
    } catch (\Throwable $e) {
        $autoloaderError = $e->getMessage();
    }
}

// ── Autoload class test ─────────────────────────────────────────────────
$testClasses = [
        \App\Models\User::class,
        \App\Services\MathService::class,
];

$classResults = [];
foreach ($testClasses as $fqcn) {
    try {
        if (!class_exists($fqcn)) {
            $classResults[$fqcn] = ['ok' => false, 'detail' => 'class not found'];
            continue;
        }
        $instance = new $fqcn();
        $detail = match (true) {
            method_exists($instance, 'hello') => $instance->hello(),
            method_exists($instance, 'sum')   => '2 + 3 = ' . $instance->sum(2, 3),
            default                           => 'instantiated OK',
        };
        $classResults[$fqcn] = ['ok' => true, 'detail' => $detail];
    } catch (\Throwable $e) {
        $classResults[$fqcn] = ['ok' => false, 'detail' => $e->getMessage()];
    }
}

// ── MySQL ───────────────────────────────────────────────────────────────
$dbHost = getenv('MYSQL_HOST') ?: 'mysql';
$dbName = getenv('MYSQL_DATABASE') ?: '';
$dbUser = getenv('MYSQL_USER') ?: '';
$dbPass = getenv('MYSQL_PASSWORD') ?: '';

$dbOk      = false;
$dbVersion = '';
$dbError   = '';
$dbTables  = null;

try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT    => 5,
    ]);
    $dbVersion = $pdo->query('SELECT VERSION()')->fetchColumn();
    $dbTables  = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $dbOk      = true;
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PHP Diagnostics</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        h1 { font-size: 1.5rem; margin-bottom: 1.5rem; color: #f8fafc; }
        h2 { font-size: 1.1rem; margin: 1.5rem 0 .75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .05em; font-weight: 600; }
        .card { background: #1e293b; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1rem; border: 1px solid #334155; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: .4rem 0; vertical-align: top; }
        td:first-child { width: 240px; color: #94a3b8; }
        code { background: #334155; padding: 2px 6px; border-radius: 4px; font-size: .85em; }
    </style>
</head>
<body>

<h1>PHP Environment Diagnostics</h1>

<!-- Nginx -->
<h2>Nginx / Web Server</h2>
<div class="card">
    <table>
        <?= row('Page served', true, 'nginx &rarr; php-fpm pipeline is working') ?>
        <?= row('Server software', $isNginx, $serverSoftware) ?>
        <?= row('FastCGI (PHP-FPM)', $isFpm, $isFpm ? 'fpm-fcgi' : php_sapi_name()) ?>
        <?= row('Server name', true, $serverName . ':' . $serverPort) ?>
        <?= row('Document root', true, $documentRoot) ?>
        <?= row('SCRIPT_FILENAME', true, $fastcgiParam) ?>
        <?= row('Scheme', true, $requestScheme) ?>
    </table>
</div>

<!-- PHP -->
<h2>PHP Runtime</h2>
<div class="card">
    <table>
        <?= row('PHP Version', true, $phpVersion) ?>
        <?= row('SAPI', true, $sapi) ?>
        <?= row('OS', true, $os) ?>
        <?= row('Memory Limit', true, $memoryLimit) ?>
        <?= row('Date/Timezone', true, date_default_timezone_get() . ' &mdash; ' . date('Y-m-d H:i:s')) ?>
    </table>
</div>

<!-- Extensions -->
<h2>PHP Extensions</h2>
<div class="card">
    <table>
        <?php foreach ($extensionResults as $ext => $loaded): ?>
            <?= row($ext, $loaded, $loaded ? phpversion($ext) ?: 'loaded' : 'not loaded') ?>
        <?php endforeach; ?>
    </table>
</div>

<!-- Xdebug -->
<h2>Xdebug</h2>
<div class="card">
    <table>
        <?= row('Extension loaded', $xdebugLoaded) ?>
        <?php if ($xdebugLoaded): ?>
            <?= row('Version', true, $xdebugVersion) ?>
            <?= row('Mode', true, $xdebugMode) ?>
            <?= row('Client (host:port)', true, $xdebugClient) ?>
            <?= row('IDE Key', true, $xdebugIdeKey) ?>
            <?= row('Start with request', true, $xdebugStart) ?>
        <?php else: ?>
            <tr><td colspan="2"><span style="color:#ef4444">Xdebug is not installed or not enabled.</span></td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- MySQL -->
<h2>MySQL</h2>
<div class="card">
    <table>
        <?= row('Connection', $dbOk, $dbOk ? "$dbUser@$dbHost/$dbName" : htmlspecialchars($dbError)) ?>
        <?php if ($dbOk): ?>
            <?= row('Server Version', true, $dbVersion) ?>
            <?= row('Tables in DB', true, $dbTables ? implode(', ', $dbTables) : '(empty database)') ?>
        <?php endif; ?>
    </table>
</div>

<!-- Composer & Autoloader -->
<h2>Composer &amp; Autoloader</h2>
<div class="card">
    <table>
        <?= row('Composer installed', $composerInstalled, $composerInstalled ? $composerVersion : 'not found in PATH') ?>
        <?= row('composer.json', $composerJsonExists, $composerJsonExists ? $composerJsonPath : 'not found') ?>
        <?php if (!$composerJsonExists): ?>
            <tr><td colspan="2"><span style="color:#f59e0b">Run <code>composer init</code> to create composer.json</span></td></tr>
        <?php else: ?>
            <?= row('Autoloader exists', $autoloaderExists, $autoloaderPath) ?>
            <?php if ($autoloaderExists): ?>
                <?= row('Autoloader works', $autoloaderWorks, $autoloaderWorks ? 'require OK' : htmlspecialchars($autoloaderError)) ?>
            <?php else: ?>
                <tr><td colspan="2"><span style="color:#f59e0b">Run <code>composer install</code> to generate vendor/autoload.php</span></td></tr>
            <?php endif; ?>
        <?php endif; ?>
    </table>
</div>

<!-- Autoloaded Classes -->
<h2>Autoloaded Classes</h2>
<div class="card">
    <table>
        <?php foreach ($classResults as $fqcn => $result): ?>
            <?= row($fqcn, $result['ok'], $result['detail']) ?>
        <?php endforeach; ?>
    </table>
</div>

<p style="margin-top:2rem;color:#475569;font-size:.8rem;">
    Generated at <?= date('Y-m-d H:i:s T') ?>
</p>

</body>
</html>