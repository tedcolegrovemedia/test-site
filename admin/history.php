<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require_login();

// Download the current content as a backup file.
if (isset($_GET['export'])) {
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="site-content-' . date('Y-m-d') . '.json"');
    echo json_encode(load_content(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Sanitize an arbitrary content array against the schema, filling gaps with defaults. */
function clean_import(array $data): array
{
    $out = [];
    foreach (schema() as $key => $section) {
        $out[$key] = sanitize_fields(section_fields($section), $data[$key] ?? [], default_content()[$key] ?? []);
    }
    return $out;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'restore') {
        $id = (string) ($_POST['id'] ?? '');
        $rev = valid_revision_id($id) ? store_read('revisions/' . $id) : null;
        if (!$rev) {
            flash('That version could not be found.', 'error');
        } else {
            save_content(clean_import($rev['content'] ?? []), 'Replaced by restoring the ' . date('M j H:i', $rev['saved_at']) . ' UTC version');
            flash('Version restored and published.');
        }
    }

    if ($action === 'import') {
        $file = $_FILES['backup'] ?? null;
        $data = null;
        if ($file && $file['error'] === UPLOAD_ERR_OK && $file['size'] < 2_000_000) {
            $data = json_decode((string) file_get_contents($file['tmp_name']), true);
        }
        if (!is_array($data)) {
            flash('That file is not a valid backup.', 'error');
        } else {
            save_content(clean_import($data), 'Replaced by a backup import');
            flash('Backup imported and published.');
        }
    }

    header('Location: history.php');
    exit;
}

$revisions = list_revisions();
$current = is_file(store_path('content')) ? filemtime(store_path('content')) : null;

admin_page_start('History', 'history');
?>
<main class="page">
  <h1>History</h1>
  <p class="muted">Every time you publish, the previous version is saved here. The last <?= MAX_REVISIONS ?> versions are kept.</p>

  <section class="card">
    <div class="revision current">
      <div>
        <strong>Current version</strong>
        <?php if ($current): ?><span class="muted">published <time datetime="<?= date('c', $current) ?>" data-local-time><?= date('M j, Y H:i', $current) ?> UTC</time></span><?php endif; ?>
      </div>
      <span class="tag tag-live">Live</span>
    </div>
    <?php foreach ($revisions as $rev): ?>
    <div class="revision">
      <div>
        <strong><time datetime="<?= date('c', $rev['saved_at']) ?>" data-local-time><?= date('M j, Y H:i', $rev['saved_at']) ?> UTC</time></strong>
        <span class="muted"><?= e($rev['note']) ?></span>
      </div>
      <form method="post" data-confirm="Restore this version? The current version will be saved to history first.">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="restore">
        <input type="hidden" name="id" value="<?= e($rev['id']) ?>">
        <button class="btn btn-ghost btn-sm" type="submit">Restore</button>
      </form>
    </div>
    <?php endforeach; ?>
    <?php if (!$revisions): ?><p class="muted pad">No earlier versions yet.</p><?php endif; ?>
  </section>

  <section class="card">
    <h2>Backup</h2>
    <p class="muted">Download all site content as a file, or restore from one you downloaded earlier.</p>
    <div class="backup-actions">
      <a class="btn btn-ghost" href="?export=1">Download backup</a>
      <form method="post" enctype="multipart/form-data" class="import-form" data-confirm="Replace the site content with this backup? The current version will be saved to history first.">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="import">
        <input type="file" name="backup" accept="application/json,.json" required>
        <button class="btn btn-ghost" type="submit">Import</button>
      </form>
    </div>
  </section>
</main>
<?php admin_page_end(); ?>
