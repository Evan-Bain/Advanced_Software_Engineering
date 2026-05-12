<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$manufacturerId = isset($_GET['manufacturer_id']) ? (int) $_GET['manufacturer_id'] : (int) ($_POST['manufacturer_id'] ?? 0);
$manufacturer = null;
$successMessage = '';
$errorMessage = '';

if ($manufacturerId <= 0) {
    $errorMessage = 'A valid manufacturer ID is required.';
} else {
    try {
        $stmt = $pdo->prepare(
            'SELECT * FROM manufacturers WHERE manufacturer_id = :manufacturer_id'
        );
        $stmt->execute([':manufacturer_id' => $manufacturerId]);
        $manufacturer = $stmt->fetch();

        if ($manufacturer === false) {
            $manufacturer = null;
            $errorMessage = 'Manufacturer was not found.';
        }
    } catch (PDOException $e) {
        $errorMessage = 'Unable to load manufacturer: ' . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $manufacturer !== null) {
    $manufacturerName = normalizeName($_POST['manufacturer_name'] ?? '');
    $status = $_POST['status'] ?? '';

    if ($manufacturerName === '' || !in_array($status, ['active', 'inactive'], true)) {
        $errorMessage = 'All fields are required.';
    } elseif (!isValidManufacturerName($manufacturerName)) {
        $errorMessage = 'Manufacturer name may only contain alphabet letters and spaces.';
    } elseif (manufacturerNameExists($pdo, $manufacturerName, $manufacturerId)) {
        $errorMessage = 'That manufacturer name already belongs to another row.';
    } else {
        try {
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
            $successMessage = 'Manufacturer was updated successfully.';

            $stmt = $pdo->prepare('SELECT * FROM manufacturers WHERE manufacturer_id = :manufacturer_id');
            $stmt->execute([':manufacturer_id' => $manufacturerId]);
            $manufacturer = $stmt->fetch();
        } catch (PDOException $e) {
            $errorMessage = 'Unable to update manufacturer: ' . $e->getMessage();
        }
    }
}
?>
<div class="section">
    <h2>Modify Manufacturer</h2>

    <?php if ($successMessage !== ''): ?>
        <div class="message success"><?= e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php endif; ?>

    <?php if ($manufacturer !== null): ?>
        <form method="post" action="modify_manufacturer.php">
            <input type="hidden" name="manufacturer_id" value="<?= (int) $manufacturer['manufacturer_id']; ?>">

            <label for="manufacturer_name">Manufacturer Name</label>
            <input type="text" name="manufacturer_name" id="manufacturer_name" maxlength="100" value="<?= e($manufacturer['manufacturer_name']); ?>" required>
            <br>

            <label for="status">Status</label>
            <select name="status" id="status" required>
                <option value="active" <?= $manufacturer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                <option value="inactive" <?= $manufacturer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
            </select>
            <br>

            <button type="submit">Update Manufacturer</button>
        </form>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
