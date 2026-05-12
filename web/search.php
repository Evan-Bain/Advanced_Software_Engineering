<?php
declare(strict_types=1);
require_once __DIR__ . '/header.php';

$pdo = getDbConnection();
$activeDeviceTypes = getActiveDeviceTypes($pdo);
$activeManufacturers = getActiveManufacturers($pdo);

$searchMode = $_GET['search_mode'] ?? '';
$results = [];
$errorMessage = '';
$totalResults = 0;
$pageSize = 100;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $pageSize;

function buildPageUrl(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;
    return 'search.php?' . http_build_query($params);
}

function selectedIfMatches(string $actual, string $expected): string
{
    return $actual === $expected ? ' selected' : '';
}

function normalizePageOffset(int $totalResults, int $pageSize, int &$page, int &$offset): void
{
    if ($totalResults > 0 && $offset >= $totalResults) {
        $page = (int) ceil($totalResults / $pageSize);
        $offset = ($page - 1) * $pageSize;
    }
}

if ($searchMode !== '') {
    try {
        switch ($searchMode) {
            case 'device_type':
                $deviceTypeId = $_GET['device_type_id'] ?? '';
                $manufacturerId = $_GET['manufacturer_id'] ?? 'all';

                if ($deviceTypeId === '') {
                    $errorMessage = 'Please select an active device type.';
                    break;
                }

                $fromSql = "FROM equipment e
                            INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
                            INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id
                            WHERE e.status = 'active'
                              AND dt.status = 'active'
                              AND m.status = 'active'
                              AND e.device_type_id = :device_type_id";
                $params = [':device_type_id' => (int) $deviceTypeId];

                if ($manufacturerId !== 'all') {
                    $fromSql .= ' AND e.manufacturer_id = :manufacturer_id';
                    $params[':manufacturer_id'] = (int) $manufacturerId;
                }

                $countStmt = $pdo->prepare("SELECT COUNT(*) $fromSql");
                $countStmt->execute($params);
                $totalResults = (int) $countStmt->fetchColumn();
                normalizePageOffset($totalResults, $pageSize, $page, $offset);

                $sql = "SELECT e.device_id, dt.type_name, m.manufacturer_name, e.serial_number, e.status
                        $fromSql
                        ORDER BY e.device_id
                        LIMIT $pageSize OFFSET $offset";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll();
                break;

            case 'manufacturer':
                $manufacturerId = $_GET['manufacturer_id'] ?? '';
                $deviceTypeId = $_GET['device_type_id'] ?? 'all';

                if ($manufacturerId === '') {
                    $errorMessage = 'Please select an active manufacturer.';
                    break;
                }

                $fromSql = "FROM equipment e
                            INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
                            INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id
                            WHERE e.status = 'active'
                              AND dt.status = 'active'
                              AND m.status = 'active'
                              AND e.manufacturer_id = :manufacturer_id";
                $params = [':manufacturer_id' => (int) $manufacturerId];

                if ($deviceTypeId !== 'all') {
                    $fromSql .= ' AND e.device_type_id = :device_type_id';
                    $params[':device_type_id'] = (int) $deviceTypeId;
                }

                $countStmt = $pdo->prepare("SELECT COUNT(*) $fromSql");
                $countStmt->execute($params);
                $totalResults = (int) $countStmt->fetchColumn();
                normalizePageOffset($totalResults, $pageSize, $page, $offset);

                $sql = "SELECT e.device_id, dt.type_name, m.manufacturer_name, e.serial_number, e.status
                        $fromSql
                        ORDER BY e.device_id
                        LIMIT $pageSize OFFSET $offset";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll();
                break;

            case 'serial_number':
                $serialNumber = strtoupper(trim($_GET['serial_number'] ?? ''));

                if ($serialNumber === '') {
                    $errorMessage = 'Please enter a serial number.';
                    break;
                }

                $stmt = $pdo->prepare(
                    "SELECT e.device_id, dt.type_name, m.manufacturer_name, e.serial_number, e.status
                     FROM equipment e
                     INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
                     INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id
                     WHERE e.status = 'active'
                       AND e.serial_number = :serial_number
                     ORDER BY e.device_id"
                );
                $stmt->execute([':serial_number' => $serialNumber]);
                $results = $stmt->fetchAll();
                $totalResults = count($results);
                break;

            case 'search_all':
                $statusFilter = $_GET['status_filter'] ?? 'all';
                $fromSql = "FROM equipment e
                            INNER JOIN device_types dt ON e.device_type_id = dt.device_type_id
                            INNER JOIN manufacturers m ON e.manufacturer_id = m.manufacturer_id";
                $params = [];

                if ($statusFilter === 'active' || $statusFilter === 'inactive') {
                    $fromSql .= ' WHERE e.status = :status';
                    $params[':status'] = $statusFilter;
                }

                $countStmt = $pdo->prepare("SELECT COUNT(*) $fromSql");
                $countStmt->execute($params);
                $totalResults = (int) $countStmt->fetchColumn();
                normalizePageOffset($totalResults, $pageSize, $page, $offset);

                $sql = "SELECT e.device_id, dt.type_name, m.manufacturer_name, e.serial_number, e.status
                        $fromSql
                        ORDER BY e.device_id
                        LIMIT $pageSize OFFSET $offset";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $results = $stmt->fetchAll();
                break;

            default:
                $errorMessage = 'Invalid search option selected.';
        }
    } catch (PDOException $e) {
        $errorMessage = 'Search failed: ' . $e->getMessage();
    }
}
?>

<div class="section">
    <h2>Search by Device Type</h2>
    <form method="get" action="search.php">
        <input type="hidden" name="search_mode" value="device_type">
        <label for="device_type_id">Active Device Type</label>
        <select name="device_type_id" id="device_type_id" required>
            <option value="">Select One</option>
            <?php foreach ($activeDeviceTypes as $deviceType): ?>
                <option value="<?= (int) $deviceType['device_type_id']; ?>"<?= selectedIfMatches((string) ($_GET['device_type_id'] ?? ''), (string) $deviceType['device_type_id']); ?>><?= e($deviceType['type_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="manufacturer_id_by_type">Manufacturer</label>
        <select name="manufacturer_id" id="manufacturer_id_by_type">
            <option value="all"<?= selectedIfMatches((string) ($_GET['manufacturer_id'] ?? 'all'), 'all'); ?>>All Active Manufacturers</option>
            <?php foreach ($activeManufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['manufacturer_id']; ?>"<?= selectedIfMatches((string) ($_GET['manufacturer_id'] ?? ''), (string) $manufacturer['manufacturer_id']); ?>><?= e($manufacturer['manufacturer_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <button type="submit">Search by Device Type</button>
    </form>
</div>

<div class="section">
    <h2>Search by Manufacturer</h2>
    <form method="get" action="search.php">
        <input type="hidden" name="search_mode" value="manufacturer">
        <label for="manufacturer_id">Active Manufacturer</label>
        <select name="manufacturer_id" id="manufacturer_id" required>
            <option value="">Select One</option>
            <?php foreach ($activeManufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['manufacturer_id']; ?>"<?= selectedIfMatches((string) ($_GET['manufacturer_id'] ?? ''), (string) $manufacturer['manufacturer_id']); ?>><?= e($manufacturer['manufacturer_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <label for="device_type_id_by_manufacturer">Device Type</label>
        <select name="device_type_id" id="device_type_id_by_manufacturer">
            <option value="all"<?= selectedIfMatches((string) ($_GET['device_type_id'] ?? 'all'), 'all'); ?>>All Active Device Types</option>
            <?php foreach ($activeDeviceTypes as $deviceType): ?>
                <option value="<?= (int) $deviceType['device_type_id']; ?>"<?= selectedIfMatches((string) ($_GET['device_type_id'] ?? ''), (string) $deviceType['device_type_id']); ?>><?= e($deviceType['type_name']); ?></option>
            <?php endforeach; ?>
        </select>
        <br>
        <button type="submit">Search by Manufacturer</button>
    </form>
</div>

<div class="section">
    <h2>Search by Serial Number</h2>
    <form method="get" action="search.php">
        <input type="hidden" name="search_mode" value="serial_number">
        <label for="serial_number">Serial Number</label>
        <input type="text" name="serial_number" id="serial_number" maxlength="67" value="<?= e($_GET['serial_number'] ?? ''); ?>" required>
        <br>
        <button type="submit">Search by Serial Number</button>
    </form>
</div>

<div class="section">
    <h2>Search All</h2>
    <form method="get" action="search.php">
        <input type="hidden" name="search_mode" value="search_all">
        <label for="status_filter">Status Filter</label>
        <select name="status_filter" id="status_filter">
            <option value="active"<?= selectedIfMatches((string) ($_GET['status_filter'] ?? 'active'), 'active'); ?>>Only Active</option>
            <option value="inactive"<?= selectedIfMatches((string) ($_GET['status_filter'] ?? 'active'), 'inactive'); ?>>Only Inactive</option>
            <option value="all"<?= selectedIfMatches((string) ($_GET['status_filter'] ?? 'active'), 'all'); ?>>All</option>
        </select>
        <br>
        <button type="submit">Search All</button>
    </form>
</div>

<?php if ($errorMessage !== ''): ?>
    <div class="message error"><?= e($errorMessage); ?></div>
<?php endif; ?>

<?php if ($searchMode !== '' && $errorMessage === ''): ?>
    <div class="section">
        <h2>Search Results</h2>
        <?php if (count($results) === 0): ?>
            <p>No equipment matched your search.</p>
        <?php else: ?>
            <?php
                $totalPages = max(1, (int) ceil($totalResults / $pageSize));
                $firstResultNumber = $offset + 1;
                $lastResultNumber = min($offset + count($results), $totalResults);
            ?>
            <p>
                Showing <?= (int) $firstResultNumber; ?>-<?= (int) $lastResultNumber; ?>
                of <?= (int) $totalResults; ?> matching equipment records.
            </p>
            <table>
                <thead>
                    <tr>
                        <th>Device ID</th>
                        <th>Device Type</th>
                        <th>Manufacturer</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= (int) $row['device_id']; ?></td>
                            <td><?= e($row['type_name']); ?></td>
                            <td><?= e($row['manufacturer_name']); ?></td>
                            <td><?= e($row['serial_number']); ?></td>
                            <td><?= e($row['status']); ?></td>
                            <td><a href="view_equipment.php?device_id=<?= (int) $row['device_id']; ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($totalPages > 1): ?>
                <p class="actions">
                    <?php if ($page > 1): ?>
                        <a href="<?= e(buildPageUrl($page - 1)); ?>">Previous</a>
                    <?php endif; ?>
                    Page <?= (int) $page; ?> of <?= (int) $totalPages; ?>
                    <?php if ($page < $totalPages): ?>
                        <a href="<?= e(buildPageUrl($page + 1)); ?>">Next</a>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
