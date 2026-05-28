# Umutekano Logistics

## Overview
This is a PHP logistics management system for admin, driver, and customer users. It is built for XAMPP / Apache + MySQL and uses a MySQL database dump stored in `database/umutekano.sql`.

## Requirements
- PHP 7.4 or later
- MySQL / MariaDB
- Apache (XAMPP recommended)
- Web browser

## Setup
1. Place the project folder in your XAMPP `htdocs` directory.
2. Start Apache and MySQL from the XAMPP control panel.
3. Import the database:
   - Open phpMyAdmin at `http://localhost/phpmyadmin`
   - Create or import the `database/umutekano.sql` file.
   - The dump creates the `umutekano_logistics` database and seed admin user.

Alternatively, import via command line:
```powershell
cd "C:\xampp\htdocs\Umutekano Logistics\database"
mysql -u root < umutekano.sql
```

## Access the app
Open your browser and go to:
```text
http://localhost/Umutekano%20Logistics/
```

## Key Features
### Core Features
- Shipment & order management: create, assign, track, and bulk import deliveries
- Real-time package tracking: GPS updates, milestone timeline, tracking lookup and stage notifications
- Customer portal: registration, delivery requests, order history, invoices, and POD download
- Driver & fleet management: driver profiles, assignments, vehicle registration and maintenance logs
- Route planning & optimization: efficient routing, multi-stop scheduling, distance and cost estimation

### Business & Admin Features
- Dashboard & analytics: total deliveries, revenue, on-time rate, charts and failed delivery reports
- Pricing & quotes: instant quote calculator, custom business rates and invoice generation
- Warehouse / inventory management: goods intake, dispatch scheduling and stock condition reporting
- Notifications & alerts: delayed shipment alerts, driver check-ins, delivery confirmation updates

### Supporting Features
- User roles & permissions: admin, operations manager, driver and customer access control
- Reports & exports: PDF/Excel exports for deliveries, revenue, drivers and performance metrics
- Support & feedback: in-app chat or ticketing, customer delivery rating and review capabilities

### Recommended Build Phases
- Phase 1 — MVP: order creation, package tracking, customer portal, driver assignment, basic notifications
- Phase 2 — Growth: admin analytics, route optimization, alerts and reports, user roles
- Phase 3 — Scale: warehouse management, pricing engine, advanced reports, support system and export tools

## Default admin login
- Email: `admin@umutekano.com`
- Password: `admin123`
1. Login & Registration
2. Customer Booking
3. Package Tracking
4. Admin Dashboard
5. Driver Assignment
6. Vehicle Management
7. Payment System
8. Notifications
9. Reports & Analytics
10. GPS Tracking

## Default admin login
- Email: `admin@umutekano.com`
- Password: `admin123`

## Notes
- The app now uses a dynamic `BASE_URL` value, so it works from subfolders or if the project is moved to a different local path.
- If the app still fails to load static assets, check that XAMPP is serving the project from the correct folder and that `assets/css/style.css` exists.
- For production deployment, replace the mobile money simulation logic in `includes/config.php` with real API integrations.
- Remove `fix_admin.php` after you reset the admin password to avoid leaving a utility file on a live site.

## Hosting / Production Deployment
1. Upload the project files to your web host's PHP-enabled document root.
2. Create a MySQL database and user.
3. Import `database/umutekano.sql` into the new database.
4. Update `includes/config.php` with the live database credentials.
5. If your live site is not in the web root, set `BASE_URL` manually in `includes/config.php` or rely on the dynamic detection.
6. Test the site by opening the live domain and logging in as the seeded admin.
