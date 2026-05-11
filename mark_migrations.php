<?php
require __DIR__.'/vendor/autoload.php';

if (file_exists(__DIR__.'/.env')) {
    (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__.'/.env');
}

use App\Kernel;

$kernel = new App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$conn = $em->getConnection();

$migrationDir = __DIR__ . '/migrations';
$migrationFiles = glob($migrationDir . '/Version*.php');

foreach ($migrationFiles as $file) {
    $version = basename($file, '.php');
    $fullVersion = 'DoctrineMigrations\\' . $version;
    try {
        $conn->executeStatement("INSERT IGNORE INTO doctrine_migration_versions (version) VALUES (?)", [$fullVersion]);
        echo "Marked $fullVersion as executed.\n";
    } catch (\Exception $e) {
        echo "Error marking $fullVersion: " . $e->getMessage() . "\n";
    }
}

echo "Done.\n";
?>