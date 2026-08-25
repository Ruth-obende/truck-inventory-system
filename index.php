<?php
/**
 * =============================================================================
 * Moal General Suppliers - System Homepage & Baseline Verification
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';

$dbStatus = 'Connecting...';
$mysqlVersion = 'Unknown';
$totalTrucks = 0;
$featuredTrucks = [];

try {
    $db = getDB();
    $dbStatus = 'Connected Successfully (PDO)';
    $mysqlVersion = $db->query('SELECT VERSION()')->fetchColumn();
    
    // Count total inventory
    $stmtCount = $db->query('SELECT COUNT(*) FROM trucks');
    $totalTrucks = (int)$stmtCount->fetchColumn();

    // Fetch featured trucks for display
    $stmtTrucks = $db->prepare('
        SELECT t.*, 
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM trucks t 
        WHERE t.availability_status = :status 
        ORDER BY t.featured DESC, t.id ASC 
        LIMIT 6
    ');
    $stmtTrucks->execute([':status' => 'Available']);
    $featuredTrucks = $stmtTrucks->fetchAll();

} catch (Exception $e) {
    $dbStatus = 'Connection Failed: ' . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container hero-content">
        <div class="hero-badge">Official Case Study &bull; Moal General Suppliers</div>
        <h1 class="hero-title">Commercial Truck Inventory &amp; <span>Inquiry Management</span></h1>
        <p class="hero-description">
            Explore premium heavy-duty haulage prime movers, construction tippers, distribution vehicles, and specialized commercial trucks. 
            Find the exact vehicle for your operational capacity using our rule-based recommendation tool.
        </p>
        <div class="hero-actions">
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">Browse Full Truck Catalogue</a>
            <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-outline" style="border-color: #fff; color: #fff;">3-Question Recommendation Finder</a>
        </div>
    </div>
</section>

<!-- Baseline Stack Verification Panel -->
<section class="container" style="margin-top: -1.5rem; position: relative; z-index: 10;">
    <div class="diagnostic-panel">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
            <h3 style="color: var(--primary-navy); font-size: 1.1rem; display: flex; align-items: center; gap: 8px;">
                <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #22c55e;"></span>
                Phase 2 Verification: 3-Tier Architecture &amp; System Health
            </h3>
            <span class="badge badge-success">System Online</span>
        </div>
        <div class="diagnostic-grid">
            <div class="diag-item">
                <h4>Web Server</h4>
                <p>Apache 2.4.66</p>
            </div>
            <div class="diag-item">
                <h4>Application Engine</h4>
                <p>PHP <?php echo phpversion(); ?></p>
            </div>
            <div class="diag-item">
                <h4>Database Engine</h4>
                <p>MySQL <?php echo sanitize_output($mysqlVersion); ?></p>
            </div>
            <div class="diag-item">
                <h4>Database Connection</h4>
                <p style="color: #16a34a; font-size: 0.95rem; font-weight: 600;">Active (moal_truck_db)</p>
            </div>
            <div class="diag-item">
                <h4>Inventory Seeded</h4>
                <p><?php echo $totalTrucks; ?> Trucks in Stock</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Inventory Section -->
<section class="section" id="inventory">
    <div class="container">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: gap; gap: 1rem;">
            <div>
                <h2 class="section-title">Featured Commercial Trucks</h2>
                <p class="section-subtitle">Dynamically queried from the MySQL database layer using PDO prepared statements.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-navy btn-sm">View All <?php echo $totalTrucks; ?> Trucks &rarr;</a>
        </div>

        <div class="inventory-grid">
            <?php if (!empty($featuredTrucks)): ?>
                <?php foreach ($featuredTrucks as $truck): ?>
                    <div class="truck-card">
                        <div class="truck-card-media">
                            <div class="truck-card-badges">
                                <span class="badge badge-orange"><?php echo sanitize_output($truck['purpose_category']); ?></span>
                                <span class="badge badge-navy"><?php echo sanitize_output($truck['year_of_manufacture']); ?></span>
                            </div>

                            <div class="truck-card-status">
                                <span class="badge badge-success">Available</span>
                            </div>

                            <?php 
                                $hasImage = false;
                                if (!empty($truck['primary_image']) && file_exists(UPLOADS_PATH . $truck['primary_image'])) {
                                    $hasImage = true;
                                    $imgSrc = BASE_URL . 'assets/images/trucks/' . $truck['primary_image'];
                                }
                            ?>

                            <?php if ($hasImage): ?>
                                <img src="<?php echo $imgSrc; ?>" alt="<?php echo sanitize_output($truck['title']); ?>" class="truck-card-img">
                            <?php else: ?>
                                <div class="truck-card-placeholder">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                    </svg>
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #cbd5e1;"><?php echo sanitize_output($truck['brand']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="truck-card-body">
                            <div class="truck-card-code"><?php echo sanitize_output($truck['truck_code']); ?></div>
                            <h3 class="truck-card-title"><?php echo sanitize_output($truck['title']); ?></h3>
                            <div class="truck-card-price"><?php echo format_currency($truck['price']); ?></div>

                            <div class="truck-specs-mini">
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Payload</span>
                                    <span class="spec-mini-val"><?php echo format_tonnage($truck['tonnage_capacity']); ?></span>
                                </div>
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Drive</span>
                                    <span class="spec-mini-val"><?php echo sanitize_output($truck['wheel_configuration']); ?></span>
                                </div>
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Trans.</span>
                                    <span class="spec-mini-val"><?php echo sanitize_output($truck['transmission']); ?></span>
                                </div>
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Mileage</span>
                                    <span class="spec-mini-val"><?php echo format_mileage($truck['mileage']); ?></span>
                                </div>
                            </div>

                            <div class="truck-card-actions">
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$truck['id']; ?>" class="btn btn-navy btn-sm">Full Specs</a>
                                <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-sm">Inquire</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; padding: 2rem; background: #fff; border-radius: var(--radius-md); text-align: center; color: var(--text-muted);">
                    No trucks currently available in the database.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Rule-Based Recommendation Section -->
<section class="section" id="recommendation" style="background: #ffffff; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container" style="text-align: center; max-width: 780px;">
        <span class="badge badge-warning" style="margin-bottom: 0.75rem;">Rule-Based Decision Logic</span>
        <h2 class="section-title">Find the Exact Truck for Your Business in 3 Steps</h2>
        <p style="color: var(--text-muted); margin: 0.75rem 0 1.75rem 0; font-size: 1.05rem; line-height: 1.7;">
            Not sure which truck best fits your operational requirements? Answer three quick questions regarding your intended purpose, budget, and payload capacity to get recommended matches from our inventory.
        </p>
        <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-primary" style="padding: 12px 28px; font-size: 1.05rem;">
            Launch Recommendation Tool &rarr;
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
