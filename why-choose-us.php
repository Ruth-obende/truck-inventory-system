<?php
/**
 * =============================================================================
 * Moal General Suppliers - Why Choose Us Page
 * =============================================================================
 * Comprehensive value proposition and trust assurance details.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Why Choose Us';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>Why Choose Us</span>
        </div>
        <h1>Why Choose Moal General Suppliers</h1>
        <p>The trusted standard in Nigerian commercial vehicle supply, transparent pricing, and verified fleet quality.</p>
    </div>
</div>

<div class="container" style="margin-bottom: 4rem;">

    <!-- Value Grid Intro -->
    <div style="text-align: center; max-width: 800px; margin: 0 auto 3rem auto;">
        <span class="badge badge-orange" style="margin-bottom: 0.75rem;">Quality, Transparency &amp; Reliability</span>
        <h2 style="font-size: 1.85rem; color: var(--primary-navy); margin-bottom: 0.75rem;">
            Built to Overcome Nigerian Commercial Fleet Challenges
        </h2>
        <p style="color: var(--text-body); font-size: 1.05rem; line-height: 1.7;">
            In a market often plagued by unclear vehicle histories, unverified mileage, and questionable documentation, Moal General Suppliers provides a transparent, professional alternative for commercial transport operators.
        </p>
    </div>

    <!-- 6 Pillars Deep Dive Grid -->
    <div class="feature-grid" style="margin-bottom: 4rem;">
        
        <div class="feature-card">
            <div class="feature-icon">️</div>
            <h3 class="feature-card-title">1. Genuine &amp; Verified Units</h3>
            <p class="feature-card-desc">
                Every truck in our inventory has a documented provenance. We verify engine compression, transmission shifting, and axle health, ensuring you acquire a vehicle ready to generate revenue immediately.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-icon"></div>
            <h3 class="feature-card-title">2. Direct Dealership Pricing</h3>
            <p class="feature-card-desc">
                We operate with direct pricing, eliminating inflated third-party middleman surcharges. What you see is transparent, fair market pricing with clear breakdown invoices.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-icon"></div>
            <h3 class="feature-card-title">3. 100% Authentic Documentation</h3>
            <p class="feature-card-desc">
                Every foreign-used vehicle comes with verifiable Nigeria Customs Service duty receipts, authentic Single Goods Declarations (SGD), and clear title ownership documentation.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-icon"></div>
            <h3 class="feature-card-title">4. Engineered for African Routes</h3>
            <p class="feature-card-desc">
                We stock multi-axle configurations (6x4, 8x4), heavy spring suspensions, and high-torque diesel powertrains proven to withstand Nigerian interstate roads and quarry conditions.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-icon"></div>
            <h3 class="feature-card-title">5. Rule-Based Fleet Matching</h3>
            <p class="feature-card-desc">
                Avoid buying oversized or underpowered vehicles. Our 3-question recommendation tool matches your exact operational payload and budget to the most suitable trucks.
            </p>
        </div>

        <div class="feature-card">
            <div class="feature-icon"></div>
            <h3 class="feature-card-title">6. Nationwide Delivery Support</h3>
            <p class="feature-card-desc">
                Whether your operational base is in Lagos, Abuja, Kano, Port Harcourt, or Onitsha, we coordinate safe transit and vehicle handover directly to your yard.
            </p>
        </div>

    </div>

    <!-- Assurance Banner -->
    <div style="background: linear-gradient(135deg, var(--primary-navy-dark) 0%, var(--primary-navy) 100%); color: #fff; border-radius: var(--radius-lg); border-bottom: 4px solid var(--accent-orange); padding: 3rem 2rem; text-align: center;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.75rem;">Experience the Moal Standard First-Hand</h2>
        <p style="color: #cbd5e1; max-width: 650px; margin: 0 auto 1.75rem auto; font-size: 1rem; line-height: 1.6;">
            Schedule a physical vehicle inspection at our Ojodu Berger yard in Lagos, or connect with our commercial sales engineers.
        </p>
        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-outline" style="border-color: #fff; color: #fff;">Browse Inventory</a>
            <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-primary">Submit Commercial Inquiry &rarr;</a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
