<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeName = normalizeName($_POST['type_name'] ?? '');

    if ($typeName === '') {
        $errorMessage = 'Device type name is required.';
    } elseif (!isValidDeviceTypeName($typeName)) {
        $errorMessage = 'Device type name may only contain alphabet letters and spaces.';
    } elseif (deviceTypeNameExists($pdo, $typeName)) {
        $errorMessage = 'That device type already exists.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO device_types (type_name, status)
                 VALUES (:type_name, 'active')"
            );
            $stmt->execute([':type_name' => $typeName]);
            $successMessage = 'Device type was added successfully.';
        } catch (PDOException $e) {
            $errorMessage = 'Unable to add device type: ' . $e->getMessage();
        }
    }
}
?>
<div class="section">
    <h2>Add New Device Type</h2>

    <?php if ($successMessage !== ''): ?>
        <div class="message success"><?= e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post" action="add_device_type.php">
        <label for="type_name">Device Type Name</label>
        <input type="text" name="type_name" id="type_name" maxlength="100" required>
        <br>
        <button type="submit">Add Device Type</button>
    </form>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
