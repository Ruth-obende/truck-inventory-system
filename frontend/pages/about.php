<?php
/**
 * =============================================================================
 * Moal General Suppliers - About Us: Our Story, Mission, Vision, and Values
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Us | Our Story, Mission, Vision, and Values';

require_once __DIR__ . '/includes/header.php';
?>

<!-- ABOUT US HERO BANNER (LANDSCAPE DAYLIGHT BACKDROP) -->
<section class="about-page-hero">
    <div class="container">
        <span class="section-tag">About Moal General Suppliers</span>
        <h1>Heavy Commercial Fleet Solutions for Nigerian Transporters</h1>
        <p>
            Moal General Suppliers provides thoroughly checked commercial trucks with genuine Customs documentation from our physical yard in Ojodu Berger, Lagos.
        </p>
    </div>
</section>

<!-- MAIN ABOUT US CONTENT SECTION -->
<div class="section" style="padding-top: 1rem; padding-bottom: 4rem;">
    <div class="container" style="max-width: 1080px;">
        
        <!-- 1. OUR STORY & AUTHENTIC DEALERSHIP YARD SHOWCASE -->
        <div class="story-showcase-grid">
            <div class="story-card" style="margin-bottom: 0;">
                <span class="badge badge-primary" style="margin-bottom: 0.75rem;">Our Story</span>
                <h2 style="font-size: clamp(1.45rem, 2.4vw, 1.95rem); color: var(--color-dark); margin-bottom: 1.15rem;">
                    Built on Trust and Real Value for Transporters
                </h2>
                <p style="font-size: 1.02rem; line-height: 1.75; color: var(--color-text); margin-bottom: 1.25rem;">
                    Buying a commercial truck in Nigeria can be a stressful and risky investment. Many haulage operators, quarry contractors, and logistics owners have lost hard earned capital to trucks with hidden engine faults, damaged gearboxes, or falsified Customs papers that lead to seizure on the road.
                </p>
                <p style="font-size: 1.02rem; line-height: 1.75; color: var(--color-text); margin-bottom: 1.25rem;">
                    <strong>Moal General Suppliers</strong> was established to give Nigerian truck buyers a transparent and dependable place to purchase heavy vehicles. Operating from our commercial yard at <strong>No. 2 Oluwakemi Street, Ojodu Berger, Lagos</strong>, we supply verified European and Asian commercial vehicles including tippers for construction, tractor heads for container haulage, and rigid cargo trucks.
                </p>
                <p style="font-size: 1.02rem; line-height: 1.75; color: var(--color-text); margin: 0;">
                    Every truck in our stock comes with authentic Nigeria Customs Single Goods Declaration documentation. We encourage you to visit our yard with your mechanic to inspect and test run any truck before making any payment. For customers outside Lagos, we also coordinate safe transit delivery directly to your work site anywhere in Nigeria.
                </p>
            </div>

            <!-- Authentic Dealership Yard Image (Responsive Fitting) -->
            <div class="story-image-card">
                <div class="story-image-wrapper">
                    <img src="<?php echo BASE_URL; ?>assets/images/branding/moal_contact_yard.jpg" alt="Moal General Suppliers Dealership Team and Fleet at Ojodu Berger Yard" class="story-yard-img" loading="lazy">
                    <div class="story-image-badge">
                        <span class="pulse-dot"></span> Ojodu Berger Dealership Yard &bull; Lagos
                    </div>
                </div>
                <div class="story-image-caption">
                    <strong>Physical Inspection Yard</strong> &bull; Commercial truck buyers are always welcome to inspect and test run any truck with their mechanic before purchase.
                </div>
            </div>
        </div>

        <!-- 2. OUR MISSION & OUR VISION -->
        <div class="vision-mission-grid">
            <!-- Our Mission -->
            <div class="vm-card" style="border-top-color: var(--color-primary);">
                <div style="margin-bottom: 0.75rem;">
                    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-primary); font-weight: 800; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Company Purpose</span>
                    <h2 style="margin: 0; font-size: 1.35rem; color: var(--color-dark);">Our Mission</h2>
                </div>
                <p>
                    To provide verified, mechanically sound commercial trucks with genuine Customs documentation, straightforward pricing, and reliable after sale support, helping transport businesses operate with confidence and minimal downtime.
                </p>
            </div>

            <!-- Our Vision -->
            <div class="vm-card" style="border-top-color: var(--color-dark);">
                <div style="margin-bottom: 0.75rem;">
                    <span style="font-size: 0.78rem; text-transform: uppercase; color: var(--color-text-muted); font-weight: 800; letter-spacing: 0.5px; display: block; margin-bottom: 4px;">Long Term Goal</span>
                    <h2 style="margin: 0; font-size: 1.35rem; color: var(--color-dark);">Our Vision</h2>
                </div>
                <p>
                    To be the most trusted and reliable commercial vehicle dealership in Nigeria, known for honesty, quality vehicles, and dependable service to the haulage and construction industries.
                </p>
            </div>
        </div>

        <!-- 3. OUR VALUES (WHAT WE STAND FOR) -->
        <div style="margin-bottom: 3.5rem;">
            <div style="text-align: center; margin-bottom: 2.25rem;">
                <span class="section-tag">Core Principles</span>
                <h2 style="font-size: clamp(1.4rem, 2.5vw, 1.85rem); color: var(--color-dark); margin-bottom: 0.5rem;">
                    Our Values: What We Stand For
                </h2>
                <p style="color: var(--color-text-muted); font-size: 0.95rem; max-width: 640px; margin: 0 auto;">
                    These four core principles guide how we source, inspect, document, and sell every commercial vehicle.
                </p>
            </div>

            <div class="values-grid">
                <!-- Value 1: Quality First -->
                <div class="value-card">
                    <div class="value-num">01</div>
                    <h3 class="value-title">Vehicle Quality</h3>
                    <p class="value-desc">
                        Every commercial truck undergoes thorough mechanical diagnostics, engine compression checks, transmission tests, and chassis inspection before placement in our yard.
                    </p>
                </div>

                <!-- Value 2: Complete Integrity -->
                <div class="value-card">
                    <div class="value-num">02</div>
                    <h3 class="value-title">Complete Integrity</h3>
                    <p class="value-desc">
                        We provide authentic Nigeria Customs Single Goods Declaration papers and duty receipts. No hidden fees, no altered numbers, and no questionable vehicle history.
                    </p>
                </div>

                <!-- Value 3: Client Partnership -->
                <div class="value-card">
                    <div class="value-num">03</div>
                    <h3 class="value-title">Client Partnership</h3>
                    <p class="value-desc">
                        We work closely with our clients to recommend the right vehicle based on actual workload, road conditions, payload requirements, and realistic operating budgets.
                    </p>
                </div>

                <!-- Value 4: Customer Support -->
                <div class="value-card">
                    <div class="value-num">04</div>
                    <h3 class="value-title">Customer Support</h3>
                    <p class="value-desc">
                        We welcome pre purchase inspections with your own mechanic at our Ojodu Berger yard and arrange safe transport delivery to project sites across all 36 states.
                    </p>
                </div>
            </div>
        </div>

        <!-- 4. READY TO TAKE THE NEXT STEP? (CALL TO ACTION) -->
        <div style="background: linear-gradient(135deg, #1A1F1C 0%, #242A27 100%); color: #FFFFFF; border-radius: var(--radius-lg); padding: clamp(2.5rem, 4.5vw, 3.5rem); box-shadow: var(--shadow-md);">
            <div style="max-width: 680px; margin-bottom: 2rem;">
                <span class="badge badge-warning" style="margin-bottom: 0.75rem;">Next Steps</span>
                <h3 style="color: #FFFFFF; font-size: clamp(1.5rem, 2.5vw, 2rem); font-weight: 800; margin-bottom: 0.75rem; letter-spacing: -0.01em;">
                    Ready to Expand or Upgrade Your Fleet?
                </h3>
                <p style="color: #C8CCC9; font-size: 1rem; line-height: 1.6; margin: 0;">
                    Take a look at our current truck inventory, use our recommendation advisor to find the right truck for your work, or reach out to our sales team in Lagos.
                </p>
            </div>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <?php if (is_customer_logged_in()): ?>
                    <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-lg" style="font-weight: 700;">
                        View All Products &rarr;
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>customer-login.php?redirect=inventory.php" class="btn btn-primary btn-lg" style="font-weight: 700;">
                        View All Products &rarr;
                    </a>
                <?php endif; ?>
                
                <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-secondary btn-lg" style="background: rgba(255, 255, 255, 0.12); color: #FFFFFF; border-color: rgba(255, 255, 255, 0.3);">
                    Find My Truck Advisor
                </a>

                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-white btn-lg">
                    Contact Sales Desk
                </a>

                <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20inquire%20about%20your%20commercial%20trucks." target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp btn-lg">
                    Chat on WhatsApp &rarr;
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
