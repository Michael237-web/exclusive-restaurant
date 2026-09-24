<?php
require_once __DIR__.'/config.php';

/**
 * Simple mail wrapper using PHP mail().
 * For production, install PHPMailer via Composer or drop the PHPMailer folder in /lib.
 */
function send_mail(string $to, string $subject, string $htmlBody, string $replyTo = ''): bool {
  $headers  = "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
  $headers .= "From: " . setting_site_name() . " <" . FROM_EMAIL . ">\r\n";
  if($replyTo) $headers .= "Reply-To: $replyTo\r\n";

  return @mail($to, $subject, $htmlBody, $headers);
}

function setting_site_name(): string {
  global $pdo;
  return setting($pdo, 'site_name', 'Restaurant');
}

function notify_order_placed(array $order, array $items): void {
  $rows = '';
  foreach($items as $i){
    $rows .= "<tr><td>{$i['name']} × {$i['quantity']}</td><td>" . money($i['price'] * $i['quantity']) . "</td></tr>";
  }
  $html = "
    <h2>Order Received ✓</h2>
    <p>Hi {$order['customer_name']},</p>
    <p>Thank you for your order! Your reference is <strong>{$order['order_ref']}</strong>.</p>
    <table cellpadding='8' style='border-collapse:collapse;border:1px solid #eee'>{$rows}</table>
    <p><strong>Total: " . money($order['total']) . "</strong></p>
    <p>We'll notify you once your order is on the way.</p>
  ";
  if(!empty($order['email'])) send_mail($order['email'], "Order {$order['order_ref']} received", $html);
  send_mail(ADMIN_EMAIL, "New Order {$order['order_ref']}", $html);
}

function notify_reservation(array $r): void {
  $html = "
    <h2>Reservation Confirmed</h2>
    <p>Hi {$r['name']}, your table for {$r['guests']} is booked for <strong>{$r['res_date']} at {$r['res_time']}</strong>.</p>
    <p>We look forward to seeing you!</p>
  ";
  if(!empty($r['email'])) send_mail($r['email'], "Reservation Confirmed", $html);
  send_mail(ADMIN_EMAIL, "New Reservation", $html);
}