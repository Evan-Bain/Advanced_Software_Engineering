<?php
declare(strict_types=1);

require_once __DIR__ . '/common.php';

try {
    $pdo = getDbConnection();
    $input = apiInput();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $status = optionalStatus($input, 'active');
        $sql = 'SELECT manufacturer_id, manufacturer_name, status FROM manufacturers';
        $params = [];

        if ($status !== 'all') {
            $sql .= ' WHERE status = :status';
            $params[':status'] = $status;
        }

        $sql .= ' ORDER BY manufacturer_name LIMIT 1000';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $manufacturers = $stmt->fetchAll();

        if (count($manufacturers) === 0) {
            apiResponse(true, 'No manufacturers matched the request.', ['manufacturers' => []]);
        }

        apiResponse(true, 'Manufacturers returned successfully.', ['manufacturers' => $manufacturers]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $manufacturerName = normalizeName($input['manufacturer_name'] ?? '');

        if ($manufacturerName === '') {
            apiResponse(false, 'Manufacturer name is required.', [], 400);
        }
        if (!isValidManufacturerName($manufacturerName)) {
            apiResponse(false, 'Manufacturer name may only contain alphabet letters and spaces.', [], 400);
        }
        if (manufacturerNameExists($pdo, $manufacturerName)) {
            apiResponse(false, 'That manufacturer already exists.', [], 409);
        }

        $stmt = $pdo->prepare(
            "INSERT INTO manufacturers (manufacturer_name, status)
             VALUES (:manufacturer_name, 'active')"
        );
        $stmt->execute([':manufacturer_name' => $manufacturerName]);

        apiResponse(true, 'Manufacturer was added successfully.', [
            'manufacturer' => [
                'manufacturer_id' => (int) $pdo->lastInsertId(),
                'manufacturer_name' => $manufacturerName,
                'status' => 'active',
            ],
        ], 201);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $manufacturerId = intParam($input, 'manufacturer_id');
        $manufacturerName = normalizeName($input['manufacturer_name'] ?? '');
        $status = writableStatus($input);

        if (fetchManufacturer($pdo, $manufacturerId) === null) {
            apiResponse(false, 'Manufacturer was not found.', [], 404);
        }
        if ($manufacturerName === '') {
            apiResponse(false, 'Manufacturer name is required.', [], 400);
        }
        if (!isValidManufacturerName($manufacturerName)) {
            apiResponse(false, 'Manufacturer name may only contain alphabet letters and spaces.', [], 400);
        }
        if (manufacturerNameExists($pdo, $manufacturerName, $manufacturerId)) {
            apiResponse(false, 'That manufacturer name already belongs to another row.', [], 409);
        }

        $stmt = $pdo->prepare(
            "UPDATE manufacturers
             SET manufacturer_name = :manufacturer_name,
                 status = :status
             WHERE manufacturer_id = :manufacturer_id"
        );
        $stmt->execute([
            ':manufacturer_name' => $manufacturerName,
            ':status' => $status,
            ':manufacturer_id' => $manufacturerId,
        ]);

        apiResponse(true, 'Manufacturer was updated successfully.', [
            'manufacturer' => fetchManufacturer($pdo, $manufacturerId),
        ]);
    }

    requireMethod(['GET', 'POST', 'PUT']);
} catch (PDOException $e) {
    apiResponse(false, 'Database error: ' . $e->getMessage(), [], 500);
}
