<?php
/**
 * =============================================================================
 * Moal General Suppliers - Admin Truck Inventory Management
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Manage Truck Inventory';
$db = getDB();

// Quick Status Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $truckId = (int)($_POST['truck_id'] ?? 0);
        $newStatus = sanitize_input($_POST['new_status'] ?? '');
        $validStatuses = ['Available', 'Reserved', 'Sold', 'Maintenance'];
        if ($truckId > 0 && in_array($newStatus, $validStatuses, true)) {
            $stmtUp = $db->prepare('UPDATE trucks SET availability_status = :status, updated_at = NOW() WHERE id = :id');
            $stmtUp->execute([':status' => $newStatus, ':id' => $truckId]);
            set_flash_message('success', 'Truck availability status updated to ' . $newStatus . '.');
            redirect(ADMIN_URL . 'trucks.php');
        }
    }
}

// Filters & Search
$searchKey = sanitize_input($_GET['search'] ?? '');
$category  = sanitize_input($_GET['category'] ?? '');
$status    = sanitize_input($_GET['status'] ?? '');
$sortBy    = sanitize_input($_GET['sort'] ?? 'id_desc');

$sql = "
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    WHERE 1=1
";
$params = [];

if (!empty($searchKey)) {
    $sql .= " AND (t.title LIKE :s1 OR t.truck_code LIKE :s2 OR t.brand LIKE :s3 OR t.model LIKE :s4)";
    $like = '%' . $searchKey . '%';
    $params[':s1'] = $like;
    $params[':s2'] = $like;
    $params[':s3'] = $like;
    $params[':s4'] = $like;
}

if (!empty($category)) {
    $sql .= " AND t.purpose_category = :category";
    $params[':category'] = $category;
}

if (!empty($status)) {
    $sql .= " AND t.availability_status = :status";
    $params[':status'] = $status;
}

// Sorting
switch ($sortBy) {
    case 'price_asc':
        $sql .= " ORDER BY t.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY t.price DESC";
        break;
    case 'tonnage_desc':
        $sql .= " ORDER BY t.tonnage_capacity DESC";
        break;
    case 'year_desc':
        $sql .= " ORDER BY t.year_of_manufacture DESC";
        break;
    default:
        $sql .= " ORDER BY t.id DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trucks = $stmt->fetchAll();

// Total count for filters
$totalCount = (int)$db->query('SELECT COUNT(*) FROM trucks')->fetchColumn();
$availableCount = (int)$db->query('SELECT COUNT(*) FROM trucks WHERE availability_status = "Available"')->fetchColumn();
$soldCount = (int)$db->query('SELECT COUNT(*) FROM trucks WHERE availability_status = "Sold"')->fetchColumn();
$reservedCount = (int)$db->query('SELECT COUNT(*) FROM trucks WHERE availability_status = "Reserved"')->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Available Commercial Trucks</h1>
        <div class="admin-heading-sub">Manage fleet inventory, technical specifications, and live sale availability</div>
    </div>
    <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary">
        + Add New Truck
    </a>
</div>

<!-- Status Filter Pills -->
<div class="filter-tabs">
    <a href="<?php echo ADMIN_URL; ?>trucks.php" class="filter-tab <?php echo (empty($status) && empty($category)) ? 'active' : ''; ?>">
        All Trucks (<?php echo $totalCount; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Available" class="filter-tab <?php echo ($status === 'Available') ? 'active' : ''; ?>">
        Available (<?php echo $availableCount; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Reserved" class="filter-tab <?php echo ($status === 'Reserved') ? 'active' : ''; ?>">
        Reserved (<?php echo $reservedCount; ?>)
    </a>
    <a href="<?php echo ADMIN_URL; ?>trucks.php?status=Sold" class="filter-tab <?php echo ($status === 'Sold') ? 'active' : ''; ?>">
        Sold (<?php echo $soldCount; ?>)
    </a>
</div>

<!-- Search & Filtering Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>trucks.php" style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1.2fr auto auto; gap: 10px; align-items: center;">
        
        <input type="text" name="search" class="form-control" placeholder="Search by code, brand, model..." value="<?php echo sanitize_output($searchKey); ?>">
        
        <select name="category" class="form-control">
            <option value="">-- All Categories --</option>
            <option value="Heavy Haulage" <?php echo ($category === 'Heavy Haulage') ? 'selected' : ''; ?>>Heavy Haulage</option>
            <option value="Construction & Mining" <?php echo ($category === 'Construction & Mining') ? 'selected' : ''; ?>>Construction &amp; Mining</option>
            <option value="Distribution & Logistics" <?php echo ($category === 'Distribution & Logistics') ? 'selected' : ''; ?>>Distribution &amp; Logistics</option>
            <option value="Agriculture & Farming" <?php echo ($category === 'Agriculture & Farming') ? 'selected' : ''; ?>>Agriculture &amp; Farming</option>
            <option value="Specialized Transport" <?php echo ($category === 'Specialized Transport') ? 'selected' : ''; ?>>Specialized Transport</option>
        </select>

        <select name="status" class="form-control">
            <option value="">-- All Statuses --</option>
            <option value="Available" <?php echo ($status === 'Available') ? 'selected' : ''; ?>>Available</option>
            <option value="Reserved" <?php echo ($status === 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
            <option value="Sold" <?php echo ($status === 'Sold') ? 'selected' : ''; ?>>Sold</option>
            <option value="Maintenance" <?php echo ($status === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
        </select>

        <select name="sort" class="form-control">
            <option value="id_desc" <?php echo ($sortBy === 'id_desc') ? 'selected' : ''; ?>>Newest Added</option>
            <option value="price_asc" <?php echo ($sortBy === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo ($sortBy === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="tonnage_desc" <?php echo ($sortBy === 'tonnage_desc') ? 'selected' : ''; ?>>Highest Tonnage</option>
            <option value="year_desc" <?php echo ($sortBy === 'year_desc') ? 'selected' : ''; ?>>Latest Year</option>
        </select>

        <button type="submit" class="btn btn-dark btn-sm" style="padding: 9px 14px;">Filter</button>
        <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Inventory Table Card -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Inventory Records (<?php echo count($trucks); ?> Units Found)</div>
        <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary btn-sm">Add New Truck</a>
    </div>

    <?php if (!empty($trucks)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Code</th>
                        <th>Vehicle Name &amp; Spec</th>
                        <th>Category</th>
                        <th>Capacity</th>
                        <th>Price</th>
                        <th>Availability</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trucks as $trk): ?>
                        <tr>
                            <td style="width: 70px;">
                                <?php 
                                    $imgSrc = !empty($trk['primary_image']) ? BASE_URL . 'assets/images/trucks/' . sanitize_output($trk['primary_image']) : BASE_URL . 'assets/images/branding/logo.jpg';
                                ?>
                                <img src="<?php echo $imgSrc; ?>" alt="Thumb" style="width: 60px; height: 42px; object-fit: cover; border-radius: 4px; border: 1px solid var(--admin-border);">
                            </td>
                            <td>
                                <strong style="color: var(--admin-orange); font-family: monospace; font-size: 0.95rem;">
                                    <?php echo sanitize_output($trk['truck_code']); ?>
                                </strong>
                                <?php if ($trk['featured']): ?>
                                    <div><span class="badge badge-primary" style="font-size: 0.68rem; margin-top: 2px;">Featured</span></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--admin-navy); font-size: 0.92rem;"><?php echo sanitize_output($trk['title']); ?></strong>
                                <div style="font-size: 0.78rem; color: var(--admin-text-muted); margin-top: 2px;">
                                    <?php echo sanitize_output($trk['brand'] . ' ' . $trk['model'] . ' (' . $trk['year_of_manufacture'] . ')'); ?> &bull; 
                                    <?php echo sanitize_output($trk['wheel_configuration'] . ' &bull; ' . $trk['fuel_type']); ?>
                                </div>
                            </td>
                            <td><span class="badge badge-subtle"><?php echo sanitize_output($trk['purpose_category']); ?></span></td>
                            <td><strong><?php echo format_tonnage($trk['tonnage_capacity']); ?></strong></td>
                            <td><strong style="color: var(--admin-navy); font-size: 0.95rem;"><?php echo format_currency($trk['price']); ?></strong></td>
                            <td>
                                <!-- Quick Status Toggle Form -->
                                <form method="POST" action="<?php echo ADMIN_URL; ?>trucks.php" style="margin: 0; display: inline-block;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="truck_id" value="<?php echo (int)$trk['id']; ?>">
                                    <select name="new_status" onchange="this.form.submit()" style="font-size: 0.78rem; font-weight: 600; padding: 4px 8px; border-radius: 4px; border: 1px solid var(--admin-border); cursor: pointer; background: #FFFFFF; color: var(--admin-navy);">
                                        <option value="Available" <?php echo ($trk['availability_status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                                        <option value="Reserved" <?php echo ($trk['availability_status'] === 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
                                        <option value="Sold" <?php echo ($trk['availability_status'] === 'Sold') ? 'selected' : ''; ?>>Sold</option>
                                        <option value="Maintenance" <?php echo ($trk['availability_status'] === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div style="display: flex; gap: 6px;">
                                    <a href="<?php echo ADMIN_URL; ?>truck-form.php?id=<?php echo (int)$trk['id']; ?>" class="btn btn-outline btn-sm" title="Edit vehicle details">
                                        Edit
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$trk['id']; ?>" target="_blank" class="btn btn-dark btn-sm" title="View public customer page">
                                        View
                                    </a>
                                    <button type="button" class="btn btn-outline btn-sm" onclick="confirmDelete(<?php echo (int)$trk['id']; ?>, '<?php echo addslashes($trk['truck_code'] . ' - ' . $trk['title']); ?>')" title="Delete truck">Delete</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-title">No Matching Trucks Found</div>
            <div class="empty-state-text">
                <?php if (!empty($searchKey) || !empty($category) || !empty($status)): ?>
                    No commercial trucks match your selected filter criteria. Try clearing search terms or changing status filters.
                <?php else: ?>
                    Your dealership catalog has no commercial trucks registered yet. Click below to add your first vehicle.
                <?php endif; ?>
            </div>
            <div class="empty-state-actions">
                <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline btn-sm">Reset Filters</a>
                <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary btn-sm">+ Add New Truck</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Deletion Confirmation Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: #FFFFFF; border-radius: var(--admin-radius-lg); padding: 2rem; max-width: 460px; width: 90%; box-shadow: var(--admin-shadow-md);">
        <h3 style="color: #991B1B; font-size: 1.25rem; margin-bottom: 0.5rem;">Confirm Truck Deletion</h3>
        <p style="color: var(--admin-text-main); font-size: 0.92rem; line-height: 1.5; margin-bottom: 1.5rem;">
            Are you sure you want to permanently remove <strong id="deleteTruckName"></strong> from the inventory system? This action cannot be undone.
        </p>
        <form method="POST" action="<?php echo ADMIN_URL; ?>truck-delete.php" id="deleteForm">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="id" id="deleteTruckId" value="">
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger">Delete Permanently</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('deleteTruckId').value = id;
    document.getElementById('deleteTruckName').textContent = name;
    var modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>