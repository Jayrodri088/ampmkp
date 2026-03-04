<?php
/**
 * One-time script to create RBAC tables and default admin.
 * Run once: http://localhost/ampmkp/admin/run_rbac_migration.php
 * Then delete this file or do not run again.
 */

// Load .env
$envFile = __DIR__ . '/../.env';
if (!file_exists($envFile)) {
    die('Missing .env file in project root.');
}
$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || strpos($line, '#') === 0) continue;
    $eq = strpos($line, '=');
    if ($eq !== false) {
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        putenv($key . '=' . $val);
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_DATABASE') ?: 'ampmkp_db';
$user = getenv('DB_USERNAME') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    header('Content-Type: text/plain; charset=utf-8');
    die('Database connection failed: ' . $e->getMessage() . "\nCheck DB_* in .env.");
}

// Run migration SQL (skip the admins INSERT - we do it with a real hash below)
$sqlFile = __DIR__ . '/database/migrations/create_rbac_tables.sql';
if (!file_exists($sqlFile)) {
    header('Content-Type: text/plain; charset=utf-8');
    die('Migration file not found: ' . $sqlFile);
}

$sql = file_get_contents($sqlFile);

// Split on semicolon+newline so we don't break JSON inside INSERTs
$statements = preg_split('/;\s*[\r\n]+/', $sql);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement === '') continue;
    if (preg_match('/^--/', $statement)) continue;
    $statement = rtrim($statement, " \t\r\n");
    if ($statement === '' || $statement === ';') continue;
    if (substr($statement, -1) !== ';') {
        $statement .= ';';
    }
    try {
        $pdo->exec($statement);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') === false && strpos($e->getMessage(), 'Duplicate') === false) {
            throw $e;
        }
    }
}

// Default super admin: email admin@angel.com, password Admin@2025
$defaultEmail = 'admin@angel.com';
$defaultPassword = 'Admin@2025';
$hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
$stmt->execute([$defaultEmail]);
$existing = $stmt->fetch();

if ($existing) {
    $pdo->prepare("UPDATE admins SET password = ?, status = 'active' WHERE email = ?")
       ->execute([$hash, $defaultEmail]);
    $message = "RBAC tables already existed. Default admin password was reset.\n";
} else {
    $pdo->prepare(
        "INSERT INTO admins (role_id, name, email, password, status) VALUES (1, 'Super Administrator', ?, ?, 'active')"
    )->execute([$defaultEmail, $hash]);
    $message = "Default super admin created.\n";
}

header('Content-Type: text/plain; charset=utf-8');
echo "RBAC migration completed successfully.\n\n";
echo $message;
echo "Login at: " . (isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI'] ?? '') . "/auth.php\n";
echo "Email: $defaultEmail\n";
echo "Password: $defaultPassword\n\n";
echo "Important: Delete this file (admin/run_rbac_migration.php) or do not run it again.\n";
