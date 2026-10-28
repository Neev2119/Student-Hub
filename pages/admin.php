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

$adminName = trim($_SESSION['student']['fullName'] ?? '');
if ($adminName === '') {
    $adminName = $_SESSION['student']['username'] ?? 'Admin';
}

$dashboardStats = null;
$registrationTrend = [];
$latestAccountRegistrations = [];
$latestRegistrations = [];
$databaseError = '';
try {
    require_once __DIR__ . '/../php/config.php';
    $currentMonth = new DateTimeImmutable('first day of this month 00:00:00');
    $trendStart = $currentMonth->modify('-5 months');
    $trendEnd = $currentMonth->modify('+1 month');
    for ($month = $trendStart; $month < $trendEnd; $month = $month->modify('+1 month')) {
        $monthKey = $month->format('Y-m');
        $registrationTrend[$monthKey] = [
            'label' => $month->format('M Y'),
            'students' => 0,
            'events' => 0
        ];
    }

    $startDate = $trendStart->format('Y-m-d H:i:s');
    $endDate = $trendEnd->format('Y-m-d H:i:s');
    $statement = $conn->prepare(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total "
        . "FROM students WHERE role = 'student' AND created_at >= ? AND created_at < ? "
        . 'GROUP BY month_key'
    );
    $statement->bind_param('ss', $startDate, $endDate);
    $statement->execute();
    $result = $statement->get_result();
    while ($monthCount = $result->fetch_assoc()) {
        if (isset($registrationTrend[$monthCount['month_key']])) {
            $registrationTrend[$monthCount['month_key']]['students'] = (int) $monthCount['total'];
        }
    }
    $statement->close();

    $statement = $conn->prepare(
        "SELECT DATE_FORMAT(time_stamp, '%Y-%m') AS month_key, COUNT(*) AS total "
        . 'FROM event_registrations WHERE time_stamp >= ? AND time_stamp < ? GROUP BY month_key'
    );
    $statement->bind_param('ss', $startDate, $endDate);
    $statement->execute();
    $result = $statement->get_result();
    while ($monthCount = $result->fetch_assoc()) {
        if (isset($registrationTrend[$monthCount['month_key']])) {
            $registrationTrend[$monthCount['month_key']]['events'] = (int) $monthCount['total'];
        }
    }
    $statement->close();

    $result = $conn->query(
        'SELECT COUNT(*) AS user_count, '
        . "COALESCE(SUM(role = 'student'), 0) AS student_count, "
        . "COALESCE(SUM(role = 'admin'), 0) AS admin_count FROM students"
    );
    $dashboardStats = $result->fetch_assoc();

    $result = $conn->query('SELECT COUNT(*) AS event_count FROM events');
    $dashboardStats['event_count'] = $result->fetch_assoc()['event_count'];

    $result = $conn->query(
        'SELECT id, username, full_name, email, created_at FROM students '
        . 'ORDER BY created_at DESC, id DESC LIMIT 10'
    );
    while ($account = $result->fetch_assoc()) {
        $latestAccountRegistrations[] = $account;
    }

    $result = $conn->query(
        'SELECT registration_id, student_name, student_email, event_title, event_date, time_stamp, registration_status '
        . 'FROM event_registrations ORDER BY time_stamp DESC, registration_id DESC LIMIT 10'
    );
    while ($registration = $result->fetch_assoc()) {
        $latestRegistrations[] = $registration;
    }
} catch (mysqli_sql_exception $e) {
    error_log('Admin dashboard query error: ' . $e->getMessage());
    $databaseError = 'Dashboard data is unavailable. Check the database setup and apply the required migrations.';
} catch (RuntimeException $e) {
    error_log('Admin dashboard database error: ' . $e->getMessage());
    $databaseError = 'Dashboard data is unavailable. Check the database connection and apply the required migrations.';
}

function adminDashboardEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$trendMaximum = 0;
$trendDescriptionParts = [];
foreach ($registrationTrend as $month) {
    $trendMaximum = max($trendMaximum, $month['students'], $month['events']);
    $trendDescriptionParts[] = $month['label'] . ': '
        . $month['students'] . ' student accounts, '
        . $month['events'] . ' event registrations';
}
$trendMaximum = max(1, $trendMaximum);
$trendDescription = 'Monthly registration totals for the past six months. '
    . implode('. ', $trendDescriptionParts) . '.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - StudentHub</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
                    <p>Administrator account</p>
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
    <main class="container dashboard-page">
        <section class="dashboard-welcome">
            <div>
                <span class="eyebrow">Administrator dashboard</span>
                <h2>Welcome, <?= adminDashboardEscape($adminName) ?></h2>
                <p>Database overview and the latest event registrations.</p>
            </div>
        </section>
        <?php if ($databaseError !== ''): ?>
            <p class="admin-message error" role="alert"><?= adminDashboardEscape($databaseError) ?></p>
        <?php else: ?>
            <section class="dashboard-metrics" aria-label="Administration statistics">
                <div class="metric-card">
                    <span class="metric-label">Total users</span>
                    <strong><?= number_format((int) $dashboardStats['user_count']) ?></strong>
                    <span class="metric-note"><?= number_format((int) $dashboardStats['admin_count']) ?> administrators</span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Students</span>
                    <strong><?= number_format((int) $dashboardStats['student_count']) ?></strong>
                    <span class="metric-note">Student accounts</span>
                </div>
                <div class="metric-card">
                    <span class="metric-label">Events</span>
                    <strong><?= number_format((int) $dashboardStats['event_count']) ?></strong>
                    <span class="metric-note">Events in the database</span>
                </div>
            </section>

            <section class="admin-student-card admin-registration-chart">
                <div class="admin-records-heading">
                    <div>
                        <h2>Registration trends</h2>
                        <p>New student accounts and event registrations over the past six months</p>
                    </div>
                </div>
                <div class="admin-chart-legend" aria-hidden="true">
                    <span><i class="admin-chart-swatch student"></i>Student accounts</span>
                    <span><i class="admin-chart-swatch event"></i>Event registrations</span>
                </div>
                <div class="admin-chart-scroll">
                    <div class="admin-chart-plot" role="img" aria-label="<?= adminDashboardEscape($trendDescription) ?>">
                        <?php foreach ($registrationTrend as $month): ?>
                            <?php
                            $studentHeight = $month['students'] === 0 ? 0 : max(5, (int) round($month['students'] / $trendMaximum * 82));
                            $eventHeight = $month['events'] === 0 ? 0 : max(5, (int) round($month['events'] / $trendMaximum * 82));
                            ?>
                            <div class="admin-chart-month">
                                <div class="admin-chart-bars">
                                    <div class="admin-chart-bar-column" style="--bar-height: <?= $studentHeight ?>%">
                                        <span class="admin-chart-value"><?= number_format($month['students']) ?></span>
                                        <span class="admin-chart-bar student<?= $studentHeight === 0 ? ' empty' : '' ?>"></span>
                                    </div>
                                    <div class="admin-chart-bar-column" style="--bar-height: <?= $eventHeight ?>%">
                                        <span class="admin-chart-value"><?= number_format($month['events']) ?></span>
                                        <span class="admin-chart-bar event<?= $eventHeight === 0 ? ' empty' : '' ?>"></span>
                                    </div>
                                </div>
                                <span class="admin-chart-month-label"><?= adminDashboardEscape($month['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="admin-student-card">
                <div class="admin-records-heading">
                    <div>
                        <h2>Latest account registrations</h2>
                        <p>Most recently created student accounts</p>
                    </div>
                    <a class="admin-back-link btn btn-outline-primary btn-sm" href="admin-students.php">Manage students</a>
                </div>
                <div class="admin-student-table-wrap">
                    <table class="admin-student-table table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$latestAccountRegistrations): ?>
                                <tr><td colspan="4">There are no student accounts yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latestAccountRegistrations as $account): ?>
                                    <tr>
                                        <td><?= adminDashboardEscape($account['full_name']) ?></td>
                                        <td><?= adminDashboardEscape($account['username']) ?></td>
                                        <td><?= adminDashboardEscape($account['email']) ?></td>
                                        <td><?= adminDashboardEscape($account['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="admin-student-card">
                <div class="admin-records-heading">
                    <div>
                        <h2>Latest event registrations</h2>
                        <p>Most recent event registrations</p>
                    </div>
                    <a class="admin-back-link btn btn-outline-primary btn-sm" href="admin-audit-logs.php">View audit log</a>
                </div>
                <div class="admin-student-table-wrap">
                    <table class="admin-student-table table table-striped table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Email</th>
                                <th>Event</th>
                                <th>Event date</th>
                                <th>Registered</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$latestRegistrations): ?>
                                <tr><td colspan="6">There are no event registrations yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($latestRegistrations as $registration): ?>
                                    <tr>
                                        <td><?= adminDashboardEscape($registration['student_name'] ?: 'Student') ?></td>
                                        <td><?= adminDashboardEscape($registration['student_email'] ?: 'Not provided') ?></td>
                                        <td><?= adminDashboardEscape($registration['event_title']) ?></td>
                                        <td><?= adminDashboardEscape($registration['event_date']) ?></td>
                                        <td><?= adminDashboardEscape($registration['time_stamp']) ?></td>
                                        <td><span class="badge text-bg-secondary"><?= adminDashboardEscape(ucfirst($registration['registration_status'])) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
        <script src="../javascript/site.js" defer></script>
    </main>
</body>
</html>
