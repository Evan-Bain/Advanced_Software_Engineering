<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

/**
 * Escapes output for HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/**
 * Valid serial numbers must be SN- followed by 64 uppercase hex characters.
 */
function isValidSerialNumber(string $serialNumber): bool
{
    return preg_match('/^SN-[0-9A-F]{64}$/', $serialNumber) === 1;
}

/**
 * Device types allow alphabet letters and spaces only.
 */
function isValidDeviceTypeName(string $typeName): bool
{
    return preg_match('/^[A-Za-z ]+$/', $typeName) === 1;
}

/**
 * Manufacturers allow alphabet letters and spaces only.
 */
function isValidManufacturerName(string $manufacturerName): bool
{
    return preg_match('/^[A-Za-z ]+$/', $manufacturerName) === 1;
}

/**
 * Normalizes names entered by the user.
 */
function normalizeName(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    return (string) $value;
}

/**
 * Returns all active device types.
 *
 * @return array<int, array<string, mixed>>
 */
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

/**
 * Returns all active manufacturers.
 *
 * @return array<int, array<string, mixed>>
 */
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

/**
 * Checks whether a serial number already belongs to another record.
 */
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

/**
 * Checks whether a device type already exists, excluding one row if needed.
 */
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

/**
 * Checks whether a manufacturer name already exists, excluding one row if needed.
 */
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
