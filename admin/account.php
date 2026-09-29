<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_login();

$account = admin_account();
$error = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $current = (string) ($_POST['current'] ?? '');
    $new = (string) ($_POST['password'] ?? '');

    if (!rate_limit('account:' . client_ip(), 5, 900)) {
        $error = 'Too many attempts. Please wait a few minutes.';
    } elseif (!password_verify($current, $account['hash'])) {
        $error = 'Your current password is incorrect.';
    } elseif ($error = validate_password($new, (string) ($_POST['confirm'] ?? ''))) {
        // $error set by validate_password
    } else {
        store_update('auth', function (array &$auth) use ($new) {
            $auth['hash'] = password_hash($new, PASSWORD_DEFAULT);
            $auth['version'] = ($auth['version'] ?? 0) + 1;
        });
        log_in(admin_account());
        flash('Password changed. Other sessions have been logged out.');
        header('Location: account.php');
        exit;
    }
}

admin_page_start('Account', 'account');
?>
<main class="page">
  <h1>Account</h1>
  <p class="muted">Signed in as <strong><?= e($account['username']) ?></strong>.</p>

  <section class="card">
    <h2>Change password</h2>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack narrow-form">
      <?= csrf_field() ?>
      <input type="text" name="username" value="<?= e($account['username']) ?>" autocomplete="username" hidden>
      <div class="field">
        <label for="current">Current password</label>
        <input id="current" name="current" type="password" autocomplete="current-password" required>
      </div>
      <div class="field">
        <label for="password">New password</label>
        <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>
        <small class="help">At least 10 characters.</small>
      </div>
      <div class="field">
        <label for="confirm">Confirm new password</label>
        <input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" required>
      </div>
      <div><button class="btn" type="submit">Update password</button></div>
    </form>
  </section>
</main>
<?php admin_page_end(); ?>
