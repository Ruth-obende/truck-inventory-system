<?php
/**
 * =============================================================================
 * Moal General Suppliers - Commercial Truck Dealership Homepage
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Commercial Truck Inventory & Fleet Solutions';
$db = getDB();

// Fetch 3 to 6 Featured Trucks from Database
$stmtFeatured = $db->query('
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    WHERE t.availability_status = "Available"
    ORDER BY t.featured DESC, t.id DESC 
    LIMIT 6
');
$featuredTrucks = $stmtFeatured->fetchAll();

// Category counts for summary
$categoryStats = [
    'Construction & Mining'   => ['icon' => '️', 'label' => 'Tipper Trucks', 'desc' => 'High-capacity tippers engineered for quarry, sand, and heavy infrastructure transit.'],
    'Heavy Haulage'           => ['icon' => '', 'label' => 'Tractor Heads / Haulage', 'desc' => '6x4 and 4x2 prime movers for long-distance interstate cargo and container transport.'],
    'Distribution & Logistics'=> ['icon' => '', 'label' => 'Cargo & Box Trucks', 'desc' => 'Reliable medium and light-duty rigid trucks for urban and regional goods delivery.'],
    'Specialized Transport'   => ['icon' => '', 'label' => 'Specialized Tankers', 'desc' => 'Certified petroleum and bulk liquid transport trucks with calibrated compartments.']
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. HERO SECTION -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-container">
        <div class="hero-content">
            <span class="hero-tag">Commercial Vehicle Dealership &bull; Lagos, Nigeria</span>
            <h1 class="hero-title">Heavy-Duty Commercial Trucks Built for Performance.</h1>
            <p class="hero-lead">
                Explore thoroughly inspected European and Asian commercial trucks with genuine Customs documentation, ready for nationwide delivery.
            </p>
            <div class="hero-actions">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-lg">
                    Explore Trucks &rarr;
                </a>
                <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-outline-white btn-lg">
                    Get Recommendation
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. FEATURED TRUCKS SECTION -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Current Inventory</span>
            <h2 class="section-title">Featured Commercial Vehicles</h2>
            <p class="section-subtitle">Hand-picked commercial vehicles currently available at our Ojodu Berger yard.</p>
        </div>

        <?php if (!empty($featuredTrucks)): ?>
            <div class="truck-grid">
                <?php foreach ($featuredTrucks as $truck): ?>
                    <div class="truck-card">
                        <div class="truck-card-media">
                            <?php 
                                $hasImg = !empty($truck['primary_image']) && file_exists(UPLOADS_PATH . $truck['primary_image']);
                            ?>
                            <?php if ($hasImg): ?>
                                <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $truck['primary_image']; ?>" alt="<?php echo sanitize_output($truck['title']); ?>" loading="lazy">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text-muted); font-size: 0.85rem; font-weight: 600;">
                                    MOAL TRUCK INVENTORY
                                </div>
                            <?php endif; ?>

                            <div class="truck-card-badge">
                                <span class="badge badge-dark"><?php echo sanitize_output($truck['purpose_category']); ?></span>
                            </div>
                            
                            <div class="truck-card-status">
                                <span class="badge badge-success"><?php echo sanitize_output($truck['availability_status']); ?></span>
                            </div>
                        </div>

                        <div class="truck-card-body">
                            <h3 class="truck-card-title"><?php echo sanitize_output($truck['title']); ?></h3>
                            <div class="truck-card-subtitle">
                                <?php echo sanitize_output($truck['brand'] . ' ' . $truck['model'] . ' (' . $truck['year_of_manufacture'] . ')'); ?>
                            </div>

                            <div class="truck-specs-row">
                                <div class="spec-item">
                                    <span class="spec-label">Payload Capacity</span>
                                    <span class="spec-value"><?php echo format_tonnage($truck['tonnage_capacity']); ?></span>
                                </div>
                                <div class="spec-item">
                                    <span class="spec-label">Wheel / Drive</span>
                                    <span class="spec-value"><?php echo sanitize_output($truck['wheel_configuration']); ?></span>
                                </div>
                                <div class="spec-item">
                                    <span class="spec-label">Transmission</span>
                                    <span class="spec-value"><?php echo sanitize_output($truck['transmission']); ?></span>
                                </div>
                                <div class="spec-item">
                                    <span class="spec-label">Condition</span>
                                    <span class="spec-value"><?php echo sanitize_output($truck['condition_type']); ?></span>
                                </div>
                            </div>

                            <div class="truck-card-footer">
                                <div class="truck-price">
                                    <span class="price-label">Price</span>
                                    <span class="price-amount"><?php echo format_currency($truck['price']); ?></span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-sm">
                                    View Details &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin-top: 3rem;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary btn-lg">
                    View All Trucks in Inventory &rarr;
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                <p style="color: var(--color-text-muted);">No featured trucks available at the moment. Please check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 3. FLEET CATEGORIES SECTION -->
<section class="section section-subtle">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Operational Applications</span>
            <h2 class="section-title">Fleet Categories</h2>
            <p class="section-subtitle">Commercial vehicles structured for specialized Nigerian industrial and logistics sectors.</p>
        </div>

        <div class="category-grid">
            <?php foreach ($categoryStats as $catKey => $cat): ?>
                <a href="<?php echo BASE_URL; ?>inventory.php?category=<?php echo urlencode($catKey); ?>" class="category-card">
                    <div class="category-icon"><?php echo $cat['icon']; ?></div>
                    <h3 class="category-title"><?php echo $cat['label']; ?></h3>
                    <p class="category-desc"><?php echo $cat['desc']; ?></p>
                    <span class="category-link">View Available Units &rarr;</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4. GET A RECOMMENDATION TEASER -->
<section class="section">
    <div class="container">
        <div class="recommend-teaser-card">
            <div>
                <span class="badge badge-primary" style="margin-bottom: 1rem;">Decision Advisor</span>
                <h2 style="color: #FFFFFF; margin-bottom: 1rem;">Not Sure Which Truck Fits Your Haulage Needs?</h2>
                <p style="color: #D1D1CB; font-size: 1.05rem;">
                    Answer 3 quick operational questions and our rule-based advisor will match you with the most suitable vehicles in stock.
                </p>

                <ul class="recommend-steps-list">
                    <li class="recommend-step-item">
                        <span class="recommend-step-num">1</span>
                        <span>Select your intended operational purpose (Quarry, Haulage, Delivery)</span>
                    </li>
                    <li class="recommend-step-item">
                        <span class="recommend-step-num">2</span>
                        <span>Specify your target acquisition budget ceiling</span>
                    </li>
                    <li class="recommend-step-item">
                        <span class="recommend-step-num">3</span>
                        <span>Define required payload tonnage capacity</span>
                    </li>
                </ul>

                <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-primary btn-lg">
                    Launch Recommendation Advisor &rarr;
                </a>
            </div>

            <div style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: var(--radius-md); padding: 2rem; text-align: center;">
                <div style="font-size: 3rem; margin-bottom: 1rem;"></div>
                <h3 style="color: #FFFFFF; font-size: 1.2rem; margin-bottom: 0.5rem;">Fast, Accurate Matching</h3>
                <p style="color: #A3A39E; font-size: 0.9rem; margin-bottom: 0;">
                    Instant specification comparison against live dealership inventory.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 5. SHORT ABOUT MOAL & TRUST PILLARS -->
<section class="section section-subtle">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Why Choose Moal</span>
            <h2 class="section-title">The Moal Dealership Standard</h2>
            <p class="section-subtitle">Providing commercial transport operators with reliable fleet assets since inception.</p>
        </div>

        <div class="trust-grid">
            <div class="trust-card">
                <div class="trust-icon"></div>
                <h3 class="trust-title">Genuine Customs Documentation</h3>
                <p class="trust-desc">
                    Every commercial vehicle comes with complete, authentic Nigeria Customs Service duty payment receipts, SGD, and clear title documentation.
                </p>
            </div>

            <div class="trust-card">
                <div class="trust-icon"></div>
                <h3 class="trust-title">Certified Mechanical Inspection</h3>
                <p class="trust-desc">
                    All vehicles undergo thorough engine compression, transmission, chassis alignment, and hydraulic hoist diagnostics before placement in our yard.
                </p>
            </div>

            <div class="trust-card">
                <div class="trust-icon"></div>
                <h3 class="trust-title">Nationwide Delivery Support</h3>
                <p class="trust-desc">
                    Direct transit and logistics coordination from our Ojodu Berger yard to operational sites across all 36 Nigerian states.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 6. SHORT DEALERSHIP CONTACT / YARD BANNER -->
<section class="section" style="padding-top: 0;">
    <div class="container">
        <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-lg); padding: clamp(2rem, 4vw, 3rem); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div>
                <h3 style="font-size: 1.4rem; margin-bottom: 0.5rem;">Visit Our Ojodu Berger Dealership Yard</h3>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 0;">
                    Schedule a physical inspection or discuss bulk fleet acquisition with our sales consultants.
                </p>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="tel:07069219001" class="btn btn-outline">
                     Call 07069219001
                </a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-primary">
                    Contact Sales Desk &rarr;
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
