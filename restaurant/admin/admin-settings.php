<?php
require_once __DIR__.'/admin-auth.php';
require_once __DIR__.'/csrf.php';
$page_title = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $allowed = ['site_name','tagline','phone','whatsapp','email','address','delivery_fee','currency','meta_description'];
    $st = $pdo->prepare("REPLACE INTO restaurant_settings (k,v) VALUES (?,?)");
    foreach ($allowed as $k) {
        if (isset($_POST[$k])) $st->execute([$k, trim($_POST[$k])]);
    }
    flash('success', 'Settings saved.');
    redirect(BASE_URL.'/admin-settings.php');
}

$settings = [];
$rows = $pdo->query("SELECT k,v FROM restaurant_settings")->fetchAll();
foreach ($rows as $r) $settings[$r['k']] = $r['v'];

require __DIR__.'/admin-header.php';
?>

<form method="post" class="checkout-form" style="max-width:600px">
  <?= csrf_field() ?>
  <label>Site Name <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? '') ?>"></label>
  <label>Tagline <input type="text" name="tagline" value="<?= e($settings['tagline'] ?? '') ?>"></label>
  <label>Meta Description <textarea name="meta_description" rows="2"><?= e($settings['meta_description'] ?? '') ?></textarea></label>
  <label>Phone <input type="text" name="phone" value="<?= e($settings['phone'] ?? '') ?>"></label>
  <label>WhatsApp (country code, no +) <input type="text" name="whatsapp" value="<?= e($settings['whatsapp'] ?? '') ?>"></label>
  <label>Email <input type="email" name="email" value="<?= e($settings['email'] ?? '') ?>"></label>
  <label>Address <input type="text" name="address" value="<?= e($settings['address'] ?? '') ?>"></label>
  <label>Delivery Fee (KSh) <input type="number" name="delivery_fee" value="<?= e($settings['delivery_fee'] ?? '200') ?>"></label>
  <label>Currency <input type="text" name="currency" value="<?= e($settings['currency'] ?? 'KSh') ?>"></label>
  <button class="btn btn-primary">Save Settings</button>
</form>

<?php require __DIR__.'/admin-footer.php'; ?>