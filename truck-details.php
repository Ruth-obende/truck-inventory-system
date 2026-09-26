<?php
/**
 * =============================================================================
 * Moal General Suppliers - Commercial Truck Product Detail & Specification Sheet
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

require_customer_login();

$truckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($truckId <= 0) {
    redirect(BASE_URL . 'inventory.php');
}

$db = getDB();

// -----------------------------------------------------------------------------
// 1. Fetch Vehicle Record
// -----------------------------------------------------------------------------
$stmt = $db->prepare('SELECT * FROM trucks WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $truckId]);
$truck = $stmt->fetch();

if (!$truck) {
    set_flash_message('error', 'The requested commercial vehicle could not be found in our inventory.');
    redirect(BASE_URL . 'inventory.php');
}

$pageTitle = $truck['title'];

// -----------------------------------------------------------------------------
// 2. Fetch Vehicle Gallery Photos
// -----------------------------------------------------------------------------
$stmtImages = $db->prepare('SELECT * FROM truck_images WHERE truck_id = :id ORDER BY is_primary DESC, id ASC');
$stmtImages->execute([':id' => $truckId]);
$images = $stmtImages->fetchAll();

// -----------------------------------------------------------------------------
// 3. Fetch Related Commercial Trucks (Same Purpose Category)
// -----------------------------------------------------------------------------
$stmtRelated = $db->prepare('
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    WHERE t.purpose_category = :category AND t.id != :id AND t.availability_status = "Available"
    ORDER BY t.id DESC 
    LIMIT 3
');
$stmtRelated->execute([
    ':category' => $truck['purpose_category'],
    ':id'       => $truckId
]);
$relatedTrucks = $stmtRelated->fetchAll();

$waPhone = '2347069219001';
$waMessage = urlencode("Hello Moal General Suppliers, I am interested in inquiring about {$truck['title']} (Stock #{$truck['truck_code']}) listed at " . format_currency($truck['price']));

require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumb -->
<div style="background-color: var(--color-bg-subtle); border-bottom: 1px solid var(--color-border); padding: 0.85rem 0;">
    <div class="container" style="font-size: 0.85rem; color: var(--color-text-muted);">
        <a href="<?php echo BASE_URL; ?>" style="color: var(--color-text-muted);">Home</a>
        <span style="margin: 0 6px;">/</span>
        <a href="<?php echo BASE_URL; ?>inventory.php" style="color: var(--color-text-muted);">Trucks</a>
        <span style="margin: 0 6px;">/</span>
        <span style="color: var(--color-dark); font-weight: 600;"><?php echo sanitize_output($truck['title']); ?></span>
    </div>
</div>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container">
        
        <!-- Main Product Section: Gallery + Action Sidebar -->
        <div style="display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 3rem; margin-bottom: 3.5rem;" class="truck-detail-main">
            
            <!-- Left: Photo Gallery -->
            <div>
                <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm); margin-bottom: 1rem;">
                    <?php 
                        $primaryImg = !empty($images) ? $images[0]['image_path'] : null;
                        $hasPrimary = $primaryImg && file_exists(UPLOADS_PATH . $primaryImg);
                    ?>
                    <div style="height: 420px; background: #EDE6DC; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                        <?php if ($hasPrimary): ?>
                            <img id="mainVehicleImage" src="<?php echo BASE_URL . 'assets/images/trucks/' . $primaryImg; ?>" alt="<?php echo sanitize_output($truck['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        <?php else: ?>
                            <div style="font-weight: 700; color: var(--color-text-muted);">MOAL COMMERCIAL TRUCKS</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Thumbnail strip -->
                <?php if (count($images) > 1): ?>
                    <div style="display: flex; gap: 10px; overflow-x: auto; padding-bottom: 6px;">
                        <?php foreach ($images as $idx => $img): ?>
                            <?php if (file_exists(UPLOADS_PATH . $img['image_path'])): ?>
                                <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $img['image_path']; ?>" 
                                     alt="Thumb <?php echo $idx + 1; ?>" 
                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: var(--radius-sm); border: 2px solid <?php echo $idx === 0 ? 'var(--color-primary)' : 'var(--color-border)'; ?>; cursor: pointer;"
                                     onclick="document.getElementById('mainVehicleImage').src=this.src; document.querySelectorAll('.thumb-img').forEach(el=>el.style.borderColor='var(--color-border)'); this.style.borderColor='var(--color-primary)';"
                                     class="thumb-img">
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Vehicle Headline, Price, Key Actions -->
            <div style="display: flex; flex-direction: column;">
                
                <div style="display: flex; gap: 8px; margin-bottom: 0.75rem; flex-wrap: wrap;">
                    <span class="badge badge-dark"><?php echo sanitize_output($truck['purpose_category']); ?></span>
                    <span class="badge badge-primary"><?php echo sanitize_output($truck['condition_type']); ?></span>
                    <span class="badge badge-success"><?php echo sanitize_output($truck['availability_status']); ?></span>
                </div>

                <h1 style="font-size: clamp(1.8rem, 3vw, 2.25rem); line-height: 1.2; margin-bottom: 0.5rem; color: var(--color-dark);">
                    <?php echo sanitize_output($truck['title']); ?>
                </h1>

                <div style="font-size: 0.95rem; color: var(--color-text-muted); margin-bottom: 1.5rem;">
                    Stock Unit: <strong style="color: var(--color-dark);"><?php echo sanitize_output($truck['truck_code']); ?></strong> &bull; 
                    Manufacture Year: <strong style="color: var(--color-dark);"><?php echo (int)$truck['year_of_manufacture']; ?></strong>
                </div>

                <!-- Price Card -->
                <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.5rem;">
                    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 700; letter-spacing: 0.5px; display: block; margin-bottom: 2px;">
                        Dealership Listed Price
                    </span>
                    <div style="font-size: 2rem; font-weight: 800; color: var(--color-dark); line-height: 1.1;">
                        <?php echo format_currency($truck['price']); ?>
                    </div>
                    <span style="font-size: 0.8rem; color: var(--color-text-muted); display: block; margin-top: 4px;">
                        Includes genuine Nigeria Customs duty clearance &amp; Single Goods Declaration (SGD).
                    </span>
                </div>

                <!-- Primary CTAs -->
                <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem;">
                    <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-lg" style="width: 100%;">
                        Request an Official Quote &rarr;
                    </a>
                    
                    <a href="https://wa.me/<?php echo $waPhone; ?>?text=<?php echo $waMessage; ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp btn-lg" style="width: 100%;">
                        <span></span> Chat with Sales Desk on WhatsApp
                    </a>
                </div>

                <!-- Dealership Assurance Checklist -->
                <div style="background: var(--color-bg-subtle); border-radius: var(--radius-sm); padding: 1.25rem; font-size: 0.88rem; color: var(--color-text);">
                    <div style="font-weight: 700; margin-bottom: 6px; color: var(--color-dark);">Moal Yard Assurance:</div>
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <div> 120-Point Mechanical &amp; Hydraulic Diagnostic Passed</div>
                        <div> Available for physical yard inspection at Ojodu Berger</div>
                        <div> Nationwide transit &amp; delivery assistance available</div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Engineering Specifications Datasheet Matrix -->
        <div class="specs-matrix-card">
            <div class="specs-matrix-header">
                Engineering &amp; Operational Specifications Matrix
            </div>
            <table class="specs-table">
                <tbody>
                    <tr>
                        <th>Truck Inventory Code</th>
                        <td><strong style="color: var(--color-primary);"><?php echo sanitize_output($truck['truck_code']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Manufacturer / Make</th>
                        <td><?php echo sanitize_output($truck['brand']); ?></td>
                    </tr>
                    <tr>
                        <th>Model Designation</th>
                        <td><?php echo sanitize_output($truck['model']); ?></td>
                    </tr>
                    <tr>
                        <th>Year of Manufacture</th>
                        <td><?php echo (int)$truck['year_of_manufacture']; ?></td>
                    </tr>
                    <tr>
                        <th>Payload Capacity (Tonnage)</th>
                        <td><strong><?php echo format_tonnage($truck['tonnage_capacity']); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Wheel &amp; Axle Drive</th>
                        <td><?php echo sanitize_output($truck['wheel_configuration']); ?></td>
                    </tr>
                    <tr>
                        <th>Transmission Type</th>
                        <td><?php echo sanitize_output($truck['transmission']); ?></td>
                    </tr>
                    <tr>
                        <th>Fuel Type</th>
                        <td><?php echo sanitize_output($truck['fuel_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Engine Output Power</th>
                        <td><?php echo !empty($truck['engine_power_hp']) ? (int)$truck['engine_power_hp'] . ' HP' : 'Standard Commercial Spec'; ?></td>
                    </tr>
                    <tr>
                        <th>Odometer Mileage</th>
                        <td><?php echo format_mileage($truck['mileage']); ?></td>
                    </tr>
                    <tr>
                        <th>Vehicle Condition</th>
                        <td><?php echo sanitize_output($truck['condition_type']); ?></td>
                    </tr>
                    <tr>
                        <th>Operational Application</th>
                        <td><?php echo sanitize_output($truck['purpose_category']); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Vehicle Description Overview -->
        <?php if (!empty($truck['description'])): ?>
            <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 2rem; margin-bottom: 3.5rem;">
                <h3 style="font-size: 1.25rem; margin-bottom: 1rem; color: var(--color-dark);">Vehicle Overview &amp; Operational Notes</h3>
                <div style="font-size: 0.98rem; line-height: 1.7; color: var(--color-text);">
                    <?php echo nl2br(sanitize_output($truck['description'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Related Trucks Showcase -->
        <?php if (!empty($relatedTrucks)): ?>
            <div style="margin-top: 4rem;">
                <div style="margin-bottom: 1.75rem;">
                    <span class="section-tag">Similar Inventory</span>
                    <h3 style="font-size: 1.5rem; color: var(--color-dark);">Other <?php echo sanitize_output($truck['purpose_category']); ?> Trucks</h3>
                </div>

                <div class="truck-grid">
                    <?php foreach ($relatedTrucks as $rel): ?>
                        <div class="truck-card">
                            <div class="truck-card-media">
                                <?php 
                                    $hasRelImg = !empty($rel['primary_image']) && file_exists(UPLOADS_PATH . $rel['primary_image']);
                                ?>
                                <?php if ($hasRelImg): ?>
                                    <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $rel['primary_image']; ?>" alt="<?php echo sanitize_output($rel['title']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--color-text-muted); font-size: 0.85rem; font-weight: 600;">
                                        MOAL INVENTORY
                                    </div>
                                <?php endif; ?>

                                <div class="truck-card-badge">
                                    <span class="badge badge-dark"><?php echo sanitize_output($rel['purpose_category']); ?></span>
                                </div>
                            </div>

                            <div class="truck-card-body">
                                <h4 class="truck-card-title"><?php echo sanitize_output($rel['title']); ?></h4>
                                <div class="truck-card-subtitle">
                                    <?php echo sanitize_output($rel['brand'] . ' ' . $rel['model']); ?> &bull; <?php echo format_tonnage($rel['tonnage_capacity']); ?>
                                </div>

                                <div class="truck-card-footer">
                                    <div class="truck-price">
                                        <span class="price-amount"><?php echo format_currency($rel['price']); ?></span>
                                    </div>
                                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$rel['id']; ?>" class="btn btn-primary btn-sm">
                                        View Details &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<style>
@media (max-width: 850px) {
    .truck-detail-main {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
