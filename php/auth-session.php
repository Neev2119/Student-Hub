<?php
require_once __DIR__ . '/session.php';
startStudentHubSession();
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json; charset=utf-8');
    $expired = refreshStudentHubSession();
    $student = $expired ? null : ($_SESSION['student'] ?? null);
    echo json_encode([
        'authenticated' => is_array($student),
        'student' => is_array($student) ? [
            'id' => $student['id'] ?? null,
            'fullName' => $student['fullName'] ?? '',
            'username' => $student['username'] ?? '',
            'email' => $student['email'] ?? '',
            'role' => $student['role'] ?? 'student',
            'mode' => $student['mode'] ?? 'student'
        ] : null
    ]);
    exit;
}

header('Allow: GET');
http_response_code(405);
exit('Method not allowed.');
