<?php
require_once __DIR__ . '/../php/require-login.php';

if (($_SESSION['student']['role'] ?? 'student') !== 'admin') {
    header('Location: dashboard.php', true, 303);
    exit;
}
if (($_SESSION['student']['mode'] ?? 'choose') !== 'admin') {
    header('Location: select-mode.php', true, 303);
    exit;
}

function adminAuditEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$logs = [];
$databaseError = '';
try {
    require_once __DIR__ . '/../php/config.php';
    $result = $conn->query(
        'SELECT log_id, actor_id, actor_email, action, entity_type, entity_id, details, created_at '
        . 'FROM audit_logs ORDER BY created_at DESC, log_id DESC LIMIT 100'
    );
    while ($log = $result->fetch_assoc()) {
        $details = $log['details'] === null ? null : json_decode($log['details'], true);
        $log['formatted_details'] = $details === null
            ? ''
            : json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $logs[] = $log;
    }
} catch (mysqli_sql_exception $e) {
    error_log('Admin audit log query error: ' . $e->getMessage());
    $databaseError = 'Audit logs are unavailable. Check that the audit log migration has been applied.';
} catch (RuntimeException $e) {
    error_log('Admin audit log connection error: ' . $e->getMessage());
    $databaseError = 'Audit logs are unavailable. Check the database connection and apply the audit log migration.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - StudentHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/admin-students.css">
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <div class="brand">
                <img class="logo" src="../images/logo.png" alt="StudentHub logo">
                <div>
                    <h1>StudentHub Admin</h1>
                    <p>Audit log</p>
                </div>
            </div>
            <div class="site-auth-links" data-auth-rendered="true">
                <form class="logout-form" action="../php/logout.php" method="post">
                    <button class="btn" type="submit">Logout</button>
                </form>
            </div>
        </div>
        <nav class="top-nav"></nav>
    </header>

    <main class="container admin-students-page">
        <div class="admin-page-heading">
            <div>
                <p class="eyebrow">Administrator activity</p>
                <h2>Audit log</h2>
                <p>Latest 100 student and event management actions.</p>
            </div>
            <a class="admin-back-link" href="admin.php">Admin dashboard</a>
        </div>

        <?php if ($databaseError !== ''): ?>
            <p class="admin-message error" role="alert"><?= adminAuditEscape($databaseError) ?></p>
        <?php else: ?>
            <section class="admin-student-card">
                <div class="admin-student-table-wrap">
                    <table class="admin-student-table">
                        <thead>
                            <tr>
                                <th>Date and time</th>
                                <th>Administrator</th>
                                <th>Action</th>
                                <th>Record</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$logs): ?>
                                <tr><td colspan="5">No admin actions have been recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= adminAuditEscape($log['created_at']) ?></td>
                                        <td><?= adminAuditEscape($log['actor_email']) ?><br><small>ID <?= adminAuditEscape($log['actor_id']) ?></small></td>
                                        <td><?= adminAuditEscape($log['action']) ?></td>
                                        <td><?= adminAuditEscape($log['entity_type']) ?><?= $log['entity_id'] === null ? '' : ' #' . adminAuditEscape($log['entity_id']) ?></td>
                                        <td><pre><?= adminAuditEscape($log['formatted_details']) ?></pre></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="../javascript/site.js" defer></script>
</body>
</html>
