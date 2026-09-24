<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST','localhost');
define('DB_NAME','restaurant_db');
define('DB_USER','root');
define('DB_PASS','');
define('BASE_URL','/restaurant');          // <-- URL prefix (still fine)
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

define('MPESA_ENV','sandbox');
define('MPESA_CONSUMER_KEY','YOUR_KEY');
define('MPESA_CONSUMER_SECRET','YOUR_SECRET');
define('MPESA_SHORTCODE','174379');
define('MPESA_PASSKEY','YOUR_PASSKEY');
define('MPESA_CALLBACK', 'https://yourdomain.co.ke' . BASE_URL . '/mpesa-callback.php');

define('ADMIN_EMAIL','admin@restaurant.com');
define('FROM_EMAIL','noreply@restaurant.com');

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Nairobi');