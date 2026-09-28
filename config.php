<?php
// ---------------------------------------------------------------
//  Customer Manager - settings
//  Change these to match your own computer / hosting.
// ---------------------------------------------------------------

// Database (XAMPP default: user "root" with an empty password)
define('DB_HOST', 'localhost');
define('DB_NAME', 'customer_manager');
define('DB_USER', 'root');
define('DB_PASS', '');

// Secret text used to encrypt the saved domain / hosting passwords.
// IMPORTANT: change it to your own long random text BEFORE adding real data.
// If you change it later, the passwords already saved can no longer be opened.
define('ENC_KEY', 'change-this-to-a-long-random-secret-text');

define('APP_NAME', 'WEBbuilders.lk');           // name shown in the menu and the browser tab
define('APP_SUBTITLE', 'Customer Manager');
define('APP_TIMEZONE', 'Asia/Kolkata');   // change if your time zone is different
define('CURRENCY', 'Rs.');                // shown before every amount

// false = only the admin can reveal saved passwords. true = staff can also reveal them.
define('STAFF_CAN_VIEW_SECRETS', false);
