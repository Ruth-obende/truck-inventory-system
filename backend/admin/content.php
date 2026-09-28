<?php
/**
 * =============================================================================
 * Moal General Suppliers - Website Content & Dealership Information
 * =============================================================================
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = 'Website Content';
$db = getDB();

$settingsFilePath = dirname(__DIR__) . '/includes/dealership_settings.json';

// Default dealership settings
$defaultSettings = [
    'dealership_name'    => 'Moal General Suppliers Ltd',
    'sales_phone'        => '+234 706 921 9001',
    'whatsapp_phone'     => '+234 706 921 9001',
    'sales_email'        => 'sales@moalgeneralsuppliers.com',
    'yard_address'       => 'KM 14, Commercial Vehicle Park, Expressway, Nigeria',
    'operating_hours'    => 'Monday – Saturday: 8:00 AM – 6:00 PM',
    'about_summary'      => 'Moal General Suppliers is a premier commercial truck dealership specializing in heavy-duty haulage, construction tippers, and logistics fleet solutions across Nigeria.'
];

$settings = $defaultSettings;
if (file_exists($settingsFilePath)) {
    $loaded = json_decode(file_get_contents($settingsFilePath), true);
    if (is_array($loaded)) {
        $settings = array_merge($defaultSettings, $loaded);
    }
}

// Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Security token expired. Please try again.');
    } else {
        $updatedSettings = [
            'dealership_name' => sanitize_input($_POST['dealership_name'] ?? $defaultSettings['dealership_name']),
            'sales_phone'     => sanitize_input($_POST['sales_phone'] ?? $defaultSettings['sales_phone']),
            'whatsapp_phone'  => sanitize_input($_POST['whatsapp_phone'] ?? $defaultSettings['whatsapp_phone']),
            'sales_email'     => sanitize_input($_POST['sales_email'] ?? $defaultSettings['sales_email']),
            'yard_address'    => sanitize_input($_POST['yard_address'] ?? $defaultSettings['yard_address']),
            'operating_hours' => sanitize_input($_POST['operating_hours'] ?? $defaultSettings['operating_hours']),
            'about_summary'   => sanitize_input($_POST['about_summary'] ?? $defaultSettings['about_summary'])
        ];

        file_put_contents($settingsFilePath, json_encode($updatedSettings, JSON_PRETTY_PRINT));
        $settings = $updatedSettings;

        // Also update featured trucks if submitted
        if (isset($_POST['featured_truck_ids'])) {
            $featuredIds = array_map('intval', (array)$_POST['featured_truck_ids']);
            $db->query('UPDATE trucks SET featured = 0');
            if (!empty($featuredIds)) {
                $inClause = implode(',', $featuredIds);
                $db->query("UPDATE trucks SET featured = 1 WHERE id IN ($inClause)");
            }
        }

        set_flash_message('success', 'Website dealership details and featured inventory updated.');
        redirect(ADMIN_URL . 'content.php');
    }
}

// Fetch all available trucks for featured selection
$stmtTrks = $db->query('SELECT id, truck_code, title, brand, model, price, availability_status, featured FROM trucks ORDER BY id DESC');
$trucksList = $stmtTrks->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="admin-page-header">
    <div>
        <h1 class="admin-heading-title">Website Content &amp; Dealership Info</h1>
        <div class="admin-heading-sub">Manage customer-facing yard addresses, sales phone lines, operating hours, and featured inventory</div>
    </div>
</div>

<form method="POST" action="<?php echo ADMIN_URL; ?>content.php">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <!-- Dealership Contact Information -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div class="admin-card-title">Dealership Contact &amp; Operating Hours</div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="dealership_name" class="form-label">Dealership Trade Name</label>
                <input type="text" id="dealership_name" name="dealership_name" class="form-control" value="<?php echo sanitize_output($settings['dealership_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="sales_email" class="form-label">Sales Department Email</label>
                <input type="email" id="sales_email" name="sales_email" class="form-control" value="<?php echo sanitize_output($settings['sales_email']); ?>" required>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="sales_phone" class="form-label">Main Sales Phone / Hotline</label>
                <input type="text" id="sales_phone" name="sales_phone" class="form-control" value="<?php echo sanitize_output($settings['sales_phone']); ?>" required>
            </div>

            <div class="form-group">
                <label for="whatsapp_phone" class="form-label">WhatsApp Sales Desk Number</label>
                <input type="text" id="whatsapp_phone" name="whatsapp_phone" class="form-control" value="<?php echo sanitize_output($settings['whatsapp_phone']); ?>" required>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label for="yard_address" class="form-label">Physical Dealership Yard Address</label>
                <input type="text" id="yard_address" name="yard_address" class="form-control" value="<?php echo sanitize_output($settings['yard_address']); ?>" required>
            </div>

            <div class="form-group">
                <label for="operating_hours" class="form-label">Operating / Inspection Hours</label>
                <input type="text" id="operating_hours" name="operating_hours" class="form-control" value="<?php echo sanitize_output($settings['operating_hours']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="about_summary" class="form-label">Dealership Summary / Brand Statement</label>
            <textarea id="about_summary" name="about_summary" rows="3" class="form-control"><?php echo sanitize_output($settings['about_summary']); ?></textarea>
        </div>
    </div>

    <!-- Featured Trucks Selection -->
    <div class="admin-card">
        <div class="admin-card-header">
            <div>
                <div class="admin-card-title">Featured Trucks Showcase</div>
                <div style="font-size: 0.82rem; color: var(--admin-text-muted);">Select vehicles to spotlight on the homepage and recommendation results.</div>
            </div>
        </div>

        <?php if (!empty($trucksList)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem;">
                <?php foreach ($trucksList as $trk): ?>
                    <label style="display: flex; align-items: center; gap: 10px; background: #F8FAFC; border: 1px solid var(--admin-border); border-radius: 6px; padding: 12px; cursor: pointer;">
                        <input type="checkbox" name="featured_truck_ids[]" value="<?php echo (int)$trk['id']; ?>" <?php echo ($trk['featured'] ? 'checked' : ''); ?> style="width: 18px; height: 18px;">
                        <div>
                            <strong style="color: var(--admin-navy); font-size: 0.9rem; display: block;">
                                <?php echo sanitize_output($trk['truck_code']); ?> &bull; <?php echo sanitize_output($trk['title']); ?>
                            </strong>
                            <span style="font-size: 0.78rem; color: var(--admin-text-muted);">
                                <?php echo format_currency($trk['price']); ?> &bull; <?php echo sanitize_output($trk['availability_status']); ?>
                            </span>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: var(--admin-text-muted); font-size: 0.88rem;">No trucks in inventory to feature.</p>
        <?php endif; ?>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 1rem;">
        <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-weight: 700;">
             Save Dealership Content
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>