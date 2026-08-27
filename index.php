<?php
/**
 * =============================================================================
 * Moal General Suppliers - Official Dealership Homepage
 * =============================================================================
 * Case Study Implementation: Commercial Truck Inventory & Customer Inquiries
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Commercial Truck Dealership & Fleet Sourcing';

$dbStatus = 'Connecting...';
$mysqlVersion = 'Unknown';
$totalTrucks = 0;
$featuredTrucks = [];

try {
    $db = getDB();
    $dbStatus = 'Connected Successfully (PDO)';
    $mysqlVersion = $db->query('SELECT VERSION()')->fetchColumn();
    
    // Count total inventory
    $stmtCount = $db->query('SELECT COUNT(*) FROM trucks WHERE availability_status = "Available"');
    $totalTrucks = (int)$stmtCount->fetchColumn();

    // Fetch featured trucks for display
    $stmtTrucks = $db->prepare('
        SELECT t.*, 
        (SELECT image_path FROM truck_images WHERE truck_id = t.id AND is_primary = 1 LIMIT 1) AS primary_image
        FROM trucks t 
        WHERE t.availability_status = :status 
        ORDER BY t.featured DESC, t.id ASC 
        LIMIT 6
    ');
    $stmtTrucks->execute([':status' => 'Available']);
    $featuredTrucks = $stmtTrucks->fetchAll();

} catch (Exception $e) {
    $dbStatus = 'Connection Failed: ' . $e->getMessage();
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- High-Impact Hero Section -->
<section class="hero">
    <div class="container hero-content">
        <div class="hero-badge">Nigeria's Premier Commercial Truck Dealership &bull; Moal General Suppliers</div>
        <h1 class="hero-title">
            Commercial Truck Supply, Heavy Haulage &amp; <span>Custom Fleet Sourcing</span>
        </h1>
        <p class="hero-description">
            Premium tractor heads, heavy-duty construction tippers, rigid distribution trucks, and specialized carriers — engineered for Nigerian routes, backed by authentic customs documentation, certified mechanical inspection, and nationwide delivery.
        </p>
        <div class="hero-actions">
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary" style="padding: 14px 28px; font-size: 1.05rem;">
                Browse Available Inventory &rarr;
            </a>
            <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-outline" style="border-color: #fff; color: #fff; padding: 14px 24px;">
                3-Question Recommendation Finder
            </a>
        </div>
    </div>
</section>

<!-- Live Stats Counter Strip -->
<section class="stats-strip">
    <div class="container">
        <div class="stats-strip-grid">
            
            <div class="stats-strip-item">
                <div class="stats-number">500+</div>
                <div class="stats-label">Commercial Trucks Delivered</div>
                <div class="stats-sub">Across haulage, mining &amp; distribution</div>
            </div>

            <div class="stats-strip-item">
                <div class="stats-number">100%</div>
                <div class="stats-label">Verified Quality Inspections</div>
                <div class="stats-sub">Authentic customs &amp; title papers</div>
            </div>

            <div class="stats-strip-item">
                <div class="stats-number">36 States</div>
                <div class="stats-label">Nationwide Delivery Network</div>
                <div class="stats-sub">Safe transport directly to your yard</div>
            </div>

            <div class="stats-strip-item">
                <div class="stats-number">24 Hours</div>
                <div class="stats-label">Inquiry &amp; Quote Response</div>
                <div class="stats-sub">Dedicated fleet sales specialists</div>
            </div>

        </div>
    </div>
</section>

<!-- Operational Categories Showcase Grid -->
<section class="section" style="background: #ffffff; border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header" style="text-align: center; max-width: 700px; margin: 0 auto 2.5rem auto;">
            <span class="badge badge-orange" style="margin-bottom: 0.5rem;">Operational Fleet Segments</span>
            <h2 class="section-title">Engineered for Every Commercial Cargo Demand</h2>
            <p class="section-subtitle">Select your operational sector to browse verified commercial vehicles tailored for high-payload reliability.</p>
        </div>

        <div class="category-cards-grid">
            
            <!-- Category 1: Heavy Haulage -->
            <a href="<?php echo BASE_URL; ?>inventory.php?category=Heavy+Haulage" class="category-card">
                <div class="category-card-icon">🚛</div>
                <h3 class="category-card-title">Tractor Heads &amp; Prime Movers</h3>
                <p class="category-card-desc">HOWO, Mercedes-Benz &amp; MAN 6x4 tractor heads for long-distance container freight and interstate heavy haulage.</p>
                <span class="category-card-link">Explore Heavy Haulage &rarr;</span>
            </a>

            <!-- Category 2: Construction & Mining -->
            <a href="<?php echo BASE_URL; ?>inventory.php?category=Construction+%26+Mining" class="category-card">
                <div class="category-card-icon">🏗️</div>
                <h3 class="category-card-title">Construction &amp; Mining Tippers</h3>
                <p class="category-card-desc">Heavy rear-tipping dump trucks with reinforced steel cargo beds for granite, sand, laterite, and quarry excavation.</p>
                <span class="category-card-link">Explore Tippers &rarr;</span>
            </a>

            <!-- Category 3: Distribution & Logistics -->
            <a href="<?php echo BASE_URL; ?>inventory.php?category=Distribution+%26+Logistics" class="category-card">
                <div class="category-card-icon">📦</div>
                <h3 class="category-card-title">Distribution &amp; FMCG Box Bodies</h3>
                <p class="category-card-desc">Enclosed aluminum box trucks and dropsides (Isuzu, Canter) for FMCG goods, retail supply, and intra-city logistics.</p>
                <span class="category-card-link">Explore Distribution &rarr;</span>
            </a>

            <!-- Category 4: Agriculture & Farming -->
            <a href="<?php echo BASE_URL; ?>inventory.php?category=Agriculture+%26+Farming" class="category-card">
                <div class="category-card-icon">🌾</div>
                <h3 class="category-card-title">Agriculture &amp; Bulk Produce Carriers</h3>
                <p class="category-card-desc">High-side bulk cargo trucks with rugged suspensions optimized for grain haulage, agro-allied produce, and rough farm roads.</p>
                <span class="category-card-link">Explore Agriculture &rarr;</span>
            </a>

            <!-- Category 5: Specialized Transport -->
            <a href="<?php echo BASE_URL; ?>inventory.php?category=Specialized+Transport" class="category-card">
                <div class="category-card-icon">⛽</div>
                <h3 class="category-card-title">Specialized Tankers &amp; Cold Chain</h3>
                <p class="category-card-desc">Multi-compartment petroleum/PMS tankers, LPG gas carriers, and refrigerated trucks built to safety standards.</p>
                <span class="category-card-link">Explore Specialized &rarr;</span>
            </a>

        </div>
    </div>
</section>

<!-- Featured Truck Inventory Section -->
<section class="section" id="inventory">
    <div class="container">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem;">
            <div>
                <span class="badge badge-orange" style="margin-bottom: 0.5rem;">Active Dealership Stock</span>
                <h2 class="section-title">Featured Commercial Trucks for Sale</h2>
                <p class="section-subtitle">Real-time inventory dynamically loaded from the MySQL database layer with verified technical specifications.</p>
            </div>
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-navy btn-sm">View Complete Catalogue (<?php echo $totalTrucks; ?>) &rarr;</a>
        </div>

        <div class="inventory-grid">
            <?php if (!empty($featuredTrucks)): ?>
                <?php foreach ($featuredTrucks as $truck): ?>
                    <div class="truck-card">
                        <div class="truck-card-media">
                            <div class="truck-card-badges">
                                <span class="badge badge-orange"><?php echo sanitize_output($truck['purpose_category']); ?></span>
                                <span class="badge badge-navy"><?php echo sanitize_output($truck['year_of_manufacture']); ?></span>
                            </div>

                            <div class="truck-card-status">
                                <span class="badge badge-success">Available</span>
                            </div>

                            <?php 
                                $hasImage = false;
                                if (!empty($truck['primary_image']) && file_exists(UPLOADS_PATH . $truck['primary_image'])) {
                                    $hasImage = true;
                                    $imgSrc = BASE_URL . 'assets/images/trucks/' . $truck['primary_image'];
                                }
                            ?>

                            <?php if ($hasImage): ?>
                                <img src="<?php echo $imgSrc; ?>" alt="<?php echo sanitize_output($truck['title']); ?>" class="truck-card-img">
                            <?php else: ?>
                                <div class="truck-card-placeholder">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M20 8h-3V4H3c-1.1 0-2 .9-2 2v11h2c0 1.66 1.34 3 3 3s3-1.34 3-3h6c0 1.66 1.34 3 3 3s3-1.34 3-3h2v-5l-3-4zM6 18.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zm13.5-9l1.96 2.5H17V9.5h2.5zm-1.5 9c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/>
                                    </svg>
                                    <span style="font-size: 0.75rem; font-weight: 700; color: #cbd5e1;"><?php echo sanitize_output($truck['brand']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="truck-card-body">
                            <div class="truck-card-code"><?php echo sanitize_output($truck['truck_code']); ?></div>
                            <h3 class="truck-card-title"><?php echo sanitize_output($truck['title']); ?></h3>
                            <div class="truck-card-price"><?php echo format_currency($truck['price']); ?></div>

                            <div class="truck-specs-mini">
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Payload</span>
                                    <span class="spec-mini-val"><?php echo format_tonnage($truck['tonnage_capacity']); ?></span>
                                </div>
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Drive</span>
                                    <span class="spec-mini-val"><?php echo sanitize_output($truck['wheel_configuration']); ?></span>
                                </div>
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Trans.</span>
                                    <span class="spec-mini-val"><?php echo sanitize_output($truck['transmission']); ?></span>
                                </div>
                                <div class="spec-mini-item">
                                    <span class="spec-mini-label">Mileage</span>
                                    <span class="spec-mini-val"><?php echo format_mileage($truck['mileage']); ?></span>
                                </div>
                            </div>

                            <div class="truck-card-actions">
                                <a href="<?php echo BASE_URL; ?>truck-details.php?id=<?php echo (int)$truck['id']; ?>" class="btn btn-navy btn-sm">Full Specs</a>
                                <a href="<?php echo BASE_URL; ?>inquiry.php?truck_id=<?php echo (int)$truck['id']; ?>" class="btn btn-primary btn-sm">Inquire</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; padding: 2.5rem; background: #fff; border-radius: var(--radius-md); text-align: center; color: var(--text-muted);">
                    No trucks currently available in the database.
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Rule-Based Recommendation Callout Banner -->
<section class="section" style="background: linear-gradient(135deg, var(--primary-navy-dark) 0%, var(--primary-navy) 100%); color: #fff; border-top: 3px solid var(--accent-orange); border-bottom: 3px solid var(--accent-orange);">
    <div class="container" style="text-align: center; max-width: 820px;">
        <span class="badge badge-warning" style="margin-bottom: 0.75rem;">Rule-Based Operational Decision Logic</span>
        <h2 style="font-size: 2.1rem; font-weight: 800; margin-bottom: 1rem; color: #fff;">
            Find Your Ideal Truck in Exactly 3 Questions
        </h2>
        <p style="color: #cbd5e1; font-size: 1.05rem; line-height: 1.7; margin-bottom: 2rem;">
            Eliminate guesswork. Our rule-based compatibility algorithm compares your intended operational purpose, target budget, and required payload tonnage against active inventory to recommend optimal commercial vehicles.
        </p>
        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-primary" style="padding: 14px 32px; font-size: 1.05rem;">
                Launch 3-Question Recommendation Tool &rarr;
            </a>
            <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="btn btn-outline" style="border-color: #fff; color: #fff; padding: 14px 24px;">
                Submit Custom Sourcing Request
            </a>
        </div>
    </div>
</section>

<!-- "Why Choose Moal General Suppliers" Feature Grid -->
<section class="section" style="background: #ffffff;">
    <div class="container">
        <div class="section-header" style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
            <span class="badge badge-orange" style="margin-bottom: 0.5rem;">Dealership Value &amp; Assurance</span>
            <h2 class="section-title">Why Nigeria's Fleet Operators Choose Moal</h2>
            <p class="section-subtitle">We combine commercial heavy-duty sourcing expertise with transparent pricing, rigorous technical inspection, and verified documentation.</p>
        </div>

        <div class="feature-grid">
            
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3 class="feature-card-title">100% Genuine &amp; Verified Units</h3>
                <p class="feature-card-desc">Every vehicle undergoes chassis, engine, and transmission inspection with verified odometers and zero hidden mechanical defects.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3 class="feature-card-title">Direct &amp; Transparent Pricing</h3>
                <p class="feature-card-desc">No inflated middleman markups. Enjoy competitive commercial pricing with clear invoicing and straightforward transaction terms.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🇳🇬</div>
                <h3 class="feature-card-title">Nationwide Delivery Coverage</h3>
                <p class="feature-card-desc">We deliver acquired commercial trucks safely across all 36 Nigerian states and the FCT with verified transit logistics.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⚙️</div>
                <h3 class="feature-card-title">Engineered for Local Roads</h3>
                <p class="feature-card-desc">Vehicles selected specifically with rugged suspensions, multi-axle configurations, and high-torque engines built for West African terrain.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📜</div>
                <h3 class="feature-card-title">Authentic Customs Papers</h3>
                <p class="feature-card-desc">Complete legal peace of mind with authentic Nigeria Customs Single Goods Declarations (SGD), duty receipts, and clear title ownership.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">👨‍💼</div>
                <h3 class="feature-card-title">Dedicated Fleet Consultants</h3>
                <p class="feature-card-desc">Experienced commercial transport specialists on call to advise on payload capacities, fuel economy, and operational lifecycle costs.</p>
            </div>

        </div>
    </div>
</section>

<!-- Client Testimonials & Industry Reviews Section -->
<section class="section" style="background: #f8fafc; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header" style="text-align: center; max-width: 700px; margin: 0 auto 3rem auto;">
            <span class="badge badge-orange" style="margin-bottom: 0.5rem;">Client Testimonials</span>
            <h2 class="section-title">Trusted by Commercial Transport &amp; Fleet Leaders</h2>
            <p class="section-subtitle">Real experiences from haulage companies, quarry operators, and logistics directors partnering with Moal General Suppliers.</p>
        </div>

        <div class="testimonials-grid">
            
            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-quote">
                    "We purchased two Mercedes Actros 3340 tippers from Moal General Suppliers for our quarry operations in Abuja. The mechanical condition was exactly as advertised, and the vehicles arrived with complete customs papers within 48 hours."
                </p>
                <div class="testimonial-author">
                    <strong>Engr. Babatunde Adeleke</strong>
                    <span>Managing Director, Apex Construction &amp; Aggregates Ltd</span>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-quote">
                    "Finding reliable 10-ton box trucks for our FMCG distribution routes was a major challenge until we worked with Moal. Their rule-based recommendation helped us select the Isuzu Forward, saving us substantial fuel and maintenance costs."
                </p>
                <div class="testimonial-author">
                    <strong>Hajiya Fatima Garba</strong>
                    <span>Head of Logistics, Savannah Prime Distribution Network</span>
                </div>
            </div>

            <div class="testimonial-card">
                <div class="testimonial-stars">★★★★★</div>
                <p class="testimonial-quote">
                    "Moal General Suppliers custom-sourced three 40-ton HOWO tractor heads for our cross-country container haulage. Professional communication, transparent pricing, and zero hidden charges. Highly recommended."
                </p>
                <div class="testimonial-author">
                    <strong>Chief Emeka Nwosu</strong>
                    <span>Operations Director, Trans-Atlantic Haulage Services</span>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Ready to Grow Your Fleet? Final High-Conversion CTA Banner -->
<section class="cta-banner">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
        <div style="max-width: 650px;">
            <h2 style="font-size: 2rem; font-weight: 800; color: #fff; margin-bottom: 0.5rem;">Ready to Expand or Upgrade Your Commercial Fleet?</h2>
            <p style="color: #cbd5e1; font-size: 1.05rem;">Speak with our commercial truck sales specialists today for verified pricing, inspection bookings, or custom vehicle sourcing.</p>
        </div>
        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
            <a href="tel:+2348031234567" class="btn btn-outline" style="border-color: #fff; color: #fff; padding: 12px 24px;">
                📞 +234 803 123 4567
            </a>
            <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-primary" style="padding: 12px 28px;">
                Request Official Quotation &rarr;
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
