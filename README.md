# Lumen CMS

A small flat-file CMS for the Lumen landing page. No database is needed. Content,
messages and settings are stored as JSON in `data/`.

## Requirements

- PHP 8.1+ (with the standard `json` and `mbstring` extensions)
- A web server that runs PHP (Apache, nginx + PHP-FPM, or any shared host)

## Install

1. Upload the contents of this folder to your web root (or a subfolder).
2. Make `data/` writable by PHP, e.g. `chmod -R 775 data`.
3. **Immediately** open `/admin/` in your browser and create your admin account.
   The setup page disables itself once an account exists, so do this right after
   uploading, before anyone else can.

To try it locally:

```bash
git clone https://github.com/tedcolegrovemedia/test-site.git
cd test-site
php -S localhost:8766
```

Then open http://localhost:8766/admin/.

## Using it

| Page      | What it does |
|-----------|--------------|
| Editor    | Edit every section, show or hide it, and reorder list items. A live preview sits alongside. Press **Publish changes** (or ⌘/Ctrl+S) to go live. |
| Inbox     | Contact form messages and newsletter subscribers (subscribers can be exported as CSV). |
| History   | Each publish saves the previous version, and you can restore any of the last 25. You can also download or import a full backup. |
| Account   | Change your password. This logs out every other session. |

To get emailed when a message arrives, set **Site settings → Email new messages to**.
This needs PHP `mail()` to work on your host.

## Structure

```
index.php              Public site, rendered from the saved content
assets/                Site CSS and JS
api/submit.php         Contact form and newsletter endpoint
admin/                 Admin panel
includes/schema.php    Defines every editable field (edit this to add fields)
includes/default-content.php   Starting content
data/                  Saved content, messages, account, revisions (keep private)
```

To add a new editable field, add it to `includes/schema.php` (and optionally a default in
`default-content.php`), then output it in `index.php` with `<?= e($c['section']['field']) ?>`.
The editor form updates automatically.

## Security notes

- Every file in `data/` starts with a PHP exit guard, so it can't be downloaded even
  where `.htaccess` is ignored (e.g. nginx). On nginx, it's still good practice to add
  `location ~ ^/(data|includes)/ { deny all; }`.
- Passwords are hashed with `password_hash`. Sessions are HttpOnly and SameSite=Strict,
  and every admin form is protected with a CSRF token.
- Login is limited to 5 attempts per 15 minutes per IP. Form submissions are rate-limited
  and include a spam honeypot.
- All content is escaped on output, and links are limited to `#anchors`, relative paths,
  `http(s)`, `mailto:` and `tel:`.
- Serve the site over HTTPS in production.
