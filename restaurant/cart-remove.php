<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
unset($_SESSION['cart'][$id]);

$count = array_sum(array_column($_SESSION['cart'] ?? [], 'qty'));
echo json_encode(['ok' => true, 'count' => $count]);