<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Method not allowed.');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/session.php';
startStudentHubSession();
if (!clearStudentHubSession()) {
    error_log('Could not destroy the StudentHub session during logout.');
    http_response_code(500);
    exit('Could not end the student session.');
}

header('Location: ../pages/login.html', true, 303);
exit;
