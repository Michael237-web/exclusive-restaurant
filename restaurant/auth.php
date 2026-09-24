<?php
function current_user() {
    if (empty($_SESSION['user_id'])) return null;
    global $pdo;
    static $u = null;
    if ($u) return $u;
    $st = $pdo->prepare("SELECT id,name,email,phone,role,loyalty_points FROM restaurant_users WHERE id=?");
    $st->execute([$_SESSION['user_id']]);
    return $u = ($st->fetch() ?: null);
}

function require_login(): void { if (!current_user()) redirect(BASE_URL.'/login.php'); }

function require_admin(): void {
    $u = current_user();
    if (!$u || $u['role'] !== 'admin') redirect(BASE_URL.'/admin/index.php');
}