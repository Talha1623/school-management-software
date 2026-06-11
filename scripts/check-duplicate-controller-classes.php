<?php

/**
 * Scan app/Http/Controllers for duplicate PHP class names.
 * Run: php scripts/check-duplicate-controller-classes.php
 */

$controllersDir = dirname(__DIR__) . '/app/Http/Controllers';

if (!is_dir($controllersDir)) {
    fwrite(STDERR, "Controllers directory not found: {$controllersDir}\n");
    exit(1);
}

$declarations = [];
$issues = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($controllersDir, FilesystemIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }

    $path = $file->getPathname();
    $contents = file_get_contents($path);
    if ($contents === false) {
        continue;
    }

    $expectedClass = basename($path, '.php');

    if (preg_match_all('/^\s*class\s+([A-Za-z_][A-Za-z0-9_]*)\s+/m', $contents, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $match) {
            $class = $match[0];
            $line = substr_count(substr($contents, 0, $match[1]), "\n") + 1;

            if ($class !== $expectedClass) {
                $issues[] = [
                    'class' => "{$class} (WRONG CLASS in {$expectedClass}.php — file should declare class {$expectedClass})",
                    'first' => ['file' => $path, 'line' => $line],
                    'second' => ['file' => $path, 'line' => $line],
                ];
            }

            if (isset($declarations[$class])) {
                $issues[] = [
                    'class' => $class,
                    'first' => $declarations[$class],
                    'second' => ['file' => $path, 'line' => $line],
                ];
            } else {
                $declarations[$class] = ['file' => $path, 'line' => $line];
            }
        }
    }

    if (preg_match_all('/^\s*class\s+AccountantController\s+/m', $contents, $acctMatches, PREG_OFFSET_CAPTURE)) {
        foreach ($acctMatches[0] as $i => $match) {
            if ($i === 0) {
                continue;
            }
            $line = substr_count(substr($contents, 0, $acctMatches[1][$i][1]), "\n") + 1;
            $issues[] = [
                'class' => 'AccountantController (DUPLICATE IN SAME FILE — delete from line ' . $line . ')',
                'first' => ['file' => $path, 'line' => substr_count(substr($contents, 0, $acctMatches[1][0][1]), "\n") + 1],
                'second' => ['file' => $path, 'line' => $line],
            ];
        }
    }
}

if ($issues === []) {
    echo "OK: No duplicate controller class names found.\n";
    exit(0);
}

echo "Duplicate controller classes found:\n";
foreach ($issues as $issue) {
    echo "- {$issue['class']}: {$issue['first']['file']} ({$issue['first']['line']})";
    echo " AND {$issue['second']['file']} ({$issue['second']['line']})\n";
}

exit(1);
