<?php
/**
 * =============================================================================
 * Moal General Suppliers - Contact & Ojodu Berger Yard Location
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact | Moal General Suppliers';

require_once __DIR__ . '/includes/header.php';
?>

<!-- CONTACT PAGE VISUAL HERO HEADER (HIGH-QUALITY TRUCK BACKGROUND) -->
<section class="contact-page-hero">
    <div class="container">
        <span class="section-tag">Direct Communication</span>
        <h1>Contact</h1>
        <p>
            Reach our commercial vehicle sales consultants directly, check current truck stock, or arrange a physical yard inspection in Lagos.
        </p>
    </div>
</section>

<!-- CONTACT DETAILS & YARD SHOWCASE SECTION -->
<section class="section" style="padding-top: 0; padding-bottom: 4rem;">
    <div class="container" style="max-width: 1080px;">
        
        <!-- Quick Contact Channels (Call, WhatsApp, Yard Office) -->
        <div class="landing-contact-grid" style="margin-bottom: 2rem;">
            <!-- 1. Call Sales Desk -->
            <div class="landing-contact-card" style="background: var(--color-white); border: 1px solid var(--color-border); color: var(--color-dark); box-shadow: var(--shadow-sm);">
                <div class="landing-contact-card-top">
                    <div class="landing-contact-icon-wrapper" aria-hidden="true" style="background: rgba(217, 130, 91, 0.12); color: var(--color-primary); border-color: rgba(217, 130, 91, 0.3);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="landing-contact-card-label" style="color: var(--color-text-muted);">Direct Sales Hotlines</div>
                    <h3 class="landing-contact-card-heading" style="color: var(--color-dark);">Call Sales</h3>
                </div>
                <div class="landing-contact-body">
                    <div class="landing-contact-phone-list">
                        <a href="tel:07069219001" class="landing-contact-phone-link" style="color: var(--color-dark);" title="Call primary sales line">
                            07069219001
                        </a>
                        <a href="tel:08151111181" class="landing-contact-phone-link secondary-phone" style="color: var(--color-text-muted);" title="Call secondary sales line">
                            08151111181
                        </a>
                    </div>
                    <a href="tel:07069219001" class="landing-contact-action-btn call-btn" style="background: var(--color-primary); color: #FFFFFF; border: none;">
                        Call Sales Desk &rarr;
                    </a>
                </div>
            </div>

            <!-- 2. WhatsApp Desk -->
            <div class="landing-contact-card" style="background: var(--color-white); border: 1px solid var(--color-border); color: var(--color-dark); box-shadow: var(--shadow-sm);">
                <div class="landing-contact-card-top">
                    <div class="landing-contact-icon-wrapper whatsapp-icon-wrapper" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.311.045-.698.034-.946-.027-.234-.057-.52-.162-.897-.321-1.61-.678-2.661-2.327-2.742-2.434-.082-.107-.655-.873-.655-1.666 0-.793.414-1.185.561-1.346.147-.161.321-.202.428-.202.107 0 .214.002.308.006.099.005.231-.037.361.275.134.321.458 1.115.498 1.196.04.081.067.176.013.283-.053.107-.08.175-.16.269-.08.093-.169.208-.242.279-.081.08-.166.166-.071.328.094.161.42 1.055 1.218 1.48.243.13.435.185.586.233.245.078.468.067.644.041.196-.029.606-.247.692-.486.086-.239.086-.444.06-.486-.027-.042-.098-.068-.205-.121z"/>
                        </svg>
                    </div>
                    <div class="landing-contact-card-label" style="color: var(--color-text-muted);">Instant Chat &amp; Inquiries</div>
                    <h3 class="landing-contact-card-heading" style="color: var(--color-dark);">WhatsApp</h3>
                </div>
                <div class="landing-contact-body">
                    <div class="landing-contact-phone-list">
                        <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20inquire%20about%20your%20commercial%20trucks." target="_blank" rel="noopener noreferrer" class="landing-contact-phone-link" style="color: var(--color-dark);" title="Open WhatsApp chat">
                            07069219001
                        </a>
                    </div>
                    <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20inquire%20about%20your%20commercial%20trucks." target="_blank" rel="noopener noreferrer" class="landing-contact-action-btn whatsapp-action-btn">
                        Chat on WhatsApp &rarr;
                    </a>
                </div>
            </div>

            <!-- 3. Lagos Office Address -->
            <div class="landing-contact-card" style="background: var(--color-white); border: 1px solid var(--color-border); color: var(--color-dark); box-shadow: var(--shadow-sm);">
                <div class="landing-contact-card-top">
                    <div class="landing-contact-icon-wrapper" aria-hidden="true" style="background: rgba(217, 130, 91, 0.12); color: var(--color-primary); border-color: rgba(217, 130, 91, 0.3);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="landing-contact-card-label" style="color: var(--color-text-muted);">Physical Yard &amp; Office</div>
                    <h3 class="landing-contact-card-heading" style="color: var(--color-dark);">Lagos Office</h3>
                </div>
                <div class="landing-contact-body">
                    <address class="landing-contact-address-text" style="color: var(--color-dark);">
                        No. 2 Oluwakemi Street, Ojodu Berger, Lagos State, Nigeria
                    </address>
                    <p class="landing-contact-address-meta">
                        Commercial Vehicle Yard &amp; Fleet Inspection Center
                    </p>
                </div>
            </div>
        </div>

        <!-- Physical Yard & Dealership Photo Showcase Card (Responsive Image & Write-Up) -->
        <div class="contact-yard-showcase" style="margin-bottom: 2rem;">
            <div class="contact-yard-grid">
                <!-- Left: Full Yard Details & Write-up -->
                <div class="contact-yard-info">
                    <span class="badge badge-primary" style="margin-bottom: 0.75rem; align-self: flex-start;">Physical Dealership Yard</span>
                    <h2 style="font-size: clamp(1.35rem, 2.2vw, 1.8rem); color: var(--color-dark); margin-bottom: 1rem;">
                        Ojodu Berger Dealership Yard &amp; Inspection Center
                    </h2>
                    <p style="font-size: 0.98rem; line-height: 1.65; color: var(--color-text); margin-bottom: 1.25rem;">
                        Moal General Suppliers operates an active physical truck yard in Ojodu Berger, Lagos. We believe in complete transparency: every commercial tipper, tractor head, and rigid cargo truck in our inventory is stationed at our yard with authentic Nigeria Customs Single Goods Declaration (SGD) papers.
                    </p>
                    <p style="font-size: 0.95rem; line-height: 1.6; color: var(--color-text-muted); margin-bottom: 1.25rem;">
                        Prospective buyers and fleet managers are encouraged to visit with their personal mechanics and technicians for pre-purchase diagnostics and test runs before completing any transaction.
                    </p>
                    
                    <div class="contact-yard-details-list">
                        <div class="contact-yard-detail-item">
                            <span class="detail-label">📍 Physical Yard Location:</span>
                            <span class="detail-value">No. 2 Oluwakemi Street, Ojodu Berger, Lagos State, Nigeria</span>
                        </div>
                        <div class="contact-yard-detail-item">
                            <span class="detail-label">🕒 Operating Hours:</span>
                            <span class="detail-value">Monday to Saturday: 8:00 AM – 6:00 PM (Closed Sundays)</span>
                        </div>
                        <div class="contact-yard-detail-item">
                            <span class="detail-label">📞 Direct Phone Lines:</span>
                            <span class="detail-value">07069219001 &bull; 08151111181</span>
                        </div>
                        <div class="contact-yard-detail-item">
                            <span class="detail-label">✉️ Official Email:</span>
                            <span class="detail-value"><a href="mailto:Moal4gs@gmail.com" style="color: var(--color-primary); font-weight: 600;">Moal4gs@gmail.com</a></span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 1.5rem;">
                        <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20schedule%20a%20yard%20inspection." target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">
                            Schedule Yard Inspection on WhatsApp &rarr;
                        </a>
                        <a href="tel:07069219001" class="btn btn-secondary">
                            Call Sales Desk
                        </a>
                    </div>
                </div>

                <!-- Right: Dealership Yard Photograph (Responsive Fitting) -->
                <div class="contact-yard-photo-card">
                    <div class="contact-yard-img-wrapper">
                        <img src="<?php echo BASE_URL; ?>assets/images/branding/moal_contact_yard.jpg" alt="Moal General Suppliers Commercial Truck Yard - Ojodu Berger, Lagos" class="contact-yard-img" loading="lazy">
                        <div class="contact-yard-img-badge">
                            <span class="pulse-dot"></span> Ojodu Berger Dealership Yard &bull; Lagos
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Operational Summary Card -->
        <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: 12px; padding: 1.75rem 2rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1.5rem; box-shadow: var(--shadow-sm);">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(217, 130, 91, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <strong style="color: var(--color-dark); font-size: 0.95rem; display: block;">Yard Operating Schedule</strong>
                    <span style="color: var(--color-text-muted); font-size: 0.88rem;">Monday to Saturday: 8:00 AM to 6:00 PM</span>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 44px; height: 44px; border-radius: 8px; background: rgba(217, 130, 91, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <div>
                    <strong style="color: var(--color-dark); font-size: 0.95rem; display: block;">Official Dealership Email</strong>
                    <a href="mailto:Moal4gs@gmail.com" style="color: var(--color-primary); font-size: 0.88rem; font-weight: 600; text-decoration: none;">Moal4gs@gmail.com</a>
                </div>
            </div>

            <a href="https://wa.me/2347069219001?text=Hello%20Moal%20General%20Suppliers,%20I%20would%20like%20to%20schedule%20a%20yard%20inspection." target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp">
                Schedule Yard Inspection on WhatsApp &rarr;
            </a>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
