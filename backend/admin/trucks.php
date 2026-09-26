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

// Filters
$searchKey = sanitize_input($_GET['search'] ?? '');
$category  = sanitize_input($_GET['category'] ?? '');
$status    = sanitize_input($_GET['status'] ?? '');

$sql = "SELECT t.*, 
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM trucks t WHERE 1=1";
$params = [];

if (!empty($searchKey)) {
    $sql .= " AND (t.title LIKE :s OR t.truck_code LIKE :s OR t.brand LIKE :s OR t.model LIKE :s)";
    $params[':s'] = '%' . $searchKey . '%';
}

if (!empty($category)) {
    $sql .= " AND t.purpose_category = :category";
    $params[':category'] = $category;
}

if (!empty($status)) {
    $sql .= " AND t.availability_status = :status";
    $params[':status'] = $status;
}

$sql .= " ORDER BY t.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trucks = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; color: var(--admin-navy);">Truck Inventory Management</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">Add, edit, upload photos, and update availability status for all dealership vehicles.</p>
    </div>
    <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary">
        <span></span> Add New Truck
    </a>
</div>

<!-- Filter Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>trucks.php" style="display: grid; grid-template-columns: 2fr 1.5fr 1.5fr auto auto; gap: 12px; align-items: center;">
        
        <input type="text" name="search" class="form-control" placeholder="Search by code, title, make..." value="<?php echo sanitize_output($searchKey); ?>">
        
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

        <button type="submit" class="btn btn-navy btn-sm" style="padding: 9px 16px;">Filter</button>
        <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Trucks Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Inventory Records (<?php echo count($trucks); ?> Trucks)</div>
    </div>

    <?php if (!empty($trucks)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Code</th>
                    <th>Vehicle Details</th>
                    <th>Purpose Category</th>
                    <th>Capacity</th>
                    <th>Listed Price</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($trucks as $trk): ?>
                    <tr>
                        <td>
                            <?php 
                                $hasImg = !empty($trk['primary_image']) && file_exists(UPLOADS_PATH . $trk['primary_image']);
                            ?>
                            <?php if ($hasImg): ?>
                                <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $trk['primary_image']; ?>" alt="Thumb" class="table-thumb">
                            <?php else: ?>
                                <div class="table-thumb" style="display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #64748b; font-weight: bold;">
                                    NO IMG
                                </div>
                            <?php endif; ?>
                        </td>

                        <td><strong style="color: var(--admin-orange);"><?php echo sanitize_output($trk['truck_code']); ?></strong></td>

                        <td>
                            <strong style="color: var(--admin-navy);"><?php echo sanitize_output($trk['title']); ?></strong>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted);">
                                <?php echo sanitize_output($trk['brand'] . ' &bull; ' . $trk['year_of_manufacture'] . ' &bull; ' . $trk['condition_type']); ?>
                            </div>
                        </td>

                        <td><span class="badge badge-navy"><?php echo sanitize_output($trk['purpose_category']); ?></span></td>
                        <td><?php echo format_tonnage($trk['tonnage_capacity']); ?></td>
                        <td><strong><?php echo format_currency($trk['price']); ?></strong></td>

                        <td>
                            <?php if ($trk['availability_status'] === 'Available'): ?>
                                <span class="badge badge-success">Available</span>
                            <?php elseif ($trk['availability_status'] === 'Reserved'): ?>
                                <span class="badge badge-warning">Reserved</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><?php echo sanitize_output($trk['availability_status']); ?></span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$trk['id']; ?>" target="_blank" class="btn btn-outline btn-sm" title="Preview Public Page">View</a>
                            <a href="<?php echo ADMIN_URL; ?>truck-form.php?id=<?php echo (int)$trk['id']; ?>" class="btn btn-navy btn-sm">Edit</a>
                            <a href="<?php echo ADMIN_URL; ?>truck-delete.php?id=<?php echo (int)$trk['id']; ?>&csrf_token=<?php echo generate_csrf_token(); ?>" class="btn btn-sm" style="background: #fee2e2; color: #991b1b;" onclick="return confirm('Are you sure you want to delete this truck from inventory?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--admin-text-muted); text-align: center; padding: 2.5rem 0;">No trucks found matching the selected filter criteria.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
