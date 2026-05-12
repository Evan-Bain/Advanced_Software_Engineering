<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$deviceTypeId = isset($_GET['device_type_id']) ? (int) $_GET['device_type_id'] : (int) ($_POST['device_type_id'] ?? 0);
$deviceType = null;
$successMessage = '';
$errorMessage = '';

if ($deviceTypeId <= 0) {
    $errorMessage = 'A valid device type ID is required.';
} else {
    try {
        $stmt = $pdo->prepare(
            'SELECT * FROM device_types WHERE device_type_id = :device_type_id'
        );
        $stmt->execute([':device_type_id' => $deviceTypeId]);
        $deviceType = $stmt->fetch();

        if ($deviceType === false) {
            $deviceType = null;
            $errorMessage = 'Device type was not found.';
        }
    } catch (PDOException $e) {
        $errorMessage = 'Unable to load device type: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $deviceType !== null) {
    $typeName = normalizeName($_POST['type_name'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($typeName === '' || !in_array($status, ['active', 'inactive'], true)) {
        $errorMessage = 'All fields are required.';
    } elseif (!isValidDeviceTypeName($typeName)) {
        $errorMessage = 'Device type name may only contain alphabet letters and spaces.';
    } elseif (deviceTypeNameExists($pdo, $typeName, $deviceTypeId)) {
        $errorMessage = 'That device type name already belongs to another row.';
    } else {
        try {
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
            $successMessage = 'Device type was updated successfully.';

            $stmt = $pdo->prepare('SELECT * FROM device_types WHERE device_type_id = :device_type_id');
            $stmt->execute([':device_type_id' => $deviceTypeId]);
            $deviceType = $stmt->fetch();
        } catch (PDOException $e) {
            $errorMessage = 'Unable to update device type: ' . $e->getMessage();
        }
    }
}
?>
<div class="section">
    <h2>Modify Device Type</h2>

    <?php if ($successMessage !== ''): ?>
        <div class="message success"><?= e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($deviceType !== null): ?>
        <form method="post" action="modify_device_type.php">
            <input type="hidden" name="device_type_id" value="<?= (int) $deviceType['device_type_id']; ?>">

            <label for="type_name">Device Type Name</label>
            <input type="text" name="type_name" id="type_name" maxlength="100" value="<?= e($deviceType['type_name']); ?>" required>
            <br>

            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="active" <?= $deviceType['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?= $deviceType['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <br>

            <button type="submit">Update Device Type</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
