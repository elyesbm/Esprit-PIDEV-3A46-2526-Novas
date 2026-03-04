<?php
use Dotenv\Dotenv;

require __DIR__.'/vendor/autoload.php';

if (file_exists(__DIR__.'/.env')) {
    (new \Symfony\Component\Dotenv\Dotenv())->bootEnv(__DIR__.'/.env');
}

use App\Kernel;

$kernel = new App\Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine')->getManager();
$conn = $em->getConnection();

$content = shell_exec('php bin/console doctrine:schema:update --dump-sql');
$content = str_replace(array("\r\n", "\n", "\r"), ' ', $content);

$sqls = array_filter(explode(';', $content));

$conn->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
foreach ($sqls as $sql) {
    if (stripos(trim($sql), 'ALTER TABLE') === 0 || stripos(trim($sql), 'CREATE') === 0 || stripos(trim($sql), 'DROP') === 0) {
        try {
            $conn->executeStatement($sql);
            echo "Executed: " . substr(trim($sql), 0, 50) . "...\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
$conn->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
echo "Done DB update.\n";
