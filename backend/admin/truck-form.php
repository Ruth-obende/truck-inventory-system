<?php
/**
 * =============================================================================
 * Moal General Suppliers - Add / Edit Truck Form
 * =============================================================================
 * Full administrative fleet management supporting:
 * - Intelligent auto-generation of inventory codes
 * - Multiple photo gallery uploads + primary thumbnail selection
 * - Live photo preview before submission
 * - Dynamic Nigerian Naira price spell-out
 * - Auto-complete datalists for makes, models, and wheel configurations
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
    'year_of_manufacture' => (int)date('Y'),
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

$allImages = [];
$primaryImage = null;

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

    // Fetch all gallery images for this truck
    $stmtImgs = $db->prepare('SELECT * FROM truck_images WHERE truck_id = :id ORDER BY is_primary DESC, sort_order ASC, id ASC');
    $stmtImgs->execute([':id' => $truckId]);
    $allImages = $stmtImgs->fetchAll();
    foreach ($allImages as $img) {
        if ($img['is_primary']) {
            $primaryImage = $img;
            break;
        }
    }
} else {
    // Intelligently suggest next available truck code for new entry
    $stmtMax = $db->query("SELECT truck_code FROM trucks WHERE truck_code LIKE 'MGS-TRK-%' OR truck_code LIKE 'MOAL-%' ORDER BY id DESC LIMIT 1");
    $lastCode = $stmtMax->fetchColumn();
    if ($lastCode && preg_match('/(\d+)$/', $lastCode, $matches)) {
        $nextNumber = (int)$matches[1] + 1;
        $prefix = preg_replace('/\d+$/', '', $lastCode);
        $truck['truck_code'] = $prefix . str_pad((string)$nextNumber, strlen($matches[1]), '0', STR_PAD_LEFT);
    } else {
        $totalTrucks = (int)$db->query("SELECT COUNT(*) FROM trucks")->fetchColumn();
        $truck['truck_code'] = 'MGS-TRK-' . str_pad((string)($totalTrucks + 1), 3, '0', STR_PAD_LEFT);
    }
}

// -----------------------------------------------------------------------------
// Handle Image Quick Actions (Delete Image / Set Primary)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        set_flash_message('error', 'Security session expired. Please try again.');
        redirect(ADMIN_URL . 'truck-form.php?id=' . $truckId);
    }

    $action = $_POST['action'];
    $imgId = (int)($_POST['image_id'] ?? 0);

    if ($action === 'delete_image' && $imgId > 0 && $isEdit) {
        $stmtFind = $db->prepare('SELECT * FROM truck_images WHERE id = :id AND truck_id = :tid');
        $stmtFind->execute([':id' => $imgId, ':tid' => $truckId]);
        $targetImg = $stmtFind->fetch();

        if ($targetImg) {
            $filePath = UPLOADS_PATH . $targetImg['image_path'];
            if (file_exists($filePath) && is_file($filePath)) {
                @unlink($filePath);
            }
            $db->prepare('DELETE FROM truck_images WHERE id = :id')->execute([':id' => $imgId]);

            // If the deleted image was primary, designate another image as primary if available
            if ($targetImg['is_primary']) {
                $db->prepare('UPDATE truck_images SET is_primary = 1 WHERE truck_id = :tid ORDER BY id ASC LIMIT 1')->execute([':tid' => $truckId]);
            }
            set_flash_message('success', 'Photo was successfully removed from vehicle gallery.');
        }
        redirect(ADMIN_URL . 'truck-form.php?id=' . $truckId);
    }

    if ($action === 'set_primary_image' && $imgId > 0 && $isEdit) {
        $db->prepare('UPDATE truck_images SET is_primary = 0 WHERE truck_id = :tid')->execute([':tid' => $truckId]);
        $db->prepare('UPDATE truck_images SET is_primary = 1 WHERE id = :id AND truck_id = :tid')->execute([':id' => $imgId, ':tid' => $truckId]);
        set_flash_message('success', 'Primary display photograph updated successfully.');
        redirect(ADMIN_URL . 'truck-form.php?id=' . $truckId);
    }
}

// -----------------------------------------------------------------------------
// Helper: Process and Save Uploaded File
// -----------------------------------------------------------------------------
function process_uploaded_image(array $fileArray, string $brand, string $model): array {
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    $maxBytes = 10 * 1024 * 1024; // 10 MB

    if ($fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error code: ' . $fileArray['error']];
    }

    if ($fileArray['size'] > $maxBytes) {
        return ['success' => false, 'error' => 'Image exceeds 10MB limit (' . round($fileArray['size'] / 1048576, 1) . 'MB).'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileArray['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedMimes, true)) {
        return ['success' => false, 'error' => 'Invalid image format. Allowed formats: JPG, PNG, WebP.'];
    }

    $ext = match($mimeType) {
        'image/png' => 'png',
        'image/webp' => 'webp',
        default => 'jpg'
    };

    // Clean brand/model slug without regex bugs
    $rawSlug = strtolower(trim($brand . ' ' . $model));
    $cleanSlug = trim(preg_replace('/[^a-z0-9]+/', '-', $rawSlug), '-');
    if (empty($cleanSlug)) {
        $cleanSlug = 'truck';
    }

    $filename = 'truck_' . $cleanSlug . '_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
    $destination = UPLOADS_PATH . $filename;

    if (!is_dir(UPLOADS_PATH)) {
        @mkdir(UPLOADS_PATH, 0755, true);
    }

    if (!move_uploaded_file($fileArray['tmp_name'], $destination)) {
        return ['success' => false, 'error' => 'Failed to save uploaded photo to storage directory.'];
    }

    return ['success' => true, 'filename' => $filename];
}

// -----------------------------------------------------------------------------
// Handle Full Form Submission (POST)
// -----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $errors[] = 'Security token expired. Please reload and submit again.';
    } else {
        $truckCode     = strtoupper(trim(sanitize_input($_POST['truck_code'] ?? '')));
        $title         = trim(sanitize_input($_POST['title'] ?? ''));
        $brand         = trim(sanitize_input($_POST['brand'] ?? ''));
        $model         = trim(sanitize_input($_POST['model'] ?? ''));
        $year          = (int)($_POST['year_of_manufacture'] ?? date('Y'));
        $price         = (float)($_POST['price'] ?? 0);
        $mileage       = (int)($_POST['mileage'] ?? 0);
        $tonnage       = (float)($_POST['tonnage_capacity'] ?? 0);
        $transmission  = sanitize_input($_POST['transmission'] ?? 'Manual');
        $fuelType      = sanitize_input($_POST['fuel_type'] ?? 'Diesel');
        $engineHp      = !empty($_POST['engine_power_hp']) ? (int)$_POST['engine_power_hp'] : null;
        $wheelConfig   = trim(sanitize_input($_POST['wheel_configuration'] ?? '6x4'));
        $conditionType = sanitize_input($_POST['condition_type'] ?? 'Foreign Used');
        $purpose       = sanitize_input($_POST['purpose_category'] ?? 'Heavy Haulage');
        $status        = sanitize_input($_POST['availability_status'] ?? 'Available');
        $featured      = isset($_POST['featured']) ? 1 : 0;
        $description   = trim(sanitize_input($_POST['description'] ?? ''));

        // Basic Validations
        if (empty($truckCode)) $errors[] = 'Truck inventory code is required (e.g. MGS-TRK-007).';
        if (empty($title))     $errors[] = 'Truck headline title is required.';
        if (empty($brand))     $errors[] = 'Manufacturer brand is required (e.g. Sinotruk HOWO, Shacman).';
        if (empty($model))     $errors[] = 'Model designation is required (e.g. 371HP Tipper).';
        if ($price <= 0)       $errors[] = 'Price must be greater than zero.';
        if ($tonnage <= 0)     $errors[] = 'Payload capacity tonnage must be specified.';

        // Check duplicate code
        $stmtCode = $db->prepare('SELECT id FROM trucks WHERE truck_code = :code AND id != :id');
        $stmtCode->execute([':code' => $truckCode, ':id' => $truckId]);
        if ($stmtCode->fetch()) {
            $errors[] = 'Truck code "' . htmlspecialchars($truckCode) . '" is already registered to another vehicle.';
        }

        // Process Primary Photo Upload (if provided)
        $primaryUploadedFile = null;
        if (isset($_FILES['truck_image']) && $_FILES['truck_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $uploadResult = process_uploaded_image($_FILES['truck_image'], $brand, $model);
            if (!$uploadResult['success']) {
                $errors[] = 'Primary Photo: ' . $uploadResult['error'];
            } else {
                $primaryUploadedFile = $uploadResult['filename'];
            }
        }

        // Process Additional Gallery Photos (if provided)
        $galleryUploadedFiles = [];
        if (isset($_FILES['gallery_images']) && !empty($_FILES['gallery_images']['name'][0])) {
            $totalGallery = count($_FILES['gallery_images']['name']);
            for ($i = 0; $i < $totalGallery; $i++) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $singleFile = [
                    'name'     => $_FILES['gallery_images']['name'][$i],
                    'type'     => $_FILES['gallery_images']['type'][$i],
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error'    => $_FILES['gallery_images']['error'][$i],
                    'size'     => $_FILES['gallery_images']['size'][$i]
                ];
                $gResult = process_uploaded_image($singleFile, $brand, $model);
                if ($gResult['success']) {
                    $galleryUploadedFiles[] = $gResult['filename'];
                } else {
                    $errors[] = 'Gallery Photo (' . htmlspecialchars($singleFile['name']) . '): ' . $gResult['error'];
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

                // Update primary photo if new one uploaded
                if ($primaryUploadedFile) {
                    $stmtCheckImg = $db->prepare('SELECT id FROM truck_images WHERE truck_id = :id AND is_primary = 1 LIMIT 1');
                    $stmtCheckImg->execute([':id' => $truckId]);
                    $existingImgId = $stmtCheckImg->fetchColumn();

                    if ($existingImgId) {
                        $stmtImgUp = $db->prepare('UPDATE truck_images SET image_path = :path, caption = :cap WHERE id = :img_id');
                        $stmtImgUp->execute([':path' => $primaryUploadedFile, ':cap' => $title, ':img_id' => $existingImgId]);
                    } else {
                        $stmtImgIn = $db->prepare('INSERT INTO truck_images (truck_id, image_path, caption, is_primary, sort_order) VALUES (:tid, :path, :cap, 1, 0)');
                        $stmtImgIn->execute([':tid' => $truckId, ':path' => $primaryUploadedFile, ':cap' => $title]);
                    }
                }

                // Insert additional gallery photos
                if (!empty($galleryUploadedFiles)) {
                    $stmtGalleryIn = $db->prepare('INSERT INTO truck_images (truck_id, image_path, caption, is_primary, sort_order) VALUES (:tid, :path, :cap, 0, 10)');
                    foreach ($galleryUploadedFiles as $gFile) {
                        $stmtGalleryIn->execute([':tid' => $truckId, ':path' => $gFile, ':cap' => $title]);
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

                // Save primary photo
                if ($primaryUploadedFile && $newId > 0) {
                    $stmtImgIn = $db->prepare('INSERT INTO truck_images (truck_id, image_path, caption, is_primary, sort_order) VALUES (:tid, :path, :cap, 1, 0)');
                    $stmtImgIn->execute([':tid' => $newId, ':path' => $primaryUploadedFile, ':cap' => $title]);
                }

                // Save additional gallery photos
                if (!empty($galleryUploadedFiles) && $newId > 0) {
                    $hasPrimaryAlready = (bool)$primaryUploadedFile;
                    $stmtGalleryIn = $db->prepare('INSERT INTO truck_images (truck_id, image_path, caption, is_primary, sort_order) VALUES (:tid, :path, :cap, :is_prim, :sort)');
                    foreach ($galleryUploadedFiles as $idx => $gFile) {
                        // If no primary photo was selected, make the first gallery photo the primary
                        $isFirstAsPrimary = (!$hasPrimaryAlready && $idx === 0) ? 1 : 0;
                        $stmtGalleryIn->execute([
                            ':tid'     => $newId,
                            ':path'    => $gFile,
                            ':cap'     => $title,
                            ':is_prim' => $isFirstAsPrimary,
                            ':sort'    => $idx + 1
                        ]);
                    }
                }

                set_flash_message('success', 'New vehicle "' . $title . '" (' . $truckCode . ') added to inventory successfully.');
                redirect(ADMIN_URL . 'trucks.php');
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Datalist Autocomplete Providers -->
<datalist id="popularBrands">
    <option value="Sinotruk HOWO">
    <option value="Shacman">
    <option value="Mercedes-Benz">
    <option value="MAN">
    <option value="Scania">
    <option value="Isuzu">
    <option value="DAF">
    <option value="Mack">
    <option value="Mitsubishi Fuso">
    <option value="FAW">
    <option value="Iveco">
</datalist>

<datalist id="wheelConfigurations">
    <option value="6x4">
    <option value="8x4">
    <option value="4x2">
    <option value="6x2">
    <option value="10x4">
</datalist>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
    <div>
        <div style="font-size: 0.85rem; color: var(--admin-text-muted); margin-bottom: 4px;">
            <a href="<?php echo ADMIN_URL; ?>trucks.php" style="color: var(--admin-text-muted);">Inventory</a> &rsaquo; <span><?php echo $isEdit ? 'Edit Vehicle' : 'Add New Vehicle'; ?></span>
        </div>
        <h1 style="font-size: 1.4rem; font-weight: 800; color: var(--admin-navy);"><?php echo $pageTitle; ?></h1>
    </div>
    <div style="display: flex; gap: 8px;">
        <?php if ($isEdit): ?>
            <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo $truckId; ?>" target="_blank" class="btn btn-dark" style="font-size: 0.88rem;">
                View on Public Site &rarr;
            </a>
        <?php endif; ?>
        <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline" style="font-size: 0.88rem;">
            &larr; Return to Inventory
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" style="margin-bottom: 1.5rem;">
        <strong style="display: block; margin-bottom: 6px;">Please correct the following errors:</strong>
        <ul style="margin-left: 1.25rem;">
            <?php foreach ($errors as $err): ?>
                <li><?php echo sanitize_output($err); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo $isEdit ? ADMIN_URL . 'truck-form.php?id=' . $truckId : ADMIN_URL . 'truck-form.php'; ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

    <!-- 1. Identity & Classification -->
    <div class="form-fieldset">
        <div class="form-legend">1. Vehicle Identity &amp; Classification</div>
        
        <div class="form-grid-3" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="truck_code" class="form-label">Inventory Stock Code *</label>
                <input type="text" id="truck_code" name="truck_code" class="form-control" placeholder="e.g. MGS-TRK-007" value="<?php echo sanitize_output($_POST['truck_code'] ?? $truck['truck_code']); ?>" required style="font-family: monospace; font-weight: 700; color: var(--admin-navy);">
                <small style="color: var(--admin-text-muted); font-size: 0.78rem;">Unique stock reference code used on proforma invoices</small>
            </div>

            <div class="form-group">
                <label for="brand" class="form-label">Make / Brand *</label>
                <input type="text" id="brand" name="brand" list="popularBrands" class="form-control" placeholder="e.g. Sinotruk HOWO" value="<?php echo sanitize_output($_POST['brand'] ?? $truck['brand']); ?>" required>
            </div>

            <div class="form-group">
                <label for="model" class="form-label">Model Designation *</label>
                <input type="text" id="model" name="model" class="form-control" placeholder="e.g. 371HP Tipper" value="<?php echo sanitize_output($_POST['model'] ?? $truck['model']); ?>" required>
            </div>
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label for="title" class="form-label">Headline Title *</label>
                <input type="text" id="title" name="title" class="form-control" placeholder="e.g. Sinotruk HOWO 371HP 30-Ton Heavy Dump Truck" value="<?php echo sanitize_output($_POST['title'] ?? $truck['title']); ?>" required>
            </div>

            <div class="form-group">
                <label for="purpose_category" class="form-label">Fleet Category *</label>
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
                <input type="number" id="year_of_manufacture" name="year_of_manufacture" class="form-control" min="1995" max="<?php echo date('Y') + 1; ?>" value="<?php echo sanitize_output($_POST['year_of_manufacture'] ?? $truck['year_of_manufacture']); ?>" required>
            </div>
        </div>
    </div>

    <!-- 2. Technical Specs -->
    <div class="form-fieldset">
        <div class="form-legend">2. Technical &amp; Operational Specifications</div>

        <div class="form-grid-3" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="tonnage_capacity" class="form-label">Payload Capacity (Tons) *</label>
                <div style="display: flex; gap: 8px;">
                    <input type="number" step="0.1" id="tonnage_capacity" name="tonnage_capacity" class="form-control" placeholder="e.g. 30" value="<?php echo sanitize_output($_POST['tonnage_capacity'] ?? $truck['tonnage_capacity']); ?>" required>
                </div>
                <div style="margin-top: 6px; display: flex; gap: 4px; flex-wrap: wrap;">
                    <span style="font-size: 0.74rem; color: var(--admin-text-muted); margin-right: 4px;">Presets:</span>
                    <?php foreach ([10, 15, 20, 30, 40] as $presetTon): ?>
                        <button type="button" onclick="document.getElementById('tonnage_capacity').value='<?php echo $presetTon; ?>'" style="background: #F3F4F6; border: 1px solid var(--admin-border); border-radius: 4px; padding: 2px 7px; font-size: 0.72rem; cursor: pointer;">
                            <?php echo $presetTon; ?>t
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="wheel_configuration" class="form-label">Wheel / Drive Configuration *</label>
                <input type="text" id="wheel_configuration" name="wheel_configuration" list="wheelConfigurations" class="form-control" placeholder="e.g. 6x4, 8x4, 4x2" value="<?php echo sanitize_output($_POST['wheel_configuration'] ?? $truck['wheel_configuration']); ?>" required>
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
        </div>

        <div class="form-grid-3">
            <div class="form-group">
                <label for="engine_power_hp" class="form-label">Engine Horsepower (HP)</label>
                <input type="number" id="engine_power_hp" name="engine_power_hp" class="form-control" placeholder="e.g. 371" value="<?php echo sanitize_output($_POST['engine_power_hp'] ?? $truck['engine_power_hp']); ?>">
            </div>

            <div class="form-group">
                <label for="condition_type" class="form-label">Condition *</label>
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

        <div class="form-group">
            <label for="mileage" class="form-label">Recorded Mileage (Kilometers)</label>
            <input type="number" id="mileage" name="mileage" class="form-control" placeholder="e.g. 45000" value="<?php echo sanitize_output($_POST['mileage'] ?? $truck['mileage']); ?>" style="max-width: 320px;">
        </div>
    </div>

    <!-- 3. Pricing & Status -->
    <div class="form-fieldset">
        <div class="form-legend">3. Commercials, Pricing &amp; Visibility</div>

        <div class="form-grid-3" style="margin-top: 1rem;">
            <div class="form-group">
                <label for="price" class="form-label">Listed Price (NGN ₦) *</label>
                <input type="number" step="1000" id="price" name="price" class="form-control" placeholder="e.g. 48500000" value="<?php echo sanitize_output($_POST['price'] ?? $truck['price']); ?>" required oninput="updatePriceHelper(this.value)">
                <div id="priceHelper" style="margin-top: 6px; font-size: 0.85rem; font-weight: 700; color: var(--admin-orange);">
                    <!-- Dynamically populated via JS -->
                </div>
            </div>

            <div class="form-group">
                <label for="availability_status" class="form-label">Dealership Availability *</label>
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

    <!-- 4. Description -->
    <div class="form-fieldset">
        <div class="form-legend">4. Vehicle Description &amp; Technical Highlights</div>

        <div class="form-group" style="margin-top: 1rem;">
            <label for="description" class="form-label">Dealership Overview &amp; Specifications Summary</label>
            <textarea id="description" name="description" rows="5" class="form-control" placeholder="Detail the engine series, transmission ratio, cabin AC, tire condition, braking system, and customs clearance status..."><?php echo sanitize_output($_POST['description'] ?? $truck['description']); ?></textarea>
        </div>
    </div>

    <!-- 5. Vehicle Photography & Gallery -->
    <div class="form-fieldset">
        <div class="form-legend">5. Vehicle Photographs &amp; Gallery</div>

        <!-- Existing Gallery Images (In Edit Mode) -->
        <?php if ($isEdit && !empty($allImages)): ?>
            <div style="margin-top: 1rem; margin-bottom: 2rem;">
                <label class="form-label" style="margin-bottom: 0.75rem;">Current Gallery Photos (<?php echo count($allImages); ?>)</label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 1rem;">
                    <?php foreach ($allImages as $gImg): ?>
                        <div style="background: #FFFFFF; border: 1px solid <?php echo $gImg['is_primary'] ? 'var(--admin-orange)' : 'var(--admin-border)'; ?>; border-radius: 8px; overflow: hidden; box-shadow: var(--admin-shadow-sm); display: flex; flex-direction: column;">
                            <div style="position: relative; height: 110px; background: #EDE6DC;">
                                <img src="<?php echo BASE_URL . 'assets/images/trucks/' . sanitize_output($gImg['image_path']); ?>" alt="Photo" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php if ($gImg['is_primary']): ?>
                                    <span style="position: absolute; top: 6px; left: 6px; background: var(--admin-orange); color: #FFFFFF; font-size: 0.7rem; font-weight: 700; padding: 2px 7px; border-radius: 4px;">
                                        Primary
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="padding: 8px; display: flex; gap: 4px; justify-content: space-between; background: #FAFAFA; border-top: 1px solid var(--admin-border); margin-top: auto;">
                                <?php if (!$gImg['is_primary']): ?>
                                    <button type="submit" formaction="<?php echo ADMIN_URL . 'truck-form.php?id=' . $truckId; ?>" name="action" value="set_primary_image" onclick="document.getElementById('actionImageId').value='<?php echo $gImg['id']; ?>'" class="btn btn-outline btn-sm" style="font-size: 0.72rem; padding: 3px 6px;">
                                        Make Primary
                                    </button>
                                <?php else: ?>
                                    <span style="font-size: 0.75rem; color: var(--admin-text-muted); font-weight: 600; padding: 3px 0;">Primary</span>
                                <?php endif; ?>
                                <button type="submit" formaction="<?php echo ADMIN_URL . 'truck-form.php?id=' . $truckId; ?>" name="action" value="delete_image" onclick="document.getElementById('actionImageId').value='<?php echo $gImg['id']; ?>'; return confirm('Are you sure you want to delete this photo?')" class="btn btn-outline btn-sm" style="font-size: 0.72rem; padding: 3px 6px; color: #DC2626; border-color: #FCA5A5;">
                                    Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- Hidden field to hold image ID for single-action submit -->
            <input type="hidden" name="image_id" id="actionImageId" value="">
        <?php endif; ?>

        <!-- Primary Image Upload -->
        <div style="margin-top: 1rem; padding: 1.25rem; background: #F9FAFB; border: 1px dashed var(--admin-border); border-radius: 8px; margin-bottom: 1.25rem;">
            <label for="truck_image" class="form-label" style="font-weight: 700; color: var(--admin-navy);">
                <?php echo $isEdit ? 'Replace Primary Display Photo' : 'Upload Primary Display Photo *'; ?>
            </label>
            <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 2px; margin-bottom: 0.75rem;">
                This photo is featured on catalog listings, search cards, and as the main image. Formats: JPG, PNG, WebP (Up to 10MB).
            </p>
            <input type="file" id="truck_image" name="truck_image" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewPrimaryImage(this)">
            
            <!-- Live Preview Container -->
            <div id="primaryPreviewBox" style="display: none; margin-top: 1rem; align-items: center; gap: 1rem;">
                <img id="primaryPreviewImg" src="" alt="Primary Preview" style="width: 150px; height: 105px; object-fit: cover; border-radius: 6px; border: 1px solid var(--admin-border); box-shadow: var(--admin-shadow-sm);">
                <div>
                    <span style="font-size: 0.85rem; font-weight: 700; color: var(--admin-navy);" id="primaryPreviewName"></span><br>
                    <span style="font-size: 0.78rem; color: var(--admin-text-muted);" id="primaryPreviewSize"></span>
                </div>
            </div>
        </div>

        <!-- Additional Gallery Images Upload -->
        <div style="padding: 1.25rem; background: #F9FAFB; border: 1px dashed var(--admin-border); border-radius: 8px;">
            <label for="gallery_images" class="form-label" style="font-weight: 700; color: var(--admin-navy);">
                Add More Photos to Gallery (Optional, Multiple)
            </label>
            <p style="font-size: 0.82rem; color: var(--admin-text-muted); margin-top: 2px; margin-bottom: 0.75rem;">
                Select multiple photos at once (cabin interior, tires, engine bay, rear chassis). Formats: JPG, PNG, WebP (Up to 10MB each).
            </p>
            <input type="file" id="gallery_images" name="gallery_images[]" class="form-control" accept="image/jpeg,image/png,image/webp" multiple onchange="previewGalleryImages(this)">
            
            <!-- Live Gallery Preview Container -->
            <div id="galleryPreviewBox" style="display: none; margin-top: 1rem;">
                <div style="font-size: 0.82rem; font-weight: 700; color: var(--admin-navy); margin-bottom: 0.5rem;" id="galleryCountLabel"></div>
                <div id="galleryThumbnailsContainer" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 2rem; margin-bottom: 3rem;">
        <a href="<?php echo ADMIN_URL; ?>trucks.php" class="btn btn-outline" style="padding: 11px 24px;">Cancel</a>
        <button type="submit" class="btn btn-primary" style="padding: 11px 32px; font-size: 0.95rem; font-weight: 700;">
            <?php echo $isEdit ? 'Save Changes' : 'Upload Truck to Inventory'; ?>
        </button>
    </div>
</form>

<script>
// Live Image Preview for Primary Photo
function previewPrimaryImage(input) {
    var previewBox = document.getElementById('primaryPreviewBox');
    var previewImg = document.getElementById('primaryPreviewImg');
    var nameLabel  = document.getElementById('primaryPreviewName');
    var sizeLabel  = document.getElementById('primaryPreviewSize');

    if (input.files && input.files[0]) {
        var file = input.files[0];
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            nameLabel.textContent = file.name;
            sizeLabel.textContent = (file.size / (1024 * 1024)).toFixed(2) + ' MB';
            previewBox.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    } else {
        previewBox.style.display = 'none';
    }
}

// Live Image Preview for Multiple Gallery Photos
function previewGalleryImages(input) {
    var previewBox = document.getElementById('galleryPreviewBox');
    var countLabel = document.getElementById('galleryCountLabel');
    var thumbsCont = document.getElementById('galleryThumbnailsContainer');
    thumbsCont.innerHTML = '';

    if (input.files && input.files.length > 0) {
        countLabel.textContent = input.files.length + ' additional photo(s) selected:';
        previewBox.style.display = 'block';

        Array.from(input.files).forEach(function(file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var thumb = document.createElement('div');
                thumb.style.width = '100px';
                thumb.style.height = '75px';
                thumb.style.borderRadius = '6px';
                thumb.style.overflow = 'hidden';
                thumb.style.border = '1px solid #D1D5DB';
                thumb.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                thumb.title = file.name;

                var img = document.createElement('img');
                img.src = e.target.result;
                img.style.width = '100%';
                img.style.height = '100%';
                img.style.objectFit = 'cover';

                thumb.appendChild(img);
                thumbsCont.appendChild(thumb);
            };
            reader.readAsDataURL(file);
        });
    } else {
        previewBox.style.display = 'none';
    }
}

// Dynamic Price Spell-Out Helper
function updatePriceHelper(val) {
    var helper = document.getElementById('priceHelper');
    var num = parseFloat(val);
    if (isNaN(num) || num <= 0) {
        helper.textContent = '';
        return;
    }

    var formatted = '₦' + num.toLocaleString('en-NG');
    var textDesc = '';

    if (num >= 1000000000) {
        textDesc = (num / 1000000000).toFixed(2) + ' Billion Naira';
    } else if (num >= 1000000) {
        textDesc = (num / 1000000).toFixed(2) + ' Million Naira';
    } else if (num >= 1000) {
        textDesc = (num / 1000).toFixed(0) + ' Thousand Naira';
    }

    helper.textContent = formatted + (textDesc ? ' (' + textDesc + ')' : '');
}

// Run price helper once on page load if price is prefilled
document.addEventListener('DOMContentLoaded', function() {
    var priceInput = document.getElementById('price');
    if (priceInput && priceInput.value) {
        updatePriceHelper(priceInput.value);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>