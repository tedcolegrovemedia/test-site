<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/_layout.php';
require __DIR__ . '/_fields.php';
require_login();

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    verify_csrf();
    $input = $_POST['content'] ?? [];
    $content = [];
    foreach (schema() as $key => $section) {
        $content[$key] = sanitize_fields(section_fields($section), $input[$key] ?? []);
    }
    try {
        save_content($content, 'Replaced by an edit');
    } catch (RuntimeException $ex) {
        if ($wantsJson) {
            json_response(['ok' => false, 'error' => $ex->getMessage()], 500);
        }
        flash($ex->getMessage(), 'error');
        header('Location: index.php');
        exit;
    }
    if ($wantsJson) {
        json_response(['ok' => true, 'message' => 'Changes published', 'saved_at' => date('c')]);
    }
    flash('Changes published.');
    header('Location: index.php' . (isset($_POST['section']) ? '#' . rawurlencode((string) $_POST['section']) : ''));
    exit;
}

$content = load_content();
$sections = schema();

admin_page_start('Editor', 'editor');
?>
<main class="editor" id="editor">
  <aside class="section-nav" aria-label="Sections">
    <p class="nav-heading">Sections</p>
    <?php foreach ($sections as $key => $section):
        $hidden = !empty($section['toggle']) && empty($content[$key]['visible']); ?>
      <button type="button" class="section-link<?= $hidden ? ' is-hidden' : '' ?>" data-section="<?= e($key) ?>">
        <span class="section-icon" aria-hidden="true"><?= e($section['icon'] ?? '•') ?></span>
        <span><?= e($section['label']) ?></span>
        <?php if ($hidden): ?><span class="tag">Hidden</span><?php endif; ?>
      </button>
    <?php endforeach; ?>
  </aside>

  <form class="editor-form" id="editor-form" method="post" action="index.php" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="section" id="current-section" value="settings">

    <?php foreach ($sections as $key => $section): ?>
    <section class="panel" data-panel="<?= e($key) ?>" id="panel-<?= e($key) ?>">
      <header class="panel-head">
        <h1><?= e($section['label']) ?></h1>
      </header>
      <div class="panel-body">
        <?php foreach (section_fields($section) as $fieldKey => $field): ?>
          <?php render_field("content[{$key}][{$fieldKey}]", $field, $content[$key][$fieldKey] ?? ''); ?>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endforeach; ?>

    <div class="savebar">
      <span class="save-status" id="save-status" aria-live="polite">All changes published</span>
      <button type="button" class="btn btn-ghost" id="toggle-preview">Preview</button>
      <button type="submit" class="btn" id="save-btn">Publish changes</button>
    </div>
  </form>

  <div class="preview" id="preview">
    <div class="preview-bar">
      <span>Live preview</span>
      <div class="device-toggle" role="group" aria-label="Preview size">
        <button type="button" class="icon-btn active" data-device="desktop" title="Desktop">🖥</button>
        <button type="button" class="icon-btn" data-device="mobile" title="Mobile">📱</button>
        <button type="button" class="btn btn-ghost btn-sm preview-close" id="close-preview">Close</button>
      </div>
    </div>
    <div class="preview-frame">
      <iframe id="preview-frame" src="../index.php" title="Site preview"></iframe>
    </div>
  </div>
</main>
<?php admin_page_end(); ?>
