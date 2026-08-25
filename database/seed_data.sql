-- =============================================================================
-- Moal General Suppliers - Seed Data (DEMO / DEVELOPMENT PURPOSES ONLY)
-- Note: All truck data below is synthetic sample data for development and testing.
-- Real dealership inventory data will replace this as provided by Moal General Suppliers.
-- =============================================================================

USE `moal_truck_db`;

-- -----------------------------------------------------------------------------
-- 1. Default Super Administrator Account
-- Username: admin
-- Default Dev Password: Admin@Moal2026
-- -----------------------------------------------------------------------------
INSERT INTO `admins` (`username`, `password_hash`, `full_name`, `email`, `role`, `status`)
VALUES 
('admin', '$2y$10$cO1OwKPwhS2vrUZP3odZzenNuaV2Rx0.he.fl0uUjSq0tMhAAWhy6', 'Dealership Administrator', 'admin@moalsuppliers.com', 'administrator', 'active')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);

-- -----------------------------------------------------------------------------
-- 2. Sample Demo Trucks Inventory
-- -----------------------------------------------------------------------------
INSERT INTO `trucks` (
    `id`, `truck_code`, `title`, `brand`, `model`, `year_of_manufacture`, 
    `price`, `mileage`, `tonnage_capacity`, `transmission`, `fuel_type`, 
    `engine_power_hp`, `wheel_configuration`, `condition_type`, 
    `purpose_category`, `availability_status`, `featured`, `description`
) VALUES
(
    1, 'MOAL-DEMO-001', 'Mercedes-Benz Actros 3340 6x4 Heavy Tipper', 
    'Mercedes-Benz', 'Actros 3340', 2018, 48500000.00, 142000, 30.00, 
    'Manual', 'Diesel', 400, '6x4', 'Foreign Used', 
    'Construction & Mining', 'Available', 1, 
    'Heavy-duty tipper truck with reinforced steel body, optimized for granite, sand, and excavation transport. Fully inspected transmission and hydraulic lift.'
),
(
    2, 'MOAL-DEMO-002', 'Sinotruk HOWO 371 6x4 Tractor Head', 
    'HOWO', '371', 2020, 42000000.00, 98000, 40.00, 
    'Manual', 'Diesel', 371, '6x4', 'Foreign Used', 
    'Heavy Haulage', 'Available', 1, 
    'Powerful long-distance haulage prime mover with double sleeper cabin, high-torque engine, and heavy-duty fifth wheel coupling.'
),
(
    3, 'MOAL-DEMO-003', 'Isuzu Forward FTR 10-Ton Box Body Truck', 
    'Isuzu', 'FTR 850', 2019, 26000000.00, 115000, 10.00, 
    'Manual', 'Diesel', 240, '4x2', 'Foreign Used', 
    'Distribution & Logistics', 'Available', 1, 
    'Enclosed aluminum cargo body ideal for FMCG, dry food distribution, and urban/interstate freight forwarding.'
),
(
    4, 'MOAL-DEMO-004', 'MAN TGS 33.440 6x4 Timber & Ag Bulk Carrier', 
    'MAN', 'TGS 33.440', 2017, 39500000.00, 175000, 26.00, 
    'Automatic', 'Diesel', 440, '6x4', 'Foreign Used', 
    'Agriculture & Farming', 'Available', 0, 
    'Rugged chassis with high-side dropside body designed for grain transport, agro-allied produce, and rough-terrain farm access.'
),
(
    5, 'MOAL-DEMO-005', 'Scania P380 6x4 20,000L Fuel Tanker', 
    'Scania', 'P380', 2016, 52000000.00, 190000, 22.00, 
    'Manual', 'Diesel', 380, '6x4', 'Foreign Used', 
    'Specialized Transport', 'Available', 1, 
    'Multi-compartment aluminum fuel tanker with certified bottom-loading valves, vapor recovery, and emergency shut-off systems.'
),
(
    6, 'MOAL-DEMO-006', 'Mitsubishi Fuso Canter 4.5-Ton Dropside', 
    'Mitsubishi', 'Canter FE', 2021, 17500000.00, 62000, 4.50, 
    'Manual', 'Diesel', 150, '4x2', 'Foreign Used', 
    'Distribution & Logistics', 'Available', 0, 
    'Agile light commercial truck perfect for intra-city supply routes, light building materials, and small-business cargo.'
)
ON DUPLICATE KEY UPDATE `truck_code` = VALUES(`truck_code`);

-- -----------------------------------------------------------------------------
-- 3. Sample Demo Inquiries
-- -----------------------------------------------------------------------------
INSERT INTO `inquiries` (
    `inquiry_code`, `truck_id`, `inquiry_type`, `customer_name`, 
    `customer_email`, `customer_phone`, `message`, `status`
) VALUES
(
    'INQ-DEMO-001', 1, 'Specific Truck', 'Alhaji Ibrahim Danladi',
    'i.danladi@samplemail.com', '+2348031234567',
    'Hello, I would like to schedule a physical inspection for the Mercedes Actros 3340 Tipper this Thursday.',
    'Pending'
),
(
    'INQ-DEMO-002', NULL, 'Custom Request', 'Grace Okon Logistics',
    'g.okon@samplelogistics.ng', '+2348029876543',
    'We are looking for two 15-ton refrigerated trucks with thermo-king units for meat and dairy distribution. Budget is 30M NGN each.',
    'In Review'
)
ON DUPLICATE KEY UPDATE `inquiry_code` = VALUES(`inquiry_code`);
