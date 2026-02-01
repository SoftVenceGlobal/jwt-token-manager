<?php

declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED);

// Try package's own vendor first (for standalone usage)
$packageAutoload = __DIR__ . '/../vendor/autoload.php';

// Fallback to parent project's vendor (for symlink development)
$projectAutoload = __DIR__ . '/../../../../vendor/autoload.php';

if (is_file($packageAutoload)) {
    require_once $packageAutoload;
} elseif (is_file($projectAutoload)) {
    require_once $projectAutoload;

    // Register package's test namespace manually
    spl_autoload_register(function ($class) {
        $prefix = 'DevToolbelt\\JwtTokenManager\\Tests\\';
        $baseDir = __DIR__ . '/';

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }

        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require $file;
        }
    });
}
