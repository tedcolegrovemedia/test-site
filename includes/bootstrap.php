<?php
declare(strict_types=1);

/*
 * Core helpers shared by the public site, the API and the admin panel.
 *
 * All data lives in /data as "*.json.php" files. Each file starts with a PHP
 * exit guard, so even on servers that ignore .htaccess the contents can never
 * be downloaded directly.
 */

const DATA_DIR = __DIR__ . '/../data';
const STORE_GUARD = "<?php http_response_code(404); exit; ?>\n";
const MAX_REVISIONS = 25;

date_default_timezone_set('UTC');

// ---------- Output ----------

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
}

// ---------- Flat-file storage ----------

function store_path(string $name): string
{
    return DATA_DIR . '/' . $name . '.json.php';
}

function store_read(string $name, mixed $default = null): mixed
{
    $path = store_path($name);
    if (!is_file($path)) {
        return $default;
    }
    $raw = file_get_contents($path);
    if ($raw === false) {
        return $default;
    }
    if (str_starts_with($raw, STORE_GUARD)) {
        $raw = substr($raw, strlen(STORE_GUARD));
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function store_write(string $name, array $data): void
{
    $path = store_path($name);
    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Cannot create directory $dir");
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $tmp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($tmp, STORE_GUARD . $json, LOCK_EX) === false || !rename($tmp, $path)) {
        @unlink($tmp);
        throw new RuntimeException("Cannot write $path — check that /data is writable by PHP.");
    }
}

/**
 * Read-modify-write a store under an exclusive lock, so concurrent requests
 * (e.g. two contact-form submissions) can't overwrite each other.
 */
function store_update(string $name, callable $fn, array $default = []): mixed
{
    $lock = fopen(DATA_DIR . '/.' . $name . '.lock', 'c');
    flock($lock, LOCK_EX);
    try {
        $data = store_read($name, $default);
        $result = $fn($data);
        store_write($name, $data);
        return $result;
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}

// ---------- Content ----------

function schema(): array
{
    static $schema;
    return $schema ??= require __DIR__ . '/schema.php';
}

function default_content(): array
{
    static $defaults;
    return $defaults ??= require __DIR__ . '/default-content.php';
}

/** Current site content, with any missing fields filled from the defaults. */
function load_content(): array
{
    $stored = store_read('content', []);
    $defaults = default_content();
    $content = [];
    foreach (schema() as $key => $section) {
        $content[$key] = sanitize_fields(
            section_fields($section),
            $stored[$key] ?? $defaults[$key] ?? [],
            $defaults[$key] ?? []
        );
    }
    return $content;
}

function save_content(array $content, string $note = 'Replaced by an edit'): void
{
    if (is_file(store_path('content'))) {
        snapshot_revision($note);
    }
    store_write('content', $content);
}

/** Fields for a section, including the implicit visibility toggle. */
function section_fields(array $section): array
{
    $fields = $section['fields'];
    if (!empty($section['toggle'])) {
        $fields = ['visible' => ['type' => 'checkbox', 'label' => 'Show this section on the site']] + $fields;
    }
    return $fields;
}

/**
 * Clean untrusted input against a field schema. Unknown keys are dropped,
 * strings are trimmed and length-limited, and each type is validated.
 * When $defaults is given, keys missing from $input fall back to it.
 */
function sanitize_fields(array $fields, mixed $input, ?array $defaults = null): array
{
    $input = is_array($input) ? $input : [];
    $out = [];
    foreach ($fields as $key => $field) {
        if (!array_key_exists($key, $input) && $defaults !== null && array_key_exists($key, $defaults)) {
            $value = $defaults[$key];
        } else {
            $value = $input[$key] ?? null;
        }
        $out[$key] = sanitize_value($field, $value);
    }
    return $out;
}

function sanitize_value(array $field, mixed $value): mixed
{
    $type = $field['type'];

    if ($type === 'checkbox') {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    if ($type === 'list') {
        $items = [];
        foreach (is_array($value) ? $value : [] as $item) {
            $items[] = sanitize_fields($field['fields'], $item);
            if (count($items) >= ($field['max'] ?? 50)) {
                break;
            }
        }
        return $items;
    }

    if ($type === 'lines') {
        $lines = is_array($value) ? $value : preg_split('/\R/u', (string) $value);
        $lines = array_map(fn ($l) => mb_substr(trim((string) $l), 0, 200), $lines);
        return array_values(array_slice(array_filter($lines, 'strlen'), 0, $field['max'] ?? 30));
    }

    $value = is_scalar($value) ? trim((string) $value) : '';
    $max = $field['max'] ?? ($type === 'textarea' ? 2000 : 200);
    $value = mb_substr($value, 0, $max);

    return match ($type) {
        'color' => preg_match('/^#[0-9a-f]{6}$/i', $value) ? strtolower($value) : ($field['default'] ?? '#000000'),
        'number' => is_numeric($value) ? $value : '0',
        'email' => $value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : '',
        'link' => safe_link($value),
        default => $value,
    };
}

/** Only allow anchors, relative paths and safe schemes — never javascript: etc. */
function safe_link(string $url): string
{
    if ($url === '') {
        return '';
    }
    if (preg_match('~^(#|/(?!/)|\./|https?://|mailto:|tel:)~i', $url)) {
        return $url;
    }
    return '';
}

// ---------- Revisions ----------

function snapshot_revision(string $note): void
{
    $current = store_read('content');
    if ($current === null) {
        return;
    }
    $id = date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    // saved_at is when this version went live, i.e. when the content file was last written.
    $publishedAt = filemtime(store_path('content')) ?: time();
    store_write('revisions/' . $id, ['saved_at' => $publishedAt, 'note' => $note, 'content' => $current]);

    $files = glob(DATA_DIR . '/revisions/*.json.php') ?: [];
    rsort($files);
    foreach (array_slice($files, MAX_REVISIONS) as $old) {
        @unlink($old);
    }
}

function list_revisions(): array
{
    $files = glob(DATA_DIR . '/revisions/*.json.php') ?: [];
    rsort($files);
    $out = [];
    foreach ($files as $file) {
        $id = basename($file, '.json.php');
        $rev = store_read('revisions/' . $id);
        if ($rev) {
            $out[] = ['id' => $id, 'saved_at' => $rev['saved_at'] ?? 0, 'note' => $rev['note'] ?? ''];
        }
    }
    return $out;
}

function valid_revision_id(string $id): bool
{
    return (bool) preg_match('/^\d{8}-\d{6}-[0-9a-f]{6}$/', $id);
}

// ---------- Rate limiting ----------

/** Returns false when $key has exceeded $max hits within $window seconds. */
function rate_limit(string $key, int $max, int $window): bool
{
    $bucket = hash('sha256', $key);
    return store_update('ratelimit', function (array &$data) use ($bucket, $max, $window) {
        $now = time();
        foreach ($data as $k => $hits) {
            $data[$k] = array_values(array_filter($hits, fn ($t) => $t > $now - 86400));
            if (!$data[$k]) {
                unset($data[$k]);
            }
        }
        $recent = array_filter($data[$bucket] ?? [], fn ($t) => $t > $now - $window);
        if (count($recent) >= $max) {
            return false;
        }
        $data[$bucket][] = $now;
        return true;
    });
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// ---------- Sessions, auth and CSRF ----------

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') === '443';
    session_name('cms_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function admin_account(): ?array
{
    return store_read('auth');
}

function is_logged_in(): bool
{
    start_session();
    $account = admin_account();
    return $account
        && ($_SESSION['user'] ?? null) === $account['username']
        // Changing the password invalidates every other session.
        && ($_SESSION['auth_version'] ?? null) === ($account['version'] ?? 0)
        && (time() - ($_SESSION['last_seen'] ?? 0)) < 60 * 60 * 8;
}

function require_login(): void
{
    if (!admin_account()) {
        header('Location: setup.php');
        exit;
    }
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_seen'] = time();
}

function log_in(array $account): void
{
    start_session();
    session_regenerate_id(true);
    $_SESSION['user'] = $account['username'];
    $_SESSION['auth_version'] = $account['version'] ?? 0;
    $_SESSION['last_seen'] = time();
}

function csrf_token(): string
{
    start_session();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    start_session();
    $sent = $_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('Your session expired. Go back, refresh the page and try again.');
    }
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function validate_password(string $password, string $confirm): ?string
{
    if (mb_strlen($password) < 10) {
        return 'Password must be at least 10 characters.';
    }
    if ($password !== $confirm) {
        return 'Passwords do not match.';
    }
    return null;
}
