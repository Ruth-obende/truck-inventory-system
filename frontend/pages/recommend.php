<?php
/**
 * =============================================================================
 * Moal General Suppliers - Find My Truck Recommendation Module
 * =============================================================================
 * Rule-based recommendation feature implementing:
 * - Section 3.4.2.4: Find My Truck Recommendation Module
 * - Section 3.5.4.6: RecommendationEngine Class
 * - Section 3.7.5:   Truck Recommendation Specification
 *
 * Evaluates buyer requirements across 3 operational parameters:
 * 1. Operational Purpose (50% weight)
 * 2. Budget Limit (30% weight)
 * 3. Minimum Payload Requirement (20% weight)
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/recommendation_engine.php';

$pageTitle = 'Find My Truck — 3-Step Recommendation Advisor';
$db = getDB();
$engine = new RecommendationEngine($db);

// -----------------------------------------------------------------------------
// 1. Process Form Parameters
// -----------------------------------------------------------------------------
$hasSubmitted = isset($_GET['purpose']) && (isset($_GET['budget']) || isset($_GET['custom_budget'])) && (isset($_GET['tonnage']) || isset($_GET['custom_tonnage']));

$selectedPurpose = trim(sanitize_input($_GET['purpose'] ?? ''));
$budgetChoice    = trim(sanitize_input($_GET['budget'] ?? ''));
$customBudget    = !empty($_GET['custom_budget']) ? (float)$_GET['custom_budget'] : 0.0;
$tonnageChoice   = trim(sanitize_input($_GET['tonnage'] ?? ''));
$customTonnage   = !empty($_GET['custom_tonnage']) ? (float)$_GET['custom_tonnage'] : 0.0;

// Resolve effective numeric budget
$effectiveBudget = 0.0;
if ($customBudget > 0) {
    $effectiveBudget = $customBudget;
} elseif ($budgetChoice === '20m') {
    $effectiveBudget = 20000000.0;
} elseif ($budgetChoice === '35m') {
    $effectiveBudget = 35000000.0;
} elseif ($budgetChoice === '50m') {
    $effectiveBudget = 50000000.0;
} elseif ($budgetChoice === '75m') {
    $effectiveBudget = 75000000.0;
} elseif ($budgetChoice === '100m') {
    $effectiveBudget = 100000000.0;
}

// Resolve effective numeric minimum payload (metric tons)
$effectiveTonnage = 0.0;
if ($customTonnage > 0) {
    $effectiveTonnage = $customTonnage;
} elseif ($tonnageChoice === '5') {
    $effectiveTonnage = 5.0;
} elseif ($tonnageChoice === '10') {
    $effectiveTonnage = 10.0;
} elseif ($tonnageChoice === '20') {
    $effectiveTonnage = 20.0;
} elseif ($tonnageChoice === '30') {
    $effectiveTonnage = 30.0;
} elseif ($tonnageChoice === '40') {
    $effectiveTonnage = 40.0;
}

$results = null;
if ($hasSubmitted && !empty($selectedPurpose)) {
    $results = $engine->findMatchingTrucks($selectedPurpose, $effectiveBudget, $effectiveTonnage);
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container">
        
        <div class="section-header" style="max-width: 760px; margin-bottom: 2.5rem;">
            <span class="section-tag">Rule-Based Decision Advisor</span>
            <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.5rem); margin-bottom: 0.5rem;">Find My Truck</h1>
            <p class="section-subtitle">
                Specify your operational application, maximum budget, and minimum payload requirements. 
                Our deterministic recommendation engine evaluates our live inventory using weighted multi-criteria scoring 
                (50% Purpose, 30% Budget, 20% Payload).
            </p>
        </div>

        <!-- 3-Step Selection Wizard Form -->
        <div class="form-card" style="margin-bottom: 3.5rem; background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: clamp(1.5rem, 3vw, 2.5rem); box-shadow: var(--shadow-sm);">
            <form method="GET" action="<?php echo BASE_URL; ?>recommend.php#results">
                
                <!-- STEP 1: OPERATIONAL PURPOSE (50% Weight) -->
                <div style="margin-bottom: 2.5rem;">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.5rem;">
                        <span class="badge badge-primary" style="font-size: 0.85rem; padding: 6px 12px;">Step 1 &bull; 50% Weight</span>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0; color: var(--color-dark);">What is your primary operational application?</h3>
                    </div>
                    <p style="font-size: 0.88rem; color: var(--color-text-muted); margin-bottom: 1.25rem;">
                        Select the commercial sector or duty cycle that best reflects how this vehicle will be utilized.
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem;">
                        <?php 
                        $purposes = [
                            'Construction & Mining'    => ['title' => 'Construction & Mining', 'desc' => 'Tippers, quarry dumpers, aggregate & sand haulage'],
                            'Heavy Haulage'            => ['title' => 'Heavy Haulage', 'desc' => 'Interstate 6x4 tractor heads, lowbeds & flatbeds'],
                            'Distribution & Logistics' => ['title' => 'Distribution & Logistics', 'desc' => 'City FMCG cargo, delivery vans & box body trucks'],
                            'Agriculture & Farming'    => ['title' => 'Agriculture & Farming', 'desc' => 'Grain haulage, timber carriers & agro logistics'],
                            'Specialized Transport'    => ['title' => 'Specialized Transport', 'desc' => 'Bulk fuel tankers, chemicals & refrigerated trucks']
                        ];
                        ?>
                        <?php foreach ($purposes as $val => $p): 
                            $isSelected = ($selectedPurpose === $val);
                        ?>
                            <label style="cursor: pointer; margin: 0;">
                                <input type="radio" name="purpose" value="<?php echo sanitize_output($val); ?>" <?php echo $isSelected ? 'checked' : ''; ?> required style="display: none;" onchange="updateCardSelection(this, 'purpose')">
                                <div class="wizard-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" style="border: 2px solid <?php echo $isSelected ? 'var(--color-primary)' : 'var(--color-border)'; ?>; background: <?php echo $isSelected ? 'var(--color-primary-light)' : '#FFFFFF'; ?>; border-radius: var(--radius-sm); padding: 1.25rem; transition: var(--transition); height: 100%;">
                                    <div style="font-weight: 700; color: var(--color-dark); font-size: 0.98rem; margin-bottom: 4px;"><?php echo $p['title']; ?></div>
                                    <div style="font-size: 0.82rem; color: var(--color-text-muted); line-height: 1.4;"><?php echo $p['desc']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- STEP 2: BUDGET LIMIT (30% Weight) -->
                <div style="margin-bottom: 2.5rem; padding-top: 2rem; border-top: 1px solid var(--color-border);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.5rem;">
                        <span class="badge badge-primary" style="font-size: 0.85rem; padding: 6px 12px;">Step 2 &bull; 30% Weight</span>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0; color: var(--color-dark);">What is your maximum target budget?</h3>
                    </div>
                    <p style="font-size: 0.88rem; color: var(--color-text-muted); margin-bottom: 1.25rem;">
                        Select a target ceiling or specify an exact Naira amount. Proximity scoring calculates suitability for units around this threshold.
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <?php 
                        $budgets = [
                            '20m'  => ['label' => 'Up to ₦20,000,000', 'desc' => 'Light & distribution units'],
                            '35m'  => ['label' => 'Up to ₦35,000,000', 'desc' => 'Mid-range commercial trucks'],
                            '50m'  => ['label' => 'Up to ₦50,000,000', 'desc' => 'Heavy 6x4 tippers & heads'],
                            '75m'  => ['label' => 'Up to ₦75,000,000', 'desc' => 'Premium heavy multi-axle units'],
                            '100m' => ['label' => 'Up to ₦100,000,000+', 'desc' => 'Brand-new prime movers & tankers']
                        ];
                        ?>
                        <?php foreach ($budgets as $bKey => $b): 
                            $isSelected = ($budgetChoice === $bKey && $customBudget <= 0);
                        ?>
                            <label style="cursor: pointer; margin: 0;">
                                <input type="radio" name="budget" value="<?php echo sanitize_output($bKey); ?>" <?php echo $isSelected ? 'checked' : ''; ?> style="display: none;" onchange="updateCardSelection(this, 'budget'); clearCustomBudget();">
                                <div class="wizard-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" style="border: 2px solid <?php echo $isSelected ? 'var(--color-primary)' : 'var(--color-border)'; ?>; background: <?php echo $isSelected ? 'var(--color-primary-light)' : '#FFFFFF'; ?>; border-radius: var(--radius-sm); padding: 1.15rem; transition: var(--transition); height: 100%;">
                                    <div style="font-weight: 700; color: var(--color-dark); font-size: 0.95rem;"><?php echo $b['label']; ?></div>
                                    <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 4px;"><?php echo $b['desc']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="max-width: 380px;">
                        <label for="custom_budget" style="font-size: 0.85rem; font-weight: 600; color: var(--color-dark); margin-bottom: 4px; display: block;">
                            Or Enter Specific Budget Ceiling (₦ NGN):
                        </label>
                        <input type="number" step="500000" min="1000000" id="custom_budget" name="custom_budget" 
                               value="<?php echo $customBudget > 0 ? (int)$customBudget : ''; ?>" 
                               placeholder="e.g. 45000000" class="form-control" style="font-size: 0.95rem;"
                               onfocus="clearBudgetRadios();">
                    </div>
                </div>

                <!-- STEP 3: MINIMUM PAYLOAD (20% Weight) -->
                <div style="margin-bottom: 2.5rem; padding-top: 2rem; border-top: 1px solid var(--color-border);">
                    <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 0.5rem;">
                        <span class="badge badge-primary" style="font-size: 0.85rem; padding: 6px 12px;">Step 3 &bull; 20% Weight</span>
                        <h3 style="font-size: 1.25rem; margin-bottom: 0; color: var(--color-dark);">What minimum payload tonnage capacity do you require?</h3>
                    </div>
                    <p style="font-size: 0.88rem; color: var(--color-text-muted); margin-bottom: 1.25rem;">
                        Specify the minimum load capacity (in metric tons) needed to handle your operational requirements.
                    </p>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
                        <?php 
                        $tonnages = [
                            '5'  => ['label' => '5.0 Metric Tons', 'desc' => 'Urban delivery (Fuso / Canter)'],
                            '10' => ['label' => '10.0 Metric Tons', 'desc' => 'Medium rigid distribution (Isuzu)'],
                            '20' => ['label' => '20.0 Metric Tons', 'desc' => 'Standard haulage & tankers'],
                            '30' => ['label' => '30.0 Metric Tons', 'desc' => 'Heavy quarry tippers (Actros 6x4)'],
                            '40' => ['label' => '40.0+ Metric Tons', 'desc' => 'Maximum heavy haulage & lowbed']
                        ];
                        ?>
                        <?php foreach ($tonnages as $tKey => $t): 
                            $isSelected = ($tonnageChoice === $tKey && $customTonnage <= 0);
                        ?>
                            <label style="cursor: pointer; margin: 0;">
                                <input type="radio" name="tonnage" value="<?php echo sanitize_output($tKey); ?>" <?php echo $isSelected ? 'checked' : ''; ?> style="display: none;" onchange="updateCardSelection(this, 'tonnage'); clearCustomTonnage();">
                                <div class="wizard-choice-card <?php echo $isSelected ? 'selected' : ''; ?>" style="border: 2px solid <?php echo $isSelected ? 'var(--color-primary)' : 'var(--color-border)'; ?>; background: <?php echo $isSelected ? 'var(--color-primary-light)' : '#FFFFFF'; ?>; border-radius: var(--radius-sm); padding: 1.15rem; transition: var(--transition); height: 100%;">
                                    <div style="font-weight: 700; color: var(--color-dark); font-size: 0.95rem;"><?php echo $t['label']; ?></div>
                                    <div style="font-size: 0.8rem; color: var(--color-text-muted); margin-top: 4px;"><?php echo $t['desc']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div style="max-width: 380px;">
                        <label for="custom_tonnage" style="font-size: 0.85rem; font-weight: 600; color: var(--color-dark); margin-bottom: 4px; display: block;">
                            Or Enter Minimum Payload (Tons):
                        </label>
                        <input type="number" step="0.5" min="1" max="100" id="custom_tonnage" name="custom_tonnage" 
                               value="<?php echo $customTonnage > 0 ? (float)$customTonnage : ''; ?>" 
                               placeholder="e.g. 25" class="form-control" style="font-size: 0.95rem;"
                               onfocus="clearTonnageRadios();">
                    </div>
                </div>

                <!-- Submit / Reset Actions -->
                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; padding-top: 1.75rem; border-top: 1px solid var(--color-border); flex-wrap: wrap;">
                    <div>
                        <?php if ($hasSubmitted): ?>
                            <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-secondary">
                                Reset Advisor &amp; Clear Filters
                            </a>
                        <?php else: ?>
                            <span style="font-size: 0.85rem; color: var(--color-text-muted);">
                                Transparent, predictable recommendations based on verified inventory.
                            </span>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="padding: 12px 28px; font-weight: 700;">
                        Calculate Vehicle Recommendations &rarr;
                    </button>
                </div>

            </form>
        </div>

        <script>
        function updateCardSelection(input, groupName) {
            var radios = document.querySelectorAll('input[name="' + groupName + '"]');
            radios.forEach(function(r) {
                var card = r.nextElementSibling;
                if (card) {
                    if (r.checked) {
                        card.style.borderColor = 'var(--color-primary)';
                        card.style.background = 'var(--color-primary-light)';
                    } else {
                        card.style.borderColor = 'var(--color-border)';
                        card.style.background = '#FFFFFF';
                    }
                }
            });
        }

        function clearCustomBudget() {
            var custom = document.getElementById('custom_budget');
            if (custom) custom.value = '';
        }

        function clearBudgetRadios() {
            var radios = document.querySelectorAll('input[name="budget"]');
            radios.forEach(function(r) {
                r.checked = false;
                if (r.nextElementSibling) {
                    r.nextElementSibling.style.borderColor = 'var(--color-border)';
                    r.nextElementSibling.style.background = '#FFFFFF';
                }
            });
        }

        function clearCustomTonnage() {
            var custom = document.getElementById('custom_tonnage');
            if (custom) custom.value = '';
        }

        function clearTonnageRadios() {
            var radios = document.querySelectorAll('input[name="tonnage"]');
            radios.forEach(function(r) {
                r.checked = false;
                if (r.nextElementSibling) {
                    r.nextElementSibling.style.borderColor = 'var(--color-border)';
                    r.nextElementSibling.style.background = '#FFFFFF';
                }
            });
        }
        </script>

        <!-- -------------------------------------------------------------------
             RECOMMENDATION RESULTS SECTION (Section 3.4.2.4 & Section 3.7.5)
             ------------------------------------------------------------------- -->
        <?php if ($hasSubmitted && $results !== null): ?>
            <div id="results" style="scroll-margin-top: 100px; margin-bottom: 4rem;">
                
                <div style="background: #FFFFFF; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 1.5rem 2rem; margin-bottom: 2rem; box-shadow: var(--shadow-sm);">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 0.75rem;">
                        <?php if ($results['match_type'] === 'exact'): ?>
                            <span class="badge" style="background: #E8F5E9; color: #2E7D32; font-size: 0.88rem; padding: 6px 14px; border: 1px solid #A5D6A7; font-weight: 700;">
                                Exact Specification Match Found (<?php echo (int)$results['total']; ?> Unit<?php echo $results['total'] > 1 ? 's' : ''; ?>)
                            </span>
                        <?php elseif ($results['match_type'] === 'alternative'): ?>
                            <span class="badge" style="background: #FEF3C7; color: #92400E; font-size: 0.88rem; padding: 6px 14px; border: 1px solid #FCD34D; font-weight: 700;">
                                Specification Proximity Match &bull; Top <?php echo (int)$results['total']; ?> Suitable Alternative<?php echo $results['total'] > 1 ? 's' : ''; ?>
                            </span>
                        <?php else: ?>
                            <span class="badge badge-secondary" style="font-size: 0.88rem; padding: 6px 14px;">
                                No Vehicle Currently Available
                            </span>
                        <?php endif; ?>

                        <div style="font-size: 0.85rem; color: var(--color-text-muted);">
                            Evaluated against: <strong><?php echo sanitize_output($selectedPurpose); ?></strong> 
                            &bull; Budget: <strong><?php echo $effectiveBudget > 0 ? format_currency($effectiveBudget) : 'Any'; ?></strong> 
                            &bull; Min Payload: <strong><?php echo $effectiveTonnage > 0 ? format_tonnage($effectiveTonnage) : 'Any'; ?></strong>
                        </div>
                    </div>

                    <?php if ($results['match_type'] === 'exact'): ?>
                        <h2 style="font-size: 1.5rem; color: var(--color-dark); margin-bottom: 0.25rem;">
                            Direct Vehicle Match Identified
                        </h2>
                        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 0;">
                            The following truck(s) fulfill 100% of your requested operational purpose, budget ceiling, and minimum payload capacity.
                        </p>
                    <?php elseif ($results['match_type'] === 'alternative'): ?>
                        <h2 style="font-size: 1.5rem; color: var(--color-dark); margin-bottom: 0.25rem;">
                            Ranked Fallback Alternatives
                        </h2>
                        <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 0;">
                            No unit currently satisfies all three criteria simultaneously. In accordance with Section 3.7.5, our proximity algorithm 
                            (50% Purpose, 30% Budget, 20% Tonnage) has scored available inventory and ranked the top 3 closest alternatives:
                        </p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($results['trucks'])): ?>
                    <div class="truck-grid">
                        <?php foreach ($results['trucks'] as $truck): 
                            $isExact = !empty($truck['is_exact']);
                            $percentage = (int)($truck['match_percentage'] ?? 100);
                        ?>
                            <div class="truck-card" style="display: flex; flex-direction: column;">
                                <div class="truck-card-media" style="position: relative;">
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

                                    <!-- Category Badge -->
                                    <div class="truck-card-badge">
                                        <span class="badge badge-dark"><?php echo sanitize_output($truck['purpose_category']); ?></span>
                                    </div>
                                    
                                    <!-- Match Percentage Badge (Section 3.7.5) -->
                                    <div style="position: absolute; top: 12px; right: 12px; z-index: 5;">
                                        <?php if ($isExact): ?>
                                            <span class="badge" style="background: #2E7D32; color: #FFFFFF; font-weight: 800; font-size: 0.85rem; padding: 6px 12px; box-shadow: var(--shadow-sm); border: none;">
                                                100% Match &bull; Exact
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: var(--color-primary); color: #FFFFFF; font-weight: 800; font-size: 0.85rem; padding: 6px 12px; box-shadow: var(--shadow-sm); border: none;">
                                                <?php echo $percentage; ?>% Match
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="truck-card-body" style="display: flex; flex-direction: column; flex-grow: 1;">
                                    <h3 class="truck-card-title"><?php echo sanitize_output($truck['title']); ?></h3>
                                    <div class="truck-card-subtitle">
                                        <?php echo sanitize_output($truck['brand'] . ' ' . $truck['model'] . ' (' . $truck['year_of_manufacture'] . ')'); ?> &bull; <span style="color: var(--color-text-muted); font-size: 0.82rem;"><?php echo sanitize_output($truck['truck_code']); ?></span>
                                    </div>

                                    <!-- Proximity Score Breakdown Tags (for Alternatives) -->
                                    <?php if (!$isExact && !empty($truck['score_breakdown'])): ?>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 0.85rem; padding: 6px 10px; background: #F8FAFC; border-radius: 6px; font-size: 0.78rem; color: var(--color-text-muted);">
                                            <span>Purpose: <strong><?php echo $truck['score_breakdown']['purpose']; ?>%</strong></span>
                                            <span>&bull;</span>
                                            <span>Budget: <strong><?php echo $truck['score_breakdown']['budget']; ?>%</strong></span>
                                            <span>&bull;</span>
                                            <span>Payload: <strong><?php echo $truck['score_breakdown']['tonnage']; ?>%</strong></span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Technical Specifications Row -->
                                    <div class="truck-specs-row" style="margin-top: auto;">
                                        <div class="spec-item">
                                            <span class="spec-label">Payload</span>
                                            <span class="spec-value"><?php echo format_tonnage($truck['tonnage_capacity']); ?></span>
                                        </div>
                                        <div class="spec-item">
                                            <span class="spec-label">Drive</span>
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

                                    <!-- Public Pricing & Open Access CTAs (Section 3.7.5) -->
                                    <div class="truck-card-footer" style="padding-top: 1rem; border-top: 1px solid var(--color-border); margin-top: 1rem;">
                                        <div class="truck-price">
                                            <span class="price-label">Price</span>
                                            <span class="price-amount" style="font-size: 1.15rem; font-weight: 800; color: var(--color-dark);">
                                                <?php echo format_currency($truck['price']); ?>
                                            </span>
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-outline btn-sm" title="Submit inquiry for this recommended truck">
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
                    <!-- Empty State -->
                    <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 3.5rem 2rem; text-align: center;">
                        <h3 style="margin-bottom: 0.5rem; color: var(--color-dark);">No Commercial Vehicles in Active Stock</h3>
                        <p style="color: var(--color-text-muted); max-width: 520px; margin: 0 auto 1.5rem auto;">
                            We currently do not have vehicles matching your combination in our local yard. Our commercial procurement team can source, clear, and deliver your exact required configuration.
                        </p>
                        <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom&purpose=<?php echo urlencode($selectedPurpose); ?>&budget=<?php echo (float)$effectiveBudget; ?>&tonnage=<?php echo (float)$effectiveTonnage; ?>" class="btn btn-primary">
                            Submit Custom Sourcing Request &rarr;
                        </a>
                    </div>
                <?php endif; ?>

                <!-- Custom Sourcing Callout Banner -->
                <div style="margin-top: 3.5rem; background: linear-gradient(135deg, #1F2421 0%, #2A302D 100%); color: #FFFFFF; border-radius: var(--radius-md); padding: 2.5rem; border-left: 6px solid var(--color-primary); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1.5rem;">
                    <div style="max-width: 620px;">
                        <span class="badge badge-warning" style="margin-bottom: 0.5rem;">Custom Fleet Procurement</span>
                        <h3 style="color: #FFFFFF; font-size: 1.35rem; margin-bottom: 0.35rem;">Require a Specific Model, Axle Setup, or Specialized Tanker?</h3>
                        <p style="color: #CBD5E1; font-size: 0.92rem; margin: 0; line-height: 1.6;">
                            If our active yard inventory does not fully cover your operational payload, our procurement team sources directly from certified European and Asian auction hubs with guaranteed Nigeria Customs clearance.
                        </p>
                    </div>
                    <div>
                        <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom&purpose=<?php echo urlencode($selectedPurpose); ?>" class="btn btn-primary btn-lg" style="font-weight: 700;">
                            Request Custom Sourcing &rarr;
                        </a>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
