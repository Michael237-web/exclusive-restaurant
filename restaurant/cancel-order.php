<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid request']); exit;
}

csrf_verify();

$ref    = trim($_POST['order_ref'] ?? '');
$reason = trim($_POST['cancel_reason'] ?? '');
$notes  = trim($_POST['cancel_notes'] ?? '');

if (!$ref || !$reason) {
    echo json_encode(['ok' => false, 'msg' => 'A cancellation reason is required.']); exit;
}

$st = $pdo->prepare("SELECT * FROM restaurant_orders WHERE order_ref=?");
$st->execute([$ref]);
$order = $st->fetch();

if (!$order) {
    echo json_encode(['ok' => false, 'msg' => 'Order not found.']); exit;
}

if (in_array($order['status'], ['delivered','cancelled','out_for_delivery'], true)) {
    echo json_encode(['ok' => false, 'msg' => 'This order can no longer be cancelled.']); exit;
}

$fullReason = $notes ? ($reason . ' — ' . $notes) : $reason;

$pdo->prepare("UPDATE restaurant_orders
               SET status='cancelled',
                   cancel_reason=?,
                   cancelled_at=NOW()
               WHERE id=?")
    ->execute([$fullReason, $order['id']]);

echo json_encode(['ok' => true, 'ref' => $ref]);