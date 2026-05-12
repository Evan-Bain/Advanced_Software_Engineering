<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $manufacturerName = normalizeName($_POST['manufacturer_name'] ?? '');

    if ($manufacturerName === '') {
        $errorMessage = 'Manufacturer name is required.';
    } elseif (!isValidManufacturerName($manufacturerName)) {
        $errorMessage = 'Manufacturer name may only contain alphabet letters and spaces.';
    } elseif (manufacturerNameExists($pdo, $manufacturerName)) {
        $errorMessage = 'That manufacturer already exists.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO manufacturers (manufacturer_name, status)
                 VALUES (:manufacturer_name, 'active')"
            );
            $stmt->execute([':manufacturer_name' => $manufacturerName]);
            $successMessage = 'Manufacturer was added successfully.';
        } catch (PDOException $e) {
            $errorMessage = 'Unable to add manufacturer: ' . $e->getMessage();
        }
    }
}
?>
<div class="section">
    <h2>Add New Manufacturer</h2>

    <?php if ($successMessage !== ''): ?>
        <div class="message success"><?= e($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php endif; ?>

    <form method="post" action="add_manufacturer.php">
        <label for="manufacturer_name">Manufacturer Name</label>
        <input type="text" name="manufacturer_name" id="manufacturer_name" maxlength="100" required>
        <br>
        <button type="submit">Add Manufacturer</button>
    </form>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
