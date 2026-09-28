WEBbuilders.lk  CUSTOMER MANAGER  -  Website / Domain / Hosting management system
==================================================================
Built with HTML, CSS, JavaScript, PHP and MySQL.

WHAT IT DOES
  - Login (admin + staff)
  - Staff: name, year, join date, exit date, user name, password
  - Customers: name, website, domain, domain login link / user name / password /
    authentication ID, "maintenance by me"
  - Hosting: customer, provider, login URL, user name, password, "maintenance by me"
  - Web plans: join date, amount, duration (shows the end date automatically)
  - Backups: join date, amount
  - Payments: year-wise, paid / pending / overdue
  - Dashboard: money waiting, money collected this year, plans ending soon
  - Domain / hosting passwords are saved ENCRYPTED (AES-256) in the database.

HOW TO RUN ON YOUR COMPUTER (XAMPP)
  1. Install XAMPP (PHP 7.4 or newer). Start "Apache" and "MySQL" in the XAMPP panel.
  2. Copy the whole "customer-manager" folder into:  C:\xampp\htdocs\
  3. Open  http://localhost/phpmyadmin  ->  click "Import"  ->  choose
     customer-manager/database/database.sql  ->  click "Go".
  4. Open  config.php  and:
       - check DB_USER / DB_PASS (XAMPP default: root and empty password)
       - change ENC_KEY to your own long random text  (do this BEFORE saving real passwords)
  5. Open  http://localhost/customer-manager/
     The first time, it asks you to create the admin account. After that, log in.

HOW TO PUT IT ON REAL HOSTING
  - Upload the folder, create a MySQL database + user in your hosting panel,
    import database/database.sql (delete the "CREATE DATABASE" and "USE" lines at the top
    if your host gives you a ready-made database), and put the real
    DB_HOST / DB_NAME / DB_USER / DB_PASS in config.php.
  - Use HTTPS on real hosting because passwords are typed into the forms.

CHANGE THE COLOURS
  Open assets/css/style.css. All colours are at the top inside :root { ... }.

FOLDER GUIDE
  config.php            settings
  login.php / setup.php / logout.php / dashboard.php
  customers.php hosting.php web-plans.php backups.php payments.php staff.php
  includes/modules.php  the fields of every page (add or rename fields here)
  includes/crud.php     add / edit / delete logic
  assets/css/style.css  design        assets/js/app.js  page behaviour
  database/database.sql database tables

Roles: Admin can do everything. Staff can add and edit records, but cannot delete,
cannot open the Staff page, and cannot reveal saved passwords
(change STAFF_CAN_VIEW_SECRETS in config.php if you want staff to see them).
