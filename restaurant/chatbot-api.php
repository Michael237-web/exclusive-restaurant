<?php
/**
 * chatbot-api.php
 * Smart restaurant chatbot API.
 * Reads live menu, prices, availability, offers from the database.
 */

require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

/* ------------------------------------------------------------------
   Robust input parsing:
   - JSON body (preferred)
   - form POST
   - GET fallback
------------------------------------------------------------------ */
$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input))      $input = $_POST;
if (!is_array($input))      $input = [];
if (empty($input['message']) && isset($_GET['message'])) {
    $input['message'] = $_GET['message'];
}

$msg = strtolower(trim((string)($input['message'] ?? '')));

/* Helper to send a reply and exit */
function reply(string $text): void {
    echo json_encode(['reply' => $text], JSON_UNESCAPED_UNICODE);
    exit;
}

/* Empty message → show welcome */
if ($msg === '') {
    reply("Hi! 👋 Ask me about any dish, price, availability, or delivery. Try \"pizza\" or \"do you deliver?\"");
}

/* ------------------------------------------------------------------
   Load menu (once) — cached for this request
------------------------------------------------------------------ */
$menuItems = [];
try {
    $menuItems = $pdo->query("
        SELECT m.id, m.name, m.slug, m.description, m.ingredients,
               m.price, m.available, m.is_vegetarian, m.is_spicy, m.is_popular,
               c.name AS category, c.slug AS cat_slug
        FROM restaurant_menu_items m
        LEFT JOIN restaurant_categories c ON c.id = m.category_id
        ORDER BY m.name ASC
    ")->fetchAll();
} catch (PDOException $e) {
    error_log('chatbot menu load failed: '.$e->getMessage());
}

/* Settings */
$siteName = setting($pdo, 'site_name', 'Restaurant');
$phone    = setting($pdo, 'phone', '+254 700 000 000');
$wa       = preg_replace('/\D+/', '', setting($pdo, 'whatsapp', '254700000000'));
$address  = setting($pdo, 'address', 'Westlands, Nairobi');
$email    = setting($pdo, 'email', 'hello@restaurant.co.ke');
$delivery = (int)setting($pdo, 'delivery_fee', 200);

/* ==================================================================
   1) GREETINGS — only match when the message is JUST a greeting
================================================================== */
$greetings = [
    'hi','hello','hey','hiya','yo','habari','sasa','jambo',
    'mambo','niaje','good morning','good afternoon','good evening',
    'good night','shalom','hallo'
];

$isGreeting = false;
foreach ($greetings as $g) {
    if ($msg === $g
        || $msg === $g.'!'
        || $msg === $g.'.'
        || $msg === $g.','
        || $msg === $g.'?'
        || preg_match('/^'.preg_quote($g, '/').'[,!\s?]+$/i', $msg)) {
        $isGreeting = true;
        break;
    }
}

if ($isGreeting) {
    $hour = (int)date('G');
    if ($hour < 12)      $timeGreet = 'Good morning';
    elseif ($hour < 17)  $timeGreet = 'Good afternoon';
    else                 $timeGreet = 'Good evening';

    reply(
        "$timeGreet! 👋 Welcome to $siteName.\n\n" .
        "I can help you with:\n" .
        "• 🍽️ Any dish, price or availability\n" .
        "• 🌱 Vegetarian & spicy options\n" .
        "• 🎁 Offers & discounts\n" .
        "• 🚚 Delivery info\n" .
        "• 🪑 Reservations\n" .
        "• 📍 Branches & hours\n\n" .
        "What would you like to know? Try \"pizza\" or \"how much is a burger?\""
    );
}

/* ==================================================================
   2) SMALL TALK
================================================================== */
if (preg_match('/\b(how are you|how\'s it going|how do you do|how is it)\b/i', $msg)) {
    reply("I'm doing great, thanks for asking! 😊 How can I help you today?");
}
if (preg_match('/\b(thank you|thanks|asante|shukran|thx|ty)\b/i', $msg)) {
    reply("You're welcome! 🙏 Enjoy your meal — let me know if you need anything else.");
}
if (preg_match('/\b(bye|goodbye|kwaheri|see you later|see ya)\b/i', $msg)) {
    reply("Goodbye! 👋 Come back soon to $siteName.");
}

/* ==================================================================
   3) HOURS
================================================================== */
if (preg_match('/\b(hours?|open|opening|closing|close[sd]?|when.*open|what time)\b/i', $msg)) {
    reply("🕐 We're open **every day from 7:00 AM to 11:00 PM**.\n" .
          "Last kitchen order at 10:30 PM. See you soon!");
}

/* ==================================================================
   4) LOCATION / BRANCHES
================================================================== */
if (preg_match('/\b(branches?|outlets?|locations?)\b/i', $msg)) {
    try {
        $rows = $pdo->query("SELECT name,address,phone,hours FROM restaurant_branches WHERE active=1 ORDER BY name")->fetchAll();
        if ($rows) {
            $out = "📍 We have " . count($rows) . " branches:\n\n";
            foreach ($rows as $r) {
                $out .= "**{$r['name']}**\n{$r['address']}\n📞 {$r['phone']}\n🕐 {$r['hours']}\n\n";
            }
            reply(trim($out));
        }
    } catch (PDOException $e) {}
    reply("📍 Main location: $address");
}

if (preg_match('/\b(where|location|address|find you|directions|map|how.*get)\b/i', $msg)) {
    reply("📍 We're at **$address**.\n\nType \"branches\" to see all our locations, " .
          "or visit the Branches page for maps & directions.");
}

/* ==================================================================
   5) CONTACT
================================================================== */
if (preg_match('/\b(phone|call|contact|reach|number|email|whatsapp)\b/i', $msg)) {
    reply("📞 You can reach us at:\n" .
          "• Phone: $phone\n" .
          "• Email: $email\n" .
          "• WhatsApp: wa.me/$wa\n\n" .
          "We reply fast during opening hours.");
}

/* ==================================================================
   6) DELIVERY
================================================================== */
if (preg_match('/\b(deliver|delivery|shipping|bring|ship)\b/i', $msg)) {
    reply("🚚 Yes, we deliver across Nairobi!\n" .
          "• Delivery fee: **KSh " . number_format($delivery) . "**\n" .
          "• Time: 30–45 minutes\n" .
          "• Free delivery Fridays on orders above KSh 1,500\n\n" .
          "Add items to your cart from the Menu page and choose Delivery at checkout.");
}

/* ==================================================================
   7) OFFERS
================================================================== */
if (preg_match('/\b(offers?|promos?|promotions?|discounts?|deals?|coupons?|codes?|special)\b/i', $msg)) {
    try {
        $rows = $pdo->query("
            SELECT title, description, code, discount_percent, expires_at
            FROM restaurant_promotions
            WHERE active=1 AND (expires_at IS NULL OR expires_at >= CURDATE())
            ORDER BY id DESC LIMIT 5
        ")->fetchAll();
        if ($rows) {
            $out = "🎁 Current offers:\n\n";
            foreach ($rows as $r) {
                $out .= "**{$r['title']}**";
                if (!empty($r['discount_percent'])) $out .= " ({$r['discount_percent']}% off)";
                $out .= "\n{$r['description']}";
                if (!empty($r['code'])) $out .= "\nCode: `{$r['code']}`";
                if (!empty($r['expires_at'])) $out .= "\nExpires: " . date('d M Y', strtotime($r['expires_at']));
                $out .= "\n\n";
            }
            $out .= "See the Offers page for the full list!";
            reply(trim($out));
        }
    } catch (PDOException $e) {}
    reply("We have some great offers right now — check the Offers page!");
}

/* ==================================================================
   8) RESERVATIONS
================================================================== */
if (preg_match('/\b(reserv\w*|book(ing)?\s*(a\s*)?table|table)\b/i', $msg)) {
    reply("🪑 Yes! You can reserve a table online.\n" .
          "Visit the **Reserve** page and pick your date, time, guests, and branch.\n\n" .
          "For large groups (10+), call us at $phone.");
}

/* ==================================================================
   9) ORDER TRACKING
================================================================== */
if (preg_match('/\b(track|where.*my order|order status|my order)\b/i', $msg)) {
    reply("📦 Track your order from the **Track Order** page.\n" .
          "You'll need your reference (starts with **ORD-**).\n\n" .
          "Go to: /order-track and enter your ref.");
}

/* ==================================================================
   10) VEGETARIAN / SPICY / POPULAR
================================================================== */
if (preg_match('/\b(vegetarian|vegan|veg\b|meatless|no meat)\b/i', $msg)) {
    $veg = array_values(array_filter($menuItems, fn($i) => !empty($i['is_vegetarian'])));
    if ($veg) {
        $out = "🌱 Vegetarian dishes (" . count($veg) . " total):\n\n";
        foreach (array_slice($veg, 0, 8) as $i) {
            $out .= "• {$i['name']} — KSh " . number_format($i['price']) . "\n";
        }
        if (count($veg) > 8) $out .= "\n…and " . (count($veg) - 8) . " more.";
        $out .= "\n\nSee the Vegetarian category on the Menu page.";
        reply($out);
    }
    reply("We have plenty of vegetarian options — check the Menu page!");
}

if (preg_match('/\b(spicy|hot|chili|chilli|peri[- ]?peri)\b/i', $msg)) {
    $spicy = array_values(array_filter($menuItems, fn($i) => !empty($i['is_spicy'])));
    if ($spicy) {
        $out = "🌶️ Spicy dishes (" . count($spicy) . " total):\n\n";
        foreach (array_slice($spicy, 0, 8) as $i) {
            $out .= "• {$i['name']} — KSh " . number_format($i['price']) . "\n";
        }
        reply($out);
    }
    reply("We have spicy options — look for the 🌶️ Spicy tag on the Menu.");
}

if (preg_match('/\b(popular|bestsellers?|best sellers?|recommend|favorite|favourite|top dishes?)\b/i', $msg)) {
    $pop = array_values(array_filter($menuItems, fn($i) => !empty($i['is_popular'])));
    if ($pop) {
        $out = "⭐ Our most popular dishes:\n\n";
        foreach (array_slice($pop, 0, 8) as $i) {
            $out .= "• {$i['name']} — KSh " . number_format($i['price']) . "\n";
        }
        reply($out);
    }
    reply("Check the ⭐ Popular tag on the Menu — those are customer favourites!");
}

/* ==================================================================
   11) CATEGORY BROWSING (with aliases)
================================================================== */
$categories = [
    'breakfast'   => ['Breakfast',  ['morning food','breakfast food','early meal']],
    'lunch'       => ['Lunch',      ['midday meal','midday','noon']],
    'dinner'      => ['Dinner',     ['supper','evening meal','night meal']],
    'kenyan'      => ['Kenyan Cuisine', ['local food','kienyeji','traditional','local dishes','kenyan food']],
    'grills'      => ['Grills & BBQ', ['bbq','barbeque','grilled','grill','barbequed']],
    'seafood'     => ['Seafood',    ['sea food','fish dishes']],
    'vegetarian'  => ['Vegetarian', ['veg','vegan','meatless']],
    'starters'    => ['Starters',   ['appetizers','appetizer','snacks','starters']],
    'desserts'    => ['Desserts',   ['sweets','sweet','cake','ice cream','pudding','desert']],
    'drinks'      => ['Drinks',     ['beverages','juice','coffee','tea','chai','soda','water','drink']],
    'chicken'     => ['Chicken',    ['chicken dishes']],
    'burgers'     => ['Burgers',    ['burger','hamburger']],
    'beef'        => ['Beef',       ['beef dishes','steak']],
    'fish'        => ['Fish',       ['fish dishes','fillet']],
    'cocktails'   => ['Cocktails',  ['alcohol','wine','beer','cocktail','drinks with alcohol']],
    'pizza'       => ['Pizza',      ['pizzas','pizza pie']],
    'main-dishes' => ['Main Dishes',['main course','mains','main']],
    'fast-food'   => ['Fast Food',  ['fastfood','chips','fries','quick food']],
];

foreach ($categories as $slug => [$name, $aliases]) {
    $terms = array_merge([$slug], [strtolower($name)], $aliases);
    foreach ($terms as $t) {
        if (preg_match('/\b' . preg_quote($t, '/') . '\b/i', $msg)) {
            $items = array_values(array_filter($menuItems, fn($i) => $i['cat_slug'] === $slug));
            if ($items) {
                $out = "🍽️ **$name** — " . count($items) . " dishes:\n\n";
                foreach (array_slice($items, 0, 8) as $i) {
                    $flag = empty($i['available']) ? ' ❌' : '';
                    $out .= "• {$i['name']} — KSh " . number_format($i['price']) . "$flag\n";
                }
                if (count($items) > 8) $out .= "\n…and " . (count($items) - 8) . " more.";
                $out .= "\n\nView all on the Menu page.";
                reply($out);
            }
        }
    }
}

/* ==================================================================
   12) SPECIFIC DISH — price / availability / description
================================================================== */
$wantsAvail = (bool)preg_match('/\b(available|in stock|do you have|have you got|can i get|is there|got)\b/i', $msg);
$wantsPrice = (bool)preg_match('/\b(price|how much|cost|charge|rate|how many)\b/i', $msg);
$wantsInfo  = (bool)preg_match('/\b(what is|tell me about|describe|ingredients|about|what\'s in)\b/i', $msg);

/* Score dishes by how many words match */
$bestMatch = null;
$bestScore = 0;

foreach ($menuItems as $item) {
    $name = strtolower($item['name']);
    $score = 0;

    /* Direct full-name substring = big win */
    if (str_contains($msg, $name)) {
        $bestMatch = $item; $bestScore = 100;
        break;
    }

    /* Word-by-word partial match (words with 3+ chars) */
    $words = preg_split('/[\s&\/\-]+/', $name);
    foreach ($words as $w) {
        $w = trim(preg_replace('/[^a-z0-9]/', '', $w));
        if (strlen($w) >= 3 && preg_match('/\b'.preg_quote($w, '/').'\b/i', $msg)) {
            $score++;
        }
    }
    if ($score > $bestScore) {
        $bestScore = $score;
        $bestMatch = $item;
    }
}

if ($bestMatch && $bestScore >= 1) {
    $name  = $bestMatch['name'];
    $price = 'KSh ' . number_format($bestMatch['price']);
    $avail = !empty($bestMatch['available']) ? '✅ Available now' : '❌ Currently unavailable';

    $tags = [];
    if (!empty($bestMatch['is_vegetarian'])) $tags[] = '🌱 Vegetarian';
    if (!empty($bestMatch['is_spicy']))      $tags[] = '🌶️ Spicy';
    if (!empty($bestMatch['is_popular']))    $tags[] = '⭐ Popular';

    if ($wantsAvail) {
        if (!empty($bestMatch['available'])) {
            reply("✅ Yes, **{$name}** is available!\nPrice: {$price}" .
                  (!empty($tags) ? "\n" . implode(' · ', $tags) : "") .
                  "\n\nAdd it to your cart from the Menu page.");
        }
        reply("❌ Sorry, **{$name}** is currently unavailable.\n" .
              "Would you like a similar recommendation?");
    }

    if ($wantsPrice) {
        reply("💰 **{$name}** costs **{$price}**.\n{$bestMatch['description']}");
    }

    if ($wantsInfo) {
        $out = "ℹ️ **{$name}**\n{$bestMatch['description']}";
        if (!empty($bestMatch['ingredients'])) $out .= "\n\nIngredients: {$bestMatch['ingredients']}";
        if (!empty($bestMatch['category']))    $out .= "\nCategory: {$bestMatch['category']}";
        $out .= "\nPrice: {$price}";
        if (!empty($bestMatch['available']))   $out .= "\nStatus: ✅ Available";
        reply($out);
    }

    /* Default — full summary */
    reply("🍽️ **{$name}**\n{$bestMatch['description']}\n\n" .
          "Price: {$price}\n" .
          (!empty($bestMatch['category']) ? "Category: {$bestMatch['category']}\n" : "") .
          $avail .
          (!empty($tags) ? "\n" . implode(' · ', $tags) : "") .
          "\n\nAdd it to your cart from the Menu page!");
}

/* ==================================================================
   13) MENU OVERVIEW
================================================================== */
if (preg_match('/\b(menu|what do you (have|serve)|what.*food|dishes|food items|full menu|list)\b/i', $msg)) {
    $total = count($menuItems);
    $counts = [];
    foreach ($menuItems as $i) {
        $c = $i['category'] ?: 'Other';
        $counts[$c] = ($counts[$c] ?? 0) + 1;
    }
    $out = "🍽️ We serve **$total dishes** across " . count($counts) . " categories:\n\n";
    foreach ($counts as $cat => $n) {
        $out .= "• $cat ($n)\n";
    }
    $out .= "\nAsk me about any dish or category — e.g. \"tell me about pizza\" or \"chicken dishes\".";
    reply($out);
}

/* ==================================================================
   14) HELP
================================================================== */
if (preg_match('/\b(help|what can you do|options|commands)\b/i', $msg)) {
    reply("Here's what I can do:\n\n" .
          "• 🍕 **Any dish** — \"pizza\", \"how much is a burger?\", \"is chicken available?\"\n" .
          "• 🌱 **Filters** — \"vegetarian dishes\", \"spicy food\", \"popular items\"\n" .
          "• 🍽️ **Categories** — \"breakfast\", \"kenyan food\", \"drinks\", \"desserts\"\n" .
          "• 🎁 **Offers** — \"any discounts?\"\n" .
          "• 🪑 **Reservations** — \"book a table\"\n" .
          "• 📦 **Tracking** — \"track my order\"\n" .
          "• 📍 **Location** — \"where are you?\", \"branches\"\n" .
          "• 🕐 **Hours** — \"what time do you open?\"\n" .
          "• 🚚 **Delivery** — \"do you deliver?\"\n\n" .
          "Just ask!");
}

/* ==================================================================
   15) FALLBACK — gentle guidance
================================================================== */
reply(
    "I'm not sure I understood that. 🤔\n\n" .
    "Try asking things like:\n" .
    "• \"How much is pizza?\"\n" .
    "• \"Show me breakfast dishes\"\n" .
    "• \"Is the chicken available?\"\n" .
    "• \"Any offers?\"\n" .
    "• \"What time do you open?\"\n\n" .
    "Or type **help** for everything I can do."
);