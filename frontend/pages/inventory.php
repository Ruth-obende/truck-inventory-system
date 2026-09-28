<?php
/**
 * =============================================================================
 * Moal General Suppliers - Commercial Truck Inventory Catalogue
 * =============================================================================
 * Displays active truck stock with high-resolution vehicle photography,
 * full technical specifications, and transparent Naira pricing.
 * Truck images are presented immediately at the top upon viewing.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Enforce customer sign in: clients must sign in to view inventory
require_customer_login();

$pageTitle = 'Commercial Truck Inventory';
$db = getDB();

// -----------------------------------------------------------------------------
// Capture & Sanitize Filter & Sort Parameters
// -----------------------------------------------------------------------------
$searchKeyword = sanitize_input($_GET['search'] ?? '');
$category      = sanitize_input($_GET['category'] ?? '');
$brand         = sanitize_input($_GET['brand'] ?? '');
$condition     = sanitize_input($_GET['condition'] ?? '');
$transmission  = sanitize_input($_GET['transmission'] ?? '');
$maxPrice      = !empty($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$tonnageRange  = sanitize_input($_GET['tonnage'] ?? '');
$sortBy        = sanitize_input($_GET['sort'] ?? 'newest');

// Build Query
$sql = "
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    WHERE 1=1
";
$params = [];

if (!empty($searchKeyword)) {
    $sql .= " AND (t.title LIKE :s1 OR t.brand LIKE :s2 OR t.model LIKE :s3 OR t.truck_code LIKE :s4 OR t.description LIKE :s5)";
    $searchLike = '%' . $searchKeyword . '%';
    $params[':s1'] = $searchLike;
    $params[':s2'] = $searchLike;
    $params[':s3'] = $searchLike;
    $params[':s4'] = $searchLike;
    $params[':s5'] = $searchLike;
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

if (!empty($transmission)) {
    $sql .= " AND t.transmission = :transmission";
    $params[':transmission'] = $transmission;
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

// Sorting
switch ($sortBy) {
    case 'price_asc':
        $sql .= " ORDER BY t.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY t.price DESC";
        break;
    case 'year_desc':
        $sql .= " ORDER BY t.year_of_manufacture DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY t.featured DESC, t.id DESC";
        break;
}

$trucks = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $trucks = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('[Inventory Search Query Error] ' . $e->getMessage() . ' | SQL: ' . $sql);
    $trucks = [];
}

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

// Active filter count
$activeFilterCount = 0;
if (!empty($searchKeyword)) $activeFilterCount++;
if (!empty($category)) $activeFilterCount++;
if (!empty($brand)) $activeFilterCount++;
if (!empty($condition)) $activeFilterCount++;
if (!empty($transmission)) $activeFilterCount++;
if (!empty($tonnageRange)) $activeFilterCount++;
if ($sortBy !== 'newest') $activeFilterCount++;
$showFilterInitially = ($activeFilterCount > 0);

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 1.75rem;">
    <div class="container">
        
        <!-- ===================================================================
             TOP HEADER & QUICK ACTION BAR
             =================================================================== -->
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid var(--color-border);">
            <div>
                <span class="section-tag" style="margin-bottom: 4px;">Live Yard Stock</span>
                <h1 style="font-size: clamp(1.6rem, 3vw, 2.2rem); margin-bottom: 0.25rem; color: var(--color-dark);">Commercial Fleet Inventory</h1>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 0;">
                    Showing <strong><?php echo count($trucks); ?></strong> vehicle<?php echo count($trucks) === 1 ? '' : 's'; ?> ready for physical inspection at our Lagos yard.
                </p>
            </div>
            
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="button" id="toggleFilterBtn" class="btn btn-outline btn-sm" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 600; padding: 9px 16px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                    </svg>
                    <span>Filter &amp; Search</span>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="badge badge-primary" style="font-size: 0.72rem; padding: 2px 6px;"><?php echo $activeFilterCount; ?></span>
                    <?php endif; ?>
                </button>
                <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-primary btn-sm" style="padding: 9px 16px; font-weight: 600;">
                    Find My Truck Advisor &rarr;
                </a>
            </div>
        </div>

        <!-- ===================================================================
             COLLAPSIBLE FILTER & SEARCH BAR
             (Hidden by default so images of trucks appear first; toggled on demand)
             =================================================================== -->
        <div id="filterPanel" class="filter-bar-card" style="<?php echo $showFilterInitially ? 'display: block;' : 'display: none;'; ?> margin-bottom: 2rem;">
            <form method="GET" action="<?php echo BASE_URL; ?>inventory.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)) auto auto; gap: 1rem; align-items: flex-end;">
                
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

                <!-- Condition Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="condition" class="form-label">Vehicle Condition</label>
                    <select id="condition" name="condition" class="form-control">
                        <option value="">All Conditions</option>
                        <option value="Foreign Used" <?php echo ($condition === 'Foreign Used') ? 'selected' : ''; ?>>Foreign Used (Tokunbo)</option>
                        <option value="Brand New" <?php echo ($condition === 'Brand New') ? 'selected' : ''; ?>>Brand New</option>
                    </select>
                </div>

                <!-- Tonnage Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="tonnage" class="form-label">Payload Capacity</label>
                    <select id="tonnage" name="tonnage" class="form-control">
                        <option value="">All Capacities</option>
                        <option value="under_10" <?php echo ($tonnageRange === 'under_10') ? 'selected' : ''; ?>>Under 10 Tons</option>
                        <option value="10_25" <?php echo ($tonnageRange === '10_25') ? 'selected' : ''; ?>>10 to 25 Tons</option>
                        <option value="over_25" <?php echo ($tonnageRange === 'over_25') ? 'selected' : ''; ?>>Over 25 Tons</option>
                    </select>
                </div>

                <!-- Transmission Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="transmission" class="form-label">Transmission</label>
                    <select id="transmission" name="transmission" class="form-control">
                        <option value="">All Transmissions</option>
                        <option value="Manual" <?php echo ($transmission === 'Manual') ? 'selected' : ''; ?>>Manual</option>
                        <option value="Automatic" <?php echo ($transmission === 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Semi-Automatic" <?php echo ($transmission === 'Semi-Automatic') ? 'selected' : ''; ?>>Semi-Automatic</option>
                    </select>
                </div>

                <!-- Sort Order -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="sort" class="form-label">Sort Results</label>
                    <select id="sort" name="sort" class="form-control">
                        <option value="newest" <?php echo ($sortBy === 'newest') ? 'selected' : ''; ?>>Newest Added</option>
                        <option value="price_asc" <?php echo ($sortBy === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sortBy === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="year_desc" <?php echo ($sortBy === 'year_desc') ? 'selected' : ''; ?>>Latest Production Year</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 8px;">
                    <button type="submit" class="btn btn-primary" style="padding: 11px 20px;">
                        Filter
                    </button>
                    <?php if ($activeFilterCount > 0): ?>
                        <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary" style="padding: 11px 16px;">
                            Reset
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- ===================================================================
             1. IMAGES OF TRUCKS COME FIRST (TRUCK CARDS GRID)
             =================================================================== -->
        <?php if (!empty($trucks)): ?>
            <div class="truck-grid">
                <?php foreach ($trucks as $truck): ?>
                    <div class="truck-card">
                        <!-- High Resolution Truck Image -->
                        <div class="truck-card-media" style="height: 230px;">
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

                        <!-- Truck Details & Specifications -->
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
                                    <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-outline btn-sm" title="Submit vehicle inquiry">
                                        Inquire
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
            <div class="no-results-card" style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 3.5rem 2rem; text-align: center; max-width: 820px; margin: 0 auto 3rem auto; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                <div style="width: 64px; height: 64px; background: rgba(217, 130, 91, 0.1); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 1.25rem; color: var(--color-primary);">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                </div>

                <h2 style="font-size: clamp(1.4rem, 2.5vw, 1.8rem); color: var(--color-dark); margin-bottom: 0.5rem; font-weight: 800;">
                    No Trucks Match Your Search Right Now
                </h2>

                <p style="color: var(--color-text-muted); font-size: 0.98rem; max-width: 580px; margin: 0 auto 1.5rem auto; line-height: 1.6;">
                    We couldn't find any commercial vehicles matching your exact filter combination. New commercial stock arrives regularly at our Lagos yard, and we can also source specific trucks directly on request.
                </p>

                <!-- Helpful Suggestions to Adjust Filters -->
                <div style="background: var(--color-bg-subtle); border: 1px solid var(--color-border); border-radius: var(--radius-sm); padding: 1.25rem 1.5rem; max-width: 580px; margin: 0 auto 2rem auto; text-align: left; font-size: 0.9rem;">
                    <strong style="color: var(--color-dark); display: block; margin-bottom: 6px;">💡 Suggestions to find what you need:</strong>
                    <ul style="margin: 0; padding-left: 1.25rem; color: var(--color-text); line-height: 1.6;">
                        <li>Try clearing specific filters (such as Brand or Condition) to see all available units.</li>
                        <li>Broaden your payload capacity range or remove the keyword search term.</li>
                        <li>Request a direct quote from our sales desk for custom truck sourcing.</li>
                    </ul>
                </div>

                <!-- Primary Action Buttons -->
                <div style="display: flex; justify-content: center; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem;">
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-secondary" style="font-weight: 600; padding: 11px 22px;">
                        Clear All Filters
                    </a>
                    <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="btn btn-primary" style="font-weight: 600; padding: 11px 22px;">
                        Request Custom Sourcing &rarr;
                    </a>
                </div>

                <!-- Contact a Sales Rep Directly -->
                <div style="border-top: 1px solid var(--color-border); padding-top: 1.75rem; max-width: 600px; margin: 0 auto;">
                    <span style="font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--color-text-muted); letter-spacing: 0.5px; display: block; margin-bottom: 0.75rem;">
                        Need Immediate Assistance? Speak With Our Fleet Advisors:
                    </span>
                    <div style="display: flex; justify-content: center; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <a href="https://wa.me/<?php echo CONTACT_WHATSAPP; ?>?text=<?php echo urlencode('Hello Moal Sales Desk, I am looking for a commercial truck with specs: ' . ($searchKeyword ? 'Keyword: '.$searchKeyword.', ' : '') . ($category ? 'Category: '.$category : '')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp btn-sm" style="display: inline-flex; align-items: center; gap: 6px; padding: 9px 16px; font-weight: 600;">
                            <span>💬</span> WhatsApp Sales Desk
                        </a>
                        <a href="tel:<?php echo CONTACT_PHONE_1; ?>" style="color: var(--color-dark); font-weight: 700; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <span>📞</span> <?php echo CONTACT_PHONE_1; ?>
                        </a>
                        <span style="color: var(--color-border);">|</span>
                        <a href="tel:<?php echo CONTACT_PHONE_2; ?>" style="color: var(--color-dark); font-weight: 700; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                            <span>📞</span> <?php echo CONTACT_PHONE_2; ?>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===================================================================
             2. SECONDARY TOOLS & ADVISOR (PLACED BELOW THE TRUCKS)
             =================================================================== -->
        <div class="advisor-banner" style="margin-top: 3.5rem;">
            <div class="advisor-banner-content">
                <span class="advisor-banner-tag">Fleet Recommendation</span>
                <h3 class="advisor-banner-title">Need Help Choosing the Right Commercial Truck?</h3>
                <p class="advisor-banner-text">
                    Use our 3-step recommendation advisor to match your workload, target budget, and required payload capacity with available trucks in our Lagos yard.
                </p>
            </div>
            <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-primary" style="white-space: nowrap; padding: 12px 24px; font-weight: 600;">
                Launch Recommendation Advisor &rarr;
            </a>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var toggleBtn = document.getElementById('toggleFilterBtn');
    var filterPanel = document.getElementById('filterPanel');
    if (toggleBtn && filterPanel) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (filterPanel.style.display === 'none' || filterPanel.style.display === '') {
                filterPanel.style.display = 'block';
                filterPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else {
                filterPanel.style.display = 'none';
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
