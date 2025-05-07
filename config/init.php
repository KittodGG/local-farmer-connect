<?php
// config/init.php
function initializeDirectories() {
    $directories = [
        'public/images/categories',
        'public/images/default',
        'public/images/hero',
        'public/uploads/products',
        'public/uploads/stores',
        'public/uploads/users'
    ];

    foreach ($directories as $dir) {
        $fullPath = __DIR__ . '/../' . $dir;
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }
    }
}

initializeDirectories();
?>