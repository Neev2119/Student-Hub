<?php
// These are the files where form data will be saved.
$jsonFile = __DIR__ . '/../data/form-submissions.json';
$csvFile = __DIR__ . '/../csv/registrations.csv';
$textFile = __DIR__ . '/../text/registrations.txt';

$errors = [];
$formType = $_POST['formType'] ?? '';

// Check which form was submitted.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errors[] = 'Please submit a form first.';
}

// Read and check the registration form.
if ($formType === 'registration') {
    $studentId = trim($_POST['studentId'] ?? '');
    $name = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';

    if ($studentId === '') $errors[] = 'Please enter your student ID.';
    if ($name === '') $errors[] = 'Please enter your name.';
    if ($email === '') $errors[] = 'Please enter your email.';
    if ($phone === '') $errors[] = 'Please enter your phone number.';
    if ($gender === '') $errors[] = 'Please select your gender.';
    if (!isset($_POST['terms'])) $errors[] = 'Please accept the terms.';

    $formData = [
        'type' => 'registration',
        'studentId' => $studentId,
        'fullName' => $name,
        'email' => $email,
        'phone' => $phone,
        'gender' => $gender,
        'submittedAt' => date('Y-m-d H:i:s')
    ];
}

// Read and check the contact form.
elseif ($formType === 'contact') {
    $name = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') $errors[] = 'Please enter your name.';
    if ($email === '') $errors[] = 'Please enter your email.';
    if ($message === '') $errors[] = 'Please enter a message.';

    $formData = [
        'type' => 'contact',
        'fullName' => $name,
        'email' => $email,
        'message' => $message,
        'submittedAt' => date('Y-m-d H:i:s')
    ];
}

else {
    $errors[] = 'Unknown form.';
}

// Save the form only when there are no validation errors.
if (count($errors) === 0) {
    // Read the old JSON records first.
    $oldData = [];
    if (file_exists($jsonFile)) {
        $oldData = json_decode(file_get_contents($jsonFile), true);
    }
    if (!is_array($oldData)) $oldData = [];

    // Add the new record and save all records again.
    $oldData[] = $formData;
    file_put_contents($jsonFile, json_encode($oldData, JSON_PRETTY_PRINT));

    // Registration data is also saved as CSV and plain text.
    if ($formType === 'registration') {
        if (!file_exists($csvFile) || filesize($csvFile) === 0) {
            $file = fopen($csvFile, 'a');
            fputcsv($file, ['Student ID', 'Full name', 'Email', 'Phone', 'Gender', 'Submitted at']);
            fclose($file);
        }

        $file = fopen($csvFile, 'a');
        fputcsv($file, [$studentId, $name, $email, $phone, $gender, $formData['submittedAt']]);
        fclose($file);

        $text = "Student ID: $studentId\nFull name: $name\nEmail: $email\nPhone: $phone\n"
            . "Gender: $gender\n"
            . "Submitted at: {$formData['submittedAt']}\n"
            . "----------------------------------------\n";
        file_put_contents($textFile, $text, FILE_APPEND);
    }
}

$success = count($errors) === 0;
$title = $success
    ? ($formType === 'registration' ? 'Registration successful' : 'Submission received')
    : 'Submission error';

// Escape text before showing it in the HTML page.
function e($text)
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?> - StudentHub</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-body">
    <main class="auth-card">
        <h1><?= e($title) ?></h1>
        <?php if ($success): ?>
            <p class="form-message success">Thank you. Your <?= $formType === 'registration' ? 'registration' : 'message' ?> was saved successfully.</p>
        <?php else: ?>
            <div class="form-message error">
                <p>Please correct the following:</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= e($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="auth-links">
            <a href="<?= $formType === 'registration' ? '../pages/register.html' : '../pages/contact.html' ?>">Return to form</a>
            <a href="../pages/index.html">Home</a>
        </div>
    </main>
</body>
</html>