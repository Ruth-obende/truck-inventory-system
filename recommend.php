<?php
/**
 * =============================================================================
 * Moal General Suppliers - Rule-Based Truck Recommendation Tool
 * =============================================================================
 * Evaluates buyer requirements across exactly three operational criteria:
 * 1. Intended Purpose / Category
 * 2. Budget Ceiling
 * 3. Payload Capacity (Tonnage)
 * 
 * Note: This module uses deterministic rule-based matching logic (NOT AI).
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Truck Recommendation Finder';
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
    
    // Determine maximum budget threshold based on user selection
    $budgetLimit = 0;
    if ($selectedBudget === 'under_20m') {
        $budgetLimit = 20000000;
    } elseif ($selectedBudget === '20m_35m') {
        $budgetLimit = 35000000;
    } elseif ($selectedBudget === '35m_50m') {
        $budgetLimit = 50000000;
    } elseif ($selectedBudget === 'over_50m') {
        $budgetLimit = 200000000; // Open upper bound
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
        // Pass 2: Relaxed Match (Matching Purpose & Tonnage, regardless of budget ceiling, to show closest available options)
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
            $matchType = 'none';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>Recommendation Tool</span>
        </div>
        <h1>Rule-Based Truck Recommendation Tool</h1>
        <p>Answer three questions to get tailored commercial truck recommendations matched against active dealership inventory.</p>
    </div>
</div>

<div class="container" style="max-width: 960px; margin-bottom: 4rem;">

    <!-- 3-Question Recommendation Form Wizard -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 2.5rem; box-shadow: var(--shadow-sm); margin-bottom: 3rem;">
        
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 1rem; margin-bottom: 2rem;">
            <div>
                <span class="badge badge-warning">Rule-Based Decision Logic</span>
                <h2 style="font-size: 1.4rem; color: var(--primary-navy); margin-top: 6px;">Commercial Truck Compatibility Finder</h2>
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">
                3 Simple Steps
            </div>
        </div>

        <form method="GET" action="<?php echo BASE_URL; ?>recommend.php#results">
            
            <!-- Question 1: Operational Purpose -->
            <div style="margin-bottom: 2.25rem;">
                <label style="display: block; font-size: 1.05rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.75rem;">
                    1. What is the primary operational purpose of the truck?
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                    
                    <label class="wizard-option">
                        <input type="radio" name="purpose" value="Heavy Haulage" <?php echo ($selectedPurpose === 'Heavy Haulage' || empty($selectedPurpose)) ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Heavy Haulage</strong>
                            <span>Long-distance freight, prime movers, multi-axle cargo</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="purpose" value="Construction & Mining" <?php echo ($selectedPurpose === 'Construction & Mining') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Construction &amp; Mining</strong>
                            <span>Tippers, dump trucks, sand, stone, and earthmoving</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="purpose" value="Distribution & Logistics" <?php echo ($selectedPurpose === 'Distribution & Logistics') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Distribution &amp; Logistics</strong>
                            <span>Enclosed box bodies, FMCG supply, urban &amp; interstate</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="purpose" value="Agriculture & Farming" <?php echo ($selectedPurpose === 'Agriculture & Farming') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Agriculture &amp; Farming</strong>
                            <span>Grain haulage, agro-allied produce, rugged terrain</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="purpose" value="Specialized Transport" <?php echo ($selectedPurpose === 'Specialized Transport') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Specialized Transport</strong>
                            <span>Fuel &amp; gas tankers, refrigerated cold chain carriers</span>
                        </div>
                    </label>

                </div>
            </div>

            <!-- Question 2: Budget Range -->
            <div style="margin-bottom: 2.25rem;">
                <label style="display: block; font-size: 1.05rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.75rem;">
                    2. What is your estimated investment budget?
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    
                    <label class="wizard-option">
                        <input type="radio" name="budget" value="under_20m" <?php echo ($selectedBudget === 'under_20m') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Under ₦20,000,000</strong>
                            <span>Light Commercial / Entry</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="budget" value="20m_35m" <?php echo ($selectedBudget === '20m_35m' || empty($selectedBudget)) ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>₦20M – ₦35,000,000</strong>
                            <span>Medium Distribution / Rigid</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="budget" value="35m_50m" <?php echo ($selectedBudget === '35m_50m') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>₦35M – ₦50,000,000</strong>
                            <span>Heavy Tippers &amp; Prime Movers</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="budget" value="over_50m" <?php echo ($selectedBudget === 'over_50m') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Above ₦50,000,000</strong>
                            <span>Premium &amp; Specialized Units</span>
                        </div>
                    </label>

                </div>
            </div>

            <!-- Question 3: Payload Capacity (Tonnage) -->
            <div style="margin-bottom: 2.5rem;">
                <label style="display: block; font-size: 1.05rem; font-weight: 700; color: var(--primary-navy); margin-bottom: 0.75rem;">
                    3. What payload capacity (tonnage) do your operations require?
                </label>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    
                    <label class="wizard-option">
                        <input type="radio" name="tonnage" value="under_5" <?php echo ($selectedTonnage === 'under_5') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Light Duty</strong>
                            <span>Under 5 Metric Tons</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="tonnage" value="5_15" <?php echo ($selectedTonnage === '5_15') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Medium Duty</strong>
                            <span>5 – 15 Metric Tons</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="tonnage" value="15_30" <?php echo ($selectedTonnage === '15_30' || empty($selectedTonnage)) ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Heavy Duty</strong>
                            <span>15 – 30 Metric Tons</span>
                        </div>
                    </label>

                    <label class="wizard-option">
                        <input type="radio" name="tonnage" value="over_30" <?php echo ($selectedTonnage === 'over_30') ? 'checked' : ''; ?>>
                        <div class="wizard-option-card">
                            <strong>Extra Heavy Haulage</strong>
                            <span>Over 30 Metric Tons</span>
                        </div>
                    </label>

                </div>
            </div>

            <div style="text-align: center;">
                <button type="submit" class="btn btn-primary" style="padding: 14px 36px; font-size: 1.05rem; box-shadow: var(--shadow-md);">
                    Generate Truck Recommendations &rarr;
                </button>
            </div>

        </form>
    </div>

    <!-- Recommendation Results Section -->
    <?php if ($hasSubmitted): ?>
        <div id="results" style="scroll-margin-top: 100px;">
            
            <?php if ($matchType === 'exact'): ?>
                <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="color: #166534; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 1.3rem;">✓</span> Exact Recommendations Found (<?php echo count($recommendations); ?>)
                    </h3>
                    <p style="color: #15803d; font-size: 0.95rem; margin-top: 4px;">
                        The following available trucks in Moal General Suppliers' inventory directly satisfy your operational category (<strong><?php echo sanitize_output($selectedPurpose); ?></strong>), budget, and payload criteria.
                    </p>
                </div>
            <?php elseif ($matchType === 'relaxed'): ?>
                <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg); padding: 1.5rem; margin-bottom: 2rem;">
                    <h3 style="color: #92400e; display: flex; align-items: center; gap: 8px;">
                        <span>ℹ</span> Closest Matching Inventory (<?php echo count($recommendations); ?>)
                    </h3>
                    <p style="color: #b45309; font-size: 0.95rem; margin-top: 4px;">
                        We found trucks matching your operational purpose (<strong><?php echo sanitize_output($selectedPurpose); ?></strong>) and payload requirements, with pricing close to your selected budget.
                    </p>
                </div>
            <?php else: ?>
                <div style="background: #fff; border: 2px dashed var(--accent-orange); border-radius: var(--radius-lg); padding: 3rem 2rem; text-align: center; margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-navy); margin-bottom: 0.5rem;">No Direct Match in Current Stock</h3>
                    <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 1.75rem auto; font-size: 0.95rem; line-height: 1.6;">
                        Moal General Suppliers regularly imports and procures commercial trucks on demand. You can submit a <strong>Custom Truck Request</strong> with your selected parameters, and our sales team will source the vehicle for you.
                    </p>
                    
                    <?php 
                        $inquiryParams = http_build_query([
                            'type'     => 'custom',
                            'purpose'  => $selectedPurpose,
                            'tonnage'  => $selectedTonnage,
                            'budget'   => $selectedBudget
                        ]);
                    ?>
                    <a href="<?php echo BASE_URL; ?>inquiry.php?<?php echo $inquiryParams; ?>" class="btn btn-primary" style="padding: 12px 24px;">
                        Submit Custom Truck Request for This Specification &rarr;
                    </a>
                </div>
            <?php endif; ?>

            <!-- Render Recommendation Cards -->
            <?php if (!empty($recommendations)): ?>
                <div class="inventory-grid">
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="truck-card" style="border: 2px solid var(--accent-orange);">
                            
                            <div class="truck-card-media">
                                <div class="truck-card-badges">
                                    <span class="badge badge-orange"><?php echo sanitize_output($rec['purpose_category']); ?></span>
                                    <span class="badge badge-navy"><?php echo sanitize_output($rec['year_of_manufacture']); ?></span>
                                </div>
                                <div class="truck-card-status">
                                    <span class="badge badge-success">Available</span>
                                </div>
                                
                                <?php 
                                    $hasImg = false;
                                    if (!empty($rec['primary_image']) && file_exists(UPLOADS_PATH . $rec['primary_image'])) {
                                        $hasImg = true;
                                        $imgUrl = BASE_URL . 'assets/images/trucks/' . $rec['primary_image'];
                                    }
                                ?>
                                <?php if ($hasImg): ?>
                                    <img src="<?php echo $imgUrl; ?>" alt="<?php echo sanitize_output($rec['title']); ?>" class="truck-card-img">
                                <?php else: ?>
                                    <div class="truck-card-placeholder">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                        </svg>
                                        <span style="font-size: 0.75rem; font-weight: 700; color: #cbd5e1;"><?php echo sanitize_output($rec['brand']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="truck-card-body">
                                <div class="truck-card-code"><?php echo sanitize_output($rec['truck_code']); ?></div>
                                <h3 class="truck-card-title"><?php echo sanitize_output($rec['title']); ?></h3>
                                <div class="truck-card-price"><?php echo format_currency($rec['price']); ?></div>

                                <!-- Rule Suitability Rationale -->
                                <div style="background: #f8fafc; border-left: 3px solid var(--accent-orange); padding: 8px 10px; margin-bottom: 1rem; font-size: 0.8rem; line-height: 1.4;">
                                    <strong style="color: var(--primary-navy); display: block; margin-bottom: 2px;">Why this matches:</strong>
                                    <div>✓ Category: <?php echo sanitize_output($rec['purpose_category']); ?></div>
                                    <div>✓ Tonnage: <?php echo format_tonnage($rec['tonnage_capacity']); ?></div>
                                    <div>✓ Price: <?php echo format_currency($rec['price']); ?></div>
                                </div>

                                <div class="truck-card-actions">
                                    <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$rec['id']; ?>" class="btn btn-navy btn-sm">Full Specs</a>
                                    <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$rec['id']; ?>&type=recommendation" class="btn btn-primary btn-sm">Inquire</a>
                                </div>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
