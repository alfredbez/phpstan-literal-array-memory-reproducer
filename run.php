<?php

declare(strict_types=1);

// Usage: php run.php [path/to/phpstan] [entries]
// Analyses each variant in its own PHPStan process with an empty tmpDir and
// prints the file's line from --debug -vvv.
$phpstan = $argv[1] ?? __DIR__ . '/vendor/bin/phpstan';
if (!is_file($phpstan)) {
    fwrite(STDERR, "Run composer install first, or pass the path to a PHPStan binary or phar.\n");
    exit(1);
}
$entries = $argv[2] ?? '20000';

passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/generate_fixture.php') . ' ' . escapeshellarg($entries));

foreach (['literal', 'json'] as $variant) {
    $tmp = sys_get_temp_dir() . '/phpstan-literal-array-' . bin2hex(random_bytes(4));
    mkdir($tmp);
    $command = [PHP_BINARY, '-d', 'memory_limit=4G', $phpstan, 'analyse', '-c', 'phpstan.neon', '--debug', '-vvv', '--no-progress', "generated/$variant.php"];
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, __DIR__, ['TMPDIR' => $tmp] + getenv());
    if (!is_resource($process)) {
        throw new RuntimeException('Could not start PHPStan');
    }
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0 || !preg_match('/--- consumed ([^,]+), total [^,]+, took ([0-9.]+ s)/', $output, $matches)) {
        fwrite(STDERR, $output);
        throw new RuntimeException("PHPStan failed for $variant (exit $exit)");
    }
    printf("%-8s consumed %-10s took %s (exit %d)\n", $variant, $matches[1], $matches[2], $exit);
}
