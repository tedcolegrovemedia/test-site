<?php
// Receives the public contact form and newsletter signups.

require __DIR__ . '/../includes/bootstrap.php';
send_security_headers();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    json_response(['ok' => false, 'error' => 'Method not allowed.'], 405);
}

$type = $_POST['type'] ?? '';
$field = fn (string $key, int $max) => mb_substr(trim((string) ($_POST[$key] ?? '')), 0, $max);

// Bots fill in the hidden "website" field; pretend it worked and drop it.
if ($field('website', 200) !== '') {
    json_response(['ok' => true]);
}

if (!rate_limit('submit:' . client_ip(), 8, 600)) {
    json_response(['ok' => false, 'error' => 'Too many submissions. Please try again in a few minutes.'], 429);
}

$content = load_content();
$email = $field('email', 200);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Please enter a valid email address.'], 422);
}

if ($type === 'contact') {
    if (empty($content['contact']['visible'])) {
        json_response(['ok' => false, 'error' => 'The contact form is closed.'], 403);
    }
    $name = $field('name', 100);
    $message = $field('message', 5000);
    if ($name === '' || mb_strlen($message) < 10) {
        json_response(['ok' => false, 'error' => 'Please fill in your name and a message of at least 10 characters.'], 422);
    }

    store_update('messages', function (array &$messages) use ($name, $email, $message) {
        array_unshift($messages, [
            'id' => bin2hex(random_bytes(8)),
            'name' => $name,
            'email' => $email,
            'message' => $message,
            'received_at' => time(),
            'read' => false,
        ]);
        $messages = array_slice($messages, 0, 2000);
    });

    $notify = $content['settings']['notify_email'];
    if ($notify !== '' && function_exists('mail')) {
        $site = $content['settings']['site_name'] ?: 'your website';
        $subject = '=?UTF-8?B?' . base64_encode("New message from $site") . '?=';
        $body = "Name: $name\nEmail: $email\n\n$message\n";
        // $email passed FILTER_VALIDATE_EMAIL, so it cannot contain header-injecting newlines.
        @mail($notify, $subject, $body, "Reply-To: $email\r\nContent-Type: text/plain; charset=UTF-8");
    }

    json_response(['ok' => true]);
}

if ($type === 'newsletter') {
    if (empty($content['newsletter']['visible'])) {
        json_response(['ok' => false, 'error' => 'Signups are closed.'], 403);
    }
    store_update('subscribers', function (array &$subs) use ($email) {
        foreach ($subs as $sub) {
            if (strcasecmp($sub['email'], $email) === 0) {
                return;
            }
        }
        $subs[] = ['email' => $email, 'subscribed_at' => time()];
    });
    json_response(['ok' => true]);
}

json_response(['ok' => false, 'error' => 'Unknown form.'], 400);
