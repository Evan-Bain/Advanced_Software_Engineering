<?php
declare(strict_types=1);

require_once __DIR__ . '/header.php';

$response = null;
$searchResponse = null;
$viewResponse = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_device_type') {
        $response = apiCall('POST', 'device_types.php', [
            'type_name' => $_POST['type_name'] ?? '',
        ]);
    } elseif ($action === 'add_manufacturer') {
        $response = apiCall('POST', 'manufacturers.php', [
            'manufacturer_name' => $_POST['manufacturer_name'] ?? '',
        ]);
    } elseif ($action === 'add_equipment') {
        $response = apiCall('POST', 'equipment.php', [
            'device_type_id' => $_POST['device_type_id'] ?? '',
            'manufacturer_id' => $_POST['manufacturer_id'] ?? '',
            'serial_number' => $_POST['serial_number'] ?? '',
        ]);
    } elseif ($action === 'update_device_type') {
        $response = apiCall('PUT', 'device_types.php', [
            'device_type_id' => $_POST['device_type_id'] ?? '',
            'type_name' => $_POST['type_name'] ?? '',
            'status' => $_POST['status'] ?? '',
        ]);
    } elseif ($action === 'update_manufacturer') {
        $response = apiCall('PUT', 'manufacturers.php', [
            'manufacturer_id' => $_POST['manufacturer_id'] ?? '',
            'manufacturer_name' => $_POST['manufacturer_name'] ?? '',
            'status' => $_POST['status'] ?? '',
        ]);
    } elseif ($action === 'update_equipment') {
        $response = apiCall('PUT', 'equipment.php', [
            'device_id' => $_POST['device_id'] ?? '',
            'device_type_id' => $_POST['device_type_id'] ?? '',
            'manufacturer_id' => $_POST['manufacturer_id'] ?? '',
            'serial_number' => $_POST['serial_number'] ?? '',
            'status' => $_POST['status'] ?? '',
        ]);
    }
}

if (isset($_GET['search_mode'])) {
    $searchResponse = apiCall('GET', 'equipment.php', [
        'search_mode' => $_GET['search_mode'] ?? 'all',
        'device_type_id' => $_GET['device_type_id'] ?? '',
        'manufacturer_id' => $_GET['manufacturer_id'] ?? '',
        'serial_number' => $_GET['serial_number'] ?? '',
        'status' => $_GET['status'] ?? 'active',
    ]);
}

if (isset($_GET['view_device_id'])) {
    $viewResponse = apiCall('GET', 'equipment.php', [
        'device_id' => $_GET['view_device_id'] ?? '',
    ]);
}

$deviceTypesResponse = apiCall('GET', 'device_types.php', ['status' => 'active']);
$manufacturersResponse = apiCall('GET', 'manufacturers.php', ['status' => 'active']);
$equipmentResponse = apiCall('GET', 'equipment.php', ['search_mode' => 'all', 'status' => 'active']);

$deviceTypes = $deviceTypesResponse['data']['device_types'] ?? [];
$manufacturers = $manufacturersResponse['data']['manufacturers'] ?? [];
$equipmentRows = $equipmentResponse['data']['equipment'] ?? [];
?>

<?= responseMessage($response); ?>

<div class="section" id="search">
    <h2>Search Equipment</h2>
    <form method="get" action="<?= h(siteUrl('index.php#search')); ?>">
        <label for="search_mode">Search Type</label>
        <select name="search_mode" id="search_mode">
            <option value="all"<?= selected((string) ($_GET['search_mode'] ?? 'all'), 'all'); ?>>All Equipment</option>
            <option value="device_type"<?= selected((string) ($_GET['search_mode'] ?? ''), 'device_type'); ?>>Device Type</option>
            <option value="manufacturer"<?= selected((string) ($_GET['search_mode'] ?? ''), 'manufacturer'); ?>>Manufacturer</option>
            <option value="serial_number"<?= selected((string) ($_GET['search_mode'] ?? ''), 'serial_number'); ?>>Serial Number</option>
        </select>

        <label for="search_device_type_id">Device Type</label>
        <select name="device_type_id" id="search_device_type_id">
            <option value="all">All Device Types</option>
            <?php foreach ($deviceTypes as $deviceType): ?>
                <option value="<?= (int) $deviceType['device_type_id']; ?>"<?= selected((string) ($_GET['device_type_id'] ?? ''), (string) $deviceType['device_type_id']); ?>><?= h($deviceType['type_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="search_manufacturer_id">Manufacturer</label>
        <select name="manufacturer_id" id="search_manufacturer_id">
            <option value="all">All Manufacturers</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['manufacturer_id']; ?>"<?= selected((string) ($_GET['manufacturer_id'] ?? ''), (string) $manufacturer['manufacturer_id']); ?>><?= h($manufacturer['manufacturer_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="search_serial_number">Serial Number</label>
        <input type="text" name="serial_number" id="search_serial_number" maxlength="67" value="<?= h($_GET['serial_number'] ?? ''); ?>">

        <label for="search_status">Status</label>
        <select name="status" id="search_status">
            <option value="active"<?= selected((string) ($_GET['status'] ?? 'active'), 'active'); ?>>Active</option>
            <option value="inactive"<?= selected((string) ($_GET['status'] ?? ''), 'inactive'); ?>>Inactive</option>
            <option value="all"<?= selected((string) ($_GET['status'] ?? ''), 'all'); ?>>All</option>
        </select>

        <button type="submit">Search</button>
    </form>

    <?php if ($searchResponse !== null): ?>
        <?= responseMessage($searchResponse); ?>
        <?php $searchRows = $searchResponse['data']['equipment'] ?? []; ?>
        <?php if (count($searchRows) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Device Type</th>
                        <th>Manufacturer</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                        <th>View</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($searchRows as $row): ?>
                        <tr>
                            <td><?= (int) $row['device_id']; ?></td>
                            <td><?= h($row['type_name']); ?></td>
                            <td><?= h($row['manufacturer_name']); ?></td>
                            <td><?= h($row['serial_number']); ?></td>
                            <td><?= h($row['status']); ?></td>
                            <td><a href="<?= h(siteUrl('index.php?view_device_id=' . (int) $row['device_id'] . '#view')); ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="section" id="view">
    <h2>View Single Equipment Entry</h2>
    <form method="get" action="<?= h(siteUrl('index.php#view')); ?>">
        <label for="view_device_id">Equipment</label>
        <select name="view_device_id" id="view_device_id" required>
            <option value="">Select Equipment</option>
            <?php foreach ($equipmentRows as $row): ?>
                <option value="<?= (int) $row['device_id']; ?>"<?= selected((string) ($_GET['view_device_id'] ?? ''), (string) $row['device_id']); ?>>
                    #<?= (int) $row['device_id']; ?> - <?= h($row['type_name']); ?> - <?= h($row['manufacturer_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">View</button>
    </form>

    <?php if ($viewResponse !== null): ?>
        <?= responseMessage($viewResponse); ?>
        <?php $viewEquipment = $viewResponse['data']['equipment'] ?? null; ?>
        <?php if (is_array($viewEquipment)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Device Name</th>
                        <th>Manufacturer Name</th>
                        <th>Serial Number</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= h($viewEquipment['type_name']); ?></td>
                        <td><?= h($viewEquipment['manufacturer_name']); ?></td>
                        <td><?= h($viewEquipment['serial_number']); ?></td>
                        <td><?= h($viewEquipment['status']); ?></td>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="grid" id="add">
    <div class="section">
        <h2>Add New Device Type</h2>
        <form method="post" action="<?= h(siteUrl('index.php#add')); ?>">
            <input type="hidden" name="action" value="add_device_type">
            <label for="add_type_name">Device Type Name</label>
            <input type="text" name="type_name" id="add_type_name" maxlength="100" required>
            <button type="submit">Add Device Type</button>
        </form>
    </div>

    <div class="section">
        <h2>Add New Manufacturer</h2>
        <form method="post" action="<?= h(siteUrl('index.php#add')); ?>">
            <input type="hidden" name="action" value="add_manufacturer">
            <label for="add_manufacturer_name">Manufacturer Name</label>
            <input type="text" name="manufacturer_name" id="add_manufacturer_name" maxlength="100" required>
            <button type="submit">Add Manufacturer</button>
        </form>
    </div>
</div>

<div class="section">
    <h2>Add New Equipment</h2>
    <form method="post" action="<?= h(siteUrl('index.php#add')); ?>">
        <input type="hidden" name="action" value="add_equipment">

        <label for="add_device_type_id">Active Device Type</label>
        <select name="device_type_id" id="add_device_type_id" required>
            <option value="">Select Device Type</option>
            <?php foreach ($deviceTypes as $deviceType): ?>
                <option value="<?= (int) $deviceType['device_type_id']; ?>"><?= h($deviceType['type_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="add_manufacturer_id">Active Manufacturer</label>
        <select name="manufacturer_id" id="add_manufacturer_id" required>
            <option value="">Select Manufacturer</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['manufacturer_id']; ?>"><?= h($manufacturer['manufacturer_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="add_serial_number">Serial Number</label>
        <input type="text" name="serial_number" id="add_serial_number" maxlength="67" required>

        <button type="submit">Add Equipment</button>
    </form>
</div>

<div class="grid" id="update">
    <div class="section">
        <h2>Modify Device Type</h2>
        <form method="post" action="<?= h(siteUrl('index.php#update')); ?>">
            <input type="hidden" name="action" value="update_device_type">
            <label for="update_device_type_id">Device Type ID</label>
            <input type="number" name="device_type_id" id="update_device_type_id" min="1" required>
            <label for="update_type_name">New Name</label>
            <input type="text" name="type_name" id="update_type_name" maxlength="100" required>
            <label for="update_device_status">Status</label>
            <select name="status" id="update_device_status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button type="submit">Update Device Type</button>
        </form>
    </div>

    <div class="section">
        <h2>Modify Manufacturer</h2>
        <form method="post" action="<?= h(siteUrl('index.php#update')); ?>">
            <input type="hidden" name="action" value="update_manufacturer">
            <label for="update_manufacturer_id">Manufacturer ID</label>
            <input type="number" name="manufacturer_id" id="update_manufacturer_id" min="1" required>
            <label for="update_manufacturer_name">New Name</label>
            <input type="text" name="manufacturer_name" id="update_manufacturer_name" maxlength="100" required>
            <label for="update_manufacturer_status">Status</label>
            <select name="status" id="update_manufacturer_status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <button type="submit">Update Manufacturer</button>
        </form>
    </div>
</div>

<div class="section">
    <h2>Modify Equipment</h2>
    <form method="post" action="<?= h(siteUrl('index.php#update')); ?>">
        <input type="hidden" name="action" value="update_equipment">

        <label for="update_device_id">Equipment ID</label>
        <input type="number" name="device_id" id="update_device_id" min="1" required>

        <label for="update_equipment_type_id">Active Device Type</label>
        <select name="device_type_id" id="update_equipment_type_id" required>
            <option value="">Select Device Type</option>
            <?php foreach ($deviceTypes as $deviceType): ?>
                <option value="<?= (int) $deviceType['device_type_id']; ?>"><?= h($deviceType['type_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="update_equipment_manufacturer_id">Active Manufacturer</label>
        <select name="manufacturer_id" id="update_equipment_manufacturer_id" required>
            <option value="">Select Manufacturer</option>
            <?php foreach ($manufacturers as $manufacturer): ?>
                <option value="<?= (int) $manufacturer['manufacturer_id']; ?>"><?= h($manufacturer['manufacturer_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="update_serial_number">Serial Number</label>
        <input type="text" name="serial_number" id="update_serial_number" maxlength="67" required>

        <label for="update_equipment_status">Equipment Status</label>
        <select name="status" id="update_equipment_status" required>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
        </select>

        <button type="submit">Update Equipment</button>
    </form>
</div>

<div class="section">
    <h2>Selection Data Loaded From API</h2>
    <p>Active device types: <?= count($deviceTypes); ?></p>
    <p>Active manufacturers: <?= count($manufacturers); ?></p>
    <p>Active equipment records loaded for selection: <?= count($equipmentRows); ?></p>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
