<?php
/**
 * =============================================================================
 * Moal General Suppliers - Admin Customer Requests Queue
 * =============================================================================
 * Manage, assign, and track incoming customer fleet sourcing requests for offline sales.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Customer Fleet Requests';
$db = getDB();

$statusFilter = sanitize_input($_GET['status'] ?? '');
$searchQuery = sanitize_input($_GET['search'] ?? '');

// Metrics Count
$counts = [
    'all' => 0,
    'New' => 0,
    'Assigned to Agent' => 0,
    'Contacted' => 0,
    'Closed' => 0
];

try {
    $stmtCounts = $db->query('
        SELECT status, COUNT(*) as cnt 
        FROM customer_requests 
        GROUP BY status
    ');
    while ($row = $stmtCounts->fetch()) {
        if (isset($counts[$row['status']])) {
            $counts[$row['status']] = (int)$row['cnt'];
        }
        $counts['all'] += (int)$row['cnt'];
    }
} catch (Exception $e) {
    error_log('[Admin Requests Count Error] ' . $e->getMessage());
}

// Build Filter Query
$sql = "
    SELECT r.*, c.full_name, c.phone, c.email, c.delivery_address, c.business_name,
    (SELECT COUNT(*) FROM request_items WHERE request_id = r.id) AS item_count
    FROM customer_requests r
    JOIN customers c ON r.customer_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($statusFilter) && in_array($statusFilter, ['New', 'Assigned to Agent', 'Contacted', 'Closed'], true)) {
    $sql .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

if (!empty($searchQuery)) {
    $sql .= " AND (r.request_code LIKE :search OR c.full_name LIKE :search OR c.phone LIKE :search OR c.email LIKE :search OR r.assigned_agent LIKE :search)";
    $params[':search'] = '%' . $searchQuery . '%';
}

$sql .= " ORDER BY r.created_at DESC";

$requests = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();

    // Fetch items for each request
    foreach ($requests as &$req) {
        $stmtItems = $db->prepare('
            SELECT ri.*, t.truck_code, t.title, t.brand, t.price, t.tonnage_capacity,
            (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
            FROM request_items ri
            JOIN trucks t ON ri.truck_id = t.id
            WHERE ri.request_id = :request_id
        ');
        $stmtItems->execute([':request_id' => $req['id']]);
        $req['items'] = $stmtItems->fetchAll();
    }
    unset($req);

} catch (Exception $e) {
    error_log('[Admin Requests Fetch Error] ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-content-header">
    <div>
        <h1 class="admin-page-title">Customer Fleet Sourcing Requests</h1>
        <p class="admin-page-subtitle">Queue of verified customer requests for offline commercial vehicle sales and proforma invoices.</p>
    </div>
</div>

<!-- Metrics Cards Strip -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    
    <a href="<?php echo ADMIN_URL; ?>requests.php" style="text-decoration: none;">
        <div style="background: #ffffff; border: 1px solid <?php echo empty($statusFilter) ? 'var(--accent-orange)' : '#e2e8f0'; ?>; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <span style="font-size: 0.8rem; color: #64748b; text-transform: uppercase; font-weight: 700;">Total Requests</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: var(--admin-navy); margin-top: 4px;"><?php echo $counts['all']; ?></div>
        </div>
    </a>

    <a href="<?php echo ADMIN_URL; ?>requests.php?status=New" style="text-decoration: none;">
        <div style="background: #fffbeb; border: 1px solid <?php echo ($statusFilter === 'New') ? '#D9825B' : '#E2E8F0'; ?>; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <span style="font-size: 0.8rem; color: #b45309; text-transform: uppercase; font-weight: 700;">● New Requests</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #92400e; margin-top: 4px;"><?php echo $counts['New']; ?></div>
        </div>
    </a>

    <a href="<?php echo ADMIN_URL; ?>requests.php?status=Assigned+to+Agent" style="text-decoration: none;">
        <div style="background: #F8FAFC; border: 1px solid <?php echo ($statusFilter === 'Assigned to Agent') ? '#0F172A' : '#E2E8F0'; ?>; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <span style="font-size: 0.8rem; color: #0F172A; text-transform: uppercase; font-weight: 700;">● Assigned to Agent</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #1e40af; margin-top: 4px;"><?php echo $counts['Assigned to Agent']; ?></div>
        </div>
    </a>

    <a href="<?php echo ADMIN_URL; ?>requests.php?status=Contacted" style="text-decoration: none;">
        <div style="background: #fdf4ff; border: 1px solid <?php echo ($statusFilter === 'Contacted') ? '#a855f7' : '#f0abfc'; ?>; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <span style="font-size: 0.8rem; color: #7e22ce; text-transform: uppercase; font-weight: 700;">● Contacted</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #6b21a8; margin-top: 4px;"><?php echo $counts['Contacted']; ?></div>
        </div>
    </a>

    <a href="<?php echo ADMIN_URL; ?>requests.php?status=Closed" style="text-decoration: none;">
        <div style="background: #F8FAFC; border: 1px solid <?php echo ($statusFilter === 'Closed') ? '#1E293B' : '#E2E8F0'; ?>; border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <span style="font-size: 0.8rem; color: #15803d; text-transform: uppercase; font-weight: 700;">● Closed</span>
            <div style="font-size: 1.8rem; font-weight: 800; color: #166534; margin-top: 4px;"><?php echo $counts['Closed']; ?></div>
        </div>
    </a>

</div>

<!-- Search & Filter Bar -->
<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
    
    <form method="GET" action="<?php echo ADMIN_URL; ?>requests.php" style="display: flex; gap: 0.5rem; flex: 1; max-width: 500px;">
        <?php if (!empty($statusFilter)): ?>
            <input type="hidden" name="status" value="<?php echo sanitize_output($statusFilter); ?>">
        <?php endif; ?>
        <input type="text" name="search" class="form-control" placeholder="Search by Code, Customer, Phone, or Agent..." value="<?php echo sanitize_output($searchQuery); ?>">
        <button type="submit" class="btn btn-primary btn-sm" style="padding: 0 16px;">Search</button>
        <?php if (!empty($searchQuery)): ?>
            <a href="<?php echo ADMIN_URL; ?>requests.php<?php echo !empty($statusFilter) ? '?status=' . urlencode($statusFilter) : ''; ?>" class="btn btn-secondary btn-sm" style="display: flex; align-items: center;">Reset</a>
        <?php endif; ?>
    </form>

    <div style="font-size: 0.88rem; color: #64748b;">
        Showing <strong><?php echo count($requests); ?></strong> request<?php echo count($requests) === 1 ? '' : 's'; ?>
    </div>
</div>

<!-- Requests Table Card -->
<div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); overflow: hidden;">
    
    <?php if (!empty($requests)): ?>
        <div style="overflow-x: auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Code / Date</th>
                        <th>Customer Profile</th>
                        <th>Requested Products</th>
                        <th>Assigned Agent</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <!-- Code & Date -->
                            <td style="white-space: nowrap;">
                                <strong style="color: var(--admin-navy); font-size: 0.95rem; display: block; font-family: monospace;">
                                    <?php echo sanitize_output($r['request_code']); ?>
                                </strong>
                                <span style="font-size: 0.78rem; color: #64748b;">
                                    <?php echo date('M d, Y &bull; h:i A', strtotime($r['created_at'])); ?>
                                </span>
                            </td>

                            <!-- Customer Info -->
                            <td>
                                <strong style="color: #0f172a; display: block; font-size: 0.95rem;"><?php echo sanitize_output($r['full_name']); ?></strong>
                                <div style="font-size: 0.82rem; color: #475569; margin-top: 2px;">
                                     <a href="tel:<?php echo sanitize_output($r['phone']); ?>" style="color: var(--admin-orange, #D9825B); text-decoration: none; font-weight: 600;"><?php echo sanitize_output($r['phone']); ?></a>
                                </div>
                                <div style="font-size: 0.78rem; color: #64748b;">
                                     <?php echo sanitize_output($r['email']); ?>
                                </div>
                                <div style="font-size: 0.78rem; color: #64748b; margin-top: 2px;">
                                     <?php echo sanitize_output(substr($r['delivery_address'], 0, 35)) . (strlen($r['delivery_address']) > 35 ? '...' : ''); ?>
                                </div>
                                <?php if (!empty($r['business_name'])): ?>
                                    <div style="font-size: 0.75rem; color: #b45309; font-weight: 600;">
                                         <?php echo sanitize_output($r['business_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Requested Trucks -->
                            <td>
                                <?php if (!empty($r['items'])): ?>
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <?php foreach ($r['items'] as $item): ?>
                                            <div style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem;">
                                                <?php if (!empty($item['primary_image'])): ?>
                                                    <img src="<?php echo BASE_URL . sanitize_output($item['primary_image']); ?>" alt="<?php echo sanitize_output($item['title']); ?>" style="width: 40px; height: 30px; object-fit: cover; border-radius: 4px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong style="color: var(--admin-navy);"><?php echo sanitize_output($item['title']); ?></strong>
                                                    <span style="display: block; font-size: 0.75rem; color: #64748b;"><?php echo sanitize_output($item['truck_code']); ?> &bull; <?php echo format_naira((float)$item['price']); ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.85rem;">No items attached</span>
                                <?php endif; ?>
                            </td>

                            <!-- Assigned Agent -->
                            <td>
                                <?php if (!empty($r['assigned_agent'])): ?>
                                    <div style="font-weight: 600; color: #0369a1; font-size: 0.88rem;">
                                         <?php echo sanitize_output($r['assigned_agent']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.82rem; font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>

                            <!-- Status Badge -->
                            <td>
                                <?php 
                                $statusBadge = match($r['status']) {
                                    'New' => 'background: #fef3c7; color: #92400e; border: 1px solid #E2E8F0;',
                                    'Assigned to Agent' => 'background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;',
                                    'Contacted' => 'background: #f3e8ff; color: #6b21a8; border: 1px solid #e9d5ff;',
                                    'Closed' => 'background: #dcfce7; color: #166534; border: 1px solid #E2E8F0;',
                                    default => 'background: #f1f5f9; color: #475569;'
                                };
                                ?>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 700; <?php echo $statusBadge; ?>">
                                    ● <?php echo sanitize_output($r['status']); ?>
                                </span>
                            </td>

                            <!-- Quick Action Buttons -->
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                    <?php 
                                    $waPhone = preg_replace('/[^0-9]/', '', $r['phone']);
                                    if (str_starts_with($waPhone, '0')) {
                                        $waPhone = '234' . substr($waPhone, 1);
                                    }
                                    ?>
                                    <a href="https://wa.me/<?php echo $waPhone; ?>?text=Hello%20<?php echo urlencode($r['full_name']); ?>,%20I%20am%20contacting%20you%20from%20Moal%20General%20Suppliers%20regarding%20your%20truck%20request%20<?php echo urlencode($r['request_code']); ?>" target="_blank" class="btn btn-sm" style="background: var(--admin-orange, #D9825B); color: #fff; padding: 4px 8px; font-size: 0.8rem;" title="Chat with customer on WhatsApp">
                                         WhatsApp
                                    </a>
                                    <a href="<?php echo ADMIN_URL; ?>request-details.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-navy" style="padding: 4px 10px; font-size: 0.8rem;">
                                        Manage &rarr;
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="padding: 3rem 1.5rem; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.75rem;"></div>
            <h3 style="color: var(--admin-navy); margin-bottom: 0.5rem;">No Customer Requests Found</h3>
            <p style="color: #64748b; font-size: 0.92rem;">There are no customer requests matching the selected filter criteria.</p>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
