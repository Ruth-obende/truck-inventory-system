<?php
/**
 * =============================================================================
 * Moal General Suppliers - Newsletter Subscribers Management
 * =============================================================================
 * Allows dealership administrators to view and export customer email subscribers.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Newsletter Subscribers';
$db = getDB();

$searchKey = sanitize_input($_GET['search'] ?? '');

$sql = "SELECT * FROM newsletter_subscribers WHERE 1=1";
$params = [];

if (!empty($searchKey)) {
    $sql .= " AND email LIKE :search";
    $params[':search'] = '%' . $searchKey . '%';
}

$sql .= " ORDER BY id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();
$totalSubscribers = count($subscribers);

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h2 style="font-size: 1.4rem; color: var(--admin-navy);">Newsletter Subscribers</h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">Subscribed customer emails captured from the public website footer.</p>
    </div>
</div>

<!-- Search Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>newsletter.php" style="display: flex; gap: 12px; align-items: center; max-width: 500px;">
        <input type="text" name="search" class="form-control" placeholder="Search by email address..." value="<?php echo sanitize_output($searchKey); ?>">
        <button type="submit" class="btn btn-navy btn-sm" style="padding: 9px 16px;">Search</button>
        <?php if (!empty($searchKey)): ?>
            <a href="<?php echo ADMIN_URL; ?>newsletter.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Subscribers Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Subscribed Contacts (<?php echo $totalSubscribers; ?> Total)</div>
    </div>

    <?php if (!empty($subscribers)): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>Email Address</th>
                    <th>Subscribed Date &amp; Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($subscribers as $idx => $sub): ?>
                    <tr>
                        <td><?php echo $idx + 1; ?></td>
                        <td>
                            <strong style="color: var(--admin-navy); font-size: 0.95rem;">
                                <?php echo sanitize_output($sub['email']); ?>
                            </strong>
                        </td>
                        <td style="color: var(--admin-text-muted); font-size: 0.88rem;">
                            <?php echo date('M j, Y &bull; g:i a', strtotime($sub['created_at'])); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="color: var(--admin-text-muted); text-align: center; padding: 2rem 0;">No newsletter subscribers recorded yet.</p>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
