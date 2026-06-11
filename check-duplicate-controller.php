<?php
/**
 * Run on server: php check-duplicate-controller.php
 * Delete this file after fixing duplicates.
 */
$root = __DIR__;
$controllersDir = $root . '/app/Http/Controllers';
$class = $argv[1] ?? 'StudentPaymentController';

if (!is_dir($controllersDir)) {
    fwrite(STDERR, "Controllers directory not found: {$controllersDir}\n");
    exit(1);
}

$matches = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($controllersDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = $file->getPathname();
    $contents = file_get_contents($path);
    if (preg_match_all('/\bclass\s+' . preg_quote($class, '/') . '\b/', $contents, $m)) {
        $count = count($m[0]);
        if ($count > 0) {
            $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
            $matches[] = ['file' => $relative, 'count' => $count];
        }
    }
}

echo "Scan: app/Http/Controllers for class {$class}\n";
echo str_repeat('-', 50) . "\n";

if ($matches === []) {
    echo "No declarations found.\n";
    exit(1);
}

$total = array_sum(array_column($matches, 'count'));
foreach ($matches as $row) {
    echo $row['file'] . ' => ' . $row['count'] . " declaration(s)\n";
}

echo str_repeat('-', 50) . "\n";
echo "Total: {$total} declaration(s)\n";

if ($total === 1) {
    echo "OK — only one class in Controllers. If you still see 'already in use', clear OPcache and check .bak/copy files.\n";
    exit(0);
}

echo "FIX — remove duplicate block(s); only StudentPaymentController.php should declare this class.\n";
exit(2);
