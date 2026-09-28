# Development of a Web-Based Inventory and Inquiry Management System

> **A Case Study of MOAL General Suppliers, Ojodu Berger, Lagos, Nigeria**  
> **Final-Year Software Engineering Project & Capstone Defense**  
> **Student Candidate:** Ruth Agimego Obende (`FCP/CSE/22/1011`)  
> **Department:** Department of Software Engineering  
> **Faculty:** Faculty of Computing  
> **Institution:** Federal University Dutse, Jigawa State, Nigeria  
> **Project Supervisor:** Mal. Abdulbasit Nuhu Musa  
> **Academic Session:** 2025 / 2026  

---

## 📋 Project Abstract & Overview

**MOAL General Suppliers** is a premier commercial truck dealership and heavy-duty fleet procurement enterprise situated at No. 2 Oluwakemi Street, Ojodu Berger, Lagos State, Nigeria.

This project delivers a production-grade, secure, web-based platform engineered to solve core operational bottlenecks in the commercial haulage and fleet procurement sector:
1. **Dynamic Heavy Commercial Vehicle Inventory:** Digital cataloging of heavy-duty trucks (HOWO, Shacman, Mercedes-Benz Actros, Mack, Scania, MAN) with verified technical specifications, axle configurations, certified Nigerian Customs Service (SGD) clearance status, and transparent Naira (`₦`) pricing.
2. **Deterministic Recommendation Advisor ("Find My Truck"):** An interactive 3-step decision support wizard matching freight operational requirements, payload tonnage, and operating terrain to appropriate fleet inventory.
3. **Client Self-Service Portal:** Customer onboarding with 6-digit Email OTP verification, customer dashboard, account settings, and a two-way threaded quotation bargaining engine (**"My Quotes"**).
4. **Dealership Staff Operations Portal:** Back-office mission-control dashboard with 4 real-time KPI metrics, visual horizontal bar status analytics (Inventory & Inquiries), a prioritized Action Required operational feed, vehicle CRUD management with photo uploads, and inquiry resolution tracking.

---

## 🏗️ 3-Tier System Architecture

The codebase strictly adheres to the classical **3-Tier Software Engineering Architecture**, guaranteeing separation of concerns between user presentation, business logic processing, and relational data persistence:

```text
MOAL TRUCK INVENTORY SYSTEM
│
├── 📂 Tier 1: Presentation Tier (Frontend / Views & Client UI)
│   ├── frontend/
│   │   ├── pages/                     # Structured client and public page templates
│   │   ├── components/                # Modular view components (header.php, footer.php)
│   │   └── assets/                    # Presentation assets (CSS stylesheets, images, branding)
│   ├── assets/
│   │   ├── css/style.css              # Custom SaaS design system stylesheet
│   │   └── images/                    # Vehicle catalog photos and dealership branding
│   ├── index.php                      # Dealership landing homepage & featured fleet showcase
│   ├── inventory.php                  # Multi-criteria truck catalog with synchronized filters
│   ├── truck-details.php              # Full technical specification sheet & lead capture
│   ├── recommend.php                  # "Find My Truck" 3-step recommendation advisor
│   ├── inquiry.php                    # Direct vehicle inquiry & custom sourcing request form
│   ├── about.php                      # Corporate background & Ojodu Berger yard inspection info
│   ├── contact.php                    # Sales desk contact, hotline, WhatsApp, and location
│   ├── customer-dashboard.php         # Authenticated client portal & "My Quotes" negotiation
│   ├── customer-settings.php          # Client profile, password security, and notifications
│   ├── customer-signup.php            # Client registration with email verification gate
│   ├── customer-login.php             # Client session authentication
│   └── verify-otp.php                 # Cryptographic 6-digit numeric OTP verification
│
├── 📂 Tier 2: Application & Business Logic Tier (Backend Services & Operations)
│   ├── backend/
│   │   ├── admin/                     # Full administrative management suite
│   │   ├── config/config.php          # Database credentials & environment constants
│   │   ├── database/db.php            # Singleton PDO connection service pool
│   │   ├── middleware/                # Route security guards, CSRF, and sanitization
│   │   └── scripts/                   # CLI maintenance & password recovery utilities
│   ├── includes/
│   │   ├── config.php                 # Core configuration, URLs, and dealership settings
│   │   ├── db.php                     # Thread-safe PDO connector (getDB())
│   │   ├── functions.php              # Input sanitization, CSRF tokens, flash messaging
│   │   ├── customer_auth.php          # Client session guards & authentication state
│   │   ├── mailer.php                 # Transactional OTP mailer & local logger
│   │   └── recommendation_engine.php  # Weighted proximity multi-criteria scoring algorithm
│   ├── admin/
│   │   ├── auth_check.php             # Role-based session access guard
│   │   ├── dashboard.php              # Executive Overview with KPIs & action feeds
│   │   ├── trucks.php                 # Inventory catalog manager & status toggles
│   │   ├── truck-form.php             # Vehicle creation/editing & multi-image upload
│   │   ├── inquiries.php              # Inbound customer leads ledger & status workflow
│   │   ├── inquiry-details.php        # Lead inspector, staff notes & client response tool
│   │   ├── requests.php               # Custom fleet sourcing dossier & quote manager
│   │   ├── customers.php              # Commercial client accounts directory
│   │   ├── newsletter.php             # Newsletter subscriber ledger & CSV exporter
│   │   ├── reports.php                # Business reports & inventory valuation printable views
│   │   └── settings.php               # Staff credentials & security management
│   └── newsletter.php                 # Public newsletter subscription capture handler
│
└── 📂 Tier 3: Data Persistence Tier (Relational Database Layer)
    ├── database/
    │   ├── schema.sql                 # Complete DDL tables, foreign keys, constraints
    │   └── seed_data.sql              # Production seed data (trucks, images, users, leads)
    └── database/schema/               # Normalized DDL script storage
```

---

## 🛠️ Technology Stack Specification

| Component | Technology | Specification / Standard |
| :--- | :--- | :--- |
| **Backend Language** | PHP | 8.3+ (Strict Types, Prepared Statements, Bcrypt) |
| **Database Engine** | MySQL / MariaDB | 8.0+ / 10.4+ (InnoDB Engine, `utf8mb4_unicode_ci`) |
| **Database Access** | PHP PDO | Parametrized Prepared Statements (Zero SQL Concatenation) |
| **Web Server** | Apache 2.4 | Windows Environment via Laragon Suite |
| **Frontend Architecture** | HTML5, CSS3, ES6 | Custom Minimalist SaaS Design System (Zero Framework Bloat) |
| **Data Visualization** | Chart.js | 4.4+ (Responsive Canvas Trend & Distribution Charts) |
| **Email & OTP Delivery** | Transactional Mailer | Dual-Mode: SMTP Production / Local Logger (`logs/emails.log`) |

---

## 🔒 Defensive Security Architecture

1. **SQL Injection Defense:** Strict use of PDO prepared statements with positional parameters (`?` or `:named`). Dynamic SQL string concatenation is strictly prohibited across all database queries.
2. **Cross-Site Request Forgery (CSRF):** Cryptographically randomized per-session tokens generated via `bin2hex(random_bytes(32))` and validated using constant-time comparison (`hash_equals()`).
3. **Password Hashing:** Passwords for both commercial clients and dealership staff are hashed using PHP's native `password_hash($password, PASSWORD_BCRYPT)`.
4. **Cross-Site Scripting (XSS):** All dynamic browser outputs are sanitized with `htmlspecialchars($input, ENT_QUOTES, 'UTF-8')`.
5. **Authentication Route Guards:** Administrative views enforce strict session verification via `admin/auth_check.php`; customer areas require `customer_auth.php`.
6. **Session Regeneration:** Active sessions execute `session_regenerate_id(true)` upon successful credential verification to prevent session fixation.

---

## 🚀 Local Installation & Quickstart

### Prerequisites
* **Laragon** (or XAMPP/WAMP) with Apache 2.4+, PHP 8.3+, and MySQL 8.0+
* Web root directory: `C:\laragon\www\moal-truck-inventory\`

### Step-by-Step Setup
1. **Clone or Copy Repository:**
   ```bash
   git clone https://github.com/Ruth-obende/truck-inventory-system.git C:\laragon\www\moal-truck-inventory
   ```
2. **Start Web Server & Database:**
   - Launch Laragon and click **Start All** (Apache on Port 80, MySQL on Port 3306).
3. **Import Database Schema & Seed Data:**
   - Open HeidiSQL, phpMyAdmin, or MySQL CLI:
   ```sql
   CREATE DATABASE IF NOT EXISTS moal_truck_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE moal_truck_db;
   SOURCE C:/laragon/www/moal-truck-inventory/database/schema.sql;
   SOURCE C:/laragon/www/moal-truck-inventory/database/seed_data.sql;
   ```
4. **Access Application:**
   - **Public Storefront:** `http://localhost/moal-truck-inventory/`
   - **Client Login:** `http://localhost/moal-truck-inventory/customer-login.php`
   - **Staff Operations Portal:** `http://localhost/moal-truck-inventory/admin/`

---

## 🔐 System Login Credentials

### Dealership Staff Portal
* **URL:** `http://localhost/moal-truck-inventory/admin/` (or `http://localhost/moal-truck-inventory/staff-login.php`)
* **Username:** `admin` *(or `Moal4gs@gmail.com`)*
* **Password:** `admin123`

### Demo Client Account
* **URL:** `http://localhost/moal-truck-inventory/customer-login.php`
* **Email:** `client@example.com`
* **Password:** `client123`
*(Or use `customer-signup.php` to test real-time 6-digit OTP registration; OTP codes are logged to `logs/emails.log` for offline evaluation).*

---

## 📄 Academic Project Defense Notes
* **Deterministic Matching:** The recommendation advisor employs a weighted multi-criteria scoring algorithm (50% Operational Purpose, 30% Budget Proximity, 20% Payload Capacity) rather than black-box AI, making it completely auditable for defense.
* **Synchronized Query Builder:** The inventory search engine uses a synchronized conditional query pipeline preventing parameter count mismatches (`PDOException: HY093`).
* **Complete Test Coverage:** All 10 verification test cases in the project documentation pass with 100% compliance.
