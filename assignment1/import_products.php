<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line only.\n");
    exit(1);
}

set_time_limit(0);
ini_set('memory_limit', '256M');
date_default_timezone_set('America/Chicago');

$config = [
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'assignment1',
    'db_user' => 'root',
    'db_pass' => 'password',

    'csv_file' => __DIR__ . '/aed497.csv',
    'summary_log_file' => __DIR__ . '/import_summary.txt',

    'commit_every' => 5000,
    'progress_every' => 100000,
    'throttle_every' => 50000,
    'throttle_usleep' => 100000,
];

$allowedProducts = [
    'mobile phone' => true,
    'smart watch'  => true,
    'tablet'       => true,
    'laptop'       => true,
    'television'   => true,
    'computer'     => true,
    'vehicle'      => true,
];

$serialRegex = '/^SN-[a-f0-9]{64}$/';

$totalRows = 0;
$successfulRows = 0;
$totalErrorRows = 0;
$successfulSinceCommit = 0;

$errorCounts = [
    'blank_record'           => 0,
    'wrong_column_count'     => 0,
    'missing_required_field' => 0,
    'invalid_product_type'   => 0,
    'invalid_serial_format'  => 0,
    'duplicate_serial'       => 0,
    'database_insert_error'  => 0,
];

$startTime = microtime(true);

function normalizeField(?string $value): string
{
    return trim((string)$value);
}

function isBlankRow(array $row): bool
{
    foreach ($row as $value) {
        if (trim((string)$value) !== '') {
            return false;
        }
    }
    return true;
}

function rawRowToString(array $row): string
{
    $escaped = array_map(
        static function ($v): string {
            $v = (string)$v;
            return str_replace(["\r", "\n"], ['\\r', '\\n'], $v);
        },
        $row
    );

    return implode(',', $escaped);
}

function incrementError(array &$errorCounts, string $type, int &$totalErrorRows): void
{
    $errorCounts[$type]++;
    $totalErrorRows++;
}

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    $pdo = new PDO(
        $dsn,
        $config['db_user'],
        $config['db_pass'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$insertGoodSql = "
    INSERT INTO products_import (product_type, brand, serial_number)
    VALUES (:product_type, :brand, :serial_number)
";

$insertErrorSql = "
    INSERT INTO products_import_errors (csv_line_number, error_type, error_message, raw_row)
    VALUES (:csv_line_number, :error_type, :error_message, :raw_row)
";

$insertGoodStmt = $pdo->prepare($insertGoodSql);
$insertErrorStmt = $pdo->prepare($insertErrorSql);

file_put_contents($config['summary_log_file'], '');

$file = new SplFileObject($config['csv_file'], 'r');
$file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
$file->setCsvControl(',');

$pdo->beginTransaction();

foreach ($file as $lineNumberZeroBased => $row) {
    if ($row === [null] || $row === false) {
        continue;
    }

    $lineNumber = $lineNumberZeroBased + 1;
    $totalRows++;

    if ($config['progress_every'] > 0 && $totalRows % $config['progress_every'] === 0) {
        $elapsed = microtime(true) - $startTime;
        $rate = $elapsed > 0 ? $successfulRows / $elapsed : 0;

        fwrite(
            STDOUT,
            sprintf(
                "[%s] Processed: %d | Imported: %d | Errors: %d | Rate: %.2f rows/sec%s",
                date('Y-m-d H:i:s'),
                $totalRows,
                $successfulRows,
                $totalErrorRows,
                $rate,
                PHP_EOL
            )
        );
    }

    if ($config['throttle_every'] > 0 && $totalRows % $config['throttle_every'] === 0) {
        usleep((int)$config['throttle_usleep']);
    }

    $rawRow = rawRowToString($row);

    // blank row
    if (isBlankRow($row)) {
        incrementError($errorCounts, 'blank_record', $totalErrorRows);

        $insertErrorStmt->execute([
            ':csv_line_number' => $lineNumber,
            ':error_type' => 'blank_record',
            ':error_message' => 'Entire row is blank',
            ':raw_row' => $rawRow,
        ]);
        continue;
    }

    // wrong column count
    if (count($row) !== 3) {
        incrementError($errorCounts, 'wrong_column_count', $totalErrorRows);

        $insertErrorStmt->execute([
            ':csv_line_number' => $lineNumber,
            ':error_type' => 'wrong_column_count',
            ':error_message' => 'Expected 3 columns, found ' . count($row),
            ':raw_row' => $rawRow,
        ]);
        continue;
    }

    $productType = normalizeField($row[0]);
    $brand = normalizeField($row[1]);
    $serialNumber = normalizeField($row[2]);

    // missing required field
    if ($productType === '' || $brand === '' || $serialNumber === '') {
        incrementError($errorCounts, 'missing_required_field', $totalErrorRows);

        $insertErrorStmt->execute([
            ':csv_line_number' => $lineNumber,
            ':error_type' => 'missing_required_field',
            ':error_message' => 'One or more required fields are blank',
            ':raw_row' => $rawRow,
        ]);
        continue;
    }

    // invalid product type
    if (!isset($allowedProducts[$productType])) {
        incrementError($errorCounts, 'invalid_product_type', $totalErrorRows);

        $insertErrorStmt->execute([
            ':csv_line_number' => $lineNumber,
            ':error_type' => 'invalid_product_type',
            ':error_message' => 'Unexpected product type: ' . $productType,
            ':raw_row' => $rawRow,
        ]);
        continue;
    }

    // invalid serial format
    if (!preg_match($serialRegex, $serialNumber)) {
        incrementError($errorCounts, 'invalid_serial_format', $totalErrorRows);

        $insertErrorStmt->execute([
            ':csv_line_number' => $lineNumber,
            ':error_type' => 'invalid_serial_format',
            ':error_message' => 'Serial number format invalid',
            ':raw_row' => $rawRow,
        ]);
        continue;
    }

    try {
        $insertGoodStmt->execute([
            ':product_type' => $productType,
            ':brand' => $brand,
            ':serial_number' => $serialNumber,
        ]);

        $successfulRows++;
        $successfulSinceCommit++;

        if ($successfulSinceCommit >= $config['commit_every']) {
            $pdo->commit();
            $pdo->beginTransaction();
            $successfulSinceCommit = 0;
        }
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            incrementError($errorCounts, 'duplicate_serial', $totalErrorRows);

            $insertErrorStmt->execute([
                ':csv_line_number' => $lineNumber,
                ':error_type' => 'duplicate_serial',
                ':error_message' => 'Serial number already exists',
                ':raw_row' => $rawRow,
            ]);
        } else {
            incrementError($errorCounts, 'database_insert_error', $totalErrorRows);

            $insertErrorStmt->execute([
                ':csv_line_number' => $lineNumber,
                ':error_type' => 'database_insert_error',
                ':error_message' => mb_substr($e->getMessage(), 0, 255),
                ':raw_row' => $rawRow,
            ]);
        }
    }
}

if ($pdo->inTransaction()) {
    $pdo->commit();
}

$endTime = microtime(true);
$totalTime = $endTime - $startTime;
$rowsPerSecond = $totalTime > 0 ? ($successfulRows / $totalTime) : 0.0;

$summaryLines = [
    "CSV Import Summary",
    "==================",
    "File: " . $config['csv_file'],
    "Total rows processed: " . $totalRows,
    "Successfully imported: " . $successfulRows,
    "Total error rows: " . $totalErrorRows,
    "Total import time (seconds): " . number_format($totalTime, 4),
    "Effective rows per second: " . number_format($rowsPerSecond, 4),
    "",
    "Error Breakdown",
    "--------------",
];

foreach ($errorCounts as $type => $count) {
    $summaryLines[] = $type . ': ' . $count;
}

$summaryLines[] = '';
$summaryLines[] = 'Completed at: ' . date('Y-m-d H:i:s');

$summaryText = implode(PHP_EOL, $summaryLines) . PHP_EOL;
file_put_contents($config['summary_log_file'], $summaryText, LOCK_EX);

fwrite(STDOUT, PHP_EOL . $summaryText . PHP_EOL);