<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
require_once __DIR__.'/auth.php';
csrf_verify();

$cart = $_SESSION['cart'] ?? [];
if (!$cart) redirect(BASE_URL.'/menu.php');

$user = current_user();

$name    = trim($_POST['name'] ?? '');
$phone   = trim($_POST['phone'] ?? '');
$email   = trim($_POST['email'] ?? '');
$address = trim($_POST['address'] ?? '');
$type    = $_POST['order_type'] ?? 'delivery';
$branch  = (int)($_POST['branch_id'] ?? 0) ?: null;
$notes   = trim($_POST['notes'] ?? '');
$method  = $_POST['payment_method'] ?? 'cash';

$subtotal = 0;
foreach ($cart as $x) $subtotal += $x['price'] * $x['qty'];
$deliveryFee = $type === 'delivery' ? (int)setting($pdo, 'delivery_fee', 200) : 0;
$total = $subtotal + $deliveryFee;

$ref = generateOrderRef();

$pdo->beginTransaction();
try {
    $st = $pdo->prepare("INSERT INTO restaurant_orders
      (order_ref, user_id, customer_name, phone, email, address, order_type, branch_id,
       subtotal, delivery_fee, total, notes, payment_method, payment_status)
      VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([
        $ref, $user['id'] ?? null, $name, $phone, $email, $address, $type, $branch,
        $subtotal, $deliveryFee, $total, $notes, $method, 'pending'
    ]);
    $orderId = $pdo->lastInsertId();

    $it = $pdo->prepare("INSERT INTO restaurant_order_items
      (order_id, menu_item_id, name, price, quantity) VALUES (?,?,?,?,?)");
    foreach ($cart as $item) {
        $it->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['qty']]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('order failed: '.$e->getMessage());
    die('We could not place your order. Please try again.');
}

$_SESSION['cart'] = [];
$_SESSION['last_order_ref'] = $ref;

if ($method === 'mpesa') {
    redirect(BASE_URL . '/mpesa-stk.php?ref=' . urlencode($ref));
}
redirect(BASE_URL . '/order-success.php?ref=' . urlencode($ref));