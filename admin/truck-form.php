<?php
/**
 * =============================================================================
 * Moal General Suppliers - Add / Edit Truck Inventory Form
 * =============================================================================
 * Handles adding new vehicles and editing existing specifications and images.
 */

require_once __DIR__ . '/auth_check.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$db = getDB();
$truckId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = ($truckId > 0);
$pageTitle = $isEdit ? 'Edit Truck Details' : 'Add New Truck';

$errors = [];
$truck = [
    'truck_code'          => '',
    'title'               => '',
    'brand'               => '',
    'model'               => '',
    'year_of_manufacture' => date('Y'),
    'price'               => '',
    'mileage'             => '0',
    'tonnage_capacity'    => '',
    'transmission'        => 'Manual',
    'fuel_type'           => 'Diesel',
    'engine_power_hp'     => '',
    'wheel_configuration' => '6x4',
    'condition_type'      => 'Foreign Used',
    'purpose_category'    => 'Heavy Haulage',
    'availability_status' => 'Available',
    'featured'            => 0,
    'description'         => ''
];

$existingImage = null;

// If editing, load existing data
if ($isEdit) {
    $stmt = $db->prepare('SELECT * FROM trucks WHERE id = :id');
    $stmt->execute([':id' => $truckId]);
    $loaded = $stmt->fetch();
    if (!$loaded) {
        set_flash_message('error', 'Truck record not found.');
        redirect(ADMIN_URL . 'trucks.php');
    }
    $truck = array_merge($truck, $loaded);

    // Fetch existing primary photo
    $stmtImg = $db->prepare('SELECT * FROM truck_images WHERE truck_id = :id AND is_primary = 1 LIMIT 1');
    $stmtImg->execute([':id' => $truckId]);
    $existingImage = $stmtImg->fetch();
}

// Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security token expired. Please reload and submit again.';
    }

    $truckCode     = sanitize_input($_POST['truck_code'] ?? '');
    $title         = sanitize_input($_POST['title'] ?? '');
    $brand         = sanitize_input($_POST['brand'] ?? '');
    $model         = sanitize_input($_POST['model'] ?? '');
    $year          = (int)($_POST['year_of_manufacture'] ?? date('Y'));
    $price         = (float)($_POST['price'] ?? 0);
    $mileage       = (int)($_POST['mileage'] ?? 0);
    $tonnage       = (float)($_POST['tonnage_capacity'] ?? 0);
    $transmission  = sanitize_input($_POST['transmission'] ?? 'Manual');
    $fuelType      = sanitize_input($_POST['fuel_type'] ?? 'Diesel');
    $engineHp      = !empty($_POST['engine_power_hp']) ? (int)$_POST['engine_power_hp'] : null;
    $wheelConfig   = sanitize_input($_POST['wheel_configuration'] ?? '6x4');
    $conditionType = sanitize_input($_POST['condition_type'] ?? 'Foreign Used');
    $purpose       = sanitize_input($_POST['purpose_category'] ?? 'Heavy Haulage');
    $status        = sanitize_input($_POST['availability_status'] ?? 'Available');
    $featured      = isset($_POST['featured']) ? 1 : 0;
    $description   = sanitize_input($_POST['description'] ?? '');

    // Validation
    if (empty($truckCode)) $errors[] = 'Truck inventory code is required.';
    if (empty($title)) $errors[] = 'Truck title/headline is required.';
    if (empty($brand)) $errors[] = 'Manufacturer brand is required.';
    if (empty($model)) $errors[] = 'Model designation is required.';
    if ($price <= 0) $errors[] = 'Price must be greater than 0.';
    if ($tonnage <= 0) $errors[] = 'Payload tonnage capacity must be specified.';

    // Check duplicate code
    $stmtCode = $db->prepare('SELECT id FROM trucks WHERE truck_code = :code AND id != :id');
    $stmtCode->execute([':code' => $truckCode, ':id' => $truckId]);
    if ($stmtCode->fetch()) {
        $errors[] = 'Truck code "' . htmlspecialchars($truckCode) . '" is already in use by another vehicle.';
    }

    // Process Image Upload
    $uploadedFilename = null;
    if (isset($_FILES['truck_image']) && $_FILES['truck_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['truck_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed with error code ' . $file['error'];
        } else {
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileMime = mime_content_type($file['tmp_name']);
            $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($fileMime, $allowedMimes) || !in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'])) {
                $errors[] = 'Only JPG, PNG, and WEBP image files are allowed.';
            } elseif ($file['size'] > (5 * 1024 * 1024)) { // 5MB limit
                $errors[] = 'Uploaded image cannot exceed 5MB in size.';
            } else {
                $uploadedFilename = 'truck_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $targetPath = UPLOADS_PATH . $uploadedFilename;
                if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $errors[] = 'Failed to save uploaded image to assets directory.';
                    $uploadedFilename = null;
                }
            }
        }
    }

    // Save Record if no errors
    if (empty($errors)) {
        if ($isEdit) {
            $sqlUpdate = '
                UPDATE trucks SET 
                    truck_code = :code, title = :title, brand = :brand, model = :model,
                    year_of_manufacture = :year, price = :price, mileage = :mileage,
                    tonnage_capacity = :tonnage, transmission = :trans, fuel_type = :fuel,
                    engine_power_hp = :hp, wheel_configuration = :wheel, condition_type = :cond,
                    purpose_category = :purpose, availability_status = :status, featured = :featured,
                    description = :desc
                WHERE id = :id
            ';
            $stmtSave = $db->prepare($sqlUpdate);
            $stmtSave->execute([
                ':code'     => $truckCode,
                ':title'    => $title,
                ':brand'    => $brand,
                ':model'    => $model,
                ':year'     => $year,
                ':price'    => $price,
                ':mileage'  => $mileage,
                ':tonnage'  => $tonnage,
                ':trans'    => $transmission,
                ':fuel'     => $fuelType,
                ':hp'       => $engineHp,
                ':wheel'    => $wheelConfig,
                ':cond'     => $conditionType,
                ':purpose'  => $purpose,
                ':status'   => $status,
                ':featured' => $featured,
                ':desc'     => $description,
                ':id'       => $truckId
            ]);
            $savedId = $truckId;
            set_flash_message('success', 'Truck specifications updated successfully.');
        } else {
            $sqlInsert = '
                INSERT INTO trucks (
                    truck_code, title, brand, model, year_of_manufacture, price, 
                    mileage, tonnage_capacity, transmission, fuel_type, engine_power_hp, 
                    wheel_configuration, condition_type, purpose_category, availability_status, 
                    featured, description
                ) VALUES (
                    :code, :title, :brand, :model, :year, :price, 
                    :mileage, :tonnage, :trans, :fuel, :hp, 
                    :wheel, :cond, :purpose, :status, 
                    :featured, :desc
                )
            ';
            $stmtSave = $db->prepare($sqlInsert);
            $stmtSave->execute([
                ':code'     => $truckCode,
                ':title'    => $title,
                ':brand'    => $brand,
                ':model'    => $model,
                ':year'     => $year,
                ':price'    => $price,
                ':mileage'  => $mileage,
                ':tonnage'  => $tonnage,
                ':trans'    => $transmission,
                ':fuel'     => $fuelType,
                ':hp'       => $engineHp,
                ':wheel'    => $wheelConfig,
                ':cond'     => $conditionType,
                ':purpose'  => $purpose,
                ':status'   => $status,
                ':featured' => $featured,
                ':desc'     => $description
            ]);
            $savedId = (int)$db->lastInsertId();
            set_flash_message('success', 'New commercial truck added to active inventory.');
        }

        // Link Image Record
        if ($uploadedFilename) {
            // Remove previous primary status if exists
            $db->prepare('UPDATE truck_images SET is_primary = 0 WHERE truck_id = :id')->execute([':id' => $savedId]);
            
            // Insert new image
            $stmtImgInsert = $db->prepare('INSERT INTO truck_images (truck_id, image_path, is_primary) VALUES (:tid, :path, 1)');
            $stmtImgInsert->execute([':tid' => $savedId, ':path' => $uploadedFilename]);
        }

        redirect(ADMIN_URL . 'trucks.php');
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h2 style="font-size: 1.4rem; color: var(--admin-navy);"><?php echo $isEdit ? 'Edit Truck Specifications' : 'Add New Commercial Truck'; ?></h2>
        <p style="color: var(--admin-text-muted); font-size: 0.9rem;">Fill in vehicle technical specs, category classification, pricing, and photography.</p>
    </div>
    <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline">
        &larr; Back to Trucks
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
        <strong style="display: block; margin-bottom: 4px;">Please correct the errors below:</strong>
        <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo sanitize_output($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="admin-card">
    <form method="POST" action="<?php echo ADMIN_URL; ?>truck-form.php<?php echo $isEdit ? '?id=' . $truckId : ''; ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <!-- Section 1: Identification & Pricing -->
        <h3 style="font-size: 1.1rem; color: var(--admin-navy); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--admin-orange);">
            1. Vehicle Identification &amp; Commercial Pricing
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 2fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label class="filter-label" for="truck_code">Inventory Code *</label>
                <input type="text" name="truck_code" id="truck_code" class="form-control" required placeholder="e.g. MOAL-TRK-007" value="<?php echo sanitize_output($_POST['truck_code'] ?? $truck['truck_code']); ?>">
            </div>

            <div>
                <label class="filter-label" for="title">Display Title / Headline *</label>
                <input type="text" name="title" id="title" class="form-control" required placeholder="e.g. Mercedes-Benz Actros 3340 6x4 Tipper" value="<?php echo sanitize_output($_POST['title'] ?? $truck['title']); ?>">
            </div>

            <div>
                <label class="filter-label" for="price">Listed Price (₦) *</label>
                <input type="number" step="1000" name="price" id="price" class="form-control" required placeholder="e.g. 48500000" value="<?php echo sanitize_output($_POST['price'] ?? $truck['price']); ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.75rem;">
            <div>
                <label class="filter-label" for="brand">Brand / Make *</label>
                <input type="text" name="brand" id="brand" class="form-control" required placeholder="e.g. Mercedes-Benz, HOWO" value="<?php echo sanitize_output($_POST['brand'] ?? $truck['brand']); ?>">
            </div>

            <div>
                <label class="filter-label" for="model">Model *</label>
                <input type="text" name="model" id="model" class="form-control" required placeholder="e.g. Actros 3340" value="<?php echo sanitize_output($_POST['model'] ?? $truck['model']); ?>">
            </div>

            <div>
                <label class="filter-label" for="year_of_manufacture">Manufacture Year *</label>
                <input type="number" name="year_of_manufacture" id="year_of_manufacture" class="form-control" required value="<?php echo sanitize_output($_POST['year_of_manufacture'] ?? $truck['year_of_manufacture']); ?>">
            </div>

            <div>
                <label class="filter-label" for="condition_type">Condition</label>
                <select name="condition_type" id="condition_type" class="form-control">
                    <option value="Foreign Used" <?php echo ($truck['condition_type'] === 'Foreign Used') ? 'selected' : ''; ?>>Foreign Used</option>
                    <option value="Brand New" <?php echo ($truck['condition_type'] === 'Brand New') ? 'selected' : ''; ?>>Brand New</option>
                    <option value="Locally Used" <?php echo ($truck['condition_type'] === 'Locally Used') ? 'selected' : ''; ?>>Locally Used</option>
                </select>
            </div>
        </div>

        <!-- Section 2: Technical Specifications & Capacity -->
        <h3 style="font-size: 1.1rem; color: var(--admin-navy); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--admin-orange);">
            2. Technical Specifications &amp; Operational Capacity
        </h3>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
            <div>
                <label class="filter-label" for="purpose_category">Purpose Category (Rule-Based Matching) *</label>
                <select name="purpose_category" id="purpose_category" class="form-control">
                    <option value="Heavy Haulage" <?php echo ($truck['purpose_category'] === 'Heavy Haulage') ? 'selected' : ''; ?>>Heavy Haulage</option>
                    <option value="Construction & Mining" <?php echo ($truck['purpose_category'] === 'Construction & Mining') ? 'selected' : ''; ?>>Construction &amp; Mining</option>
                    <option value="Distribution & Logistics" <?php echo ($truck['purpose_category'] === 'Distribution & Logistics') ? 'selected' : ''; ?>>Distribution &amp; Logistics</option>
                    <option value="Agriculture & Farming" <?php echo ($truck['purpose_category'] === 'Agriculture & Farming') ? 'selected' : ''; ?>>Agriculture &amp; Farming</option>
                    <option value="Specialized Transport" <?php echo ($truck['purpose_category'] === 'Specialized Transport') ? 'selected' : ''; ?>>Specialized Transport</option>
                </select>
            </div>

            <div>
                <label class="filter-label" for="tonnage_capacity">Payload Tonnage (Tons) *</label>
                <input type="number" step="0.1" name="tonnage_capacity" id="tonnage_capacity" class="form-control" required placeholder="e.g. 30.0" value="<?php echo sanitize_output($_POST['tonnage_capacity'] ?? $truck['tonnage_capacity']); ?>">
            </div>

            <div>
                <label class="filter-label" for="wheel_configuration">Wheel Drive</label>
                <input type="text" name="wheel_configuration" id="wheel_configuration" class="form-control" placeholder="e.g. 6x4, 4x2" value="<?php echo sanitize_output($_POST['wheel_configuration'] ?? $truck['wheel_configuration']); ?>">
            </div>

            <div>
                <label class="filter-label" for="mileage">Mileage (km)</label>
                <input type="number" name="mileage" id="mileage" class="form-control" value="<?php echo sanitize_output($_POST['mileage'] ?? $truck['mileage']); ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.75rem;">
            <div>
                <label class="filter-label" for="transmission">Transmission</label>
                <select name="transmission" id="transmission" class="form-control">
                    <option value="Manual" <?php echo ($truck['transmission'] === 'Manual') ? 'selected' : ''; ?>>Manual</option>
                    <option value="Automatic" <?php echo ($truck['transmission'] === 'Automatic') ? 'selected' : ''; ?>>Automatic</option>
                    <option value="Semi-Automatic" <?php echo ($truck['transmission'] === 'Semi-Automatic') ? 'selected' : ''; ?>>Semi-Automatic</option>
                </select>
            </div>

            <div>
                <label class="filter-label" for="fuel_type">Fuel Type</label>
                <select name="fuel_type" id="fuel_type" class="form-control">
                    <option value="Diesel" <?php echo ($truck['fuel_type'] === 'Diesel') ? 'selected' : ''; ?>>Diesel</option>
                    <option value="Electric" <?php echo ($truck['fuel_type'] === 'Electric') ? 'selected' : ''; ?>>Electric</option>
                    <option value="Hybrid" <?php echo ($truck['fuel_type'] === 'Hybrid') ? 'selected' : ''; ?>>Hybrid</option>
                    <option value="Other" <?php echo ($truck['fuel_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div>
                <label class="filter-label" for="engine_power_hp">Engine Power (HP)</label>
                <input type="number" name="engine_power_hp" id="engine_power_hp" class="form-control" placeholder="e.g. 400" value="<?php echo sanitize_output($_POST['engine_power_hp'] ?? $truck['engine_power_hp']); ?>">
            </div>
        </div>

        <!-- Section 3: Status, Photography & Description -->
        <h3 style="font-size: 1.1rem; color: var(--admin-navy); margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid var(--admin-orange);">
            3. Inventory Status, Photography &amp; Condition Overview
        </h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <label class="filter-label" for="availability_status">Availability Status</label>
                <select name="availability_status" id="availability_status" class="form-control">
                    <option value="Available" <?php echo ($truck['availability_status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                    <option value="Reserved" <?php echo ($truck['availability_status'] === 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
                    <option value="Sold" <?php echo ($truck['availability_status'] === 'Sold') ? 'selected' : ''; ?>>Sold</option>
                    <option value="Maintenance" <?php echo ($truck['availability_status'] === 'Maintenance') ? 'selected' : ''; ?>>Under Maintenance / Inspection</option>
                </select>

                <div style="margin-top: 1rem;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="featured" value="1" <?php echo !empty($truck['featured']) ? 'checked' : ''; ?>>
                        <span style="font-weight: 600; color: var(--admin-navy); font-size: 0.9rem;">Feature this truck on public homepage showcase</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="filter-label" for="truck_image">Upload Primary Truck Photograph</label>
                <input type="file" name="truck_image" id="truck_image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <small style="color: var(--admin-text-muted); display: block; margin-top: 4px;">Max size 5MB. Formats: JPG, PNG, WEBP.</small>
                
                <?php if ($existingImage && file_exists(UPLOADS_PATH . $existingImage['image_path'])): ?>
                    <div style="margin-top: 8px; display: flex; align-items: center; gap: 10px;">
                        <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $existingImage['image_path']; ?>" style="height: 50px; border-radius: 4px; border: 1px solid var(--admin-border);">
                        <span style="font-size: 0.8rem; color: var(--admin-text-muted);">Current Photo on File</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-bottom: 2rem;">
            <label class="filter-label" for="description">Detailed Description &amp; Condition Overview</label>
            <textarea name="description" id="description" rows="4" class="form-control" placeholder="Provide complete inspection details, bodywork, hydraulic status, and service history..."><?php echo sanitize_output($_POST['description'] ?? $truck['description']); ?></textarea>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 1rem;">
            <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; font-size: 1rem;">
                <?php echo $isEdit ? 'Save Changes' : 'Create Truck Record'; ?> &rarr;
            </button>
        </div>

    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
