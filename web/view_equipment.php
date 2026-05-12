<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$deviceId = isset($_GET['device_id']) ? (int) $_GET['device_id'] : 0;
$equipment = null;
$errorMessage = '';

if ($deviceId <= 0) {
    $errorMessage = 'A valid device ID must be provided from the search results.';
} else {
    try {
        $stmt = $pdo->prepare(
            "SELECT e.device_id,
                    dt.device_type_id,
                    dt.type_name,
                    m.manufacturer_id,
                    m.manufacturer_name,
                    e.serial_number,
                    e.status
             FROM equipment e
             INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
             INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id
             WHERE e.device_id = :device_id"
        );
        $stmt->execute([':device_id' => $deviceId]);
        $equipment = $stmt->fetch();

        if ($equipment === false) {
            $errorMessage = 'Equipment was not found.';
            $equipment = null;
        }
    } catch (PDOException $e) {
        $errorMessage = 'Unable to load equipment: ' . $e->getMessage();
    }
}
?>
<div class="section">
    <h2>View Equipment</h2>

    <?php if ($errorMessage !== ''): ?>
        <div class="message error"><?= e($errorMessage); ?></div>
    <?php elseif ($equipment !== null): ?>
        <table>
            <thead>
                <tr>
                    <th>Device ID</th>
                    <th>Device Type</th>
                    <th>Manufacturer Type</th>
                    <th>Serial Number</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= (int) $equipment['device_id']; ?></td>
                    <td><?= e($equipment['type_name']); ?></td>
                    <td><?= e($equipment['manufacturer_name']); ?></td>
                    <td><?= e($equipment['serial_number']); ?></td>
                    <td><?= e($equipment['status']); ?></td>
                </tr>
            </tbody>
        </table>

        <p class="actions">
            <a href="modify_equipment.php?device_id=<?= (int) $equipment['device_id']; ?>">Modify</a>
            <a href="search.php">Back to Search</a>
        </p>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
