<?php
/**
 * =============================================================================
 * Moal General Suppliers - Customer Fleet Request & Inquiry Dashboard
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_customer_login();

$customer = get_logged_in_customer();
$pageTitle = 'My Account';
$db = getDB();

// 1. Fetch Customer's Fleet Requests
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

// 2. Fetch Customer's Inquiries & Staff Responses (matched by email)
$stmtInq = $db->prepare('
    SELECT i.*, t.truck_code, t.title AS truck_title,
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM inquiries i
    LEFT JOIN trucks t ON i.truck_id = t.id
    WHERE i.customer_email = :email
    ORDER BY i.id DESC
');
$stmtInq->execute([':email' => $customer['email']]);
$inquiries = $stmtInq->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem; padding-bottom: 4rem;">
    <div class="container">
        
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="section-tag">Account Overview</span>
                <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.2rem); margin-bottom: 0.25rem;">
                    Welcome, <?php echo sanitize_output($customer['full_name']); ?>
                </h1>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin: 0;">
                    Track your commercial truck quotes, proforma updates, and staff responses.
                </p>
            </div>

            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                    <span></span> Browse Trucks
                </a>
                <a href="<?php echo BASE_URL; ?>customer-logout.php" class="btn btn-secondary">
                    Log Out
                </a>
            </div>
        </div>

        <!-- Inquiries & Staff Responses Section -->
        <?php if (!empty($inquiries)): ?>
            <div style="margin-bottom: 2.5rem;">
                <h2 style="font-size: 1.3rem; margin-bottom: 1rem; color: var(--color-dark);">
                     Vehicle Inquiries &amp; Dealership Responses (<?php echo count($inquiries); ?>)
                </h2>

                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <?php foreach ($inquiries as $inq): ?>
                        <div class="form-card" style="padding: 1.5rem; border: 1px solid var(--color-border);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--color-border); padding-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                <div>
                                    <span style="font-size: 0.75rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700;">Inquiry Ref:</span>
                                    <strong style="color: var(--color-primary); font-family: monospace; font-size: 1rem; margin-left: 6px;"><?php echo sanitize_output($inq['inquiry_code']); ?></strong>
                                    <span style="font-size: 0.82rem; color: var(--color-text-muted); margin-left: 10px;">
                                        &bull; <?php echo date('M d, Y', strtotime($inq['created_at'])); ?>
                                    </span>
                                </div>

                                <div>
                                    <?php 
                                        $inqBadge = match($inq['status']) {
                                            'Pending' => 'badge-warning',
                                            'In Review' => 'badge-primary',
                                            'Contacted' => 'badge-dark',
                                            'Resolved' => 'badge-success',
                                            'Cancelled' => 'badge-danger',
                                            default => 'badge-subtle'
                                        };
                                    ?>
                                    <span class="badge <?php echo $inqBadge; ?>">● <?php echo sanitize_output($inq['status']); ?></span>
                                </div>
                            </div>

                            <?php if (!empty($inq['truck_code'])): ?>
                                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 1rem; background: var(--color-bg-subtle); padding: 10px 14px; border-radius: var(--radius-sm);">
                                    <?php if (!empty($inq['primary_image'])): ?>
                                        <img src="<?php echo BASE_URL . 'assets/images/trucks/' . sanitize_output($inq['primary_image']); ?>" alt="Thumb" style="width: 60px; height: 42px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                    <div>
                                        <strong style="color: var(--color-dark); font-size: 0.95rem;">
                                            <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$inq['truck_id']; ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                                <?php echo sanitize_output($inq['truck_title']); ?> &nearr;
                                            </a>
                                        </strong>
                                        <div style="font-size: 0.78rem; color: var(--color-text-muted);">Stock #<?php echo sanitize_output($inq['truck_code']); ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div style="margin-bottom: 1rem; font-size: 0.9rem; color: var(--color-text);">
                                <strong>Your Inquiry:</strong>
                                <p style="margin: 4px 0 0 0; background: var(--color-white); padding: 10px 14px; border-radius: var(--radius-sm); border: 1px solid var(--color-border);"><?php echo nl2br(sanitize_output($inq['message'])); ?></p>
                            </div>

                            <?php if (!empty($inq['admin_notes'])): ?>
                                <div style="background: #FDF3ED; border-left: 4px solid var(--color-primary); padding: 12px 16px; border-radius: 4px; font-size: 0.9rem;">
                                    <strong style="color: var(--color-primary); display: block; margin-bottom: 4px;">Dealership Staff Response:</strong>
                                    <div style="color: var(--color-dark); line-height: 1.6;"><?php echo nl2br(sanitize_output($inq['admin_notes'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Fleet Requests List -->
        <h2 style="font-size: 1.3rem; margin-bottom: 1rem; color: var(--color-dark);">
             Fleet Sourcing &amp; Quote Requests (<?php echo count($requests); ?>)
        </h2>

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
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 3rem 2rem; text-align: center;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;"></div>
                <h3 style="margin-bottom: 0.5rem;">No Active Fleet Requests</h3>
                <p style="color: var(--color-text-muted); max-width: 460px; margin: 0 auto 1.5rem auto;">
                    You have not submitted any multi-truck fleet requests yet. Browse our commercial inventory and click "Request Quote".
                </p>
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                    Explore Inventory &rarr;
                </a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>