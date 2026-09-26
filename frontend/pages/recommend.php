<?php
/**
 * =============================================================================
 * Moal General Suppliers - 3-Step "Find My Truck" Recommendation Tool
 * =============================================================================
 * Evaluates buyer requirements across 3 operational parameters:
 * 1. Operational Purpose
 * 2. Budget Ceiling
 * 3. Payload Capacity (Tonnage)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Find My Truck — 3-Step Recommendation Advisor';
$db = getDB();

// -----------------------------------------------------------------------------
// 1. Process Form Submission (Rule-Based Matching Engine)
// -----------------------------------------------------------------------------
$hasSubmitted = isset($_GET['purpose']) && isset($_GET['budget']) && isset($_GET['tonnage']);

$selectedPurpose = sanitize_input($_GET['purpose'] ?? '');
$selectedBudget  = sanitize_input($_GET['budget'] ?? '');
$customBudget    = !empty($_GET['custom_budget']) ? (float)$_GET['custom_budget'] : 0;
$selectedTonnage = sanitize_input($_GET['tonnage'] ?? '');

$recommendations = [];
$matchType = 'none'; // 'exact', 'relaxed', 'none'

if ($hasSubmitted && !empty($selectedPurpose) && !empty($selectedBudget) && !empty($selectedTonnage)) {
    
    // Determine maximum budget threshold
    $budgetLimit = 0;
    if ($selectedBudget === 'under_20m') {
        $budgetLimit = 20000000;
    } elseif ($selectedBudget === '20m_35m') {
        $budgetLimit = 35000000;
    } elseif ($selectedBudget === '35m_50m') {
        $budgetLimit = 50000000;
    } elseif ($selectedBudget === 'over_50m') {
        $budgetLimit = 200000000;
    } elseif ($selectedBudget === 'custom' && $customBudget > 0) {
        $budgetLimit = $customBudget;
    }

    // Determine tonnage filter condition
    $tonnageCondition = "";
    if ($selectedTonnage === 'under_5') {
        $tonnageCondition = "AND t.tonnage_capacity < 5.0";
    } elseif ($selectedTonnage === '5_15') {
        $tonnageCondition = "AND t.tonnage_capacity >= 5.0 AND t.tonnage_capacity <= 15.0";
    } elseif ($selectedTonnage === '15_30') {
        $tonnageCondition = "AND t.tonnage_capacity > 15.0 AND t.tonnage_capacity <= 30.0";
    } elseif ($selectedTonnage === 'over_30') {
        $tonnageCondition = "AND t.tonnage_capacity > 30.0";
    }

    // Pass 1: Strict Exact Rule-Based Matching
    $sqlExact = "
        SELECT t.*, 
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM trucks t 
        WHERE t.purpose_category = :purpose 
        AND t.price <= :budget 
        {$tonnageCondition} 
        AND t.availability_status = 'Available'
        ORDER BY t.price DESC
    ";

    $stmtExact = $db->prepare($sqlExact);
    $stmtExact->execute([
        ':purpose' => $selectedPurpose,
        ':budget'  => ($budgetLimit > 0 ? $budgetLimit : 200000000)
    ]);
    $recommendations = $stmtExact->fetchAll();

    if (!empty($recommendations)) {
        $matchType = 'exact';
    } else {
        // Pass 2: Relaxed Match (Matching Purpose & Tonnage)
        $sqlRelaxed = "
            SELECT t.*, 
            (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
            FROM trucks t 
            WHERE t.purpose_category = :purpose 
            {$tonnageCondition} 
            AND t.availability_status = 'Available'
            ORDER BY t.price ASC
            LIMIT 4
        ";
        $stmtRelaxed = $db->prepare($sqlRelaxed);
        $stmtRelaxed->execute([':purpose' => $selectedPurpose]);
        $recommendations = $stmtRelaxed->fetchAll();

        if (!empty($recommendations)) {
            $matchType = 'relaxed';
        } else {
            // Pass 3: General Category Fallback
            $stmtFallback = $db->prepare('
                SELECT t.*, 
                (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
                FROM trucks t 
                WHERE t.purpose_category = :purpose AND t.availability_status = "Available"
                ORDER BY t.price ASC 
                LIMIT 3
            ');
            $stmtFallback->execute([':purpose' => $selectedPurpose]);
            $recommendations = $stmtFallback->fetchAll();
            $matchType = !empty($recommendations) ? 'category' : 'none';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container">
        
        <div class="section-header" style="max-width: 720px; margin-bottom: 2.5rem;">
            <span class="section-tag">Decision Advisor</span>
            <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.4rem); margin-bottom: 0.5rem;">Find My Truck</h1>
            <p class="section-subtitle">
                Specify your operational requirements across 3 key criteria to receive tailored commercial vehicle recommendations from our live inventory.
            </p>
        </div>

        <!-- 3-Step Selection Wizard Form -->
        <div class="form-card" style="margin-bottom: 3.5rem;">
            <form method="GET" action="<?php echo BASE_URL; ?>recommend.php">
                
                <!-- STEP 1: OPERATIONAL PURPOSE -->
                <div style="margin-bottom: 2.5rem;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem;">
                        <span class="badge badge-primary" style="font-size: 0.9rem; padding: 6px 12px;">Step 1</span>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0;">What is your primary operational application?</h3>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                        <?php 
                        $purposes = [
                            'Construction & Mining'    => ['icon' => '️', 'title' => 'Construction & Mining', 'sub' => 'Tippers, quarry & sand haulage'],
                            'Heavy Haulage'            => ['icon' => '', 'title' => 'Heavy Haulage', 'sub' => 'Interstate tractor heads & flatbeds'],
                            'Distribution & Logistics' => ['icon' => '', 'title' => 'Distribution & Logistics', 'sub' => 'City FMCG cargo & box trucks'],
                            'Agriculture & Farming'    => ['icon' => '', 'title' => 'Agriculture & Farming', 'sub' => 'Grain, produce & farm logistics'],
                            'Specialized Transport'    => ['icon' => '', 'title' => 'Specialized Transport', 'sub' => 'Bulk fuel & petroleum tankers']
                        ];
                        ?>
                        <?php foreach ($purposes as $val => $p): ?>
                            <label style="cursor: pointer;">
                                <input type="radio" name="purpose" value="<?php echo sanitize_output($val); ?>" <?php echo ($selectedPurpose === $val) ? 'checked' : ''; ?> required style="display: none;" onchange="updateRadioSelection(this)">
                                <div class="rec-option-card <?php echo ($selectedPurpose === $val) ? 'selected' : ''; ?>" style="border: 2px solid <?php echo ($selectedPurpose === $val) ? 'var(--color-primary)' : 'var(--color-border)'; ?>; background: <?php echo ($selectedPurpose === $val) ? 'var(--color-primary-light)' : 'var(--color-white)'; ?>; border-radius: var(--radius-sm); padding: 1.25rem; transition: var(--transition); height: 100%;">
                                    <div style="font-size: 1.75rem; margin-bottom: 0.5rem;"><?php echo $p['icon']; ?></div>
                                    <div style="font-weight: 700; color: var(--color-dark); font-size: 0.95rem;"><?php echo $p['title']; ?></div>
                                    <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px;"><?php echo $p['sub']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- STEP 2: BUDGET CEILING -->
                <div style="margin-bottom: 2.5rem; padding-top: 2rem; border-top: 1px solid var(--color-border);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem;">
                        <span class="badge badge-primary" style="font-size: 0.9rem; padding: 6px 12px;">Step 2</span>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0;">What is your target budget ceiling?</h3>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php 
                        $budgets = [
                            'under_20m' => ['label' => 'Under ₦20,000,000', 'desc' => 'Entry / Light Commercial'],
                            '20m_35m'   => ['label' => '₦20M – ₦35,000,000', 'desc' => 'Mid-range Commercial'],
                            '35m_50m'   => ['label' => '₦35M – ₦50,000,000', 'desc' => 'Heavy Duty 6x4 Units'],
                            'over_50m'  => ['label' => 'Above ₦50,000,000', 'desc' => 'Premium Heavy Duty / Tankers']
                        ];
                        ?>
                        <?php foreach ($budgets as $bVal => $b): ?>
                            <label style="cursor: pointer;">
                                <input type="radio" name="budget" value="<?php echo sanitize_output($bVal); ?>" <?php echo ($selectedBudget === $bVal) ? 'checked' : ''; ?> required style="display: none;" onchange="updateRadioSelection(this)">
                                <div class="rec-option-card <?php echo ($selectedBudget === $bVal) ? 'selected' : ''; ?>" style="border: 2px solid <?php echo ($selectedBudget === $bVal) ? 'var(--color-primary)' : 'var(--color-border)'; ?>; background: <?php echo ($selectedBudget === $bVal) ? 'var(--color-primary-light)' : 'var(--color-white)'; ?>; border-radius: var(--radius-sm); padding: 1.25rem; transition: var(--transition); height: 100%;">
                                    <div style="font-weight: 700; color: var(--color-dark); font-size: 0.95rem;"><?php echo $b['label']; ?></div>
                                    <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px;"><?php echo $b['desc']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- STEP 3: PAYLOAD TONNAGE -->
                <div style="margin-bottom: 2.5rem; padding-top: 2rem; border-top: 1px solid var(--color-border);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 1.25rem;">
                        <span class="badge badge-primary" style="font-size: 0.9rem; padding: 6px 12px;">Step 3</span>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0;">What payload capacity (tonnage) do you require?</h3>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                        <?php 
                        $tonnages = [
                            'under_5' => ['label' => 'Under 5 Tons', 'desc' => 'Light Urban Logistics (Canter / 4x2)'],
                            '5_15'    => ['label' => '5 – 15 Tons', 'desc' => 'Medium Distribution / Rigid Trucks'],
                            '15_30'   => ['label' => '15 – 30 Tons', 'desc' => 'Heavy Tippers & Standard Haulage'],
                            'over_30' => ['label' => 'Over 30 Tons', 'desc' => 'Heavy Haulage / 40T Quarry Tippers']
                        ];
                        ?>
                        <?php foreach ($tonnages as $tVal => $t): ?>
                            <label style="cursor: pointer;">
                                <input type="radio" name="tonnage" value="<?php echo sanitize_output($tVal); ?>" <?php echo ($selectedTonnage === $tVal) ? 'checked' : ''; ?> required style="display: none;" onchange="updateRadioSelection(this)">
                                <div class="rec-option-card <?php echo ($selectedTonnage === $tVal) ? 'selected' : ''; ?>" style="border: 2px solid <?php echo ($selectedTonnage === $tVal) ? 'var(--color-primary)' : 'var(--color-border)'; ?>; background: <?php echo ($selectedTonnage === $tVal) ? 'var(--color-primary-light)' : 'var(--color-white)'; ?>; border-radius: var(--radius-sm); padding: 1.25rem; transition: var(--transition); height: 100%;">
                                    <div style="font-weight: 700; color: var(--color-dark); font-size: 0.95rem;"><?php echo $t['label']; ?></div>
                                    <div style="font-size: 0.78rem; color: var(--color-text-muted); margin-top: 4px;"><?php echo $t['desc']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 1rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border); flex-wrap: wrap;">
                    <?php if ($hasSubmitted): ?>
                        <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-secondary">
                            Reset Advisor
                        </a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-lg">
                        Find Matching Trucks &rarr;
                    </button>
                </div>

            </form>
        </div>

        <script>
        function updateRadioSelection(input) {
            var groupName = input.name;
            var radios = document.querySelectorAll('input[name="' + groupName + '"]');
            radios.forEach(function(r) {
                var card = r.nextElementSibling;
                if (r.checked) {
                    card.style.borderColor = 'var(--color-primary)';
                    card.style.background = 'var(--color-primary-light)';
                } else {
                    card.style.borderColor = 'var(--color-border)';
                    card.style.background = 'var(--color-white)';
                }
            });
        }
        </script>

        <!-- RECOMMENDATION RESULTS SECTION -->
        <?php if ($hasSubmitted): ?>
            <div id="results" style="scroll-margin-top: 100px;">
                
                <div style="margin-bottom: 2rem; border-bottom: 1px solid var(--color-border); padding-bottom: 1rem;">
                    <?php if ($matchType === 'exact'): ?>
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: #E8F5E9; color: #2E7D32; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem;">
                            <span></span> Exact Specification Match Found (<?php echo count($recommendations); ?> units)
                        </div>
                        <h2 style="font-size: 1.6rem; color: var(--color-dark);">Recommended Vehicles for Your Criteria</h2>
                    <?php elseif ($matchType === 'relaxed'): ?>
                        <div style="display: inline-flex; align-items: center; gap: 8px; background: #FEF3C7; color: #92400E; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; margin-bottom: 0.75rem;">
                            <span></span> Closest Specifications in Inventory (<?php echo count($recommendations); ?> units)
                        </div>
                        <h2 style="font-size: 1.6rem; color: var(--color-dark);">Closest Matching Options in Stock</h2>
                    <?php else: ?>
                        <h2 style="font-size: 1.6rem; color: var(--color-dark);">Alternative Recommendations</h2>
                    <?php endif; ?>
                </div>

                <?php if (!empty($recommendations)): ?>
                    <div class="truck-grid">
                        <?php foreach ($recommendations as $truck): ?>
                            <div class="truck-card">
                                <div class="truck-card-media">
                                    <?php 
                                        $hasImg = !empty($truck['primary_image']) && file_exists(UPLOADS_PATH . $truck['primary_image']);
                                    ?>
                                    <?php if ($hasImg): ?>
                                        <img src="<?php echo BASE_URL . 'assets/images/trucks/' . $truck['primary_image']; ?>" alt="<?php echo sanitize_output($truck['title']); ?>">
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
                                            <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-outline btn-sm">
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
                    <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 3rem 2rem; text-align: center;">
                        <h3 style="margin-bottom: 0.5rem;">No Direct Vehicle In Stock</h3>
                        <p style="color: var(--color-text-muted); max-width: 500px; margin: 0 auto 1.5rem auto;">
                            We do not have a unit that meets your exact combination of purpose, budget, and tonnage right now. Our procurement desk can source it directly for you.
                        </p>
                        <a href="<?php echo BASE_URL; ?>inquiry.php?type=recommendation&purpose=<?php echo urlencode($selectedPurpose); ?>&tonnage=<?php echo urlencode($selectedTonnage); ?>" class="btn btn-primary">
                            Submit Custom Sourcing Request &rarr;
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
