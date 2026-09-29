<?php
// Renders editor form controls from the content schema.

function field_id(string $name): string
{
    return 'f-' . trim(preg_replace('/[^a-z0-9_]+/i', '-', $name), '-');
}

function render_field(string $name, array $field, mixed $value): void
{
    $id = field_id($name);
    $type = $field['type'];
    $label = $field['label'] ?? '';
    $help = $field['help'] ?? '';
    $max = $field['max'] ?? null;

    if ($type === 'list') {
        render_list($name, $field, is_array($value) ? $value : []);
        return;
    }

    if ($type === 'checkbox') {
        ?>
        <div class="field field-check">
          <input type="hidden" name="<?= e($name) ?>" value="0">
          <label class="check">
            <input type="checkbox" id="<?= e($id) ?>" name="<?= e($name) ?>" value="1"<?= $value ? ' checked' : '' ?>>
            <span><?= e($label) ?></span>
          </label>
        </div>
        <?php
        return;
    }
    ?>
    <div class="field field-<?= e($type) ?>">
      <label for="<?= e($id) ?>"><?= e($label) ?></label>
      <?php if ($type === 'textarea' || $type === 'lines'):
          $text = $type === 'lines' ? implode("\n", (array) $value) : (string) $value;
          $rows = $field['rows'] ?? ($type === 'lines' ? max(3, min(8, count((array) $value) + 1)) : 4);
          ?>
        <textarea id="<?= e($id) ?>" name="<?= e($name) ?>" rows="<?= (int) $rows ?>"<?= $max && $type === 'textarea' ? ' maxlength="' . (int) $max . '"' : '' ?>><?= e($text) ?></textarea>
      <?php elseif ($type === 'color'): ?>
        <div class="color-input">
          <input type="color" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>">
          <code><?= e($value) ?></code>
        </div>
      <?php else:
          $inputType = match ($type) {
              'email' => 'email',
              'number' => 'text',
              default => 'text',
          };
          ?>
        <input type="<?= $inputType ?>" id="<?= e($id) ?>" name="<?= e($name) ?>" value="<?= e($value) ?>"
          <?= $max ? 'maxlength="' . (int) $max . '"' : '' ?>
          <?= $type === 'number' ? 'inputmode="decimal" pattern="-?[0-9]*\.?[0-9]+"' : '' ?>
          <?= $type === 'link' ? 'placeholder="#section, /page or https://…"' : '' ?>>
      <?php endif; ?>
      <?php if ($help): ?><small class="help"><?= e($help) ?></small><?php endif; ?>
    </div>
    <?php
}

function render_list(string $name, array $field, array $items): void
{
    $itemLabel = $field['item_label'] ?? 'item';
    $max = $field['max'] ?? 50;
    ?>
    <div class="list-field" data-list data-max="<?= (int) $max ?>">
      <div class="list-head">
        <h3><?= e($field['label']) ?></h3>
        <span class="list-count"></span>
      </div>
      <div class="list-items" data-items>
        <?php foreach (array_values($items) as $i => $item): ?>
          <?php render_list_item($name, $field, $i, $item); ?>
        <?php endforeach; ?>
      </div>
      <template data-template>
        <?php render_list_item($name, $field, '__i__', [], true); ?>
      </template>
      <button type="button" class="btn btn-ghost btn-add" data-add>+ Add <?= e($itemLabel) ?></button>
    </div>
    <?php
}

function render_list_item(string $name, array $field, int|string $index, array $item, bool $open = false): void
{
    $first = array_key_first($field['fields']);
    $title = trim((string) ($item[$first] ?? ''));
    ?>
    <details class="list-item" data-item<?= $open ? ' open' : '' ?>>
      <summary>
        <span class="item-title" data-title-from="<?= e(field_id("{$name}[{$index}][{$first}]")) ?>"><?= e($title !== '' ? $title : 'New ' . ($field['item_label'] ?? 'item')) ?></span>
        <span class="item-actions">
          <button type="button" class="icon-btn" data-move="-1" aria-label="Move up" title="Move up">↑</button>
          <button type="button" class="icon-btn" data-move="1" aria-label="Move down" title="Move down">↓</button>
          <button type="button" class="icon-btn danger" data-remove aria-label="Remove" title="Remove">✕</button>
        </span>
      </summary>
      <div class="item-body">
        <?php foreach ($field['fields'] as $key => $sub): ?>
          <?php render_field("{$name}[{$index}][{$key}]", $sub, $item[$key] ?? ($sub['type'] === 'color' ? '#000000' : '')); ?>
        <?php endforeach; ?>
      </div>
    </details>
    <?php
}
