<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$body = file_get_contents('php://input');
$data = json_decode($body, true);

$callback = $data['Body']['stkCallback'] ?? null;
if(!$callback){ http_response_code(200); echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Ignored']); exit; }

$checkoutId = $callback['CheckoutRequestID'] ?? '';
$resultCode = $callback['ResultCode'] ?? 1;
$items      = $callback['CallbackMetadata']['Item'] ?? [];
$mpesaReceipt = null;
foreach($items as $i){
  if($i['Name'] === 'MpesaReceiptNumber') $mpesaReceipt = $i['Value'];
}

$status = ($resultCode == 0) ? 'paid' : 'failed';

$pdo->prepare("UPDATE restaurant_orders SET payment_status=?, payment_ref=? WHERE payment_ref=?")
    ->execute([$status, $mpesaReceipt ?: $checkoutId, $checkoutId]);

http_response_code(200);
echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'OK']);