<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';
header('Content-Type: application/json');

if($_SERVER['REQUEST_METHOD'] !== 'POST'){ echo json_encode(['ok'=>false]); exit; }
csrf_verify();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$msg = trim($_POST['message'] ?? '');

if(!$name || !filter_var($email, FILTER_VALIDATE_EMAIL) || !$msg){
  echo json_encode(['ok'=>false,'msg'=>'Invalid input']); exit;
}

$pdo->prepare("INSERT INTO restaurant_contact_messages (name,email,phone,message) VALUES (?,?,?,?)")
    ->execute([$name, $email, $phone, $msg]);

// Notify admin
@mail(ADMIN_EMAIL, 'New Contact Message',
      "From: $name <$email>\nPhone: $phone\n\n$msg",
      "From: " . FROM_EMAIL);

echo json_encode(['ok'=>true]);