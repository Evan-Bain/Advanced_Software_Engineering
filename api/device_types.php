<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

try {
    $pdo = getDbConnection();
    $input = apiInput();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = optionalStatus($input, 'active');
        $sql = 'SELECT device_type_id, type_name, status FROM device_types';
        $params = [];

        if ($status !== 'all') {
            $sql .= ' WHERE status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY type_name LIMIT 1000';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $deviceTypes = $stmt->fetchAll();

        if (count($deviceTypes) === 0) {
            apiResponse(true, 'No device types matched the request.', ['device_types' => []]);
        }

        apiResponse(true, 'Device types returned successfully.', ['device_types' => $deviceTypes]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $typeName = normalizeName($input['type_name'] ?? '');

        if ($typeName === '') {
            apiResponse(false, 'Device type name is required.', [], 400);
        }
        if (!isValidDeviceTypeName($typeName)) {
            apiResponse(false, 'Device type name may only contain alphabet letters and spaces.', [], 400);
        }
        if (deviceTypeNameExists($pdo, $typeName)) {
            apiResponse(false, 'That device type already exists.', [], 409);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO device_types (type_name, status)
             VALUES (:type_name, 'active')"
        );
        $stmt->execute([':type_name' => $typeName]);

        apiResponse(true, 'Device type was added successfully.', [
            'device_type' => [
                'device_type_id' => (int) $pdo->lastInsertId(),
                'type_name' => $typeName,
                'status' => 'active',
            ],
        ], 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $deviceTypeId = intParam($input, 'device_type_id');
        $typeName = normalizeName($input['type_name'] ?? '');
        $status = writableStatus($input);

        if (fetchDeviceType($pdo, $deviceTypeId) === null) {
            apiResponse(false, 'Device type was not found.', [], 404);
        }
        if ($typeName === '') {
            apiResponse(false, 'Device type name is required.', [], 400);
        }
        if (!isValidDeviceTypeName($typeName)) {
            apiResponse(false, 'Device type name may only contain alphabet letters and spaces.', [], 400);
        }
        if (deviceTypeNameExists($pdo, $typeName, $deviceTypeId)) {
            apiResponse(false, 'That device type name already belongs to another row.', [], 409);
        }

        $stmt = $pdo->prepare(
            "UPDATE device_types
             SET type_name = :type_name,
                 status = :status
             WHERE device_type_id = :device_type_id"
        );
        $stmt->execute([
            ':type_name' => $typeName,
            ':status' => $status,
            ':device_type_id' => $deviceTypeId,
        ]);

        apiResponse(true, 'Device type was updated successfully.', [
            'device_type' => fetchDeviceType($pdo, $deviceTypeId),
        ]);
    }

    requireMethod(['GET', 'POST', 'PUT']);
} catch (PDOException $e) {
    apiResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
