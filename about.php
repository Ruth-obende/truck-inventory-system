<?php
/**
 * =============================================================================
 * Moal General Suppliers - About Us Page
 * =============================================================================
 * Company overview, mission, core values, and dealership operational reach.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>About Us</span>
        </div>
        <h1>About Moal General Suppliers</h1>
        <p>Your trusted commercial heavy-duty truck supply partner and fleet procurement specialist in Nigeria.</p>
    </div>
</div>

<div class="container" style="margin-bottom: 4rem;">

    <!-- Company Story & Mission Grid -->
    <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 3rem; align-items: center; margin-bottom: 4rem;">
        <div>
            <span class="badge badge-orange" style="margin-bottom: 0.75rem;">Commercial Transport Excellence</span>
            <h2 style="font-size: 2rem; color: var(--primary-navy); margin-bottom: 1rem; line-height: 1.25;">
                Supplying Dependable Commercial Trucks Built for West African Demands
            </h2>
            <p style="font-size: 1.05rem; color: var(--text-body); line-height: 1.7; margin-bottom: 1.25rem;">
                <strong>Moal General Suppliers</strong> is a premier commercial truck dealership and logistics solutions provider headquartered at <strong>No. 2 Oluwakemi Street, Ojodu Berger, Lagos, Nigeria</strong>.
            </p>
            <p style="font-size: 0.95rem; color: var(--text-muted); line-height: 1.7; margin-bottom: 1.5rem;">
                We specialize in sourcing, inspecting, and supplying verified heavy-duty tractor heads, multi-axle construction tippers, rigid FMCG distribution box trucks, and specialized fuel tankers. Our mission is to empower logistics operators, quarry companies, agricultural enterprises, and haulage contractors with dependable vehicles that maximize uptime and profitability.
            </p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">Browse Truck Inventory</a>
                <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-navy">Connect with Us</a>
            </div>
        </div>

        <div style="background: linear-gradient(135deg, var(--primary-navy-dark) 0%, var(--primary-navy) 100%); border-radius: var(--radius-lg); border: 2px solid var(--accent-orange); padding: 2.5rem; color: #fff; box-shadow: var(--shadow-lg);">
            <h3 style="font-size: 1.4rem; color: #fff; margin-bottom: 1.25rem;">Our Core Operating Pillars</h3>
            
            <div style="margin-bottom: 1.5rem;">
                <strong style="color: var(--accent-orange); display: block; font-size: 1.05rem;">🎯 Our Mission</strong>
                <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 4px; line-height: 1.6;">
                    To deliver verified, road-ready heavy commercial vehicles backed by honest inspection reports, authentic customs papers, and prompt customer advisory.
                </p>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <strong style="color: var(--accent-orange); display: block; font-size: 1.05rem;">👁️ Our Vision</strong>
                <p style="font-size: 0.9rem; color: #cbd5e1; margin-top: 4px; line-height: 1.6;">
                    To be Nigeria’s most trusted commercial fleet dealership, recognized for transparency, technical rigor, and nationwide haulage support.
                </p>
            </div>

            <div>
                <strong style="color: var(--accent-orange); display: block; font-size: 1.05rem;">⚡ Our Tagline</strong>
                <p style="font-size: 1rem; color: #fff; font-weight: 700; margin-top: 4px;">
                    "Driving Business Forward"
                </p>
            </div>
        </div>
    </div>

    <!-- Dealership Milestones / Operational Highlights -->
    <div style="background: #ffffff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 3rem 2rem; margin-bottom: 4rem; box-shadow: var(--shadow-sm);">
        <div class="section-header" style="text-align: center; max-width: 650px; margin: 0 auto 2.5rem auto;">
            <h2 class="section-title">The Moal General Suppliers Difference</h2>
            <p class="section-subtitle">A structured, customer-first approach to commercial vehicle acquisition.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 2rem;">
            
            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🔍</div>
                <h3 style="color: var(--primary-navy); font-size: 1.15rem; margin-bottom: 0.5rem;">Rigorous Pre-Listing Inspection</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Full mechanical diagnostic evaluation covering engine, gearbox, chassis, and brake systems before any vehicle is cataloged.</p>
            </div>

            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">📜</div>
                <h3 style="color: var(--primary-navy); font-size: 1.15rem; margin-bottom: 0.5rem;">100% Verified Customs Papers</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Genuine Nigeria Customs duty clearance receipts, Single Goods Declarations (SGD), and clear title ownership transfers.</p>
            </div>

            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🎯</div>
                <h3 style="color: var(--primary-navy); font-size: 1.15rem; margin-bottom: 0.5rem;">Rule-Based Fleet Matching</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Our multi-criteria decision tool matches buyers with vehicles specifically suited to their payload requirements and operational routes.</p>
            </div>

            <div style="text-align: center; padding: 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🚛</div>
                <h3 style="color: var(--primary-navy); font-size: 1.15rem; margin-bottom: 0.5rem;">Nationwide Delivery</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; line-height: 1.6;">Safe, coordinated transit across all 36 states with dedicated handover verification at your operating facility.</p>
            </div>

        </div>
    </div>

    <!-- CTA Section -->
    <div style="background: linear-gradient(135deg, var(--primary-navy-dark) 0%, var(--primary-navy) 100%); color: #fff; border-radius: var(--radius-lg); border-bottom: 4px solid var(--accent-orange); padding: 3rem 2rem; text-align: center;">
        <h2 style="font-size: 1.8rem; font-weight: 800; margin-bottom: 0.75rem;">Ready to Discuss Your Fleet Requirements?</h2>
        <p style="color: #cbd5e1; max-width: 650px; margin: 0 auto 1.75rem auto; font-size: 1rem; line-height: 1.6;">
            Visit our dealership yard at No. 2 Oluwakemi Street, Ojodu Berger, Lagos, or contact our sales team directly.
        </p>
        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="tel:<?php echo CONTACT_PHONE_1; ?>" class="btn btn-outline" style="border-color: #fff; color: #fff;">📞 Call: <?php echo CONTACT_PHONE_1; ?></a>
            <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-primary">Submit Inquiry &rarr;</a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
