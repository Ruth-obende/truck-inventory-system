<?php
/**
 * =============================================================================
 * Moal General Suppliers - Administrator Dashboard Overview
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Dashboard Overview';
$db = getDB();

// -----------------------------------------------------------------------------
// 1. Fetch Metrics & Aggregations
// -----------------------------------------------------------------------------
$totalTrucks = (int)$db->query('SELECT COUNT(*) FROM trucks')->fetchColumn();
$availableTrucks = (int)$db->query('SELECT COUNT(*) FROM trucks WHERE availability_status = "Available"')->fetchColumn();
$totalValuation = (float)$db->query('SELECT SUM(price) FROM trucks WHERE availability_status = "Available"')->fetchColumn();

$totalInquiries = (int)$db->query('SELECT COUNT(*) FROM inquiries')->fetchColumn();
$pendingInquiries = (int)$db->query('SELECT COUNT(*) FROM inquiries WHERE status = "Pending"')->fetchColumn();

// -----------------------------------------------------------------------------
// 2. Fetch Recent Customer Inquiries
// -----------------------------------------------------------------------------
$stmtRecentInq = $db->query('
    SELECT i.*, t.truck_code, t.title AS truck_title 
    FROM inquiries i 
    LEFT JOIN trucks t ON i.truck_id = t.id 
    ORDER BY i.id DESC 
    LIMIT 5
');
$recentInquiries = $stmtRecentInq->fetchAll();

// -----------------------------------------------------------------------------
// 3. Fetch Recent Trucks Added
// -----------------------------------------------------------------------------
$stmtRecentTrucks = $db->query('
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    ORDER BY t.id DESC 
    LIMIT 4
');
$recentTrucks = $stmtRecentTrucks->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Statistics Metric Cards -->
<div class="stats-grid">
    
    <div class="stat-card" style="border-left: 4px solid var(--admin-orange);">
        <div class="stat-card-label">Active Stock</div>
        <div class="stat-card-value"><?php echo $availableTrucks; ?> <span style="font-size: 1rem; color: var(--admin-text-muted); font-weight: 500;">/ <?php echo $totalTrucks; ?> Total</span></div>
        <div class="stat-card-sub">Available for sale / delivery</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #16a34a;">
        <div class="stat-card-label">Inventory Valuation</div>
        <div class="stat-card-value" style="font-size: 1.45rem; color: #16a34a;"><?php echo format_currency($totalValuation); ?></div>
        <div class="stat-card-sub">Total active listing asset value</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid #ef4444;">
        <div class="stat-card-label">Pending Inquiries</div>
        <div class="stat-card-value" style="color: <?php echo $pendingInquiries > 0 ? '#dc2626' : '#0f172a'; ?>;">
            <?php echo $pendingInquiries; ?>
        </div>
        <div class="stat-card-sub">Requires customer follow-up</div>
    </div>

    <div class="stat-card" style="border-left: 4px solid var(--admin-navy);">
        <div class="stat-card-label">Total Inquiries</div>
        <div class="stat-card-value"><?php echo $totalInquiries; ?></div>
        <div class="stat-card-sub">All-time customer requests</div>
    </div>

</div>

<!-- Quick Action Shortcuts -->
<div style="display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap;">
    <a href="<?php echo ADMIN_URL; ?>truck-form.php" class="btn btn-primary">
        <span>➕</span> Add New Truck to Inventory
    </a>
    <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-navy">
        <span>🚛</span> Manage All Trucks
    </a>
    <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-outline">
        <span>📬</span> View Customer Inquiries (<?php echo $pendingInquiries; ?> Pending)
    </a>
</div>

<!-- Recent Inquiries Section -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Recent Customer Inquiries &amp; Custom Requests</div>
        <a href="<?php echo ADMIN_URL; ?>inquiries.php" class="btn btn-navy btn-sm">View All Inquiries &rarr;</a>
    </div>

    <?php if (!empty($recentInquiries)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Ref Code</th>
                    <th>Customer Name</th>
                    <th>Contact</th>
                    <th>Nature</th>
                    <th>Linked Truck / Subject</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentInquiries as $inq): ?>
                    <tr>
                        <td><strong style="color: var(--admin-orange);"><?php echo sanitize_output($inq['inquiry_code']); ?></strong></td>
                        <td><strong><?php echo sanitize_output($inq['customer_name']); ?></strong></td>
                        <td>
                            <div><?php echo sanitize_output($inq['customer_phone']); ?></div>
                            <small style="color: var(--admin-text-muted);"><?php echo sanitize_output($inq['customer_email']); ?></small>
                        </td>
                        <td><span class="badge badge-navy"><?php echo sanitize_output($inq['inquiry_type']); ?></span></td>
                        <td>
                            <?php if (!empty($inq['truck_code'])): ?>
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$inq['truck_id']; ?>" target="_blank" style="font-weight: 600;">
                                    <?php echo sanitize_output($inq['truck_code']); ?>
                                </a>
                            <?php else: ?>
                                <span style="color: var(--admin-text-muted); font-style: italic;">Custom Sourcing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $statusClass = 'badge-warning';
                                if ($inq['status'] === 'Resolved') $statusClass = 'badge-success';
                                elseif ($inq['status'] === 'In Review') $statusClass = 'badge-orange';
                                elseif ($inq['status'] === 'Cancelled') $statusClass = 'badge-danger';
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo sanitize_output($inq['status']); ?></span>
                        </td>
                        <td>
                            <a href="<?php echo ADMIN_URL; ?>inquiry-details.php?id=<?php echo (int)$inq['id']; ?>" class="btn btn-primary btn-sm">
                                Review &rarr;
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--admin-text-muted); text-align: center; padding: 2rem 0;">No inquiries received yet.</p>
    <?php endif; ?>
</div>

<!-- Recent Trucks Showcase -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Recently Listed Commercial Trucks</div>
        <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-navy btn-sm">Manage Inventory &rarr;</a>
    </div>

    <?php if (!empty($recentTrucks)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Vehicle Title</th>
                    <th>Category</th>
                    <th>Tonnage</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTrucks as $trk): ?>
                    <tr>
                        <td><strong><?php echo sanitize_output($trk['truck_code']); ?></strong></td>
                        <td>
                            <strong><?php echo sanitize_output($trk['title']); ?></strong>
                            <div style="font-size: 0.78rem; color: var(--admin-text-muted);"><?php echo sanitize_output($trk['brand'] . ' ' . $trk['model'] . ' (' . $trk['year_of_manufacture'] . ')'); ?></div>
                        </td>
                        <td><span class="badge badge-orange"><?php echo sanitize_output($trk['purpose_category']); ?></span></td>
                        <td><?php echo format_tonnage($trk['tonnage_capacity']); ?></td>
                        <td><strong><?php echo format_currency($trk['price']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $trk['availability_status'] === 'Available' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo sanitize_output($trk['availability_status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo ADMIN_URL; ?>truck-form.php?id=<?php echo (int)$trk['id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                            <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$trk['id']; ?>" target="_blank" class="btn btn-navy btn-sm">View</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
