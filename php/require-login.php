<?php
require_once __DIR__ . '/session.php';
startStudentHubSession();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (refreshStudentHubSession()) {
    header('Location: login.html?reason=timeout', true, 303);
    exit;
}

if (!is_array($_SESSION['student'] ?? null)) {
    header('Location: login.html', true, 303);
    exit;
}
