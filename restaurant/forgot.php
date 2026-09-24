<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

// The forgot-password flow now lives inside login.php as a modal.
// Redirect anyone who lands here straight to the login page.
redirect(BASE_URL.'/login.php');