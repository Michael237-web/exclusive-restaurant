<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/csrf.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Invalid method']);
    exit;
}

// Collect inputs
$room_id    = (int)($_POST['room_id'] ?? 0);
$check_in   = $_POST['check_in']  ?? '';
$check_out  = $_POST['check_out'] ?? '';
$guests     = max(1, (int)($_POST['guests'] ?? 1));
$rooms_cnt  = max(1, (int)($_POST['rooms_count'] ?? 1));
$name       = trim($_POST['guest_name']  ?? '');
$email      = trim($_POST['guest_email'] ?? '');
$phone      = trim($_POST['guest_phone'] ?? '');
$notes      = trim($_POST['notes'] ?? '');

// Validation
if (!$room_id || !$check_in || !$check_out || !$name || !$email || !$phone) {
    echo json_encode(['ok' => false, 'error' => 'Please fill in all required fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Please enter a valid email address.']);
    exit;
}
$ci = strtotime($check_in);
$co = strtotime($check_out);
if (!$ci || !$co || $co <= $ci) {
    echo json_encode(['ok' => false, 'error' => 'Check-out must be after check-in.']);
    exit;
}
$nights = (int)round(($co - $ci) / 86400);
if ($nights < 1 || $nights > 60) {
    echo json_encode(['ok' => false, 'error' => 'Stay must be between 1 and 60 nights.']);
    exit;
}

// Load room
$st = $pdo->prepare("SELECT * FROM restaurant_rooms WHERE id=? AND active=1");
$st->execute([$room_id]);
$room = $st->fetch();
if (!$room) {
    echo json_encode(['ok' => false, 'error' => 'Room not found or unavailable.']);
    exit;
}
if ($guests > $room['capacity'] * $rooms_cnt) {
    echo json_encode(['ok' => false, 'error' => 'Too many guests for the selected rooms.']);
    exit;
}

// Availability check — count booked rooms that overlap
$sql = "SELECT COALESCE(SUM(rooms_count), 0) AS booked
        FROM restaurant_room_bookings
        WHERE room_id = ?
          AND status IN ('pending','confirmed')
          AND NOT (check_out <= ? OR check_in >= ?)";
$st = $pdo->prepare($sql);
$st->execute([$room_id, $check_in, $check_out]);
$booked = (int)$st->fetchColumn();

if ($booked + $rooms_cnt > (int)$room['total_units']) {
    $available = max(0, (int)$room['total_units'] - $booked);
    echo json_encode([
        'ok' => false,
        'error' => "Only {$available} room(s) available for those dates."
    ]);
    exit;
}

// Calculate total
$total = $nights * $rooms_cnt * (float)$room['price_per_night'];

// Generate booking reference
$ref = 'RB' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 4));

// Insert
try {
    $sql = "INSERT INTO restaurant_room_bookings
              (booking_ref, room_id, guest_name, guest_email, guest_phone,
               check_in, check_out, guests, rooms_count, nights,
               total_amount, notes, status, payment_status, user_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending','unpaid',?)";
    $st = $pdo->prepare($sql);
    $st->execute([
        $ref, $room_id, $name, $email, $phone,
        $check_in, $check_out, $guests, $rooms_cnt, $nights,
        $total, $notes,
        $_SESSION['user_id'] ?? null
    ]);

    echo json_encode(['ok' => true, 'ref' => $ref, 'total' => $total]);

} catch (PDOException $e) {
    error_log('Room booking failed: '.$e->getMessage());
    echo json_encode(['ok' => false, 'error' => 'We could not save your booking. Please try again.']);
}