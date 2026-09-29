<?php
// Shared page chrome for the admin panel.

function unread_count(): int
{
    return count(array_filter(store_read('messages', []), fn ($m) => empty($m['read'])));
}

function admin_page_start(string $title, string $active = '', bool $bare = false): void
{
    start_session(); // must happen before any output
    send_security_headers();
    header('Cache-Control: no-store');
    $site = load_content()['settings']['site_name'];
    $v = filemtime(__DIR__ . '/admin.css');
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <title><?= e($title) ?> · <?= e($site) ?> CMS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="admin.css?v=<?= $v ?>">
</head>
<body class="<?= $bare ? 'bare' : '' ?>">
<?php if (!$bare): $unread = unread_count(); ?>
  <header class="topbar">
    <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true"></span><?= e($site) ?> <small>CMS</small></a>
    <nav class="topnav" aria-label="Admin">
      <a href="index.php" class="<?= $active === 'editor' ? 'active' : '' ?>">Editor</a>
      <a href="messages.php" class="<?= $active === 'messages' ? 'active' : '' ?>">Inbox<?php if ($unread): ?> <span class="pill"><?= $unread ?></span><?php endif; ?></a>
      <a href="history.php" class="<?= $active === 'history' ? 'active' : '' ?>">History</a>
      <a href="account.php" class="<?= $active === 'account' ? 'active' : '' ?>">Account</a>
    </nav>
    <div class="topbar-actions">
      <a class="btn btn-ghost btn-sm" href="../" target="_blank" rel="noopener">View site ↗</a>
      <form method="post" action="logout.php"><?= csrf_field() ?><button class="btn btn-ghost btn-sm" type="submit">Log out</button></form>
    </div>
  </header>
<?php endif; ?>
<?php if ($f = flash()): ?>
  <div class="toast toast-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
<?php endif; ?>
    <?php
}

function admin_page_end(): void
{
    $v = filemtime(__DIR__ . '/admin.js');
    ?>
  <script src="admin.js?v=<?= $v ?>"></script>
</body>
</html>
    <?php
}
