<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$activeDeviceTypes = getActiveDeviceTypes($pdo);
$activeManufacturers = getActiveManufacturers($pdo);
$deviceId = isset($_GET['device_id']) ? (int) $_GET['device_id'] : (int) ($_POST['device_id'] ?? 0);
$equipment = null;
$successMessage = '';
$errorMessage = '';

if ($deviceId <= 0) {
    $errorMessage = 'A valid device ID is required.';
} else {
    try {
        $stmt = $pdo->prepare(
            "SELECT *
             FROM equipment
             WHERE device_id = :device_id"
        );
        $stmt->execute([':device_id' => $deviceId]);
        $equipment = $stmt->fetch();

        if ($equipment === false) {
            $equipment = null;
            $errorMessage = 'Equipment was not found.';
        }
    } catch (PDOException $e) {
        $errorMessage = 'Unable to load equipment: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $equipment !== null) {
    $deviceTypeId = isset($_POST['device_type_id']) ? (int) $_POST['device_type_id'] : 0;
    $manufacturerId = isset($_POST['manufacturer_id']) ? (int) $_POST['manufacturer_id'] : 0;
    $serialNumber = strtoupper(trim($_POST['serial_number'] ?? ''));
    $status = $_POST['status'] ?? '';

    if ($deviceTypeId <= 0 || $manufacturerId <= 0 || $serialNumber === '' || !in_array($status, ['active', 'inactive'], true)) {
        $errorMessage = 'All fields are required.';
    } elseif (!isValidSerialNumber($serialNumber)) {
        $errorMessage = 'Serial number must match SN- followed by 64 characters using only 0-9 and A-F.';
    } elseif (isSerialNumberInUse($pdo, $serialNumber, $deviceId)) {
        $errorMessage = 'That serial number already exists for another equipment record.';
    } else {
        try {
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
            $successMessage = 'Equipment was updated successfully.';

            $stmt = $pdo->prepare('SELECT * FROM equipment WHERE device_id = :device_id');
            $stmt->execute([':device_id' => $deviceId]);
            $equipment = $stmt->fetch();
        } catch (PDOException $e) {
            $errorMessage = 'Unable to update equipment: ' . $e->getMessage();
        }
    }
}
?>
<div class="section">
    <h2>Modify Equipment</h2>

    <?php if ($successMessage !== ''): ?>
        <div class="message success"><?= e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($equipment !== null): ?>
        <form method="post" action="modify_equipment.php">
            <input type="hidden" name="device_id" value="<?= (int) $equipment['device_id']; ?>">

            <label for="device_type_id">Active Device Type</label>
            <select name="device_type_id" id="device_type_id" required>
                <?php foreach ($activeDeviceTypes as $deviceType): ?>
                    <option value="<?= (int) $deviceType['device_type_id']; ?>" <?= (int) $equipment['device_type_id'] === (int) $deviceType['device_type_id'] ? 'selected' : ''; ?>>
                        <?= e($deviceType['type_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>

            <label for="manufacturer_id">Active Manufacturer</label>
            <select name="manufacturer_id" id="manufacturer_id" required>
                <?php foreach ($activeManufacturers as $manufacturer): ?>
                    <option value="<?= (int) $manufacturer['manufacturer_id']; ?>" <?= (int) $equipment['manufacturer_id'] === (int) $manufacturer['manufacturer_id'] ? 'selected' : ''; ?>>
                        <?= e($manufacturer['manufacturer_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <br>

            <label for="serial_number">Serial Number</label>
            <input type="text" name="serial_number" id="serial_number" maxlength="67" value="<?= e($equipment['serial_number']); ?>" required>
            <br>

            <label for="status">Equipment Status</label>
            <select name="status" id="status" required>
                <option value="active" <?= $equipment['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?= $equipment['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <br>

            <button type="submit">Update Equipment</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
