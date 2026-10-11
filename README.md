# PHPStan memory use for a large literal array

Generate 20,000 string entries, then analyse a PHP array literal and a JSON-loading control in separate processes.

```sh
composer install
XDEBUG_MODE=off php run.php
```

Requires PHP 8.2 or newer and Composer. To compare another version or input size:

```sh
XDEBUG_MODE=off php run.php /path/to/phpstan.phar 40000
```

Each run uses a fresh temporary directory and `--debug -vvv`, at level 6.
The two generated files return identical runtime values, but the JSON control does not require PHPStan to infer every literal entry. It is a lower-cost control, not equivalent static-analysis work.

The [CI workflow](https://github.com/alfredbez/phpstan-literal-array-memory-reproducer/actions/workflows/reproduce.yml)
runs the pinned version, records both measurements, and verifies that both fixtures
return the same 20,000 entries. Analysis errors fail the workflow. There is no
timing threshold: a green run verifies the setup, not a performance improvement.

## Measurements

macOS arm64, PHP 8.5.10, Xdebug disabled, 20,000 entries. Medians of three runs; all analyses exit 0.

| PHPStan | Literal: peak increase / time | JSON control: peak increase / time |
|---|---|---|
| 2.3.0 | 121.5 MB / 1.19 s | 9.98 MB / 0.07 s |
| 2.3.1 | 121.5 MB / 1.19 s | 7.98 MB / 0.07 s |

The table uses standalone PHPStan installations passed to `run.php`. A separate check using this repository's Composer-installed 2.3.1 reported 119.52 MB / 1.20 s for the literal and 6 MB / 0.07 s for the control. Small peak-increase differences depend on process initialization.

`consumed` is the increase in `memory_get_peak_usage(true)` during analysis of the file, not retained memory or RSS. These are debug-mode measurements, not normal parallel throughput.

The large-array cost remains in 2.3.1; this comparison does **not** show a regression from 2.3.0 or a memory leak. It is a reduced profiling case for finding whether literal-array inference can use less memory without losing useful type information.
