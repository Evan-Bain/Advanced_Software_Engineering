<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

function equipmentSelectSql(): string
{
    return "SELECT e.device_id,
                   e.device_type_id,
                   dt.type_name,
                   e.manufacturer_id,
                   m.manufacturer_name,
                   e.serial_number,
                   e.status
            FROM equipment e
            INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
            INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id";
}

try {
    $pdo = getDbConnection();
    $input = apiInput();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($input['device_id'])) {
            $deviceId = intParam($input, 'device_id');
            $equipment = fetchEquipment($pdo, $deviceId);

            if ($equipment === null) {
                apiResponse(false, 'Equipment was not found for the provided device_id.', [], 404);
            }

            apiResponse(true, 'Equipment entry returned successfully.', ['equipment' => $equipment]);
        }

        $searchMode = (string) ($input['search_mode'] ?? 'all');
        $status = optionalStatus($input, 'active');
        $params = [];
        $where = [];

        if ($status !== 'all') {
            $where[] = 'e.status = :status';
            $params[':status'] = $status;
        }

        if ($searchMode === 'device_type') {
            $deviceTypeId = intParam($input, 'device_type_id');
            if (fetchDeviceType($pdo, $deviceTypeId) === null) {
                apiResponse(false, 'Invalid search data: device type was not found.', [], 400);
            }
            $where[] = 'e.device_type_id = :device_type_id';
            $params[':device_type_id'] = $deviceTypeId;

            $manufacturerId = (string) ($input['manufacturer_id'] ?? 'all');
            if ($manufacturerId !== 'all') {
                if (filter_var($manufacturerId, FILTER_VALIDATE_INT) === false) {
                    apiResponse(false, 'Invalid search data: manufacturer_id must be an integer or all.', [], 400);
                }
                if (fetchManufacturer($pdo, (int) $manufacturerId) === null) {
                    apiResponse(false, 'Invalid search data: manufacturer was not found.', [], 400);
                }
                $where[] = 'e.manufacturer_id = :manufacturer_id';
                $params[':manufacturer_id'] = (int) $manufacturerId;
            }
        } elseif ($searchMode === 'manufacturer') {
            $manufacturerId = intParam($input, 'manufacturer_id');
            if (fetchManufacturer($pdo, $manufacturerId) === null) {
                apiResponse(false, 'Invalid search data: manufacturer was not found.', [], 400);
            }
            $where[] = 'e.manufacturer_id = :manufacturer_id';
            $params[':manufacturer_id'] = $manufacturerId;

            $deviceTypeId = (string) ($input['device_type_id'] ?? 'all');
            if ($deviceTypeId !== 'all') {
                if (filter_var($deviceTypeId, FILTER_VALIDATE_INT) === false) {
                    apiResponse(false, 'Invalid search data: device_type_id must be an integer or all.', [], 400);
                }
                if (fetchDeviceType($pdo, (int) $deviceTypeId) === null) {
                    apiResponse(false, 'Invalid search data: device type was not found.', [], 400);
                }
                $where[] = 'e.device_type_id = :device_type_id';
                $params[':device_type_id'] = (int) $deviceTypeId;
            }
        } elseif ($searchMode === 'serial_number') {
            $serialNumber = strtoupper(trim($input['serial_number'] ?? ''));
            if ($serialNumber === '') {
                apiResponse(false, 'Invalid search data: serial_number is required.', [], 400);
            }
            if (!isValidSerialNumber($serialNumber)) {
                apiResponse(false, 'Invalid search data: serial number must match SN- followed by 64 uppercase hex characters.', [], 400);
            }
            $where[] = 'e.serial_number = :serial_number';
            $params[':serial_number'] = $serialNumber;
        } elseif ($searchMode !== 'all') {
            apiResponse(false, 'Invalid search data: search_mode must be all, device_type, manufacturer, or serial_number.', [], 400);
        }

        $sql = equipmentSelectSql();
        if (count($where) > 0) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY e.device_id LIMIT 1000';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $equipment = $stmt->fetchAll();

        if (count($equipment) === 0) {
            apiResponse(true, 'No equipment matched the search criteria.', ['equipment' => []]);
        }

        apiResponse(true, 'Equipment search returned successfully. Results are limited to 1000 records.', [
            'limit' => 1000,
            'count' => count($equipment),
            'equipment' => $equipment,
        ]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $deviceTypeId = intParam($input, 'device_type_id');
        $manufacturerId = intParam($input, 'manufacturer_id');
        $serialNumber = strtoupper(trim($input['serial_number'] ?? ''));

        if ($serialNumber === '') {
            apiResponse(false, 'Serial number is required.', [], 400);
        }
        if (!isValidSerialNumber($serialNumber)) {
            apiResponse(false, 'Serial number must match SN- followed by 64 uppercase hex characters.', [], 400);
        }
        if (isSerialNumberInUse($pdo, $serialNumber)) {
            apiResponse(false, 'That serial number already exists.', [], 409);
        }

        ensureActiveDeviceType($pdo, $deviceTypeId);
        ensureActiveManufacturer($pdo, $manufacturerId);

        $stmt = $pdo->prepare(
            "INSERT INTO equipment (device_type_id, manufacturer_id, serial_number, status)
             VALUES (:device_type_id, :manufacturer_id, :serial_number, 'active')"
        );
        $stmt->execute([
            ':device_type_id' => $deviceTypeId,
            ':manufacturer_id' => $manufacturerId,
            ':serial_number' => $serialNumber,
        ]);

        $deviceId = (int) $pdo->lastInsertId();
        apiResponse(true, 'Equipment was added successfully.', [
            'equipment' => fetchEquipment($pdo, $deviceId),
        ], 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $deviceId = intParam($input, 'device_id');
        $deviceTypeId = intParam($input, 'device_type_id');
        $manufacturerId = intParam($input, 'manufacturer_id');
        $serialNumber = strtoupper(trim($input['serial_number'] ?? ''));
        $status = writableStatus($input);

        if (fetchEquipment($pdo, $deviceId) === null) {
            apiResponse(false, 'Equipment was not found.', [], 404);
        }
        if ($serialNumber === '') {
            apiResponse(false, 'Serial number is required.', [], 400);
        }
        if (!isValidSerialNumber($serialNumber)) {
            apiResponse(false, 'Serial number must match SN- followed by 64 uppercase hex characters.', [], 400);
        }
        if (isSerialNumberInUse($pdo, $serialNumber, $deviceId)) {
            apiResponse(false, 'That serial number already exists for another equipment record.', [], 409);
        }

        ensureActiveDeviceType($pdo, $deviceTypeId);
        ensureActiveManufacturer($pdo, $manufacturerId);

        $stmt = $pdo->prepare(
            "UPDATE equipment
             SET device_type_id = :device_type_id,
                 manufacturer_id = :manufacturer_id,
                 serial_number = :serial_number,
                 status = :status
             WHERE device_id = :device_id"
        );
        $stmt->execute([
            ':device_type_id' => $deviceTypeId,
            ':manufacturer_id' => $manufacturerId,
            ':serial_number' => $serialNumber,
            ':status' => $status,
            ':device_id' => $deviceId,
        ]);

        apiResponse(true, 'Equipment was updated successfully.', [
            'equipment' => fetchEquipment($pdo, $deviceId),
        ]);
    }

    requireMethod(['GET', 'POST', 'PUT']);
} catch (PDOException $e) {
    apiResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
