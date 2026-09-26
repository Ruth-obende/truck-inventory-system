<?php
/**
 * =============================================================================
 * Moal General Suppliers - About Us Dealership Overview
 * =============================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About Our Commercial Truck Dealership';

require_once __DIR__ . '/includes/header.php';
?>

<div class="section" style="padding-top: 2.5rem;">
    <div class="container" style="max-width: 960px;">
        
        <div class="section-header" style="text-align: left; margin-bottom: 2.5rem;">
            <span class="section-tag">Company Background</span>
            <h1 style="font-size: clamp(1.8rem, 3.5vw, 2.4rem); margin-bottom: 0.75rem;">Moal General Suppliers</h1>
            <p class="section-subtitle" style="margin: 0;">
                Supplying robust, duty-cleared commercial transport equipment and heavy-duty trucks to Nigerian haulage operators, construction contractors, and logistics companies.
            </p>
        </div>

        <div style="background: var(--color-white); border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: clamp(2rem, 4vw, 3rem); box-shadow: var(--shadow-sm); margin-bottom: 3rem;">
            <h2 style="font-size: 1.4rem; margin-bottom: 1rem; color: var(--color-dark);">Our Dealership Mission</h2>
            <p style="font-size: 1.05rem; line-height: 1.7; color: var(--color-text); margin-bottom: 1.5rem;">
                At Moal General Suppliers, our commitment is to provide transparent, reliable, and mechanically certified commercial vehicles that power Nigerian industrial commerce. Based in our expansive dealership yard in Ojodu Berger, Lagos, we specialize in heavy-duty tippers, long-haul prime movers, cargo box trucks, and specialized fuel tankers.
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
                <div style="background: var(--color-bg-subtle); padding: 1.5rem; border-radius: var(--radius-sm);">
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--color-dark);">️ Transparent Customs Status</h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-muted); margin: 0;">
                        Every commercial vehicle in our yard is backed by authentic Nigeria Customs Service single goods declarations and duty documentation.
                    </p>
                </div>

                <div style="background: var(--color-bg-subtle); padding: 1.5rem; border-radius: var(--radius-sm);">
                    <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem; color: var(--color-dark);"> 120-Point Inspection</h3>
                    <p style="font-size: 0.9rem; color: var(--color-text-muted); margin: 0;">
                        All engines, transmissions, differentials, chassis beams, and hydraulic systems undergo strict diagnostic checks prior to listing.
                    </p>
                </div>
            </div>
        </div>

        <div style="background: var(--color-dark); color: #FFFFFF; border-radius: var(--radius-lg); padding: clamp(2rem, 4vw, 3rem); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 2rem;">
            <div>
                <h3 style="color: #FFFFFF; font-size: 1.35rem; margin-bottom: 0.5rem;">Looking for Commercial Vehicles?</h3>
                <p style="color: #D1D1CB; font-size: 0.95rem; margin: 0;">
                    Browse our live inventory or test our 3-step recommendation advisor.
                </p>
            </div>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">
                    Explore Inventory &rarr;
                </a>
                <a href="<?php echo BASE_URL; ?>contact.php" class="btn btn-outline-white">
                    Contact Sales Desk
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
