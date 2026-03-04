<?php

$dir = __DIR__ . '/src/Entity/';
foreach (glob($dir . '*.php') as $file) {
    if (!is_file($file)) continue;
    $content = file_get_contents($file);
    
    // 1. Foreign keys: id_something to something_id
    $content = preg_replace("/name:\s*'id_([a-zA-Z0-9_]+)'/", "name: '$1_id'", $content);
    $content = preg_replace('/name:\s*"id_([a-zA-Z0-9_]+)"/', 'name: "$1_id"', $content);
    
    // 2. ACTIF and ID to snake_case id -> lowercase
    // Wait, the professor screenshot says ACTIF should be snake_case. So we leave it for now.
    
    file_put_contents($file, $content);
}
echo "Foreign keys updated.\n";
