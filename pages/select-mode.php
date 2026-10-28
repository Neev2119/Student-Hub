<?php
require_once __DIR__ . '/../php/require-login.php';

if (($_SESSION['student']['role'] ?? 'student') !== 'admin') {
    $_SESSION['student']['mode'] = 'student';
    header('Location: dashboard.php', true, 303);
    exit;
}

if (!isset($_SESSION['admin_mode_csrf'])) {
    $_SESSION['admin_mode_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $mode = $_POST['mode'] ?? '';

    if (!is_string($token) || !hash_equals($_SESSION['admin_mode_csrf'], $token)) {
        http_response_code(403);
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!in_array($mode, ['admin', 'student'], true)) {
        http_response_code(422);
        $error = 'Choose either administrator mode or student mode.';
    } else {
        $_SESSION['student']['mode'] = $mode;
        header('Location: ' . ($mode === 'admin' ? 'admin.php' : 'dashboard.php'), true, 303);
        exit;
    }
}

function modeSelectionEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$fullName = trim($_SESSION['student']['fullName'] ?? '');
if ($fullName === '') {
    $fullName = $_SESSION['student']['username'] ?? 'Administrator';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose a mode - StudentHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <div class="brand">
                <img class="logo" src="../images/logo.png" alt="StudentHub logo">
                <div>
                    <h1>StudentHub</h1>
                    <p>Choose your workspace</p>
                </div>
            </div>
            <div class="site-auth-links" data-auth-rendered="true">
                <form class="logout-form" action="../php/logout.php" method="post">
                    <button class="btn" type="submit">Logout</button>
                </form>
            </div>
        </div>
    </header>

    <main class="container mode-selection-page">
        <section class="dashboard-welcome">
            <div>
                <span class="eyebrow">Administrator account</span>
                <h2>Welcome, <?= modeSelectionEscape($fullName) ?></h2>
                <p>Select the area you want to use. Admin and student workspaces are kept separate.</p>
            </div>
        </section>

        <?php if ($error !== ''): ?>
            <p class="admin-message error" role="alert"><?= modeSelectionEscape($error) ?></p>
        <?php endif; ?>

        <section class="mode-selection-options" aria-label="Choose a workspace">
            <form class="mode-selection-card" method="post" action="select-mode.php">
                <input type="hidden" name="csrf_token" value="<?= modeSelectionEscape($_SESSION['admin_mode_csrf']) ?>">
                <input type="hidden" name="mode" value="admin">
                <span class="eyebrow">Manage StudentHub</span>
                <h2>Administrator mode</h2>
                <p>Review dashboard statistics, manage students and events, and inspect audit activity.</p>
                <button class="btn primary" type="submit">Open admin dashboard</button>
            </form>

            <form class="mode-selection-card" method="post" action="select-mode.php">
                <input type="hidden" name="csrf_token" value="<?= modeSelectionEscape($_SESSION['admin_mode_csrf']) ?>">
                <input type="hidden" name="mode" value="student">
                <span class="eyebrow">Use the student portal</span>
                <h2>Student mode</h2>
                <p>Open the student dashboard and student-facing campus tools.</p>
                <button class="btn primary" type="submit">Open student dashboard</button>
            </form>
        </section>
    </main>
</body>
</html>
