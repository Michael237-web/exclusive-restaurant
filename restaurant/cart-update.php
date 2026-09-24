<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
header('Content-Type: application/json');

$id  = (int)($_POST['id'] ?? 0);
$qty = max(0, (int)($_POST['qty'] ?? 1));

if ($qty === 0) unset($_SESSION['cart'][$id]);
elseif (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['qty'] = $qty;

$sub = 0;
foreach ($_SESSION['cart'] ?? [] as $x) $sub += $x['price'] * $x['qty'];
$count = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));

echo json_encode(['ok' => true, 'count' => $count, 'subtotal' => $sub]);