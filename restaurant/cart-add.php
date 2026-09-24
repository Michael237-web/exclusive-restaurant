<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if (!$id) { echo json_encode(['ok' => false]); exit; }

$st = $pdo->prepare("SELECT id,name,price,image FROM restaurant_menu_items WHERE id=? AND available=1");
$st->execute([$id]);
$item = $st->fetch();
if (!$item) { echo json_encode(['ok' => false, 'msg' => 'Item unavailable']); exit; }

if (empty($_SESSION['cart'])) $_SESSION['cart'] = [];

if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['qty']++;
} else {
    $_SESSION['cart'][$id] = [
        'id'    => $item['id'],
        'name'  => $item['name'],
        'price' => $item['price'],
        'image' => $item['image'],   // ← IMPORTANT
        'qty'   => 1,
    ];
}

$count = array_sum(array_column($_SESSION['cart'], 'qty'));
echo json_encode(['ok' => true, 'count' => $count]);