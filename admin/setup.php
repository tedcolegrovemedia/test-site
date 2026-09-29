<?php
// First-run page: creates the admin account. Disabled once an account exists.

require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';

if (admin_account()) {
    header('Location: login.php');
    exit;
}

$error = null;
$username = '';
$writable = is_writable(DATA_DIR) && is_writable(DATA_DIR . '/revisions');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $error = !preg_match('/^[A-Za-z0-9_.@-]{3,50}$/', $username)
        ? 'Username must be 3–50 letters, numbers or . _ @ -'
        : validate_password($password, (string) ($_POST['confirm'] ?? ''));

    if (!$error) {
        // Lock so two simultaneous setup requests can't both create an account.
        $claimed = store_update('auth', function (array &$auth) use ($username, $password) {
            if ($auth) {
                return false;
            }
            $auth = ['username' => $username, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'version' => 1];
            return true;
        });
        if ($claimed) {
            if (!is_file(store_path('content'))) {
                store_write('content', load_content());
            }
            log_in(admin_account());
            flash('Welcome! Your site is ready to edit.');
            header('Location: index.php');
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

admin_page_start('Set up', '', true);
?>
<main class="auth-card">
  <div class="brand brand-lg"><span class="brand-mark" aria-hidden="true"></span>Welcome</div>
  <p class="muted">Create the admin account for this site. This page stops working as soon as an account exists.</p>

  <?php if (!$writable): ?>
    <div class="alert alert-error">PHP can't write to the <code>data/</code> folder. Make it writable (e.g. <code>chmod -R 775 data</code>) and reload.</div>
  <?php endif; ?>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" class="stack">
    <?= csrf_field() ?>
    <div class="field">
      <label for="username">Username</label>
      <input id="username" name="username" value="<?= e($username) ?>" autocomplete="username" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="new-password" minlength="10" required>
      <small class="help">At least 10 characters.</small>
    </div>
    <div class="field">
      <label for="confirm">Confirm password</label>
      <input id="confirm" name="confirm" type="password" autocomplete="new-password" minlength="10" required>
    </div>
    <button class="btn btn-block" type="submit"<?= $writable ? '' : ' disabled' ?>>Create account</button>
  </form>
</main>
<?php admin_page_end(); ?>
