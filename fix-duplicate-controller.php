<?php
/**
 * Removes wrongly pasted StudentPaymentController from other controller files.
 *
 * Run on server:  php fix-duplicate-controller.php
 * Then verify:   php check-duplicate-controller.php
 * Delete both scripts after use.
 */
$root = __DIR__;
$controllersDir = $root . '/app/Http/Controllers';
$class = 'StudentPaymentController';
$canonical = $controllersDir . DIRECTORY_SEPARATOR . 'StudentPaymentController.php';

if (!is_dir($controllersDir)) {
    fwrite(STDERR, "Controllers directory not found.\n");
    exit(1);
}

$fixed = 0;
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($controllersDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    if (realpath($path) === realpath($canonical)) {
        continue;
    }

    $contents = file_get_contents($path);
    if (!preg_match('/\bclass\s+' . preg_quote($class, '/') . '\b/', $contents)) {
        continue;
    }

    // Drop pasted block: optional second <?php + namespace, then class StudentPaymentController … EOF
    $pattern = '/\R\s*(?:<\?php\s*)?(?:\s*namespace\s+[^;]+;\s*)?(?:\s*use\s+[^;]+;\s*)*class\s+' . preg_quote($class, '/') . '\b[\s\S]*\z/';
    $cleaned = preg_replace($pattern, '', $contents, 1);

    if ($cleaned === null || $cleaned === $contents) {
        // Fallback: cut from first "class StudentPaymentController" to EOF
        $pos = strpos($contents, 'class ' . $class);
        if ($pos === false) {
            continue;
        }
        $cleaned = rtrim(substr($contents, 0, $pos)) . "\n";
    } else {
        $cleaned = rtrim($cleaned) . "\n";
    }

    if ($cleaned === $contents) {
        echo "SKIP (could not auto-fix): {$path}\n";
        continue;
    }

    file_put_contents($path, $cleaned);
    $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    echo "FIXED: {$relative}\n";
    $fixed++;

    // If we stripped too much, AdminAuthController must still exist in its own file.
    if (basename($path) === 'AdminAuthController.php'
        && !preg_match('/\bclass\s+AdminAuthController\b/', $cleaned)) {
        echo "WARN: AdminAuthController missing after fix — run: php restore-admin-auth-controller.php\n";
    }
}

$adminAuthPath = $controllersDir . DIRECTORY_SEPARATOR . 'AdminAuthController.php';
if (!is_file($adminAuthPath)
    || !preg_match('/\bclass\s+AdminAuthController\b/', (string) file_get_contents($adminAuthPath))) {
    echo "AdminAuthController.php is missing or empty — run: php restore-admin-auth-controller.php\n";
    exit(2);
}

echo str_repeat('-', 50) . "\n";
echo "Files fixed: {$fixed}\n";

if ($fixed > 0) {
    echo "Run: composer dump-autoload -o && php artisan optimize:clear\n";
    exit(0);
}

echo "Nothing to fix (no stray {$class} in other controllers).\n";
exit(0);
