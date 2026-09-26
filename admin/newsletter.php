<?php
/**
 * =============================================================================
 * Moal General Suppliers - Newsletter Subscribers Management
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Newsletter Subscribers';
$db = getDB();

// Handle Export to CSV
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    $stmtAll = $db->query('SELECT id, email, created_at FROM newsletter_subscribers ORDER BY id DESC');
    $subscribers = $stmtAll->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=moal_newsletter_subscribers_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Subscriber ID', 'Email Address', 'Subscription Date']);
    foreach ($subscribers as $s) {
        fputcsv($output, [$s['id'], $s['email'], $s['created_at']]);
    }
    fclose($output);
    exit;
}

// Handle Delete Subscriber (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $subId = (int)($_POST['subscriber_id'] ?? 0);
        if ($subId > 0) {
            $stmtDel = $db->prepare('DELETE FROM newsletter_subscribers WHERE id = :id');
            $stmtDel->execute([':id' => $subId]);
            set_flash_message('success', 'Subscriber removed from newsletter list.');
            redirect(ADMIN_URL . 'newsletter.php');
        }
    }
}

// Search
$searchKey = sanitize_input($_GET['search'] ?? '');
$sql = "SELECT * FROM newsletter_subscribers WHERE 1=1";
$params = [];

if (!empty($searchKey)) {
    $sql .= " AND email LIKE :s";
    $params[':s'] = '%' . $searchKey . '%';
}

$sql .= " ORDER BY id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$subscribers = $stmt->fetchAll();

$totalSubscribers = (int)$db->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Newsletter Subscribers</h1>
        <div class="admin-heading-sub">Manage audience email contacts and export subscriber lists</div>
    </div>
    <div>
        <a href="<?php echo ADMIN_URL; ?>newsletter.php?action=export" class="btn btn-outline">
            Export CSV (<?php echo $totalSubscribers; ?>)
        </a>
    </div>
</div>

<!-- Search Bar -->
<div class="admin-card" style="padding: 1.25rem; margin-bottom: 1.5rem;">
    <form method="GET" action="<?php echo ADMIN_URL; ?>newsletter.php" style="display: grid; grid-template-columns: 3fr auto auto; gap: 10px; align-items: center;">
        <input type="text" name="search" class="form-control" placeholder="Search by email address..." value="<?php echo sanitize_output($searchKey); ?>">
        <button type="submit" class="btn btn-dark btn-sm" style="padding: 9px 16px;">Search</button>
        <a href="<?php echo ADMIN_URL; ?>newsletter.php" class="btn btn-outline btn-sm" style="padding: 9px 12px;">Reset</a>
    </form>
</div>

<!-- Subscribers Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <div class="admin-card-title">Subscribed Contacts (<?php echo count($subscribers); ?> Total)</div>
    </div>

    <?php if (!empty($subscribers)): ?>
        <div class="admin-table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Email Address</th>
                        <th>Subscribed Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $idx => $s): ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td>
                                <strong>
                                    <a href="mailto:<?php echo sanitize_output($s['email']); ?>" style="color: var(--c-navy); text-decoration: none;">
                                        <?php echo sanitize_output($s['email']); ?>
                                    </a>
                                </strong>
                            </td>
                            <td style="color: var(--c-muted); font-size: 0.82rem;">
                                <?php echo date('M j, Y - g:ia', strtotime($s['created_at'])); ?>
                            </td>
                            <td>
                                <form method="POST" action="<?php echo ADMIN_URL; ?>newsletter.php" style="margin: 0; display: inline;" onsubmit="return confirm('Remove <?php echo addslashes($s['email']); ?> from newsletter list?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="subscriber_id" value="<?php echo (int)$s['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state-card">
            <div class="empty-state-title">No Subscribers Found</div>
            <div class="empty-state-text">
                <?php if (!empty($searchKey)): ?>
                    No newsletter contacts match your search query "<?php echo sanitize_output($searchKey); ?>".
                <?php else: ?>
                    Subscribers registered through website newsletter forms will appear here.
                <?php endif; ?>
            </div>
            <div class="empty-state-actions">
                <a href="<?php echo ADMIN_URL; ?>newsletter.php" class="btn btn-outline btn-sm">Reset Search</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>