<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Request Dashboard
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_customer_login();

$customer = get_logged_in_customer();
$pageTitle = 'My Fleet Requests';
$db = getDB();

// Fetch Customer's Requests
$stmtReq = $db->prepare('
    SELECT r.*,
    (SELECT COUNT(*) FROM request_items WHERE request_id = r.id) AS item_count
    FROM customer_requests r
    WHERE r.customer_id = :cid
    ORDER BY r.created_at DESC
');
$stmtReq->execute([':cid' => $customer['id']]);
$requests = $stmtReq->fetchAll();

// Fetch items for each request
foreach ($requests as &$r) {
    $stmtItems = $db->prepare('
        SELECT ri.*, t.truck_code, t.title, t.brand, t.price, t.tonnage_capacity,
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM request_items ri
        JOIN trucks t ON ri.truck_id = t.id
        WHERE ri.request_id = :request_id
    ');
    $stmtItems->execute([':request_id' => $r['id']]);
    $r['items'] = $stmtItems->fetchAll();
}
unset($r);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="section-tag">Customer Account</span>
                <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.2rem); margin-bottom: 0.25rem;">
                    Welcome, <?php echo sanitize_output($customer['full_name']); ?>
                </h1>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                    Track your commercial truck quotes, proforma updates, and assigned dealership sales consultants.
                </p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                    <span></span> Request Another Truck
                </a>
                <a href="<?php echo BASE_URL; ?>customer-logout.php" class="btn btn-secondary">
                    Log Out
                </a>
            </div>
        </div>

        <!-- Requests List -->
        <?php if (!empty($requests)): ?>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <?php foreach ($requests as $req): ?>
                    <div class="form-card" style="padding: 1.75rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem;">
                            <div>
                                <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Request Reference:</span>
                                <strong style="color: var(--color-primary); font-family: monospace; font-size: 1.1rem; margin-left: 6px;"><?php echo sanitize_output($req['request_code']); ?></strong>
                                <span style="font-size: 0.85rem; color: var(--color-text-muted); margin-left: 12px;">Submitted on <?php echo date('M d, Y', strtotime($req['created_at'])); ?></span>
                            </div>

                            <div>
                                <?php 
                                    $statusBadge = match($req['status']) {
                                        'New' => 'badge-warning',
                                        'Assigned to Agent' => 'badge-primary',
                                        'Contacted' => 'badge-dark',
                                        'Closed' => 'badge-success',
                                        default => 'badge-subtle'
                                    };
                                ?>
                                <span class="badge <?php echo $statusBadge; ?>" style="font-size: 0.85rem; padding: 6px 12px;">
                                    ● <?php echo sanitize_output($req['status']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Requested Trucks -->
                        <?php if (!empty($req['items'])): ?>
                            <div style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.25rem;">
                                <?php foreach ($req['items'] as $item): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: var(--color-bg-subtle); padding: 1rem; border-radius: var(--radius-sm); flex-wrap: wrap; gap: 1rem;">
                                        <div style="display: flex; gap: 1rem; align-items: center;">
                                            <?php if (!empty($item['primary_image'])): ?>
                                                <img src="<?php echo BASE_URL . 'assets/images/trucks/' . sanitize_output($item['primary_image']); ?>" alt="Thumb" style="width: 70px; height: 50px; object-fit: cover; border-radius: 4px;">
                                            <?php endif; ?>
                                            <div>
                                                <h4 style="margin: 0; font-size: 1rem; color: var(--color-dark);">
                                                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$item['truck_id']; ?>" target="_blank" style="color: inherit;">
                                                        <?php echo sanitize_output($item['title']); ?> &nearr;
                                                    </a>
                                                </h4>
                                                <span style="font-size: 0.8rem; color: var(--color-text-muted);">
                                                    Stock #<?php echo sanitize_output($item['truck_code']); ?> &bull; <?php echo format_tonnage($item['tonnage_capacity']); ?>
                                                </span>
                                            </div>
                                        </div>

                                        <div style="font-weight: 800; color: var(--color-dark); font-size: 1.1rem;">
                                            <?php echo format_currency($item['price']); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Assigned Agent & Follow-up Notes -->
                        <div style="background: var(--color-white); border: 1px dashed var(--color-border); border-radius: var(--radius-sm); padding: 1rem; font-size: 0.88rem;">
                            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
                                <div>
                                    <strong style="color: var(--color-dark);">Assigned Sales Consultant:</strong>
                                    <span style="color: var(--color-primary); font-weight: 600; margin-left: 4px;">
                                        <?php echo !empty($req['assigned_agent']) ? sanitize_output($req['assigned_agent']) : 'Pending Consultant Assignment'; ?>
                                    </span>
                                </div>
                                <div>
                                    <a href="https://wa.me/2347069219001?text=Hello%20Moal%20Sales%20Desk,%20I%20am%20following%20up%20on%20my%20request%20<?php echo urlencode($req['request_code']); ?>" target="_blank" rel="noopener noreferrer" style="color: var(--color-primary); font-weight: 700; text-decoration: none;">
                                         WhatsApp Dealership Desk
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 4rem 2rem; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
                <h3 style="margin-bottom: 0.5rem;">No Active Fleet Requests</h3>
                <p style="color: var(--color-text-muted); max-width: 460px; margin: 0 auto 1.5rem auto;">
                    You have not submitted any truck quote requests yet. Browse our commercial inventory and click "Request Quote".
                </p>
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                    Explore Inventory &rarr;
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
