<?php
/**
 * =============================================================================
 * Moal General Suppliers - Commercial Truck Inventory Catalogue
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_customer_login();

$pageTitle = 'Truck Inventory Catalogue';
$db = getDB();

// -----------------------------------------------------------------------------
// Capture & Sanitize Filter Parameters
// -----------------------------------------------------------------------------
$searchKeyword = sanitize_input($_GET['search'] ?? '');
$category      = sanitize_input($_GET['category'] ?? '');
$brand         = sanitize_input($_GET['brand'] ?? '');
$condition     = sanitize_input($_GET['condition'] ?? '');
$maxPrice      = !empty($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$tonnageRange  = sanitize_input($_GET['tonnage'] ?? '');

// Build Query
$sql = "
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    WHERE t.availability_status = 'Available'
";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (t.title LIKE :search OR t.brand LIKE :search OR t.model LIKE :search OR t.truck_code LIKE :search)";
    $params[':search'] = '%' . $searchKeyword . '%';
}

if (!empty($category)) {
    $sql .= " AND t.purpose_category = :category";
    $params[':category'] = $category;
}

if (!empty($brand)) {
    $sql .= " AND t.brand = :brand";
    $params[':brand'] = $brand;
}

if (!empty($condition)) {
    $sql .= " AND t.condition_type = :condition";
    $params[':condition'] = $condition;
}

if ($maxPrice > 0) {
    $sql .= " AND t.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

if (!empty($tonnageRange)) {
    if ($tonnageRange === 'under_10') {
        $sql .= " AND t.tonnage_capacity < 10.0";
    } elseif ($tonnageRange === '10_25') {
        $sql .= " AND t.tonnage_capacity >= 10.0 AND t.tonnage_capacity <= 25.0";
    } elseif ($tonnageRange === 'over_25') {
        $sql .= " AND t.tonnage_capacity > 25.0";
    }
}

$sql .= " ORDER BY t.featured DESC, t.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trucks = $stmt->fetchAll();

// Fetch distinct brands for filter dropdown
$distinctBrands = $db->query('SELECT DISTINCT brand FROM trucks WHERE brand IS NOT NULL AND brand != "" ORDER BY brand ASC')->fetchAll(PDO::FETCH_COLUMN);

// Categories
$categories = [
    'Construction & Mining'    => 'Tipper Trucks',
    'Heavy Haulage'            => 'Tractor Heads / Haulage',
    'Distribution & Logistics' => 'Cargo & Box Trucks',
    'Specialized Transport'    => 'Specialized Tankers',
    'Agriculture & Farming'    => 'Agricultural Transport'
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container">
        
        <!-- Header -->
        <div style="margin-bottom: 2rem;">
            <span class="section-tag">Dealership Inventory</span>
            <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.4rem); margin-bottom: 0.5rem;">Commercial Fleet Inventory</h1>
            <p style="color: var(--color-text-muted); font-size: 1rem;">
                Browse available heavy-duty commercial trucks with full technical specifications and direct quote options.
            </p>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar-card">
            <form method="GET" action="<?php echo BASE_URL; ?>inventory.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)) auto auto; gap: 1rem; align-items: flex-end;">
                
                <!-- Search Input -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="search" class="form-label">Search Keyword</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="e.g. Actros, HOWO, Tipper..." value="<?php echo sanitize_output($searchKeyword); ?>">
                </div>

                <!-- Category Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="category" class="form-label">Fleet Category</label>
                    <select id="category" name="category" class="form-control">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $catVal => $catLabel): ?>
                            <option value="<?php echo sanitize_output($catVal); ?>" <?php echo ($category === $catVal) ? 'selected' : ''; ?>>
                                <?php echo sanitize_output($catLabel); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Brand Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="brand" class="form-label">Make / Brand</label>
                    <select id="brand" name="brand" class="form-control">
                        <option value="">All Brands</option>
                        <?php foreach ($distinctBrands as $b): ?>
                            <option value="<?php echo sanitize_output($b); ?>" <?php echo ($brand === $b) ? 'selected' : ''; ?>>
                                <?php echo sanitize_output($b); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Tonnage Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="tonnage" class="form-label">Payload Capacity</label>
                    <select id="tonnage" name="tonnage" class="form-control">
                        <option value="">All Capacities</option>
                        <option value="under_10" <?php echo ($tonnageRange === 'under_10') ? 'selected' : ''; ?>>Under 10 Tons</option>
                        <option value="10_25" <?php echo ($tonnageRange === '10_25') ? 'selected' : ''; ?>>10 – 25 Tons</option>
                        <option value="over_25" <?php echo ($tonnageRange === 'over_25') ? 'selected' : ''; ?>>Over 25 Tons</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="padding: 11px 20px;">
                        Filter
                    </button>
                    <?php if (!empty($searchKeyword) || !empty($category) || !empty($brand) || !empty($tonnageRange)): ?>
                        <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary" style="padding: 11px 16px;">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Inventory Results Meta -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div style="font-size: 0.95rem; color: var(--color-text-muted);">
                Showing <strong><?php echo count($trucks); ?></strong> vehicle<?php echo count($trucks) === 1 ? '' : 's'; ?> available for purchase
            </div>

            <div style="display: flex; gap: 8px; align-items: center; font-size: 0.88rem; color: var(--color-text-muted);">
                <span>Need guidance?</span>
                <a href="<?php echo BASE_URL; ?>recommend.php" style="color: var(--color-primary); font-weight: 600;">Use Find My Truck Advisor &rarr;</a>
            </div>
        </div>

        <!-- Truck Cards Grid -->
        <?php if (!empty($trucks)): ?>
            <div class="truck-grid">
                <?php foreach ($trucks as $truck): ?>
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
                                <?php echo sanitize_output($truck['brand'] . ' ' . $truck['model'] . ' (' . $truck['year_of_manufacture'] . ')'); ?> &bull; <strong style="color: var(--color-dark);"><?php echo sanitize_output($truck['truck_code']); ?></strong>
                            </div>

                            <div class="truck-specs-row">
                                <div class="spec-item">
                                    <span class="spec-label">Capacity</span>
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
                                <div style="display: flex; gap: 6px;">
                                    <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-outline btn-sm" title="Request official proforma quote">
                                        Quote
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-sm">
                                        Details &rarr;
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
                <h3 style="margin-bottom: 0.5rem;">No Commercial Vehicles Found</h3>
                <p style="color: var(--color-text-muted); max-width: 480px; margin: 0 auto 1.5rem auto;">
                    We couldn't find any vehicles matching your selected criteria. Try loosening your filter criteria or submit a custom procurement request.
                </p>
                <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary">
                        Clear All Filters
                    </a>
                    <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="btn btn-primary">
                        Request Custom Sourcing &rarr;
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
