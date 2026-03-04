<?php
$dir = __DIR__ . '/src/Entity/';
foreach (glob($dir . '*.php') as $file) {
    if (!is_file($file)) continue;
    $content = file_get_contents($file);
    $content = preg_replace("/referencedColumnName:\s*'id_([a-zA-Z0-9_]+)'/", "referencedColumnName: '$1_id'", $content);
    $content = preg_replace('/referencedColumnName:\s*"id_([a-zA-Z0-9_]+)"/', 'referencedColumnName: "$1_id"', $content);
    file_put_contents($file, $content);
}
echo "referencedColumnNames updated.\n";
