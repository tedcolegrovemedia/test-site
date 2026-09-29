<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';

$account = admin_account();
if (!$account) {
    header('Location: setup.php');
    exit;
}
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = null;
$username = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!rate_limit('login:' . client_ip(), 5, 900)) {
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    } elseif (hash_equals($account['username'], $username) && password_verify($password, $account['hash'])) {
        if (password_needs_rehash($account['hash'], PASSWORD_DEFAULT)) {
            store_update('auth', function (array &$auth) use ($password) {
                $auth['hash'] = password_hash($password, PASSWORD_DEFAULT);
            });
        }
        log_in($account);
        header('Location: index.php');
        exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}

admin_page_start('Log in', '', true);
?>
<main class="auth-card">
  <div class="brand brand-lg"><span class="brand-mark" aria-hidden="true"></span><?= e(load_content()['settings']['site_name']) ?> <small>CMS</small></div>
  <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
  <form method="post" class="stack">
    <?= csrf_field() ?>
    <div class="field">
      <label for="username">Username</label>
      <input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
    </div>
    <button class="btn btn-block" type="submit">Log in</button>
  </form>
  <p class="muted small"><a href="../">← Back to site</a></p>
</main>
<?php admin_page_end(); ?>
