# Moal General Suppliers — Web-Based Truck Inventory & Customer Inquiry Management System

A web-based commercial truck inventory, multi-criteria recommendation, and customer inquiry management platform developed as a Final-Year Software Engineering Capstone Project.

---

## 🏗️ 3-Tier System Architecture

The codebase follows the classic **3-Tier Software Engineering Architecture**, ensuring a clean separation of concerns between presentation, application logic, and data persistence.

```text
MOAL TRUCK INVENTORY (PROJECT ROOT)
│
├── 📂 Presentation Tier (Frontend / Views & Assets)
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css              # Main responsive public stylesheet
│   │   └── images/
│   │       ├── branding/              # Official Moal logos & vectors
│   │       └── trucks/                # Uploaded commercial vehicle photos
│   ├── includes/
│   │   ├── header.php                 # Global navigation & contact bar
│   │   └── footer.php                 # Global multi-column footer & newsletter
│   ├── index.php                      # Dealership landing homepage
│   ├── about.php                      # About Moal General Suppliers
│   ├── inventory.php                  # Multi-criteria truck catalogue
│   ├── truck-details.php              # Technical specifications view
│   ├── services.php                   # Flat dealership services showcase
│   ├── why-choose-us.php              # 6 pillars of assurance & trust
│   ├── recommend.php                  # "Find My Truck" 3-question tool
│   ├── inquiry.php                    # Lead submission & custom sourcing
│   └── contact.php                    # Ojodu Berger yard contact & map
│
├── 📂 Application & Business Logic Tier (Backend & Admin Portal)
│   ├── includes/
│   │   ├── config.php                 # Global constants & environment setup
│   │   ├── db.php                     # PDO Singleton database connection
│   │   └── functions.php              # XSS defense, CSRF, formatters
│   ├── staff-login.php                # Dedicated staff login portal
│   ├── staff-dashboard.php            # Protected staff dashboard gateway
│   ├── staff-logout.php               # Clean session destruction route
│   ├── newsletter.php                 # Newsletter email capture handler
│   ├── admin/
│   │   ├── auth_check.php             # Route guard middleware
│   │   ├── dashboard.php              # Executive KPI metrics & overview
│   │   ├── trucks.php                 # Inventory table & filtering
│   │   ├── truck-form.php             # Add/Edit vehicle & image uploader
│   │   ├── truck-delete.php           # Vehicle deletion & image cleanup
│   │   ├── inquiries.php              # Customer leads workflow & status tabs
│   │   ├── inquiry-details.php        # Lead inspector, WhatsApp CTA & notes
│   │   ├── newsletter.php             # Captured newsletter subscribers
│   │   ├── profile.php                # Staff profile & password manager
│   │   ├── forgot-password.php        # Password reset token generator
│   │   ├── reset-password.php         # Bcrypt password reset updater
│   │   ├── assets/css/admin.css       # Admin dashboard styles
│   │   └── includes/                  # Admin sidebar header & footer
│   └── scripts/
│       └── reset_password.php         # CLI password recovery tool
│
├── 📂 Data Persistence Tier (Database Layer)
│   ├── database/
│   │   ├── schema/
│   │   │   └── schema.sql             # Table definitions, constraints, indexes
│   │   └── seed/
│   │       └── seed_data.sql          # Demo inventory, admin account, leads
│
├── .gitignore                         # Git exclusion rules
└── README.md                          # Comprehensive documentation
```

---

## 🛠️ Technology Stack

* **Backend Environment:** PHP 8.3+ (Native PDO, Bcrypt Password Hashing, Session Security)
* **Database Management System:** MySQL 8.4+ / MariaDB 10.4+ (InnoDB Engine, UTF8mb4 Unicode)
* **Web Server:** Apache 2.4+ (Laragon Web Server on Windows)
* **Frontend Layer:** Semantic HTML5, Modular CSS3 (Custom Properties & Flexbox/Grid), Vanilla JavaScript
* **Security Layers:** Prepared Statements (SQL Injection defense), HTML Entity Encoding (XSS defense), CSRF Verification Tokens, Session Fixation Regeneration

---

## 🚀 Installation & Local Setup

### 1. Prerequisites
* **Laragon** (or XAMPP/WAMP) with Apache, PHP 8.3, and MySQL 8.4 installed.
* Project located in web root: `C:\laragon\www\moal-truck-inventory\`

### 2. Start Services
1. Open the **Laragon** control panel.
2. Click **Start All** (starts Apache on Port 80 and MySQL on Port 3306).

### 3. Database Initialization
If setting up on a fresh machine:
1. In Laragon, click **Database** (HeidiSQL) or open phpMyAdmin.
2. Create database `moal_truck_db` (or run `database/schema/schema.sql`).
3. Execute `database/seed/seed_data.sql` to populate demo trucks, admin user, and sample inquiries.

---

## 🌐 Complete Application Route Map

### Public Customer Pages
* **Homepage:** `http://localhost/moal-truck-inventory/`
* **About Us:** `http://localhost/moal-truck-inventory/about.php`
* **Our Products (Catalogue):** `http://localhost/moal-truck-inventory/inventory.php`
* **Truck Details:** `http://localhost/moal-truck-inventory/truck-details.php?id=1`
* **Dealership Services:** `http://localhost/moal-truck-inventory/services.php`
* **Why Choose Us:** `http://localhost/moal-truck-inventory/why-choose-us.php`
* **Find My Truck (Recommendation):** `http://localhost/moal-truck-inventory/recommend.php`
* **Inquiry & Sourcing:** `http://localhost/moal-truck-inventory/inquiry.php`
* **Contact Us:** `http://localhost/moal-truck-inventory/contact.php`

### Staff Management Portal
* **Staff Login:** `http://localhost/moal-truck-inventory/staff-login.php`
* **Staff Dashboard:** `http://localhost/moal-truck-inventory/staff-dashboard.php`
* **Staff Logout:** `http://localhost/moal-truck-inventory/staff-logout.php`

---

## 🔐 Staff Portal Credentials

* **Username:** `admin` *(or `Moal4gs@gmail.com`)*
* **Password:** `admin123`
* **Password Reset Tool (CLI):**
  ```bash
  php scripts/reset_password.php admin newpassword123
  ```

---

## 🎓 Academic Defense Highlights

* **No Proprietary Framework Dependency:** Built in pure, clean PHP and PDO MySQL for maximum portability and straightforward code defense.
* **Deterministic Recommendation System:** Uses rule-based multi-criteria matching across Operational Purpose, Budget, and Tonnage (explicitly not AI-dependent).
* **Robust Security:** Zero unescaped outputs, full PDO parameter binding, and multi-step inquiry tracking codes (`INQ-YYYY-XXXX`).
