<?php
/**
 * Shared helpers for the restaurant site.
 * Flat-folder layout: no /uploads subfolder — all files live in restaurant/.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

/* ------------------------------------------------------------------
 |  Output / formatting
 * ------------------------------------------------------------------ */

function e($s): string {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function attr($s): string {
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money($n): string {
    return 'KSh ' . number_format((float)$n, 0, '.', ',');
}

/* ------------------------------------------------------------------
 |  Navigation / flash
 * ------------------------------------------------------------------ */

function redirect(string $url): void {
    header("Location: $url", true, 302);
    exit;
}

function flash(string $key, ?string $msg = null) {
    if ($msg === null) {
        $v = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    $_SESSION['flash'][$key] = $msg;
    return null;
}

/* ------------------------------------------------------------------
 |  Settings (cached per request)
 * ------------------------------------------------------------------ */

function setting(PDO $pdo, string $k, string $default = ''): string {
    static $cache = null;
    static $loaded = false;

    if (!$loaded) {
        $cache = [];
        try {
            $rows = $pdo->query("SELECT k, v FROM restaurant_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $cache = $rows ?: [];
        } catch (PDOException $e) {
            error_log('settings load failed: ' . $e->getMessage());
        }
        $loaded = true;
    }

    $val = $cache[$k] ?? null;
    return ($val !== null && $val !== '') ? (string)$val : $default;
}

/* ------------------------------------------------------------------
 |  Strings / IDs
 * ------------------------------------------------------------------ */

function slugify(string $s): string {
    $s = strtolower(trim($s));
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function generateOrderRef(): string {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function excerpt(?string $text, int $len = 120, string $end = '…'): string {
    $text = trim((string)$text);
    if (mb_strlen($text) <= $len) return $text;
    return rtrim(mb_substr($text, 0, $len)) . $end;
}

/* ------------------------------------------------------------------
 |  Clean URLs — the definitive fix
 * ------------------------------------------------------------------ */

/**
 * Build a clean, extensionless URL for internal navigation.
 *   clean_url('menu.php')           → /restaurant/menu
 *   clean_url('menu.php?cat=pizza') → /restaurant/menu?cat=pizza
 *   clean_url('index.php')          → /restaurant/
 *   clean_url('')                   → /restaurant/
 *   clean_url('menu.php#results')   → /restaurant/menu#results
 */
function clean_url(string $path = ''): string {
    $path = trim($path);

    /* Split off fragment, then query string */
    $query = '';
    $frag  = '';
    if (str_contains($path, '#')) {
        [$path, $frag] = explode('#', $path, 2);
        $frag = '#' . $frag;
    }
    if (str_contains($path, '?')) {
        [$path, $query] = explode('?', $path, 2);
        $query = '?' . $query;
    }

    /* If it's already an absolute URL, leave it alone */
    if (preg_match('#^https?://#i', $path)) {
        return $path . $query . $frag;
    }

    /* Strip ".php" */
    $path = preg_replace('/\.php$/i', '', $path);

    /* "index" → root */
    if ($path === '' || $path === 'index' || $path === '/') {
        return rtrim(BASE_URL, '/') . '/' . $query . $frag;
    }

    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/') . $query . $frag;
}

/* ------------------------------------------------------------------
 |  Uploads — flat folder (files saved directly in restaurant/)
 * ------------------------------------------------------------------ */

function upload_image(array $file, string $sub = ''): ?string {
    if (empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) return null;

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;

    $ext  = $allowed[$mime];
    $dir  = rtrim(__DIR__, '/') . '/';

    $name = 'img_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        error_log("upload_image: move_uploaded_file failed for {$file['name']}");
        return null;
    }
    @chmod($dir . $name, 0644);

    return $name;
}

/* ------------------------------------------------------------------
 |  Image URL resolver — flat folder
 * ------------------------------------------------------------------ */

function menu_image_url(?string $image): string {
    $image = trim((string)$image);

    if ($image === '') {
        return 'data:image/svg+xml;utf8,' . rawurlencode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300">'
          . '<rect fill="#f4f4f4" width="400" height="300"/>'
          . '<text x="50%" y="50%" text-anchor="middle" dy=".35em" '
          . 'font-family="sans-serif" font-size="20" fill="#bbb">No image</text>'
          . '</svg>'
        );
    }

    if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://')) {
        return $image;
    }

    return rtrim(BASE_URL, '/') . '/' . ltrim($image, '/');
}

function logo_url(): string {
    return rtrim(BASE_URL, '/') . '/restaurantlogo.png';
}

/* ------------------------------------------------------------------
 |  Icons (inline SVG)
 * ------------------------------------------------------------------ */

function icon(string $name, int $size = 18, string $class = 'icon'): string {
    $paths = [
        'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin'      => '<path d="M12 21s-7-5.5-7-11a7 7 0 1 1 14 0c0 5.5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'phone'    => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.6a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.5-1.2a2 2 0 0 1 2.1-.5c.8.3 1.7.6 2.6.7a2 2 0 0 1 1.7 2z"/>',
        'star'     => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8 5.8 21l1.2-6.8-5-4.9 6.9-1z"/>',
        'arrow'    => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
        'cart'     => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.4 12.3a2 2 0 0 0 2 1.7h8.5a2 2 0 0 0 2-1.6L21.5 7H6"/>',
        'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close'    => '<path d="M6 6l12 12M18 6L6 18"/>',
        'moon'     => '<path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/>',
        'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'whatsapp' => '<path d="M20.5 3.5A11.4 11.4 0 0 0 3.9 20.2L2 22l1.9-1.9A11.4 11.4 0 1 0 20.5 3.5zM17.5 15.3c-.2.6-1.3 1.1-1.8 1.1-.5.1-1.1.1-1.8-.1-.4-.1-1-.3-1.8-.7-3.1-1.4-5.1-4.6-5.3-4.8-.1-.2-1.3-1.7-1.3-3.3 0-1.5.8-2.3 1.1-2.6.3-.3.6-.4.8-.4h.6c.2 0 .4 0 .6.5l.8 2c.1.2.1.4 0 .5l-.4.5-.3.3c-.1.1-.2.3-.1.5.1.2.6 1 1.3 1.6.9.8 1.6 1 1.9 1.2.2.1.4.1.5-.1l.8-1c.2-.2.3-.2.6-.1l1.9.9c.2.1.4.2.4.3.1.2.1.7 0 1.3z"/>',
        'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'check'    => '<path d="M4 12.5 9 17.5l11-11"/>',
        'facebook' => '<path d="M14 9h3V5h-3a4 4 0 0 0-4 4v2H8v4h2v7h4v-7h3l1-4h-4V9z"/>',
        'instagram'=> '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".7" fill="currentColor" stroke="none"/>',
        'twitter'  => '<path d="M22 5.9c-.7.3-1.5.5-2.4.6.9-.5 1.5-1.3 1.8-2.3-.8.5-1.7.8-2.6 1a4.1 4.1 0 0 0-7 3.7A11.7 11.7 0 0 1 3.4 4.6a4.1 4.1 0 0 0 1.3 5.5c-.7 0-1.3-.2-1.9-.5v.1c0 2 1.4 3.6 3.3 4-.4.1-.8.1-1.2.1-.3 0-.6 0-.8-.1.5 1.6 2 2.8 3.8 2.8A8.3 8.3 0 0 1 2 18.3a11.7 11.7 0 0 0 6.3 1.8c7.5 0 11.7-6.3 11.7-11.7v-.5c.8-.6 1.5-1.3 2-2z"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'calendar' => '<rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>',
        'truck'    => '<path d="M3 6h11v10H3zM14 8h4l3 3v5h-7"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'award'    => '<circle cx="12" cy="8" r="6"/><path d="m8.2 13.9-1.2 8 5-3 5 3-1.2-8"/>',
        'leaf'     => '<path d="M11 20A7 7 0 0 1 4 13c0-6 8-9 14-9-1 6-4 14-7 16z"/><path d="M4 20c2-4 6-7 11-8"/>',
        'fire'     => '<path d="M12 2s4 5 4 9a4 4 0 1 1-8 0c0-1 .5-2 1-3-2 2-3 4-3 6a6 6 0 1 0 12 0c0-5-6-12-6-12z"/>',
        'utensils' => '<path d="M4 3v8a3 3 0 0 0 6 0V3M7 3v18M18 3v8a3 3 0 0 1-3 3v8"/>',
        'quote'    => '<path d="M7 7h4v6a4 4 0 0 1-4 4v-3a2 2 0 0 0 2-2H7zM15 7h4v6a4 4 0 0 1-4 4v-3a2 2 0 0 0 2-2h-2z"/>',
    ];

    $p   = $paths[$name] ?? '';
    $cls = attr($class);
    return '<svg class="'.$cls.'" width="'.$size.'" height="'.$size
         . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
         . 'stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" '
         . 'aria-hidden="true" focusable="false">'.$p.'</svg>';
}

/* ------------------------------------------------------------------
 |  Misc
 * ------------------------------------------------------------------ */

/**
 * Return the current page filename (with .php if applicable).
 * Handles clean URLs and PHP URLs.
 */
function current_page(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $base = basename($path);

    if ($base === '' || $base === '/') return 'index.php';

    return $base;
}

function is_active(string ...$files): bool {
    $current      = current_page();
    $currentNoExt = preg_replace('/\.php$/i', '', $current);

    foreach ($files as $f) {
        $fNoExt = preg_replace('/\.php$/i', '', $f);

        if ($current === $f)            return true;
        if ($currentNoExt === $f)       return true;
        if ($current === $fNoExt)       return true;
        if ($currentNoExt === $fNoExt)  return true;
    }
    return false;
}

/**
 * Return a category image URL based on slug.
 */
function category_image(string $slug): string {
    $map = [
        'breakfast'   => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=800&q=80',
        'lunch'       => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80',
        'dinner'      => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80',
        'kenyan'      => 'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?w=800&q=80',
        'drinks'      => 'https://images.unsplash.com/photo-1544145945-f90425340c7e?w=800&q=80',
        'desserts'    => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=800&q=80',
        'grills'      => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&q=80',
        'seafood'     => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=800&q=80',
        'vegetarian'  => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80',
        'starters'    => 'https://images.unsplash.com/photo-1541529086526-db283c563270?w=800&q=80',
        'snacks'      => 'https://images.unsplash.com/photo-1565958011703-44f9829ba187?w=800&q=80',
        'chicken'     => 'https://images.unsplash.com/photo-1626645738196-c2a7c87a8f58?w=800&q=80',
        'burgers'     => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80',
        'beef'        => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800&q=80',
        'fish'        => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?w=800&q=80',
        'cocktails'   => 'https://images.unsplash.com/photo-1551538827-9c037cb4f32a?w=800&q=80',
        'pizza'       => 'https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?w=800&q=80',
        'main-dishes' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=800&q=80',
        'fast-food'   => 'https://images.unsplash.com/photo-1562967914-608f82629710?w=800&q=80',
    ];
    return $map[$slug] ?? 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=800&q=80';
}

function safe_query(PDO $pdo, string $sql, array $params = []): array {
    try {
        $st = $pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    } catch (PDOException $e) {
        error_log('safe_query failed: ' . $e->getMessage() . ' — SQL: ' . $sql);
        return [];
    }
}

/**
 * Backward-compat helper — same as clean_url.
 */
function url(string $path = '', array $params = []): string {
    $q = $params ? '?' . http_build_query($params) : '';
    return clean_url($path . $q);
}

/**
 * Compute the next occurrence of a recurring event.
 */
function next_event_date(?int $dayOfWeek, ?string $storedDate): string {
    $today = new DateTime('today');

    if ($dayOfWeek !== null && $dayOfWeek >= 0 && $dayOfWeek <= 6) {
        $todayDow = (int)$today->format('w');
        $diff     = ($dayOfWeek - $todayDow + 7) % 7;
        if ($diff === 0) return $today->format('Y-m-d');
        $today->modify("+{$diff} days");
        return $today->format('Y-m-d');
    }

    if ($storedDate) {
        $stored = new DateTime($storedDate);
        if ($stored < $today) {
            while ($stored < $today) {
                $stored->modify('+7 days');
            }
        }
        return $stored->format('Y-m-d');
    }

    $today->modify('+1 day');
    return $today->format('Y-m-d');
}

/**
 * Human-friendly date label.
 */
function friendly_date(string $ymd): string {
    $date  = new DateTime($ymd);
    $today = new DateTime('today');
    $diff  = (int)$today->diff($date)->format('%r%a');

    if ($diff === 0) return 'Today';
    if ($diff === 1) return 'Tomorrow';

    return $date->format('l, d M Y');
}