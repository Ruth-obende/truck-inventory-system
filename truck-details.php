<?php
/**
 * =============================================================================
 * Moal General Suppliers - Truck Detailed Specification View
 * =============================================================================
 * Displays in-depth technical specifications, condition notes, and inquiry CTA.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$truckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($truckId <= 0) {
    redirect(BASE_URL . 'inventory.php');
}

$db = getDB();

// 1. Fetch Truck Details
$stmt = $db->prepare('SELECT * FROM trucks WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $truckId]);
$truck = $stmt->fetch();

if (!$truck) {
    // If truck not found in database, return 404 header and friendly message
    http_response_code(404);
    $pageTitle = 'Truck Not Found';
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container" style="padding: 4rem 20px; text-align: center;">';
    echo '<h2 style="color: var(--primary-navy);">Truck Not Found</h2>';
    echo '<p style="color: var(--text-muted); margin: 1rem 0 2rem 0;">The requested truck does not exist or has been removed from active inventory.</p>';
    echo '<a href="' . BASE_URL . 'inventory.php" class="btn btn-primary">Return to Inventory</a>';
    echo '</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $truck['title'];

// 2. Fetch Associated Images
$stmtImages = $db->prepare('SELECT * FROM truck_images WHERE truck_id = :id ORDER BY is_primary DESC, sort_order ASC');
$stmtImages->execute([':id' => $truckId]);
$images = $stmtImages->fetchAll();

// 3. Fetch Related / Similar Trucks in Same Category
$stmtRelated = $db->prepare('
    SELECT * FROM trucks 
    WHERE purpose_category = :category AND id != :id AND availability_status = "Available"
    LIMIT 3
');
$stmtRelated->execute([
    ':category' => $truck['purpose_category'],
    ':id'       => $truckId
]);
$relatedTrucks = $stmtRelated->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header / Breadcrumb -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; 
            <a href="<?php echo BASE_URL; ?>inventory.php">Inventory</a> &rsaquo; 
            <span><?php echo sanitize_output($truck['truck_code']); ?></span>
        </div>
        <h1><?php echo sanitize_output($truck['title']); ?></h1>
        <p><?php echo sanitize_output($truck['brand'] . ' ' . $truck['model'] . ' &bull; ' . $truck['year_of_manufacture'] . ' &bull; ' . $truck['purpose_category']); ?></p>
    </div>
</div>

<div class="container">
    <div class="details-layout">
        
        <!-- Main Content Column -->
        <div class="details-main">
            
            <!-- Gallery / Media Hero -->
            <div class="details-gallery">
                <?php 
                    $mainImage = null;
                    if (!empty($images)) {
                        $mainImage = $images[0]['image_path'];
                    }
                ?>
                <?php if ($mainImage && file_exists(UPLOADS_PATH . $mainImage)): ?>
                    <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $mainImage; ?>" alt="<?php echo sanitize_output($truck['title']); ?>">
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem;">
                        <svg viewBox="0 0 24 24" style="width: 70px; height: 70px; fill: #475569; margin-bottom: 10px;">
                            <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                        </svg>
                        <h3 style="color: #cbd5e1;"><?php echo sanitize_output($truck['title']); ?></h3>
                        <p style="color: #94a3b8; font-size: 0.85rem; margin-top: 4px;">Photographs will be displayed here once uploaded by Moal General Suppliers.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Overview & Description -->
            <div style="margin-bottom: 2rem;">
                <h3 class="details-section-title">Vehicle Overview</h3>
                <p style="color: var(--text-body); font-size: 1rem; line-height: 1.8;">
                    <?php echo nl2br(sanitize_output($truck['description'] ?? 'No detailed description specified.')); ?>
                </p>
            </div>

            <!-- Full Technical Specifications Breakdown -->
            <div>
                <h3 class="details-section-title">Technical Specifications</h3>
                
                <table class="specs-table">
                    <tbody>
                        <tr>
                            <th>Stock / Inventory Code</th>
                            <td><span class="badge badge-navy"><?php echo sanitize_output($truck['truck_code']); ?></span></td>
                        </tr>
                        <tr>
                            <th>Manufacturer / Brand</th>
                            <td><?php echo sanitize_output($truck['brand']); ?></td>
                        </tr>
                        <tr>
                            <th>Model Designation</th>
                            <td><?php echo sanitize_output($truck['model']); ?></td>
                        </tr>
                        <tr>
                            <th>Year of Manufacture</th>
                            <td><?php echo sanitize_output($truck['year_of_manufacture']); ?></td>
                        </tr>
                        <tr>
                            <th>Intended Purpose / Category</th>
                            <td><?php echo sanitize_output($truck['purpose_category']); ?></td>
                        </tr>
                        <tr>
                            <th>Payload / Tonnage Capacity</th>
                            <td><?php echo format_tonnage($truck['tonnage_capacity']); ?></td>
                        </tr>
                        <tr>
                            <th>Wheel Configuration</th>
                            <td><?php echo sanitize_output($truck['wheel_configuration']); ?></td>
                        </tr>
                        <tr>
                            <th>Transmission</th>
                            <td><?php echo sanitize_output($truck['transmission']); ?></td>
                        </tr>
                        <tr>
                            <th>Fuel Type</th>
                            <td><?php echo sanitize_output($truck['fuel_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Engine Power (HP)</th>
                            <td><?php echo $truck['engine_power_hp'] ? sanitize_output($truck['engine_power_hp']) . ' HP' : 'Standard Factory Spec'; ?></td>
                        </tr>
                        <tr>
                            <th>Recorded Mileage</th>
                            <td><?php echo format_mileage($truck['mileage']); ?></td>
                        </tr>
                        <tr>
                            <th>Condition Type</th>
                            <td><?php echo sanitize_output($truck['condition_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Current Availability Status</th>
                            <td>
                                <?php if ($truck['availability_status'] === 'Available'): ?>
                                    <span class="badge badge-success">Available in Stock</span>
                                <?php elseif ($truck['availability_status'] === 'Reserved'): ?>
                                    <span class="badge badge-warning">Reserved</span>
                                <?php else: ?>
                                    <span class="badge badge-danger"><?php echo sanitize_output($truck['availability_status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

        <!-- Sidebar / Inquiry CTA -->
        <aside class="details-sidebar">
            
            <div class="inquiry-cta-card">
                <span class="badge badge-orange" style="margin-bottom: 0.5rem;"><?php echo sanitize_output($truck['purpose_category']); ?></span>
                <h3>Purchase &amp; Inquiries</h3>
                
                <div class="price-display-box">
                    <div class="price-display-label">Listed Dealership Price</div>
                    <div class="price-display-val"><?php echo format_currency($truck['price']); ?></div>
                </div>

                <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 1.25rem;">
                    Interested in this commercial truck? Submit an inquiry to schedule physical inspection, verify paperwork, or request formal quotation from Moal General Suppliers.
                </p>

                <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-block" style="padding: 12px 20px; font-size: 1rem;">
                    Inquire About This Truck
                </a>
                
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-outline btn-block" style="margin-top: 10px;">
                    Back to All Inventory
                </a>
            </div>

            <!-- Moal Dealership Assurance Box -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 1.5rem; box-shadow: var(--shadow-sm);">
                <h4 style="color: var(--primary-navy); margin-bottom: 0.75rem; font-size: 1rem;">Moal Dealership Assurance</h4>
                <ul style="list-style: none; font-size: 0.85rem; color: var(--text-body); line-height: 1.8;">
                    <li>✓ Complete Mechanical &amp; Chassis Inspection</li>
                    <li>✓ Verified Customs &amp; Registration Paperwork</li>
                    <li>✓ Transparent Pricing with No Hidden Fees</li>
                    <li>✓ Nationwide Delivery Support</li>
                </ul>
            </div>

        </aside>

    </div>

    <!-- Similar Trucks Showcase -->
    <?php if (!empty($relatedTrucks)): ?>
        <section style="margin-top: 3rem; border-top: 1px solid var(--border-color); padding-top: 2.5rem; margin-bottom: 4rem;">
            <div class="section-header">
                <h2 class="section-title">Similar Trucks in <?php echo sanitize_output($truck['purpose_category']); ?></h2>
                <p class="section-subtitle">Explore other options that match this vehicle's operational classification.</p>
            </div>

            <div class="inventory-grid">
                <?php foreach ($relatedTrucks as $rTrk): ?>
                    <div class="truck-card">
                        <div class="truck-card-body">
                            <div class="truck-card-code"><?php echo sanitize_output($rTrk['truck_code']); ?></div>
                            <h3 class="truck-card-title"><?php echo sanitize_output($rTrk['title']); ?></h3>
                            <div class="truck-card-price"><?php echo format_currency($rTrk['price']); ?></div>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
                                <?php echo format_tonnage($rTrk['tonnage_capacity']) . ' &bull; ' . sanitize_output($rTrk['wheel_configuration']) . ' &bull; ' . sanitize_output($rTrk['year_of_manufacture']); ?>
                            </div>
                            <div class="truck-card-actions">
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$rTrk['id']; ?>" class="btn btn-navy btn-sm">View Specs</a>
                                <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$rTrk['id']; ?>" class="btn btn-primary btn-sm">Inquire</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
