<?php
require __DIR__ . '/includes/bootstrap.php';
send_security_headers();

$c = load_content();
$s = $c['settings'];
$currency = $s['currency'];

$show = fn (string $section) => !empty($c[$section]['visible']);

// Menu links, in page order, for visible sections that have a menu label.
$nav = [];
foreach (['features', 'about', 'testimonials', 'pricing', 'faq'] as $key) {
    if ($show($key) && $c[$key]['nav_label'] !== '') {
        $nav[$key] = $c[$key]['nav_label'];
    }
}
$contactCta = $show('contact') && $c['contact']['nav_label'] !== '' ? $c['contact']['nav_label'] : null;

// Alternate section backgrounds across whichever sections are visible.
$altIndex = 0;
$sectionClass = function () use (&$altIndex): string {
    return 'section' . ($altIndex++ % 2 ? ' section-alt' : '');
};

$initials = function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $letters = array_map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)), array_slice($parts, 0, 2));
    return implode('', $letters) ?: '?';
};

$decimals = fn (string $n) => str_contains($n, '.') ? strlen(substr($n, strpos($n, '.') + 1)) : 0;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($s['page_title']) ?></title>
  <meta name="description" content="<?= e($s['meta_description']) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css?v=<?= filemtime(__DIR__ . '/assets/styles.css') ?>">
  <style>:root { --brand: <?= e($s['primary_color']) ?>; --brand-accent: <?= e($s['accent_color']) ?>; }</style>
</head>
<body>
  <a class="skip-link" href="#main">Skip to content</a>

  <header class="site-header" id="top">
    <div class="container nav">
      <a href="#top" class="logo" aria-label="<?= e($s['site_name']) ?> home">
        <span class="logo-mark" aria-hidden="true"></span><?= e($s['site_name']) ?>
      </a>

      <nav class="nav-links" id="nav-links" aria-label="Primary">
        <?php foreach ($nav as $id => $label): ?>
          <a href="#<?= e($id) ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
        <?php if ($contactCta): ?>
          <a href="#contact" class="btn btn-small"><?= e($contactCta) ?></a>
        <?php endif; ?>
      </nav>

      <div class="nav-actions">
        <button class="icon-btn" id="theme-toggle" aria-label="Toggle dark mode">
          <svg class="icon-sun" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="icon-moon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </button>
        <?php if ($nav || $contactCta): ?>
        <button class="icon-btn menu-toggle" id="menu-toggle" aria-label="Open menu" aria-expanded="false" aria-controls="nav-links">
          <span></span><span></span><span></span>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main id="main">
    <?php $h = $c['hero']; ?>
    <section class="hero">
      <div class="container hero-grid">
        <div class="hero-copy reveal">
          <?php if ($h['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($h['eyebrow']) ?></p><?php endif; ?>
          <h1><?= e($h['title']) ?><?php if ($h['title_highlight'] !== ''): ?> <span class="gradient-text"><?= e($h['title_highlight']) ?></span><?php endif; ?></h1>
          <?php if ($h['lead'] !== ''): ?><p class="lead"><?= e($h['lead']) ?></p><?php endif; ?>
          <div class="hero-cta">
            <?php if ($h['primary_label'] !== ''): ?><a href="<?= e($h['primary_link'] ?: '#') ?>" class="btn"><?= e($h['primary_label']) ?></a><?php endif; ?>
            <?php if ($h['secondary_label'] !== ''): ?><a href="<?= e($h['secondary_link'] ?: '#') ?>" class="btn btn-ghost"><?= e($h['secondary_label']) ?></a><?php endif; ?>
          </div>
          <?php if ($h['fine_print'] !== ''): ?><p class="fine-print"><?= e($h['fine_print']) ?></p><?php endif; ?>
        </div>

        <div class="hero-visual reveal" aria-hidden="true">
          <div class="mock-window">
            <div class="mock-bar"><span></span><span></span><span></span></div>
            <div class="mock-body">
              <div class="mock-sidebar"><i></i><i></i><i></i><i></i></div>
              <div class="mock-main">
                <div class="mock-chart">
                  <b style="--h:40%"></b><b style="--h:65%"></b><b style="--h:50%"></b>
                  <b style="--h:80%"></b><b style="--h:60%"></b><b style="--h:92%"></b>
                </div>
                <div class="mock-rows"><i></i><i></i><i></i></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <?php if ($show('logos') && $c['logos']['names']): ?>
    <section class="logos" aria-label="<?= e($c['logos']['heading'] ?: 'Customers') ?>">
      <div class="container">
        <?php if ($c['logos']['heading'] !== ''): ?><p><?= e($c['logos']['heading']) ?></p><?php endif; ?>
        <ul>
          <?php foreach ($c['logos']['names'] as $name): ?><li><?= e($name) ?></li><?php endforeach; ?>
        </ul>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($show('features')): $f = $c['features']; ?>
    <section class="<?= $sectionClass() ?>" id="features">
      <div class="container">
        <div class="section-head reveal">
          <?php if ($f['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($f['eyebrow']) ?></p><?php endif; ?>
          <h2><?= e($f['heading']) ?></h2>
          <?php if ($f['subheading'] !== ''): ?><p><?= e($f['subheading']) ?></p><?php endif; ?>
        </div>
        <div class="feature-grid">
          <?php foreach ($f['items'] as $item): ?>
          <article class="card reveal">
            <?php if ($item['icon'] !== ''): ?><div class="card-icon"><?= e($item['icon']) ?></div><?php endif; ?>
            <h3><?= e($item['title']) ?></h3>
            <p><?= e($item['text']) ?></p>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($show('about')): $a = $c['about']; ?>
    <section class="<?= $sectionClass() ?>" id="about">
      <div class="container about-grid<?= $a['stats'] ? '' : ' single' ?>">
        <div class="reveal">
          <?php if ($a['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($a['eyebrow']) ?></p><?php endif; ?>
          <h2><?= e($a['heading']) ?></h2>
          <?php foreach (preg_split('/\R{2,}/u', $a['body']) as $para): if (trim($para) === '') continue; ?>
            <p><?= nl2br(e($para)) ?></p>
          <?php endforeach; ?>
          <?php if ($a['checklist']): ?>
          <ul class="checklist">
            <?php foreach ($a['checklist'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
        <?php if ($a['stats']): ?>
        <div class="stats reveal">
          <?php foreach ($a['stats'] as $stat): ?>
          <div class="stat">
            <strong data-count="<?= e($stat['value']) ?>" data-suffix="<?= e($stat['suffix']) ?>" data-decimals="<?= $decimals($stat['value']) ?>"><?= e($stat['value'] . $stat['suffix']) ?></strong>
            <span><?= e($stat['label']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($show('testimonials') && $c['testimonials']['items']): $t = $c['testimonials']; ?>
    <section class="<?= $sectionClass() ?>" id="testimonials">
      <div class="container">
        <div class="section-head reveal">
          <?php if ($t['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($t['eyebrow']) ?></p><?php endif; ?>
          <h2><?= e($t['heading']) ?></h2>
        </div>
        <div class="slider reveal">
          <div class="slides" id="slides">
            <?php foreach ($t['items'] as $item): ?>
            <figure class="slide">
              <blockquote>“<?= e($item['quote']) ?>”</blockquote>
              <figcaption>
                <span class="avatar"><?= e($initials($item['name'])) ?></span>
                <div><strong><?= e($item['name']) ?></strong><small><?= e($item['role']) ?></small></div>
              </figcaption>
            </figure>
            <?php endforeach; ?>
          </div>
          <?php if (count($t['items']) > 1): ?>
          <div class="slider-controls">
            <button class="icon-btn" id="prev" aria-label="Previous testimonial">←</button>
            <div class="dots" id="dots" role="tablist"></div>
            <button class="icon-btn" id="next" aria-label="Next testimonial">→</button>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($show('pricing') && $c['pricing']['plans']): $p = $c['pricing']; ?>
    <section class="<?= $sectionClass() ?>" id="pricing">
      <div class="container">
        <div class="section-head reveal">
          <?php if ($p['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($p['eyebrow']) ?></p><?php endif; ?>
          <h2><?= e($p['heading']) ?></h2>
          <div class="billing-toggle">
            <span>Monthly</span>
            <label class="switch">
              <input type="checkbox" id="billing" aria-label="Bill yearly">
              <span class="slider-knob"></span>
            </label>
            <span>Yearly <?php if ($p['yearly_badge'] !== ''): ?><em class="badge"><?= e($p['yearly_badge']) ?></em><?php endif; ?></span>
          </div>
        </div>
        <div class="pricing-grid" style="--plans: <?= count($p['plans']) ?>">
          <?php foreach ($p['plans'] as $plan): ?>
          <article class="plan<?= $plan['featured'] ? ' plan-featured' : '' ?> reveal">
            <?php if ($plan['featured'] && $plan['tag'] !== ''): ?><span class="plan-tag"><?= e($plan['tag']) ?></span><?php endif; ?>
            <h3><?= e($plan['name']) ?></h3>
            <p class="plan-desc"><?= e($plan['description']) ?></p>
            <p class="price">
              <span class="amount" data-currency="<?= e($currency) ?>" data-monthly="<?= e($plan['monthly']) ?>" data-yearly="<?= e($plan['yearly']) ?>"><?= e($currency . $plan['monthly']) ?></span><small>/mo</small>
            </p>
            <ul>
              <?php foreach ($plan['features'] as $line): ?><li><?= e($line) ?></li><?php endforeach; ?>
            </ul>
            <?php if ($plan['cta_label'] !== ''): ?>
            <a href="<?= e($plan['cta_link'] ?: '#') ?>" class="btn btn-block<?= $plan['featured'] ? '' : ' btn-ghost' ?>"><?= e($plan['cta_label']) ?></a>
            <?php endif; ?>
          </article>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($show('faq') && $c['faq']['items']): $q = $c['faq']; ?>
    <section class="<?= $sectionClass() ?>" id="faq">
      <div class="container narrow">
        <div class="section-head reveal">
          <?php if ($q['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($q['eyebrow']) ?></p><?php endif; ?>
          <h2><?= e($q['heading']) ?></h2>
        </div>
        <div class="faq reveal">
          <?php foreach ($q['items'] as $item): ?>
          <details>
            <summary><?= e($item['question']) ?></summary>
            <p><?= nl2br(e($item['answer'])) ?></p>
          </details>
          <?php endforeach; ?>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <?php if ($show('contact')): $ct = $c['contact']; ?>
    <section class="<?= $sectionClass() ?>" id="contact">
      <div class="container contact-grid">
        <div class="reveal">
          <?php if ($ct['eyebrow'] !== ''): ?><p class="eyebrow"><?= e($ct['eyebrow']) ?></p><?php endif; ?>
          <h2><?= e($ct['heading']) ?></h2>
          <?php if ($ct['body'] !== ''): ?><p><?= nl2br(e($ct['body'])) ?></p><?php endif; ?>
          <ul class="contact-list">
            <?php if ($ct['email'] !== ''): ?><li>✉️ <?= e($ct['email']) ?></li><?php endif; ?>
            <?php if ($ct['phone'] !== ''): ?><li>📞 <?= e($ct['phone']) ?></li><?php endif; ?>
            <?php if ($ct['address'] !== ''): ?><li>📍 <?= e($ct['address']) ?></li><?php endif; ?>
          </ul>
        </div>

        <form class="contact-form reveal" id="contact-form" action="api/submit.php" method="post" novalidate
              data-success="<?= e($ct['success_message']) ?>">
          <input type="hidden" name="type" value="contact">
          <div class="hp" aria-hidden="true">
            <label>Leave this empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>
          <div class="field">
            <label for="name">Name</label>
            <input id="name" name="name" type="text" autocomplete="name" required maxlength="100">
            <small class="error" aria-live="polite"></small>
          </div>
          <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required maxlength="200">
            <small class="error" aria-live="polite"></small>
          </div>
          <div class="field">
            <label for="message">Message</label>
            <textarea id="message" name="message" rows="5" required minlength="10" maxlength="5000"></textarea>
            <small class="error" aria-live="polite"></small>
          </div>
          <button type="submit" class="btn btn-block"><?= e($ct['button_label'] ?: 'Send') ?></button>
          <p class="form-status" id="form-status" role="status"></p>
        </form>
      </div>
    </section>
    <?php endif; ?>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <div>
        <a href="#top" class="logo"><span class="logo-mark" aria-hidden="true"></span><?= e($s['site_name']) ?></a>
        <?php if ($s['footer_tagline'] !== ''): ?><p><?= e($s['footer_tagline']) ?></p><?php endif; ?>
      </div>
      <div>
        <?php if ($nav): ?>
        <h4>Explore</h4>
        <?php foreach ($nav as $id => $label): ?><a href="#<?= e($id) ?>"><?= e($label) ?></a><?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($show('contact') && ($c['contact']['email'] !== '' || $c['contact']['phone'] !== '')): ?>
        <h4>Contact</h4>
        <?php if ($c['contact']['email'] !== ''): ?><a href="mailto:<?= e($c['contact']['email']) ?>"><?= e($c['contact']['email']) ?></a><?php endif; ?>
        <?php if ($c['contact']['phone'] !== ''): ?><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $c['contact']['phone'])) ?>"><?= e($c['contact']['phone']) ?></a><?php endif; ?>
        <?php endif; ?>
      </div>
      <div>
        <?php if ($show('newsletter')): $n = $c['newsletter']; ?>
        <h4><?= e($n['heading']) ?></h4>
        <form class="newsletter" id="newsletter" action="api/submit.php" method="post">
          <input type="hidden" name="type" value="newsletter">
          <div class="hp" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
          <input type="email" name="email" placeholder="<?= e($n['placeholder']) ?>" aria-label="Email address" required maxlength="200">
          <button class="btn btn-small" type="submit"><?= e($n['button_label'] ?: 'Subscribe') ?></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <div class="container footer-bottom">
      <p>© <?= date('Y') ?> <?= e($s['copyright_name']) ?>. All rights reserved.</p>
      <a href="#top" class="back-to-top">Back to top ↑</a>
    </div>
  </footer>

  <script src="assets/script.js?v=<?= filemtime(__DIR__ . '/assets/script.js') ?>"></script>
</body>
</html>
