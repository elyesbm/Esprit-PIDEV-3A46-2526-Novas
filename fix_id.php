<?php
$dir = __DIR__ . '/src/Entity/';
foreach (glob($dir . '*.php') as $file) {
    if (!is_file($file)) continue;
    $content = file_get_contents($file);
    $content = str_replace("referencedColumnName: 'ID'", "referencedColumnName: 'id'", $content);
    file_put_contents($file, $content);
}
echo "IDs updated.\n";
