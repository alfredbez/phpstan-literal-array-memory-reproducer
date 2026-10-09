<?php

declare(strict_types=1);

// Usage: php generate_fixture.php [entries]
// Writes two files with the same runtime value: a literal array and the same
// data read from a JSON file.
$entries = (int) ($argv[1] ?? 20000);
$directory = __DIR__ . '/generated';
if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}

$data = [];
for ($i = 0; $i < $entries; $i++) {
    $data[sprintf('%02d-%03d', intdiv($i, 1000), $i % 1000)] = sprintf('%.4f,%.4f', 50 + $i / 1e4, 20 - $i / 1e4);
}

$literal = "<?php\n\ndeclare(strict_types=1);\n\nreturn [\n";
foreach ($data as $key => $value) {
    $literal .= sprintf("    %s => %s,\n", var_export($key, true), var_export($value, true));
}
file_put_contents("$directory/literal.php", $literal . "];\n");

file_put_contents("$directory/data.json", json_encode($data, JSON_THROW_ON_ERROR));
file_put_contents(
    "$directory/json.php",
    "<?php\n\ndeclare(strict_types=1);\n\nreturn json_decode((string) file_get_contents(__DIR__ . '/data.json'), true, 512, JSON_THROW_ON_ERROR);\n",
);

echo "Generated literal.php and json.php with $entries identical entries.\n";
