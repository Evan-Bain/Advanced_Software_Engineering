<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$activeDeviceTypes = getActiveDeviceTypes($pdo);
$activeManufacturers = getActiveManufacturers($pdo);

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceTypeId = isset($_POST['device_type_id']) ? (int) $_POST['device_type_id'] : 0;
    $manufacturerId = isset($_POST['manufacturer_id']) ? (int) $_POST['manufacturer_id'] : 0;
    $serialNumber = strtoupper(trim($_POST['serial_number'] ?? ''));

    if ($deviceTypeId <= 0 || $manufacturerId <= 0 || $serialNumber === '') {
        $errorMessage = 'All fields are required.';
    } elseif (!isValidSerialNumber($serialNumber)) {
        $errorMessage = 'Serial number must match SN- followed by 64 characters using only 0-9 and A-F.';
    } elseif (isSerialNumberInUse($pdo, $serialNumber)) {
        $errorMessage = 'That serial number already exists.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO equipment (device_type_id, manufacturer_id, serial_number, status)
                 VALUES (:device_type_id, :manufacturer_id, :serial_number, 'active')"
            );
            $stmt->execute([
                ':device_type_id' => $deviceTypeId,
                ':manufacturer_id' => $manufacturerId,
                ':serial_number' => $serialNumber,
            ]);
            $successMessage = 'Equipment was added successfully.';
        } catch (PDOException $e) {
            $errorMessage = 'Unable to add equipment: ' . $e->getMessage();
        }
    }

    $activeDeviceTypes = getActiveDeviceTypes($pdo);
    $activeManufacturers = getActiveManufacturers($pdo);
}
?>
<div class="section">
    <h2>Add New Equipment</h2>

    <?php if ($successMessage !== ''): ?>
        <div class="message success"><?= e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post" action="add_equipment.php">
        <label for="device_type_id">Active Device Type</label>
        <select name="device_type_id" id="device_type_id" required>
            <option value="">Select One</option>
            <?php foreach ($activeDeviceTypes as $deviceType): ?>
                <option value="<?= (int) $deviceType['device_type_id']; ?>"><?= e($deviceType['type_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <br>

        <label for="manufacturer_id">Active Manufacturer</label>
        <select name="manufacturer_id" id="manufacturer_id" required>
            <option value="">Select One</option>
            <?php foreach ($activeManufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['manufacturer_id']; ?>"><?= e($manufacturer['manufacturer_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <br>

        <label for="serial_number">Serial Number</label>
        <input type="text" name="serial_number" id="serial_number" maxlength="67" required>
        <br>

        <button type="submit">Add Equipment</button>
    </form>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
