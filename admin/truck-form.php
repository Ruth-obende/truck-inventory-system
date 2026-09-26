<?php
/**
 * =============================================================================
 * Moal General Suppliers - Add / Edit Truck Form
 * =============================================================================
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

// Load existing truck if editing
if ($isEdit) {
    $stmt = $db->prepare('SELECT * FROM trucks WHERE id = :id');
    $stmt->execute([':id' => $truckId]);
    $loaded = $stmt->fetch();
    if (!$loaded) {
        set_flash_message('error', 'Truck record not found.');
        redirect(ADMIN_URL . 'trucks.php');
    }
    $truck = array_merge($truck, $loaded);

    $stmtImg = $db->prepare('SELECT * FROM truck_images WHERE truck_id = :id AND is_primary = 1 LIMIT 1');
    $stmtImg->execute([':id' => $truckId]);
    $existingImage = $stmtImg->fetch();
}

// Handle Form Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security token expired. Please reload and submit again.';
    } else {
        $truckCode     = strtoupper(trim(sanitize_input($_POST['truck_code'] ?? '')));
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

        // Validations
        if (empty($truckCode)) $errors[] = 'Truck inventory code is required (e.g. MGS-TRK-010).';
        if (empty($title)) $errors[] = 'Truck title / headline is required.';
        if (empty($brand)) $errors[] = 'Manufacturer brand is required (e.g. Sinotruk HOWO, Shacman).';
        if (empty($model)) $errors[] = 'Model designation is required (e.g. 371HP Tipper).';
        if ($price <= 0) $errors[] = 'Price must be greater than zero.';
        if ($tonnage <= 0) $errors[] = 'Payload capacity tonnage must be specified.';

        // Check duplicate code
        $stmtCode = $db->prepare('SELECT id FROM trucks WHERE truck_code = :code AND id != :id');
        $stmtCode->execute([':code' => $truckCode, ':id' => $truckId]);
        if ($stmtCode->fetch()) {
            $errors[] = 'Truck code "' . htmlspecialchars($truckCode) . '" is already in use by another vehicle.';
        }

        // Image upload handling
        $uploadedFilename = null;
        if (isset($_FILES['truck_image']) && $_FILES['truck_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['truck_image'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Error uploading image file. Code: ' . $file['error'];
            } else {
                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedMimes, true)) {
                    $errors[] = 'Invalid image format. Only JPG, PNG, and WebP images are permitted.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = 'Image size must be under 5MB.';
                } else {
                    $ext = match($mimeType) {
                        'image/jpeg', 'image/jpg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg'
                    };
                    $cleanSlug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $brand . '-' . $model));
                    $uploadedFilename = 'truck_' . $cleanSlug . '_' . time() . '.' . $ext;
                    $targetPath = UPLOADS_PATH . $uploadedFilename;

                    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                        $errors[] = 'Failed to move uploaded photo to storage directory.';
                        $uploadedFilename = null;
                    }
                }
            }
        }

        // Save to Database
        if (empty($errors)) {
            if ($isEdit) {
                $stmtUpdate = $db->prepare('
                    UPDATE trucks SET 
                        truck_code = :truck_code,
                        title = :title,
                        brand = :brand,
                        model = :model,
                        year_of_manufacture = :year,
                        price = :price,
                        mileage = :mileage,
                        tonnage_capacity = :tonnage,
                        transmission = :transmission,
                        fuel_type = :fuel_type,
                        engine_power_hp = :engine_hp,
                        wheel_configuration = :wheel_config,
                        condition_type = :condition_type,
                        purpose_category = :purpose,
                        availability_status = :status,
                        featured = :featured,
                        description = :description,
                        updated_at = NOW()
                    WHERE id = :id
                ');
                $stmtUpdate->execute([
                    ':truck_code'     => $truckCode,
                    ':title'          => $title,
                    ':brand'          => $brand,
                    ':model'          => $model,
                    ':year'           => $year,
                    ':price'          => $price,
                    ':mileage'        => $mileage,
                    ':tonnage'        => $tonnage,
                    ':transmission'   => $transmission,
                    ':fuel_type'      => $fuelType,
                    ':engine_hp'      => $engineHp,
                    ':wheel_config'   => $wheelConfig,
                    ':condition_type' => $conditionType,
                    ':purpose'        => $purpose,
                    ':status'         => $status,
                    ':featured'       => $featured,
                    ':description'    => $description,
                    ':id'             => $truckId
                ]);

                if ($uploadedFilename) {
                    // Update or insert primary photo
                    $stmtCheckImg = $db->prepare('SELECT id FROM truck_images WHERE truck_id = :id AND is_primary = 1 LIMIT 1');
                    $stmtCheckImg->execute([':id' => $truckId]);
                    $existingImgId = $stmtCheckImg->fetchColumn();

                    if ($existingImgId) {
                        $stmtImgUp = $db->prepare('UPDATE truck_images SET image_path = :path WHERE id = :img_id');
                        $stmtImgUp->execute([':path' => $uploadedFilename, ':img_id' => $existingImgId]);
                    } else {
                        $stmtImgIn = $db->prepare('INSERT INTO truck_images (truck_id, image_path, caption, is_primary, sort_order) VALUES (:tid, :path, :cap, 1, 0)');
                        $stmtImgIn->execute([':tid' => $truckId, ':path' => $uploadedFilename, ':cap' => $title]);
                    }
                }

                set_flash_message('success', 'Truck "' . $title . '" (' . $truckCode . ') was updated successfully.');
                redirect(ADMIN_URL . 'trucks.php');
            } else {
                $stmtInsert = $db->prepare('
                    INSERT INTO trucks (
                        truck_code, title, brand, model, year_of_manufacture, price, mileage,
                        tonnage_capacity, transmission, fuel_type, engine_power_hp, wheel_configuration,
                        condition_type, purpose_category, availability_status, featured, description
                    ) VALUES (
                        :truck_code, :title, :brand, :model, :year, :price, :mileage,
                        :tonnage, :transmission, :fuel_type, :engine_hp, :wheel_config,
                        :condition_type, :purpose, :status, :featured, :description
                    )
                ');
                $stmtInsert->execute([
                    ':truck_code'     => $truckCode,
                    ':title'          => $title,
                    ':brand'          => $brand,
                    ':model'          => $model,
                    ':year'           => $year,
                    ':price'          => $price,
                    ':mileage'        => $mileage,
                    ':tonnage'        => $tonnage,
                    ':transmission'   => $transmission,
                    ':fuel_type'      => $fuelType,
                    ':engine_hp'      => $engineHp,
                    ':wheel_config'   => $wheelConfig,
                    ':condition_type' => $conditionType,
                    ':purpose'        => $purpose,
                    ':status'         => $status,
                    ':featured'       => $featured,
                    ':description'    => $description
                ]);
                $newId = (int)$db->lastInsertId();

                if ($uploadedFilename && $newId > 0) {
                    $stmtImgIn = $db->prepare('INSERT INTO truck_images (truck_id, image_path, caption, is_primary, sort_order) VALUES (:tid, :path, :cap, 1, 0)');
                    $stmtImgIn->execute([':tid' => $newId, ':path' => $uploadedFilename, ':cap' => $title]);
                }

                set_flash_message('success', 'New truck "' . $title . '" (' . $truckCode . ') added to inventory successfully.');
                redirect(ADMIN_URL . 'trucks.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>trucks.php" style="color: var(--admin-text-muted);">Inventory</a> &rsaquo; <span><?php echo $isEdit ? 'Edit Vehicle' : 'Add New Vehicle'; ?></span>
        </div>
        <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--admin-navy);"><?php echo $pageTitle; ?></h1>
    </div>
    <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline">
        &larr; Return to Inventory
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo sanitize_output($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo $isEdit ? ADMIN_URL . 'truck-form.php?id=' . $truckId : ADMIN_URL . 'truck-form.php'; ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <div class="form-fieldset">
        <div class="form-legend">1. Truck Identity &amp; Classification</div>
        
        <div class="form-grid-3" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="truck_code" class="form-label">Inventory Code *</label>
                <input type="text" id="truck_code" name="truck_code" class="form-control" placeholder="e.g. MGS-TRK-001" value="<?php echo sanitize_output($_POST['truck_code'] ?? $truck['truck_code']); ?>" required>
            </div>

            <div class="form-group">
                <label for="brand" class="form-label">Brand / Make *</label>
                <input type="text" id="brand" name="brand" class="form-control" placeholder="e.g. Sinotruk HOWO" value="<?php echo sanitize_output($_POST['brand'] ?? $truck['brand']); ?>" required>
            </div>

            <div class="form-group">
                <label for="model" class="form-label">Model Designation *</label>
                <input type="text" id="model" name="model" class="form-control" placeholder="e.g. 371HP Tipper" value="<?php echo sanitize_output($_POST['model'] ?? $truck['model']); ?>" required>
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label for="title" class="form-label">Vehicle Headline Title *</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Sinotruk HOWO 371HP 30-Ton Heavy Dump Truck" value="<?php echo sanitize_output($_POST['title'] ?? $truck['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="purpose_category" class="form-label">Commercial Category *</label>
                <select id="purpose_category" name="purpose_category" class="form-control" required>
                    <?php 
                        $cats = ['Heavy Haulage', 'Construction & Mining', 'Distribution & Logistics', 'Agriculture & Farming', 'Specialized Transport'];
                        $curCat = $_POST['purpose_category'] ?? $truck['purpose_category'];
                        foreach ($cats as $c):
                    ?>
                        <option value="<?php echo $c; ?>" <?php echo ($curCat === $c) ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="year_of_manufacture" class="form-label">Year of Manufacture *</label>
                <input type="number" id="year_of_manufacture" name="year_of_manufacture" class="form-control" min="1990" max="<?php echo date('Y') + 1; ?>" value="<?php echo sanitize_output($_POST['year_of_manufacture'] ?? $truck['year_of_manufacture']); ?>" required>
            </div>
        </div>
    </div>

    <div class="form-fieldset">
        <div class="form-legend">2. Technical &amp; Engineering Specifications</div>

        <div class="form-grid-3" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="tonnage_capacity" class="form-label">Payload Capacity (Tons) *</label>
                <input type="number" step="0.1" id="tonnage_capacity" name="tonnage_capacity" class="form-control" placeholder="e.g. 30" value="<?php echo sanitize_output($_POST['tonnage_capacity'] ?? $truck['tonnage_capacity']); ?>" required>
            </div>

            <div class="form-group">
                <label for="transmission" class="form-label">Transmission *</label>
                <select id="transmission" name="transmission" class="form-control" required>
                    <?php 
                        $trans = ['Manual', 'Automatic', 'Semi-Automatic'];
                        $curTrans = $_POST['transmission'] ?? $truck['transmission'];
                        foreach ($trans as $t):
                    ?>
                        <option value="<?php echo $t; ?>" <?php echo ($curTrans === $t) ? 'selected' : ''; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="fuel_type" class="form-label">Fuel Type *</label>
                <select id="fuel_type" name="fuel_type" class="form-control" required>
                    <?php 
                        $fuels = ['Diesel', 'Electric', 'Hybrid', 'Other'];
                        $curFuel = $_POST['fuel_type'] ?? $truck['fuel_type'];
                        foreach ($fuels as $f):
                    ?>
                        <option value="<?php echo $f; ?>" <?php echo ($curFuel === $f) ? 'selected' : ''; ?>><?php echo $f; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label for="engine_power_hp" class="form-label">Engine Power (Horsepower)</label>
                <input type="number" id="engine_power_hp" name="engine_power_hp" class="form-control" placeholder="e.g. 371" value="<?php echo sanitize_output($_POST['engine_power_hp'] ?? $truck['engine_power_hp']); ?>">
            </div>

            <div class="form-group">
                <label for="wheel_configuration" class="form-label">Wheel / Axle Configuration</label>
                <input type="text" id="wheel_configuration" name="wheel_configuration" class="form-control" placeholder="e.g. 6x4, 8x4, 4x2" value="<?php echo sanitize_output($_POST['wheel_configuration'] ?? $truck['wheel_configuration']); ?>">
            </div>

            <div class="form-group">
                <label for="condition_type" class="form-label">Vehicle Condition *</label>
                <select id="condition_type" name="condition_type" class="form-control" required>
                    <?php 
                        $conds = ['Foreign Used', 'Brand New', 'Locally Used'];
                        $curCond = $_POST['condition_type'] ?? $truck['condition_type'];
                        foreach ($conds as $cd):
                    ?>
                        <option value="<?php echo $cd; ?>" <?php echo ($curCond === $cd) ? 'selected' : ''; ?>><?php echo $cd; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="mileage" class="form-label">Mileage (Kilometers)</label>
            <input type="number" id="mileage" name="mileage" class="form-control" placeholder="e.g. 45000" value="<?php echo sanitize_output($_POST['mileage'] ?? $truck['mileage']); ?>">
        </div>
    </div>

    <div class="form-fieldset">
        <div class="form-legend">3. Commercials, Pricing &amp; Availability</div>

        <div class="form-grid-3" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="price" class="form-label">Listed Price (NGN ₦) *</label>
                <input type="number" step="1000" id="price" name="price" class="form-control" placeholder="e.g. 48500000" value="<?php echo sanitize_output($_POST['price'] ?? $truck['price']); ?>" required>
            </div>

            <div class="form-group">
                <label for="availability_status" class="form-label">Availability Status *</label>
                <select id="availability_status" name="availability_status" class="form-control" required>
                    <?php 
                        $stats = ['Available', 'Reserved', 'Sold', 'Maintenance'];
                        $curSt = $_POST['availability_status'] ?? $truck['availability_status'];
                        foreach ($stats as $s):
                    ?>
                        <option value="<?php echo $s; ?>" <?php echo ($curSt === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="display: flex; align-items: center; gap: 8px; padding-top: 1.8rem;">
                <input type="checkbox" id="featured" name="featured" value="1" <?php echo (!empty($_POST['featured']) || $truck['featured']) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                <label for="featured" style="font-weight: 700; color: var(--admin-navy); cursor: pointer;">
                     Feature on Homepage &amp; Top Recommendations
                </label>
            </div>
        </div>
    </div>

    <div class="form-fieldset">
        <div class="form-legend">4. Vehicle Description &amp; Technical Highlights</div>

        <div class="form-group" style="margin-top: 1rem;">
            <label for="description" class="form-label">Vehicle Description</label>
            <textarea id="description" name="description" rows="5" class="form-control" placeholder="Provide detailed technical highlights, condition summary, braking systems, engine displacement, and operational readiness..."><?php echo sanitize_output($_POST['description'] ?? $truck['description']); ?></textarea>
        </div>
    </div>

    <div class="form-fieldset">
        <div class="form-legend">5. Primary Vehicle Photograph</div>

        <div style="margin-top: 1rem;">
            <?php if ($existingImage && !empty($existingImage['image_path'])): ?>
                <div style="margin-bottom: 1rem; display: flex; align-items: center; gap: 1rem;">
                    <img src="<?php echo BASE_URL . 'assets/images/trucks/' . sanitize_output($existingImage['image_path']); ?>" alt="Current Primary" style="width: 140px; height: 95px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border);">
                    <div style="font-size: 0.85rem; color: var(--admin-text-muted);">
                        <strong>Current Primary Photo:</strong> <?php echo sanitize_output($existingImage['image_path']); ?><br>
                        Upload a new photo below to replace it.
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label for="truck_image" class="form-label">Upload Truck Photo (JPG, PNG, WebP &bull; Max 5MB)</label>
                <input type="file" id="truck_image" name="truck_image" class="form-control" accept="image/jpeg,image/png,image/webp">
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 2rem;">
        <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline" style="padding: 11px 24px;">Cancel</a>
        <button type="submit" class="btn btn-primary" style="padding: 11px 28px; font-size: 0.95rem;">
             <?php echo $isEdit ? 'Save Changes' : 'Add Truck to Inventory'; ?>
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>