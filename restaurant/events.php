<?php
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$page_title = 'Events | ' . setting($pdo, 'site_name', 'Restaurant');

/* Load active events. Include both recurring (event_date NULL) and one-off */
$events = safe_query($pdo, "
    SELECT * FROM restaurant_events
    WHERE active = 1
    ORDER BY
        CASE WHEN recurring_day IS NOT NULL THEN 0 ELSE 1 END ASC,
        event_date ASC,
        id ASC
");

/* Fallback seed — shown only if DB has no events */
if (!$events) {
    $events = [
      ['title' => 'Live Jazz Fridays',        'recurring_day' => 5, 'event_date' => null, 'event_time' => '8:00 PM',  'description' => 'Enjoy smooth live jazz with our signature cocktail menu every Friday evening.', 'image' => 'https://images.unsplash.com/photo-1516450360452-9312f5e86fc7?w=1200&q=80'],
      ['title' => 'Premier League Nights',    'recurring_day' => 6, 'event_date' => null, 'event_time' => '7:00 PM',  'description' => 'Watch the big matches on our giant screens with beer buckets and grill platters.', 'image' => 'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=1200&q=80'],
      ['title' => 'Kenyan Food Festival',     'recurring_day' => null, 'event_date' => date('Y-m-d', strtotime('+10 days')), 'event_time' => '12:00 PM', 'description' => 'A celebration of Kenyan cuisine — tasting menu, cooking demos, and live music.', 'image' => 'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?w=1200&q=80'],
      ['title' => 'Sunday Family Brunch',     'recurring_day' => 0, 'event_date' => null, 'event_time' => '10:00 AM', 'description' => 'Bottomless brunch with a kids play area and live acoustic performance.',        'image' => 'https://images.unsplash.com/photo-1533089860892-a7c6f0a88666?w=1200&q=80'],
      ['title' => 'Wine Tasting Evening',     'recurring_day' => 4, 'event_date' => null, 'event_time' => '6:30 PM',  'description' => 'Six curated wines paired with artisan cheeses and small plates.',               'image' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=1200&q=80'],
      ['title' => "Chef's Table Experience",  'recurring_day' => null, 'event_date' => date('Y-m-d', strtotime('+21 days')), 'event_time' => '7:30 PM', 'description' => 'An intimate 7-course tasting menu hosted by our head chef. Limited seats.', 'image' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80'],
    ];
}

/* Enrich each event with its *next* occurrence date */
foreach ($events as &$ev) {
    $ev['next_date'] = next_event_date(
        isset($ev['recurring_day']) ? (int)$ev['recurring_day'] : null,
        $ev['event_date'] ?? null
    );
    $ev['is_recurring'] = !empty($ev['recurring_day']) || empty($ev['event_date']);
}
unset($ev);

/* Sort by next occurrence ascending */
usort($events, fn($a, $b) => strcmp($a['next_date'], $b['next_date']));

require __DIR__.'/header.php';
?>

<section class="page-hero" style="background-image:url('https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3?w=1920&q=80')">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <p class="hero-eyebrow">What's on</p>
    <h1>Upcoming Events</h1>
    <p class="hero-sub">Live music, football nights, brunches and more</p>
  </div>
</section>

<section class="container section">
  <?php if (!$events): ?>
    <p class="empty-state">No upcoming events. Follow our socials for updates!</p>
  <?php else: ?>

    <p class="results-count">
      <strong><?= count($events) ?></strong>
      <?= count($events) === 1 ? 'event' : 'events' ?> coming up
    </p>

    <div class="grid grid-2">
      <?php foreach ($events as $ev): ?>
        <?php
          $img = !empty($ev['image'])
            ? (str_starts_with($ev['image'], 'http')
                ? $ev['image']
                : UPLOAD_URL . e($ev['image']))
            : 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=1200&q=80';

          $dateLabel = friendly_date($ev['next_date']);
          $isToday    = $dateLabel === 'Today';
          $isTomorrow = $dateLabel === 'Tomorrow';
        ?>
        <article class="event-card">
          <div class="event-img">
            <img src="<?= e($img) ?>" alt="<?= e($ev['title']) ?>" loading="lazy"
                 onerror="this.onerror=null;this.src='<?= UPLOAD_URL ?>menu/placeholder.jpg';">
            <?php if ($isToday): ?>
              <span class="event-flag is-today">Happening today</span>
            <?php elseif ($isTomorrow): ?>
              <span class="event-flag is-tomorrow">Tomorrow</span>
            <?php elseif (!empty($ev['is_recurring'])): ?>
              <span class="event-flag is-recurring">Weekly</span>
            <?php endif; ?>
          </div>

          <div class="event-body">
            <h3><?= e($ev['title']) ?></h3>

            <p class="event-date">
              <?= icon('calendar', 16) ?>
              <strong><?= e($dateLabel) ?></strong>
              <?php if (!empty($ev['event_time'])): ?>
                · <?= icon('clock', 16) ?> <?= e($ev['event_time']) ?>
              <?php endif; ?>
            </p>

            <p><?= nl2br(e($ev['description'])) ?></p>

            <a href="<?= BASE_URL ?>/reserve.php" class="btn btn-primary btn-sm">
              <?= icon('calendar', 16) ?> Book a Table
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<style>
/* Event flag badges */
.event-flag{
  position:absolute;top:14px;left:14px;z-index:2;
  padding:5px 12px;border-radius:20px;
  font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;
  backdrop-filter:blur(6px);
  box-shadow:0 4px 14px rgba(0,0,0,.25);
}
.event-flag.is-today{
  background:#dc2626;color:#fff;
  animation:flagPulse 1.8s infinite;
}
.event-flag.is-tomorrow{background:#f59e0b;color:#fff}
.event-flag.is-recurring{background:rgba(14,14,16,.85);color:#fff}

@keyframes flagPulse{
  0%,100%{box-shadow:0 4px 14px rgba(220,38,38,.4)}
  50%    {box-shadow:0 4px 20px rgba(220,38,38,.7)}
}

.event-img{position:relative}

/* Highlight today's card */
.event-card:has(.is-today){border-color:#dc2626;box-shadow:0 8px 30px rgba(220,38,38,.18)}
</style>

<?php require __DIR__.'/footer.php'; ?>