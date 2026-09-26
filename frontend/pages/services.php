<?php
/**
 * =============================================================================
 * Moal General Suppliers - Dealership Services & Commercial Solutions
 * =============================================================================
 * Detailed overview of commercial truck sales, sourcing, inspection, fleet advisory,
 * and logistics delivery services.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Our Dealership Services';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo BASE_URL; ?>">Home</a> &rsaquo; <span>Dealership Services</span>
        </div>
        <h1>Commercial Dealership Services &amp; Fleet Solutions</h1>
        <p>Comprehensive commercial vehicle procurement, quality inspection, customs documentation, and fleet advisory.</p>
    </div>
</div>

<div class="container" style="margin-bottom: 4rem;">

    <!-- Intro Value Proposition -->
    <div style="background: #fff; border-radius: var(--radius-lg); border: 1px solid var(--border-color); padding: 2.5rem; margin-bottom: 3rem; box-shadow: var(--shadow-sm); text-align: center; max-width: 900px; margin-left: auto; margin-right: auto;">
        <span class="badge badge-orange" style="margin-bottom: 0.75rem;">End-to-End Heavy Commercial Solutions</span>
        <h2 style="font-size: 1.6rem; color: var(--primary-navy); margin-bottom: 0.75rem;">Driving Your Fleet Operations Forward</h2>
        <p style="color: var(--text-body); font-size: 1.05rem; line-height: 1.7; max-width: 750px; margin: 0 auto 1.5rem auto;">
            Moal General Suppliers bridges the gap between commercial transport demands and verified heavy-duty vehicle supply. 
            Whether you are expanding an interstate logistics fleet, procuring construction tippers, or commissioning specialized fuel tankers, we provide transparent, reliable solutions.
        </p>
        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-primary">Browse Current Inventory</a>
            <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="btn btn-outline">Request Custom Sourcing</a>
        </div>
    </div>

    <!-- 6 Detailed Service Cards Grid (All Listed Directly on Page) -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 2rem; margin-bottom: 3.5rem;">
        
        <!-- Service 1: Truck Sales -->
        <div class="service-detail-card" id="truck-sales">
            <div class="service-icon-box"></div>
            <h3 class="service-card-title">Commercial Truck Sales &amp; Supply</h3>
            <p class="service-card-desc">
                Direct procurement and sale of verified foreign-used and brand-new commercial trucks. Our active inventory includes heavy tractor heads (HOWO, Mercedes-Benz, MAN, Scania), rigid distribution box bodies (Isuzu, Fuso), and multi-axle tippers.
            </p>
            <ul class="service-check-list">
                <li> Multi-brand heavy-duty tractor units and tippers</li>
                <li> Physical yard inspection at Ojodu Berger prior to payment</li>
                <li> Verified vehicle history and honest mileage reports</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>inventory.php" class="btn btn-navy btn-sm" style="margin-top: auto;">Explore Available Trucks &rarr;</a>
        </div>

        <!-- Service 2: Custom Sourcing -->
        <div class="service-detail-card" id="custom-sourcing">
            <div class="service-icon-box"></div>
            <h3 class="service-card-title">Custom Vehicle Sourcing &amp; Procurement</h3>
            <p class="service-card-desc">
                Need a specific axle configuration, rare hydraulic spec, heavy low-bed trailer, or certified cold-chain refrigeration truck? Our procurement specialists source exact units on demand directly through verified international and local distribution networks.
            </p>
            <ul class="service-check-list">
                <li> Sourcing according to precise operational payloads</li>
                <li> Dedicated progress updates and transparent delivery timelines</li>
                <li> Pre-shipment photographic &amp; video inspection reports</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>inquiry.php?type=custom" class="btn btn-primary btn-sm" style="margin-top: auto;">Submit Sourcing Request &rarr;</a>
        </div>

        <!-- Service 3: Fleet Advisory -->
        <div class="service-detail-card" id="fleet-procurement">
            <div class="service-icon-box"></div>
            <h3 class="service-card-title">Fleet Expansion Advisory</h3>
            <p class="service-card-desc">
                We assist haulage firms, agricultural enterprises, and construction contractors in selecting the most cost-effective vehicle models for Nigerian road conditions, ensuring lower lifecycle maintenance costs and maximum payload return.
            </p>
            <ul class="service-check-list">
                <li> Route-specific powertrain and suspension matching</li>
                <li> Fuel-efficiency and spare parts availability analysis</li>
                <li> Rule-based multi-criteria operational evaluation</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>recommend.php" class="btn btn-navy btn-sm" style="margin-top: auto;">Use Find My Truck Tool &rarr;</a>
        </div>

        <!-- Service 4: Mechanical Inspection -->
        <div class="service-detail-card" id="inspection">
            <div class="service-icon-box"></div>
            <h3 class="service-card-title">Mechanical Diagnostics &amp; Inspection</h3>
            <p class="service-card-desc">
                Every vehicle in our catalog undergoes rigorous multi-point technical evaluation before listing. We verify engine compression, gearbox synchros, differential performance, braking systems, and structural chassis integrity.
            </p>
            <ul class="service-check-list">
                <li> Computerized engine &amp; transmission diagnostics</li>
                <li> Heavy-duty hydraulic cylinder and pump pressure testing</li>
                <li> Chassis alignment and structural load tolerance check</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>inquiry.php?type=inspection" class="btn btn-outline btn-sm" style="margin-top: auto;">Inquire on Inspection &rarr;</a>
        </div>

        <!-- Service 5: Documentation & Customs -->
        <div class="service-detail-card" id="documentation">
            <div class="service-icon-box"></div>
            <h3 class="service-card-title">Customs Clearance &amp; Vehicle Registration</h3>
            <p class="service-card-desc">
                We eliminate paperwork uncertainty. All our foreign-used vehicles come with verified Nigeria Customs Service duty receipts, authentic Single Goods Declarations (SGD), and comprehensive change-of-ownership documentation.
            </p>
            <ul class="service-check-list">
                <li> 100% Genuine, verifiable Nigeria Customs duty clearance</li>
                <li> Direct vehicle licensing and registration assistance</li>
                <li> Clear title transfers with no encumbrances</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-navy btn-sm" style="margin-top: auto;">Inquire on Documentation &rarr;</a>
        </div>

        <!-- Service 6: Nationwide Delivery -->
        <div class="service-detail-card" id="delivery">
            <div class="service-icon-box"></div>
            <h3 class="service-card-title">Nationwide Haulage &amp; Delivery Logistics</h3>
            <p class="service-card-desc">
                Once purchased, your truck or prime mover is safely driven or transported directly to your operating facility, mining quarry, agricultural depot, or warehouse yard anywhere in Nigeria with professional logistics tracking.
            </p>
            <ul class="service-check-list">
                <li> Delivery coverage across all 36 Nigerian states &amp; FCT</li>
                <li> Experienced commercial heavy-duty transit drivers</li>
                <li> Comprehensive handover verification upon arrival</li>
            </ul>
            <a href="<?php echo BASE_URL; ?>inquiry.php?type=delivery" class="btn btn-primary btn-sm" style="margin-top: auto;">Inquire on Delivery &rarr;</a>
        </div>

    </div>

    <!-- Contact & Sourcing Callout Banner -->
    <div style="background: linear-gradient(135deg, var(--primary-navy-dark) 0%, var(--primary-navy) 100%); color: #fff; border-radius: var(--radius-lg); border-bottom: 4px solid var(--accent-orange); padding: 3rem 2.5rem; text-align: center;">
        <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 0.75rem;">Need a Tailored Commercial Fleet Solution?</h2>
        <p style="color: #cbd5e1; max-width: 650px; margin: 0 auto 1.75rem auto; font-size: 1rem; line-height: 1.6;">
            Speak directly with our commercial fleet consultants at No. 2 Oluwakemi Street, Ojodu Berger, Lagos, or submit an inquiry online.
        </p>
        <div style="display: flex; justify-content: center; gap: 1rem; flex-wrap: wrap;">
            <a href="tel:<?php echo CONTACT_PHONE_1; ?>" class="btn btn-outline" style="border-color: #fff; color: #fff;"> Call: <?php echo CONTACT_PHONE_1; ?></a>
            <a href="<?php echo BASE_URL; ?>inquiry.php" class="btn btn-primary">Submit Commercial Inquiry &rarr;</a>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
