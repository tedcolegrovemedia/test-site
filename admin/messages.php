<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_login();

$tab = ($_GET['tab'] ?? '') === 'subscribers' ? 'subscribers' : 'messages';

// CSV download of subscribers.
if ($tab === 'subscribers' && isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="subscribers-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'subscribed_at'], ',', '"', '');
    foreach (store_read('subscribers', []) as $sub) {
        // Prefix cells that spreadsheets would treat as formulas.
        $email = preg_match('/^[=+\-@\t\r]/', $sub['email']) ? "'" . $sub['email'] : $sub['email'];
        fputcsv($out, [$email, date('Y-m-d H:i', $sub['subscribed_at'])], ',', '"', '');
    }
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $id = (string) ($_POST['id'] ?? '');

    if ($tab === 'messages') {
        store_update('messages', function (array &$messages) use ($action, $id) {
            foreach ($messages as $i => &$m) {
                if ($action === 'mark_all_read') {
                    $m['read'] = true;
                } elseif ($m['id'] === $id) {
                    if ($action === 'delete') {
                        unset($messages[$i]);
                    } elseif ($action === 'toggle_read') {
                        $m['read'] = empty($m['read']);
                    }
                }
            }
            unset($m);
            $messages = array_values($messages);
        });
        flash(match ($action) {
            'delete' => 'Message deleted.',
            'mark_all_read' => 'All messages marked as read.',
            default => 'Message updated.',
        });
    } else {
        store_update('subscribers', function (array &$subs) use ($action, $id) {
            if ($action === 'delete') {
                $subs = array_values(array_filter($subs, fn ($s) => strcasecmp($s['email'], $id) !== 0));
            }
        });
        flash('Subscriber removed.');
    }
    header('Location: messages.php?tab=' . $tab);
    exit;
}

$messages = store_read('messages', []);
$subscribers = store_read('subscribers', []);
$unread = count(array_filter($messages, fn ($m) => empty($m['read'])));

admin_page_start('Inbox', 'messages');
?>
<main class="page">
  <div class="page-head">
    <h1>Inbox</h1>
    <div class="tabs">
      <a href="?tab=messages" class="<?= $tab === 'messages' ? 'active' : '' ?>">Messages <span class="count"><?= count($messages) ?></span></a>
      <a href="?tab=subscribers" class="<?= $tab === 'subscribers' ? 'active' : '' ?>">Subscribers <span class="count"><?= count($subscribers) ?></span></a>
    </div>
  </div>

  <?php if ($tab === 'messages'): ?>
    <?php if ($unread): ?>
    <form method="post" class="toolbar">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="mark_all_read">
      <button class="btn btn-ghost btn-sm" type="submit">Mark all as read</button>
    </form>
    <?php endif; ?>

    <?php if (!$messages): ?>
      <div class="empty">
        <p class="empty-icon">📭</p>
        <p>No messages yet. Submissions from your site's contact form will appear here.</p>
      </div>
    <?php endif; ?>

    <div class="message-list">
      <?php foreach ($messages as $m): ?>
      <article class="message card<?= empty($m['read']) ? ' unread' : '' ?>">
        <header>
          <div>
            <strong><?= e($m['name']) ?></strong>
            <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
          </div>
          <time datetime="<?= date('c', $m['received_at']) ?>" data-local-time><?= date('M j, Y H:i', $m['received_at']) ?> UTC</time>
        </header>
        <p class="message-body"><?= nl2br(e($m['message'])) ?></p>
        <footer>
          <a class="btn btn-sm" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: your message') ?>">Reply</a>
          <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($m['id']) ?>">
            <input type="hidden" name="action" value="toggle_read">
            <button class="btn btn-ghost btn-sm" type="submit"><?= empty($m['read']) ? 'Mark read' : 'Mark unread' ?></button>
          </form>
          <form method="post" data-confirm="Delete this message? This can't be undone.">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= e($m['id']) ?>">
            <input type="hidden" name="action" value="delete">
            <button class="btn btn-ghost btn-sm danger" type="submit">Delete</button>
          </form>
        </footer>
      </article>
      <?php endforeach; ?>
    </div>

  <?php else: ?>
    <?php if ($subscribers): ?>
    <div class="toolbar">
      <a class="btn btn-ghost btn-sm" href="?tab=subscribers&amp;export=1">Download CSV</a>
    </div>
    <div class="card table-card">
      <table>
        <thead><tr><th>Email</th><th>Subscribed</th><th></th></tr></thead>
        <tbody>
        <?php foreach (array_reverse($subscribers) as $sub): ?>
          <tr>
            <td><?= e($sub['email']) ?></td>
            <td><time datetime="<?= date('c', $sub['subscribed_at']) ?>" data-local-time><?= date('M j, Y', $sub['subscribed_at']) ?></time></td>
            <td class="right">
              <form method="post" data-confirm="Remove this subscriber?">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e($sub['email']) ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn btn-ghost btn-sm danger" type="submit">Remove</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
      <div class="empty">
        <p class="empty-icon">📰</p>
        <p>No subscribers yet. Newsletter signups from your site's footer will appear here.</p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>
<?php admin_page_end(); ?>
