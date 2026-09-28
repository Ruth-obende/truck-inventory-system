<?php
/**
 * =============================================================================
 * Moal General Suppliers - Commercial Truck Dealership Landing Page
 * =============================================================================
 * Clean, minimal public landing page introducing Moal General Suppliers:
 * - Company / brand presentation
 * - Featured commercial trucks without prices
 * - Brief company information & trust standards
 * - Contact information & direct WhatsApp contact
 * - Navigation: Home, About, Contact, Sign In
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Commercial Truck Inventory & Fleet Solutions';
$db = getDB();

// Fetch 6 Featured Trucks from Database (without prices)
$stmtFeatured = $db->query('
    SELECT t.*, 
    (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
    FROM trucks t 
    WHERE t.availability_status = "Available"
    ORDER BY t.featured DESC, t.id DESC 
    LIMIT 6
');
$featuredTrucks = $stmtFeatured->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. COMPANY / BRAND PRESENTATION (HERO) -->
<section class="hero-section">
    <div class="hero-overlay"></div>
    <div class="container hero-container">
        <div class="hero-content">
            <span class="hero-tag">Commercial Vehicle Dealership &bull; Lagos, Nigeria</span>
            <h1 class="hero-title">Heavy Commercial Trucks Built for Real Work</h1>
            <p class="hero-lead">
                Moal General Suppliers provides thoroughly inspected European and Asian commercial trucks with genuine Customs documentation, ready for immediate work across Nigeria.
            </p>
            <div class="hero-actions">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-lg">
                    View All Products &rarr;
                </a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-white btn-lg">
                    Contact Sales Desk
                </a>
            </div>
        </div>
    </div>
</section>

<!-- 2. OUR FLEET (COMMERCIAL VEHICLES WITHOUT PUBLIC PRICES) -->
<section class="section" id="our-fleet">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Commercial Stock</span>
            <h2 class="section-title">Our Fleet</h2>
            <p class="section-subtitle">Commercial vehicles currently available for physical inspection at our Ojodu Berger yard in Lagos.</p>
        </div>

        <?php if (!empty($featuredTrucks)): ?>
            <div class="truck-grid">
                <?php foreach ($featuredTrucks as $truck): ?>
                    <div class="truck-card">
                        <div class="truck-card-media">
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
                                    <span class="spec-label">Payload Capacity</span>
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

                            <div class="truck-card-footer" style="padding-top: 0.75rem;">
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; text-align: center;">
                                    View Truck Details &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- View All Products Action Button -->
            <div style="text-align: center; margin-top: 3rem;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-lg" style="padding: 14px 40px; font-weight: 700; box-shadow: var(--shadow-sm);">
                    View All Products &rarr;
                </a>
                <p style="font-size: 0.88rem; color: var(--color-text-muted); margin-top: 0.75rem;">
                    Browse our complete heavy commercial inventory catalogue, verified specifications, and listed prices.
                </p>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 3rem; background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md);">
                <p style="color: var(--color-text-muted);">No featured trucks available at the moment. Please check back shortly.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- 3. "WHY CHOOSE MOAL GENERAL SUPPLIERS" FEATURE GRID -->
<section class="section features-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Dealership Standards</span>
            <h2 class="section-title">Why Nigeria's Fleet Operators Choose Moal</h2>
            <p class="section-subtitle">We combine commercial truck sourcing expertise with clear pricing, thorough mechanical inspection, and verified documentation.</p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-num">01</div>
                <h3 class="feature-card-title">100% Genuine and Verified Units</h3>
                <p class="feature-card-desc">Every commercial vehicle undergoes thorough engine compression, transmission, chassis alignment, and hydraulic diagnostics before placement in our yard.</p>
            </div>

            <div class="feature-card">
                <div class="feature-num">02</div>
                <h3 class="feature-card-title">Authentic Customs Documentation</h3>
                <p class="feature-card-desc">Complete peace of mind with authentic Nigeria Customs Single Goods Declarations (SGD), official duty payment receipts, and clear ownership title.</p>
            </div>

            <div class="feature-card">
                <div class="feature-num">03</div>
                <h3 class="feature-card-title">Nationwide Delivery Coverage</h3>
                <p class="feature-card-desc">Direct transit and secured logistics coordination from our Ojodu Berger yard to operational sites across all 36 Nigerian states and Abuja.</p>
            </div>

            <div class="feature-card">
                <div class="feature-num">04</div>
                <h3 class="feature-card-title">Selected for Local Roads</h3>
                <p class="feature-card-desc">Vehicles chosen specifically with multi axle configurations, reinforced suspension, and high torque engines built for Nigerian road conditions.</p>
            </div>

            <div class="feature-card">
                <div class="feature-num">05</div>
                <h3 class="feature-card-title">Transparent Dealership Terms</h3>
                <p class="feature-card-desc">Direct dealership access with no inflated middleman markups, straightforward proforma invoicing, and verified physical inspection appointments.</p>
            </div>

            <div class="feature-card">
                <div class="feature-num">06</div>
                <h3 class="feature-card-title">Experienced Sales Guidance</h3>
                <p class="feature-card-desc">Commercial transport specialists on call to advise on payload capacities, fuel economy, and operational fit for your business.</p>
            </div>
        </div>
    </div>
</section>

<!-- 4. CLIENT TESTIMONIALS & INDUSTRY REVIEWS -->
<section class="section testimonials-section">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Client Reviews</span>
            <h2 class="section-title">Trusted by Commercial Transport &amp; Fleet Leaders</h2>
            <p class="section-subtitle">Real experiences from haulage companies, quarry operators, and logistics directors partnering with Moal General Suppliers.</p>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div>
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-quote">
                        "We acquired two Mercedes-Benz Actros 3340 tippers from Moal General Suppliers for our quarry operations. The mechanical condition was exactly as advertised, and the vehicles arrived with complete Customs documentation within 48 hours."
                    </p>
                </div>
                <div class="testimonial-author">
                    <strong>Engr. Babatunde Adeleke</strong>
                    <span>Managing Director, Apex Construction &amp; Aggregates Ltd</span>
                </div>
            </div>

            <div class="testimonial-card">
                <div>
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-quote">
                        "Finding reliable commercial box trucks for our FMCG distribution routes was a major challenge until we worked with Moal. Their fleet guidance helped us select the ideal models, saving us substantial maintenance and downtime."
                    </p>
                </div>
                <div class="testimonial-author">
                    <strong>Hajiya Fatima Garba</strong>
                    <span>Head of Logistics, Savannah Prime Distribution Network</span>
                </div>
            </div>

            <div class="testimonial-card">
                <div>
                    <div class="testimonial-stars">★★★★★</div>
                    <p class="testimonial-quote">
                        "Moal General Suppliers supplied three heavy duty HOWO tractor heads for our container haulage routes. Professional communication, direct documentation, and zero hidden issues. Highly recommended."
                    </p>
                </div>
                <div class="testimonial-author">
                    <strong>Chief Emeka Nwosu</strong>
                    <span>Operations Director, Trans-Atlantic Haulage Services</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. READY TO GROW YOUR FLEET CTA BANNER -->
<section class="section" style="background: linear-gradient(135deg, #1A1F1C 0%, #242A27 100%); color: #FFFFFF; padding: clamp(3rem, 5vw, 4rem) 0; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
        <div style="max-width: 660px;">
            <span class="badge badge-warning" style="margin-bottom: 0.75rem;">Fleet Procurement</span>
            <h2 style="font-size: clamp(1.6rem, 2.8vw, 2.25rem); color: #FFFFFF; font-weight: 800; margin-bottom: 0.5rem; letter-spacing: -0.01em;">
                Ready to Expand or Upgrade Your Commercial Fleet?
            </h2>
            <p style="color: #C8CCC9; font-size: 1.02rem; line-height: 1.6; margin: 0;">
                Speak with our sales consultants today for verified stock, yard inspection bookings, or proforma invoice requests.
            </p>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary btn-lg" style="font-weight: 700;">
                View All Products &rarr;
            </a>
            <a href="tel:07069219001" class="btn btn-outline-white btn-lg">
                Call Sales Desk
            </a>
        </div>
    </div>
</section>

<!-- 5. VISUAL CONTACT SECTION (HIGH-QUALITY TRUCK BACKGROUND) -->
<section class="landing-contact-section" id="contact">
    <div class="container">
        <div class="landing-contact-header">
            <span class="landing-contact-tag">Contact</span>
            <h2 class="landing-contact-title">Connect with Our Dealership Team</h2>
            <p class="landing-contact-lead">
                Have questions regarding commercial truck specifications, fleet availability, or scheduling a physical yard inspection? Reach out to our sales desk directly.
            </p>
        </div>

        <div class="landing-contact-grid">
            <!-- 1. Call / Sales Number -->
            <div class="landing-contact-card">
                <div class="landing-contact-card-top">
                    <div class="landing-contact-icon-wrapper" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="landing-contact-card-label">Direct Lines</div>
                    <h3 class="landing-contact-card-heading">Call Sales</h3>
                </div>
                <div class="landing-contact-body">
                    <div class="landing-contact-phone-list">
                        <a href="tel:07069219001" class="landing-contact-phone-link" title="Call primary sales line">
                            07069219001
                        </a>
                        <a href="tel:08151111181" class="landing-contact-phone-link secondary-phone" title="Call secondary sales line">
                            08151111181
                        </a>
                    </div>
                    <a href="tel:07069219001" class="landing-contact-action-btn call-btn">
                        Call Sales Desk &rarr;
                    </a>
                </div>
            </div>

            <!-- 2. WhatsApp Number -->
            <div class="landing-contact-card">
                <div class="landing-contact-card-top">
                    <div class="landing-contact-icon-wrapper whatsapp-icon-wrapper" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.311.045-.698.034-.946-.027-.234-.057-.52-.162-.897-.321-1.61-.678-2.661-2.327-2.742-2.434-.082-.107-.655-.873-.655-1.666 0-.793.414-1.185.561-1.346.147-.161.321-.202.428-.202.107 0 .214.002.308.006.099.005.231-.037.361.275.134.321.458 1.115.498 1.196.04.081.067.176.013.283-.053.107-.08.175-.16.269-.08.093-.169.208-.242.279-.081.08-.166.166-.071.328.094.161.42 1.055 1.218 1.48.243.13.435.185.586.233.245.078.468.067.644.041.196-.029.606-.247.692-.486.086-.239.086-.444.06-.486-.027-.042-.098-.068-.205-.121z"/>
                        </svg>
                    </div>
                    <div class="landing-contact-card-label">Instant Messaging</div>
                    <h3 class="landing-contact-card-heading">WhatsApp</h3>
                </div>
                <div class="landing-contact-body">
                    <div class="landing-contact-phone-list">
                        <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20inquire%20about%20your%20commercial%20trucks." target="_blank" rel="noopener noreferrer" class="landing-contact-phone-link" title="Open WhatsApp chat">
                            07069219001
                        </a>
                    </div>
                    <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20inquire%20about%20your%20commercial%20trucks." target="_blank" rel="noopener noreferrer" class="landing-contact-action-btn whatsapp-action-btn">
                        Chat on WhatsApp &rarr;
                    </a>
                </div>
            </div>

            <!-- 3. Lagos Office Address -->
            <div class="landing-contact-card">
                <div class="landing-contact-card-top">
                    <div class="landing-contact-icon-wrapper" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="landing-contact-card-label">Physical Location</div>
                    <h3 class="landing-contact-card-heading">Lagos Office</h3>
                </div>
                <div class="landing-contact-body">
                    <address class="landing-contact-address-text">
                        No. 2 Oluwakemi Street, Ojodu Berger, Lagos State, Nigeria
                    </address>
                    <p class="landing-contact-address-meta">
                        Dealership Yard &amp; Commercial Vehicle Fleet Inspection
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
