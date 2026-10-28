<?php
require_once __DIR__ . '/session.php';
startStudentHubSession();

// These are the files where form data will be saved.
$jsonFile = __DIR__ . '/../data/form-submissions.json';
$csvFile = __DIR__ . '/../csv/registrations.csv';
$textFile = __DIR__ . '/../text/registrations.txt';
$accountsFile = __DIR__ . '/accounts.json';

$errors = [];
$warnings = [];
$formType = $_POST['formType'] ?? '';

if ($formType === 'contact') {
    if (refreshStudentHubSession()) {
        header('Location: ../pages/login.html?reason=timeout', true, 303);
        exit;
    }
    if (!is_array($_SESSION['student'] ?? null)) {
        header('Location: ../pages/login.html', true, 303);
        exit;
    }
    if (($_SESSION['student']['role'] ?? 'student') === 'admin'
        && ($_SESSION['student']['mode'] ?? 'choose') !== 'student') {
        header('Location: ../pages/select-mode.php', true, 303);
        exit;
    }
}

// Check which form was submitted.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $errors[] = 'Please submit a form first.';
}

// Read and check the registration form.
if ($formType === 'registration') {
    $usernameValue = $_POST['username'] ?? '';
    $nameValue = $_POST['fullName'] ?? '';
    $emailValue = $_POST['email'] ?? '';
    $phoneValue = $_POST['phone'] ?? '';
    $genderValue = $_POST['gender'] ?? '';
    $passwordValue = $_POST['password'] ?? '';
    $confirmPasswordValue = $_POST['confirmPassword'] ?? '';

    $username = is_string($usernameValue) ? strtolower(trim($usernameValue)) : '';
    $name = is_string($nameValue) ? trim($nameValue) : '';
    $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';
    $phone = is_string($phoneValue) ? trim($phoneValue) : '';
    $gender = is_string($genderValue) ? $genderValue : '';
    $password = is_string($passwordValue) ? $passwordValue : '';
    $confirmPassword = is_string($confirmPasswordValue) ? $confirmPasswordValue : '';

    if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
        $errors[] = 'Use a username with 3 to 30 letters, numbers, or underscores.';
    }
    if (!preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)+$/", $name) || strlen($name) > 100) {
        $errors[] = 'Enter your first and last name using letters, spaces, apostrophes, or hyphens.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = 'Enter a 10-digit mobile number.';
    }
    if (!in_array($gender, ['female', 'male'], true)) {
        $errors[] = 'Please select a valid gender.';
    }
    if (($_POST['terms'] ?? '') !== 'on') $errors[] = 'Please accept the terms.';
    if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}$/', $password)) {
        $errors[] = 'Use at least 8 characters with a letter, number, and special character.';
    }
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';

    $accounts = file_exists($accountsFile)
        ? json_decode(file_get_contents($accountsFile), true)
        : [];
    if (!is_array($accounts)) $accounts = [];
    $normalizedEmail = strtolower($email);

    $formData = [
        'type' => 'registration',
        'username' => $username,
        'fullName' => $name,
        'email' => $email,
        'phone' => $phone,
        'gender' => $gender,
        'submittedAt' => date('Y-m-d H:i:s')
    ];
}

// Authenticate a registered student account.
elseif ($formType === 'login') {
    $emailValue = $_POST['email'] ?? '';
    $passwordValue = $_POST['password'] ?? '';
    $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';
    $password = is_string($passwordValue) ? $passwordValue : '';
    $accounts = file_exists($accountsFile)
        ? json_decode(file_get_contents($accountsFile), true)
        : [];
    if (!is_array($accounts)) $accounts = [];

    unset($_SESSION['student'], $_SESSION['last_activity']);
    $authenticatedAccount = null;
    $databaseAvailable = true;
    $databaseAccountFound = false;
    $validLoginInput = filter_var($email, FILTER_VALIDATE_EMAIL)
        && strlen($email) <= 254
        && $password !== ''
        && strlen($password) <= 4096;

    if ($validLoginInput) {
        try {
            require_once __DIR__ . '/config.php';
            $statement = $conn->prepare(
                'SELECT id, username, full_name, email, password, role FROM students WHERE email = ? LIMIT 1'
            );
            $statement->bind_param('s', $email);
            $statement->execute();
            $result = $statement->get_result();
            $databaseAccount = $result->fetch_assoc();
            $statement->close();

            if ($databaseAccount) {
                $databaseAccountFound = true;
                if (password_verify($password, $databaseAccount['password'])) {
                    $authenticatedAccount = [
                        'id' => $databaseAccount['id'],
                        'username' => $databaseAccount['username'],
                        'fullName' => $databaseAccount['full_name'],
                        'email' => $databaseAccount['email'],
                        'role' => $databaseAccount['role']
                    ];
                }
            }
        } catch (Exception $e) {
            error_log('Database login error: ' . $e->getMessage());
            $databaseAvailable = false;
            $errors[] = 'Login is temporarily unavailable. Please try again.';
        }
    }

    if ($validLoginInput && $databaseAvailable && !$databaseAccountFound) {
        foreach ($accounts as $account) {
            if (is_array($account)
                && strtolower((string) ($account['email'] ?? '')) === $email
                && is_string($account['passwordHash'] ?? null)
                && password_verify($password, $account['passwordHash'])) {
                $authenticatedAccount = $account;
                $authenticatedAccount['id'] = $account['id'] ?? ('legacy:' . strtolower($account['email']));
                $authenticatedAccount['role'] = 'student';
                break;
            }
        }
    }

    if ($authenticatedAccount) {
        if (!session_regenerate_id(true)) {
            error_log('Could not regenerate the session ID after StudentHub login.');
            $errors[] = 'Login could not be completed. Please try again.';
        } else {
            $_SESSION['student'] = [
                'id' => $authenticatedAccount['id'],
                'email' => $authenticatedAccount['email'],
                'fullName' => $authenticatedAccount['fullName'],
                'username' => $authenticatedAccount['username'] ?? '',
                'role' => in_array($authenticatedAccount['role'] ?? '', ['student', 'admin'], true)
                    ? $authenticatedAccount['role']
                    : 'student'
            ];
            $_SESSION['student']['mode'] = $_SESSION['student']['role'] === 'admin' ? 'choose' : 'student';
            $_SESSION['last_activity'] = time();
            header(
                $_SESSION['student']['role'] === 'admin'
                    ? 'Location: ../pages/select-mode.php'
                    : 'Location: ../pages/dashboard.php'
            );
            exit;
        }
    }

    if (($validLoginInput && $databaseAvailable && !$authenticatedAccount) || !$validLoginInput) {
        $errors[] = 'Email or password is incorrect. Check your details or register first.';
    }
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

// Save a registration to MySQL before reporting success.
if (count($errors) === 0 && $formType === 'registration') {
    $accountId = null;
    try {
        require_once __DIR__ . '/config.php';
        $duplicateStatement = $conn->prepare(
            'SELECT '
            . 'EXISTS(SELECT 1 FROM students WHERE email = ?) AS email_exists, '
            . 'EXISTS(SELECT 1 FROM students WHERE username = ?) AS username_exists'
        );
        $duplicateStatement->bind_param('ss', $email, $username);
        $duplicateStatement->execute();
        $duplicates = $duplicateStatement->get_result()->fetch_assoc();
        $duplicateStatement->close();

        if ((int) $duplicates['email_exists'] === 1) {
            $errors[] = 'An account with this email already exists. Please log in.';
        }
        if ((int) $duplicates['username_exists'] === 1) {
            $errors[] = 'That username is already taken. Please choose another.';
        }

        if (count($errors) === 0) {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $conn->prepare(
                    'INSERT INTO students (username, full_name, email, phone, gender, password) '
                . 'VALUES (?, ?, ?, ?, ?, ?)'
            );
            $statement->bind_param(
                'ssssss',
                $username,
                $name,
                $email,
                $phone,
                $gender,
                $passwordHash
            );
            $statement->execute();
            $accountId = $conn->insert_id;
            $statement->close();
        }
    } catch (Exception $e) {
        error_log('Database registration error: ' . $e->getMessage());
        if ($e instanceof mysqli_sql_exception && $e->getCode() === 1062) {
            $errors[] = 'That username or email is already registered.';
        } else {
            $errors[] = 'Your registration could not be saved to the database. Please try again.';
        }
    }
}

if (count($errors) === 0 && $formType === 'registration') {
    $accounts[] = [
        'id' => $accountId,
        'username' => $username,
        'fullName' => $name,
        'email' => $normalizedEmail,
        'passwordHash' => $passwordHash
    ];
    $accountsJson = json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($accountsJson === false || file_put_contents($accountsFile, $accountsJson, LOCK_EX) === false) {
        error_log('Could not update legacy account file after saving registration to the database.');
        $warnings[] = 'The database account was saved, but the legacy account file could not be updated.';
    }
}

if (count($errors) === 0) {
    // Read the old JSON records first.
    $oldData = [];
    if (file_exists($jsonFile)) {
        $oldData = json_decode(file_get_contents($jsonFile), true);
    }
    if (!is_array($oldData)) $oldData = [];

    // Add the new record and save all records again.
    $oldData[] = $formData;
    if (file_put_contents($jsonFile, json_encode($oldData, JSON_PRETTY_PRINT), LOCK_EX) === false) {
        error_log('Could not write form submission data to ' . $jsonFile);
        if ($formType === 'registration') {
            $warnings[] = 'The database account was saved, but the JSON backup could not be updated.';
        }
    }

    // Registration data is also saved as CSV and plain text.
    if ($formType === 'registration') {
        if (!file_exists($csvFile) || filesize($csvFile) === 0) {
            $file = fopen($csvFile, 'a');
            if ($file === false) {
                error_log('Could not open registration CSV file: ' . $csvFile);
                $warnings[] = 'The database account was saved, but the CSV backup could not be updated.';
            } else {
                fputcsv($file, ['Username', 'Full name', 'Email', 'Phone', 'Gender', 'Submitted at']);
                fclose($file);
            }
        }

        $file = fopen($csvFile, 'a');
        if ($file === false || fputcsv($file, [$username, $name, $email, $phone, $gender, $formData['submittedAt']]) === false) {
            error_log('Could not append registration data to CSV file: ' . $csvFile);
            $warnings[] = 'The database account was saved, but the CSV backup could not be updated.';
            if ($file !== false) fclose($file);
        } else {
            fclose($file);
        }

        $text = "Username: $username\nFull name: $name\nEmail: $email\nPhone: $phone\n"
            . "Gender: $gender\n"
            . "Submitted at: {$formData['submittedAt']}\n"
            . "----------------------------------------\n";
        if (file_put_contents($textFile, $text, FILE_APPEND | LOCK_EX) === false) {
            error_log('Could not append registration data to text file: ' . $textFile);
            $warnings[] = 'The database account was saved, but the text backup could not be updated.';
        }
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
            <p class="form-message success">Thank you. Your <?= $formType === 'registration' ? 'registration was saved successfully to the database' : 'message was saved successfully' ?>.</p>
            <?php if ($warnings): ?>
                <div class="form-message error">
                    <p>Some backup files could not be updated:</p>
                    <ul>
                        <?php foreach (array_unique($warnings) as $warning): ?>
                            <li><?= e($warning) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
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
            <a href="<?= $formType === 'registration' ? '../pages/register.html' : ($formType === 'login' ? '../pages/login.html' : '../pages/contact.php') ?>">Return to form</a>
            <?php if ($success && $formType === 'registration'): ?>
                <a href="../pages/login.html">Log in</a>
            <?php endif; ?>
            <a href="../pages/index.php">Home</a>
        </div>
    </main>
</body>
</html>