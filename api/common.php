<?php
declare(strict_types=1);

require_once __DIR__ . '/../web/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: https://ec2-18-119-235-98.us-east-2.compute.amazonaws.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function apiResponse(bool $success, string $message, array $data = [], int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => (object) $data,
    ], JSON_PRETTY_PRINT);
    exit;
}

function apiInput(): array
{
    $input = $_REQUEST;
    $rawBody = file_get_contents('php://input');

    if ($rawBody !== false && trim($rawBody) !== '') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded)) {
                apiResponse(false, 'Request body must be valid JSON.', [], 400);
            }
            $input = array_merge($input, $decoded);
        } else {
            parse_str($rawBody, $parsed);
            if (is_array($parsed)) {
                $input = array_merge($input, $parsed);
            }
        }
    }

    return $input;
}

function requireMethod(array $allowedMethods): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods, true)) {
        apiResponse(false, 'HTTP method is not allowed for this endpoint.', [
            'allowed_methods' => $allowedMethods,
        ], 405);
    }
}

function intParam(array $input, string $name): int
{
    if (!isset($input[$name]) || filter_var($input[$name], FILTER_VALIDATE_INT) === false) {
        apiResponse(false, "$name must be a valid integer.", [], 400);
    }

    return (int) $input[$name];
}

function optionalStatus(array $input, string $default = 'active'): string
{
    $status = (string) ($input['status'] ?? $input['status_filter'] ?? $default);
    if (!in_array($status, ['active', 'inactive', 'all'], true)) {
        apiResponse(false, 'Status must be active, inactive, or all.', [], 400);
    }

    return $status;
}

function writableStatus(array $input): string
{
    $status = (string) ($input['status'] ?? '');
    if (!in_array($status, ['active', 'inactive'], true)) {
        apiResponse(false, 'Status must be active or inactive.', [], 400);
    }

    return $status;
}

function fetchDeviceType(PDO $pdo, int $deviceTypeId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM device_types WHERE device_type_id = :device_type_id');
    $stmt->execute([':device_type_id' => $deviceTypeId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function fetchManufacturer(PDO $pdo, int $manufacturerId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM manufacturers WHERE manufacturer_id = :manufacturer_id');
    $stmt->execute([':manufacturer_id' => $manufacturerId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function fetchEquipment(PDO $pdo, int $deviceId): ?array
{
    $stmt = $pdo->prepare(
        "SELECT e.device_id,
                e.device_type_id,
                dt.type_name,
                e.manufacturer_id,
                m.manufacturer_name,
                e.serial_number,
                e.status
         FROM equipment e
         INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
         INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id
         WHERE e.device_id = :device_id"
    );
    $stmt->execute([':device_id' => $deviceId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function ensureActiveDeviceType(PDO $pdo, int $deviceTypeId): void
{
    $deviceType = fetchDeviceType($pdo, $deviceTypeId);
    if ($deviceType === null) {
        apiResponse(false, 'Device type was not found.', [], 404);
    }
    if ($deviceType['status'] !== 'active') {
        apiResponse(false, 'Selected device type is not active.', [], 400);
    }
}

function ensureActiveManufacturer(PDO $pdo, int $manufacturerId): void
{
    $manufacturer = fetchManufacturer($pdo, $manufacturerId);
    if ($manufacturer === null) {
        apiResponse(false, 'Manufacturer was not found.', [], 404);
    }
    if ($manufacturer['status'] !== 'active') {
        apiResponse(false, 'Selected manufacturer is not active.', [], 400);
    }
}
