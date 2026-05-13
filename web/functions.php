<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function isValidSerialNumber(string $serialNumber): bool
{
    return preg_match('/^SN-[0-9A-F]{64}$/', $serialNumber) === 1;
}

function isValidDeviceTypeName(string $typeName): bool
{
    return preg_match('/^[A-Za-z ]+$/', $typeName) === 1;
}

function isValidManufacturerName(string $manufacturerName): bool
{
    return preg_match('/^[A-Za-z ]+$/', $manufacturerName) === 1;
}

function normalizeName(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    return (string) $value;
}

function getActiveDeviceTypes(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT device_type_id, type_name
         FROM device_types
         WHERE status = 'active'
         ORDER BY type_name"
    );

    return $stmt->fetchAll();
}

function getActiveManufacturers(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT manufacturer_id, manufacturer_name
         FROM manufacturers
         WHERE status = 'active'
         ORDER BY manufacturer_name"
    );

    return $stmt->fetchAll();
}

function isSerialNumberInUse(PDO $pdo, string $serialNumber, ?int $excludeDeviceId = null): bool
{
    if ($excludeDeviceId === null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM equipment WHERE serial_number = :serial_number'
        );
        $stmt->execute([':serial_number' => $serialNumber]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM equipment
             WHERE serial_number = :serial_number
               AND device_id <> :device_id'
        );
        $stmt->execute([
            ':serial_number' => $serialNumber,
            ':device_id' => $excludeDeviceId,
        ]);
    }

    return (int) $stmt->fetchColumn() > 0;
}

function deviceTypeNameExists(PDO $pdo, string $typeName, ?int $excludeId = null): bool
{
    if ($excludeId === null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM device_types WHERE LOWER(type_name) = LOWER(:type_name)'
        );
        $stmt->execute([':type_name' => $typeName]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM device_types
             WHERE LOWER(type_name) = LOWER(:type_name)
               AND device_type_id <> :device_type_id'
        );
        $stmt->execute([
            ':type_name' => $typeName,
            ':device_type_id' => $excludeId,
        ]);
    }

    return (int) $stmt->fetchColumn() > 0;
}

function manufacturerNameExists(PDO $pdo, string $manufacturerName, ?int $excludeId = null): bool
{
    if ($excludeId === null) {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM manufacturers WHERE LOWER(manufacturer_name) = LOWER(:manufacturer_name)'
        );
        $stmt->execute([':manufacturer_name' => $manufacturerName]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM manufacturers
             WHERE LOWER(manufacturer_name) = LOWER(:manufacturer_name)
               AND manufacturer_id <> :manufacturer_id'
        );
        $stmt->execute([
            ':manufacturer_name' => $manufacturerName,
            ':manufacturer_id' => $excludeId,
        ]);
    }

    return (int) $stmt->fetchColumn() > 0;
}
