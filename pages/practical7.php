<?php
session_start();

$storageFile = __DIR__ . '/../data/practical7-submissions.json';
$errors = [];
$success = '';
$values = [
    'fullName' => '',
    'email' => '',
    'phone' => '',
    'message' => ''
];

if (!isset($_SESSION['practical7_csrf'])) {
    $_SESSION['practical7_csrf'] = bin2hex(random_bytes(32));
}

function practical7Escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function practical7PostString($key)
{
    return isset($_POST[$key]) && is_string($_POST[$key])
        ? trim($_POST[$key])
        : '';
}

function practical7ReadSubmissions($file, &$error)
{
    $error = '';
    if (!file_exists($file)) {
        return [];
    }

    $handle = fopen($file, 'rb');
    if ($handle === false) {
        $error = 'Stored submissions could not be read.';
        error_log('Practical 7: unable to open the submissions file for reading.');
        return [];
    }

    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        $error = 'Stored submissions are temporarily unavailable.';
        error_log('Practical 7: unable to lock the submissions file for reading.');
        return [];
    }

    $contents = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    if ($contents === false || trim($contents) === '') {
        if ($contents === false) {
            $error = 'Stored submissions could not be read.';
            error_log('Practical 7: failed while reading the submissions file.');
        }
        return [];
    }

    try {
        $submissions = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        $error = 'Stored submissions could not be read because the JSON file is invalid.';
        error_log('Practical 7: invalid submissions JSON: ' . $exception->getMessage());
        return [];
    }

    if (!is_array($submissions)
        || ($submissions !== [] && array_keys($submissions) !== range(0, count($submissions) - 1))) {
        $error = 'Stored submissions have an unexpected JSON format.';
        error_log('Practical 7: submissions JSON root is not a list.');
        return [];
    }

    return $submissions;
}

function practical7SaveSubmission($file, $submission, &$error)
{
    $error = '';
    $handle = fopen($file, 'c+');
    if ($handle === false) {
        $error = 'Your submission could not be saved. Check that the data folder is writable.';
        error_log('Practical 7: unable to open the submissions file for writing.');
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        $error = 'Your submission could not be saved. Please try again.';
        error_log('Practical 7: unable to lock the submissions file for writing.');
        return false;
    }

    $contents = stream_get_contents($handle);
    if ($contents === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        $error = 'Existing submissions could not be read, so your data was not saved.';
        error_log('Practical 7: failed while reading the submissions file before writing.');
        return false;
    }

    try {
        $submissions = trim($contents) === ''
            ? []
            : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        flock($handle, LOCK_UN);
        fclose($handle);
        $error = 'Existing submissions contain invalid JSON, so your data was not saved.';
        error_log('Practical 7: invalid submissions JSON before writing: ' . $exception->getMessage());
        return false;
    }

    if (!is_array($submissions)
        || ($submissions !== [] && array_keys($submissions) !== range(0, count($submissions) - 1))) {
        flock($handle, LOCK_UN);
        fclose($handle);
        $error = 'Existing submissions have an unexpected format, so your data was not saved.';
        error_log('Practical 7: submissions JSON root is not a list before writing.');
        return false;
    }

    $submissions[] = $submission;
    try {
        $json = json_encode(
            $submissions,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    } catch (JsonException $exception) {
        flock($handle, LOCK_UN);
        fclose($handle);
        $error = 'Your submission could not be encoded for storage.';
        error_log('Practical 7: JSON encoding failed: ' . $exception->getMessage());
        return false;
    }

    $written = 0;
    $length = strlen($json);
    rewind($handle);
    if (!ftruncate($handle, 0)) {
        $written = false;
    } else {
        while ($written < $length) {
            $result = fwrite($handle, substr($json, $written));
            if ($result === false || $result === 0) {
                $written = false;
                break;
            }
            $written += $result;
        }
    }

    $saved = $written !== false && fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    if (!$saved) {
        $error = 'Your submission could not be fully saved. Please try again.';
        error_log('Practical 7: failed while writing or flushing the submissions file.');
        return false;
    }

    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['fullName'] = practical7PostString('fullName');
    $values['email'] = strtolower(practical7PostString('email'));
    $values['phone'] = practical7PostString('phone');
    $values['message'] = practical7PostString('message');

    $submittedToken = practical7PostString('csrfToken');
    if (!isset($_SESSION['practical7_csrf'])
        || !hash_equals($_SESSION['practical7_csrf'], $submittedToken)) {
        $errors[] = 'The form expired or could not be verified. Refresh the page and try again.';
    }
    if ($values['fullName'] === '' || strlen($values['fullName']) > 100) {
        $errors[] = 'Enter your name using no more than 100 characters.';
    }
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($values['phone'] !== '' && !preg_match('/^[0-9+\s().-]{7,25}$/', $values['phone'])) {
        $errors[] = 'Enter a valid phone number, or leave the phone field blank.';
    }
    if ($values['message'] === '' || strlen($values['message']) > 5000) {
        $errors[] = 'Enter a message using no more than 5000 characters.';
    }

    if ($errors === []) {
        $submission = [
            'fullName' => $values['fullName'],
            'email' => $values['email'],
            'phone' => $values['phone'],
            'message' => $values['message'],
            'submittedAt' => date(DATE_ATOM)
        ];
        $storageError = '';

        if (practical7SaveSubmission($storageFile, $submission, $storageError)) {
            $success = 'Thank you. Your message was validated and saved successfully.';
            $_SESSION['practical7_csrf'] = bin2hex(random_bytes(32));
            $values = ['fullName' => '', 'email' => '', 'phone' => '', 'message' => ''];
        } else {
            $errors[] = $storageError;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Practical 7 | PHP Form Processing</title>
    <style>
        :root {
            color-scheme: light;
            font-family: Arial, sans-serif;
            color: #172033;
            background: #f3f6fb;
        }
        body { margin: 0; padding: 2rem 1rem; }
        main { max-width: 680px; margin: 0 auto; }
        .card { padding: 2rem; background: #fff; border: 1px solid #dce3ee; border-radius: 14px; box-shadow: 0 8px 24px #17203312; }
        h1 { margin-top: 0; color: #174ea6; }
        .intro { color: #526075; line-height: 1.5; }
        label { display: block; margin: 1rem 0 0.4rem; font-weight: 700; }
        input, textarea { box-sizing: border-box; width: 100%; padding: 0.75rem; border: 1px solid #aeb9c9; border-radius: 7px; font: inherit; }
        textarea { min-height: 140px; resize: vertical; }
        button { margin-top: 1.25rem; padding: 0.8rem 1.2rem; border: 0; border-radius: 7px; background: #174ea6; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
        button:hover { background: #123d82; }
        .notice { margin: 1rem 0; padding: 0.9rem 1rem; border-radius: 7px; }
        .success { background: #e7f6ec; color: #155c2c; }
        .errors { background: #fff0f0; color: #8c2020; }
        .errors ul { margin: 0; padding-left: 1.25rem; }
        .hint { color: #657187; font-size: 0.9rem; }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1>Practical 7: PHP Form Processing</h1>
        <p class="intro">Submit a contact message using POST. PHP validates the fields, verifies a CSRF token, and stores valid submissions in a JSON file.</p>

        <?php if ($success !== ''): ?>
            <p class="notice success" role="status"><?= practical7Escape($success) ?></p>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="notice errors" role="alert">
                <strong>Please correct the following:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= practical7Escape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <input type="hidden" name="csrfToken" value="<?= practical7Escape($_SESSION['practical7_csrf']) ?>">

            <label for="fullName">Full name</label>
            <input id="fullName" name="fullName" type="text" maxlength="100" autocomplete="name" required value="<?= practical7Escape($values['fullName']) ?>">

            <label for="email">Email address</label>
            <input id="email" name="email" type="email" maxlength="254" autocomplete="email" required value="<?= practical7Escape($values['email']) ?>">

            <label for="phone">Phone number <span class="hint">(optional)</span></label>
            <input id="phone" name="phone" type="tel" maxlength="25" autocomplete="tel" value="<?= practical7Escape($values['phone']) ?>">

            <label for="message">Message</label>
            <textarea id="message" name="message" maxlength="5000" required><?= practical7Escape($values['message']) ?></textarea>

            <button type="submit">Submit message</button>
        </form>
        <p class="hint">Valid submissions are stored in <code>data/practical7-submissions.json</code>.</p>
    </section>
</main>
</body>
</html>
