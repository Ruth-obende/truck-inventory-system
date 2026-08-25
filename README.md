# Moal General Suppliers - Truck Inventory and Customer Inquiry Management System

## Project Overview
**Official Academic Title:**  
*Design and Development of a Web-Based Truck Inventory and Customer Inquiry Management System for Truck Dealerships: A Case Study of Moal General Suppliers*

This system serves as a full-stack web platform designed to streamline truck dealership operations. It provides potential buyers with an intuitive inventory catalog, search and filtering tools, a rule-based recommendation workflow, and inquiry/custom request submission. For dealership management, the system provides a secure administrative dashboard to manage truck inventory, specifications, photography, and track customer inquiries.

---

## Three-Tier Architecture
The project strictly follows a classic **3-Tier Software Architecture**:

1. **Presentation Layer (Client-Side)**
   * Semantic HTML5, Modular CSS3 (Dark Navy, Orange, White theme), Vanilla JavaScript.
   * Mobile-responsive layout, accessible interactive forms, and dynamic UI filtering.

2. **Application Layer (Server-Side Logic)**
   * PHP 8.x Object-Oriented and Procedural modules.
   * Business logic handlers, rule-based recommendation matching algorithms, secure session management, input validation, and role-based access control.

3. **Database Layer (Data Persistence)**
   * MySQL / MariaDB relational database.
   * Parameterized PDO queries to prevent SQL Injection, structured relational schemas, indexing, and foreign key constraints.

---

## Directory Structure
```text
moal-truck-inventory/
│
├── assets/                  # Public static assets
│   ├── css/                 # Global and component stylesheets
│   ├── js/                  # Client-side scripts and validation
│   └── images/              # Media assets
│       ├── branding/        # Moal General Suppliers logos and icons
│       └── trucks/          # Uploaded truck inventory photographs
│
├── includes/                # Server-side business logic and utilities
│   ├── config.php           # Global application constants and settings
│   ├── db.php               # PDO database connection handler
│   ├── functions.php        # Helper functions (sanitization, formatting, CSRF)
│   ├── header.php           # Common public header/navigation template
│   └── footer.php           # Common public footer template
│
├── admin/                   # Dealership Administrative Management Area
│   ├── assets/              # Admin-specific CSS and JS
│   ├── dashboard.php        # Inventory and inquiry summary metrics
│   ├── login.php            # Secure admin authentication
│   └── ...                  # Truck and inquiry management modules
│
├── database/                # Database definitions and seed scripts
│   ├── schema.sql           # Table definitions, indices, and constraints
│   └── seed_data.sql        # Demo truck inventory and initial admin seed
│
├── index.php                # System homepage / entry point
└── README.md                # Project documentation
```

---

## Technology Stack
* **Frontend:** HTML5, CSS3, JavaScript (ES6+)
* **Backend:** PHP 8.3+
* **Database:** MySQL 8.4+ / MariaDB via PHP Data Objects (PDO)
* **Local Server:** Laragon (Apache 2.4 + MySQL 8.4)
* **Version Control:** Git & GitHub
