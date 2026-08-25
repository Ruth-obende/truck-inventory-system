<?php
/**
 * =============================================================================
 * Moal General Suppliers - Truck Inventory Catalogue & Search
 * =============================================================================
 * Allows prospective buyers to search, filter, and inspect commercial trucks.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Commercial Truck Inventory';
$db = getDB();

// -----------------------------------------------------------------------------
// 1. Capture and Sanitize Filter Inputs
// -----------------------------------------------------------------------------
$searchKey    = sanitize_input($_GET['search'] ?? '');
$category     = sanitize_input($_GET['category'] ?? '');
$brand        = sanitize_input($_GET['brand'] ?? '');
$condition    = sanitize_input($_GET['condition'] ?? '');
$transmission = sanitize_input($_GET['transmission'] ?? '');
$tonnageRange = sanitize_input($_GET['tonnage_range'] ?? '');
$minPrice     = !empty($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$maxPrice     = !empty($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sortBy       = sanitize_input($_GET['sort'] ?? 'newest');

// -----------------------------------------------------------------------------
// 2. Fetch Dynamic Filter Option Lists from Database
// -----------------------------------------------------------------------------
$brandsList = $db->query('SELECT DISTINCT brand FROM trucks ORDER BY brand ASC')->fetchAll(PDO::FETCH_COLUMN);
$purposesList = [
    'Heavy Haulage',
    'Construction & Mining',
    'Distribution & Logistics',
    'Agriculture & Farming',
    'Specialized Transport'
];

// -----------------------------------------------------------------------------
// 3. Construct Filtered SQL Query via Prepared Statement
// -----------------------------------------------------------------------------
$sql = "SELECT t.*, 
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM trucks t 
        WHERE 1=1";
$params = [];

// Keyword Search
if (!empty($searchKey)) {
    $sql .= " AND (t.title LIKE :search OR t.brand LIKE :search OR t.model LIKE :search OR t.truck_code LIKE :search OR t.description LIKE :search)";
    $params[':search'] = '%' . $searchKey . '%';
}

// Purpose Category
if (!empty($category)) {
    $sql .= " AND t.purpose_category = :category";
    $params[':category'] = $category;
}

// Brand / Make
if (!empty($brand)) {
    $sql .= " AND t.brand = :brand";
    $params[':brand'] = $brand;
}

// Condition
if (!empty($condition)) {
    $sql .= " AND t.condition_type = :condition";
    $params[':condition'] = $condition;
}

// Transmission
if (!empty($transmission)) {
    $sql .= " AND t.transmission = :transmission";
    $params[':transmission'] = $transmission;
}

// Min Price
if ($minPrice !== null && $minPrice > 0) {
    $sql .= " AND t.price >= :min_price";
    $params[':min_price'] = $minPrice;
}

// Max Price
if ($maxPrice !== null && $maxPrice > 0) {
    $sql .= " AND t.price <= :max_price";
    $params[':max_price'] = $maxPrice;
}

// Tonnage Capacity Range
if (!empty($tonnageRange)) {
    switch ($tonnageRange) {
        case 'under_5':
            $sql .= " AND t.tonnage_capacity < 5.0";
            break;
        case '5_15':
            $sql .= " AND t.tonnage_capacity >= 5.0 AND t.tonnage_capacity <= 15.0";
            break;
        case '15_30':
            $sql .= " AND t.tonnage_capacity > 15.0 AND t.tonnage_capacity <= 30.0";
            break;
        case 'over_30':
            $sql .= " AND t.tonnage_capacity > 30.0";
            break;
    }
}

// Sorting logic
switch ($sortBy) {
    case 'price_asc':
        $sql .= " ORDER BY t.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY t.price DESC";
        break;
    case 'tonnage_desc':
        $sql .= " ORDER BY t.tonnage_capacity DESC";
        break;
    case 'mileage_asc':
        $sql .= " ORDER BY t.mileage ASC";
        break;
    case 'year_desc':
        $sql .= " ORDER BY t.year_of_manufacture DESC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY t.id DESC";
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$trucks = $stmt->fetchAll();
$totalFound = count($trucks);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>Truck Inventory</span>
        </div>
        <h1>Commercial Truck Inventory</h1>
        <p>Explore high-performance haulage prime movers, heavy tippers, rigid distribution trucks, and specialized carriers.</p>
    </div>
</div>

<div class="container">
    <div class="inventory-layout">
        
        <!-- Filter Sidebar -->
        <aside class="filter-sidebar">
            <div class="filter-header">
                <div class="filter-title">Filter Catalogue</div>
                <a href="<?php echo BASE_URL; ?>inventory.php" style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Reset All</a>
            </div>

            <form method="GET" action="<?php echo BASE_URL; ?>inventory.php" id="filterForm">
                
                <!-- Keyword Search -->
                <div class="filter-group">
                    <label class="filter-label" for="search">Keyword / Model</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="e.g. Actros, HOWO, 6x4..." value="<?php echo sanitize_output($searchKey); ?>">
                </div>

                <!-- Purpose / Industry Category -->
                <div class="filter-group">
                    <label class="filter-label" for="category">Purpose Category</label>
                    <select name="category" id="category" class="form-control">
                        <option value="">-- All Categories --</option>
                        <?php foreach ($purposesList as $p): ?>
                            <option value="<?php echo sanitize_output($p); ?>" <?php echo ($category === $p) ? 'selected' : ''; ?>>
                                <?php echo sanitize_output($p); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Brand / Make -->
                <div class="filter-group">
                    <label class="filter-label" for="brand">Brand / Manufacturer</label>
                    <select name="brand" id="brand" class="form-control">
                        <option value="">-- All Brands --</option>
                        <?php foreach ($brandsList as $b): ?>
                            <option value="<?php echo sanitize_output($b); ?>" <?php echo ($brand === $b) ? 'selected' : ''; ?>>
                                <?php echo sanitize_output($b); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Load Capacity (Tonnage) -->
                <div class="filter-group">
                    <label class="filter-label" for="tonnage_range">Payload Capacity</label>
                    <select name="tonnage_range" id="tonnage_range" class="form-control">
                        <option value="">-- Any Tonnage --</option>
                        <option value="under_5" <?php echo ($tonnageRange === 'under_5') ? 'selected' : ''; ?>>Under 5 Tons (Light)</option>
                        <option value="5_15" <?php echo ($tonnageRange === '5_15') ? 'selected' : ''; ?>>5 – 15 Tons (Medium)</option>
                        <option value="15_30" <?php echo ($tonnageRange === '15_30') ? 'selected' : ''; ?>>15 – 30 Tons (Heavy)</option>
                        <option value="over_30" <?php echo ($tonnageRange === 'over_30') ? 'selected' : ''; ?>>Over 30 Tons (Extra Heavy)</option>
                    </select>
                </div>

                <!-- Condition -->
                <div class="filter-group">
                    <label class="filter-label" for="condition">Condition</label>
                    <select name="condition" id="condition" class="form-control">
                        <option value="">-- Any Condition --</option>
                        <option value="Brand New" <?php echo ($condition === 'Brand New') ? 'selected' : ''; ?>>Brand New</option>
                        <option value="Foreign Used" <?php echo ($condition === 'Foreign Used') ? 'selected' : ''; ?>>Foreign Used</option>
                        <option value="Locally Used" <?php echo ($condition === 'Locally Used') ? 'selected' : ''; ?>>Locally Used</option>
                    </select>
                </div>

                <!-- Price Range Inputs -->
                <div class="filter-group">
                    <label class="filter-label">Budget Range (₦)</label>
                    <div class="range-inputs">
                        <input type="number" name="min_price" class="form-control" placeholder="Min ₦" value="<?php echo $minPrice ? sanitize_output($minPrice) : ''; ?>">
                        <input type="number" name="max_price" class="form-control" placeholder="Max ₦" value="<?php echo $maxPrice ? sanitize_output($maxPrice) : ''; ?>">
                    </div>
                </div>

                <!-- Transmission -->
                <div class="filter-group">
                    <label class="filter-label" for="transmission">Transmission</label>
                    <select name="transmission" id="transmission" class="form-control">
                        <option value="">-- Any Transmission --</option>
                        <option value="Manual" <?php echo ($transmission === 'Manual') ? 'selected' : ''; ?>>Manual</option>
                        <option value="Automatic" <?php echo ($transmission === 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                        <option value="Semi-Automatic" <?php echo ($transmission === 'Semi-Automatic') ? 'selected' : ''; ?>>Semi-Automatic</option>
                    </select>
                </div>

                <input type="hidden" name="sort" value="<?php echo sanitize_output($sortBy); ?>">

                <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
            </form>
        </aside>

        <!-- Main Results Area -->
        <section class="results-area">
            
            <div class="results-header">
                <div class="results-count">
                    Showing <strong><?php echo $totalFound; ?></strong> truck<?php echo ($totalFound === 1) ? '' : 's'; ?> matching your criteria
                </div>

                <div class="results-sort">
                    <label for="sortSelect" style="color: var(--text-muted); font-weight: 600;">Sort By:</label>
                    <select id="sortSelect" class="form-control" style="width: auto; padding: 6px 10px;" onchange="document.querySelector('#filterForm input[name=sort]').value = this.value; document.getElementById('filterForm').submit();">
                        <option value="newest" <?php echo ($sortBy === 'newest') ? 'selected' : ''; ?>>Latest Listed</option>
                        <option value="price_asc" <?php echo ($sortBy === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
                        <option value="price_desc" <?php echo ($sortBy === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
                        <option value="tonnage_desc" <?php echo ($sortBy === 'tonnage_desc') ? 'selected' : ''; ?>>Tonnage: High to Low</option>
                        <option value="year_desc" <?php echo ($sortBy === 'year_desc') ? 'selected' : ''; ?>>Year: Newest First</option>
                        <option value="mileage_asc" <?php echo ($sortBy === 'mileage_asc') ? 'selected' : ''; ?>>Mileage: Lowest First</option>
                    </select>
                </div>
            </div>

            <?php if (!empty($trucks)): ?>
                <div class="inventory-grid">
                    <?php foreach ($trucks as $trk): ?>
                        <div class="truck-card">
                            <div class="truck-card-media">
                                <div class="truck-card-badges">
                                    <span class="badge badge-orange"><?php echo sanitize_output($trk['purpose_category']); ?></span>
                                    <span class="badge badge-navy"><?php echo sanitize_output($trk['year_of_manufacture']); ?></span>
                                </div>

                                <div class="truck-card-status">
                                    <?php if ($trk['availability_status'] === 'Available'): ?>
                                        <span class="badge badge-success">Available</span>
                                    <?php elseif ($trk['availability_status'] === 'Reserved'): ?>
                                        <span class="badge badge-warning">Reserved</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo sanitize_output($trk['availability_status']); ?></span>
                                    <?php endif; ?>
                                </div>

                                <?php 
                                    $hasImage = false;
                                    if (!empty($trk['primary_image']) && file_exists(UPLOADS_PATH . $trk['primary_image'])) {
                                        $hasImage = true;
                                        $imgSrc = BASE_URL . 'assets/images/trucks/' . $trk['primary_image'];
                                    }
                                ?>

                                <?php if ($hasImage): ?>
                                    <img src="<?php echo $imgSrc; ?>" alt="<?php echo sanitize_output($trk['title']); ?>" class="truck-card-img">
                                <?php else: ?>
                                    <div class="truck-card-placeholder">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                        </svg>
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #cbd5e1;"><?php echo sanitize_output($trk['brand']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="truck-card-body">
                                <div class="truck-card-code"><?php echo sanitize_output($trk['truck_code']); ?></div>
                                <h3 class="truck-card-title"><?php echo sanitize_output($trk['title']); ?></h3>
                                <div class="truck-card-price"><?php echo format_currency($trk['price']); ?></div>

                                <div class="truck-specs-mini">
                                    <div class="spec-mini-item">
                                        <span class="spec-mini-label">Payload</span>
                                        <span class="spec-mini-val"><?php echo format_tonnage($trk['tonnage_capacity']); ?></span>
                                    </div>
                                    <div class="spec-mini-item">
                                        <span class="spec-mini-label">Wheel Drive</span>
                                        <span class="spec-mini-val"><?php echo sanitize_output($trk['wheel_configuration']); ?></span>
                                    </div>
                                    <div class="spec-mini-item">
                                        <span class="spec-mini-label">Transmission</span>
                                        <span class="spec-mini-val"><?php echo sanitize_output($trk['transmission']); ?></span>
                                    </div>
                                    <div class="spec-mini-item">
                                        <span class="spec-mini-label">Mileage</span>
                                        <span class="spec-mini-val"><?php echo format_mileage($trk['mileage']); ?></span>
                                    </div>
                                </div>

                                <div class="truck-card-actions">
                                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$trk['id']; ?>" class="btn btn-navy btn-sm">Full Specs</a>
                                    <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$trk['id']; ?>" class="btn btn-primary btn-sm">Inquire</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="background: #fff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center;">
                    <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">No matching trucks found</h3>
                    <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 1.5rem auto;">
                        We couldn't find any trucks matching your exact filter parameters. Try adjusting your filters or submit a custom truck request.
                    </p>
                    <div style="display: flex; justify-content: center; gap: 1rem;">
                        <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-outline">Clear All Filters</a>
                        <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="btn btn-primary">Submit Custom Truck Request</a>
                    </div>
                </div>
            <?php endif; ?>

        </section>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
