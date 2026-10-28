<?php
require_once __DIR__ . '/session.php';
startStudentHubSession();
header('Cache-Control: no-store');
header('Content-Type: application/json; charset=utf-8');

function respondToEventRegistration($statusCode, $message)
{
    http_response_code($statusCode);
    echo json_encode(['message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respondToEventRegistration(405, 'Please submit the event registration form.');
}

$sessionExpired = refreshStudentHubSession();
$student = $sessionExpired ? null : ($_SESSION['student'] ?? null);
if (!is_array($student)) {
    respondToEventRegistration(401, 'Please log in before registering for an event.');
}
if (($student['role'] ?? 'student') === 'admin' && ($student['mode'] ?? 'choose') !== 'student') {
    respondToEventRegistration(403, 'Switch to student mode before registering for an event.');
}

$submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
$sessionToken = $_SESSION['event_registration_csrf'] ?? '';
if (!is_string($submittedToken) || !is_string($sessionToken)
    || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
    respondToEventRegistration(403, 'Your session expired. Refresh the page and try again.');
}

$eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);
$mobileNumber = isset($_POST['mobile_no']) && is_string($_POST['mobile_no'])
    ? trim($_POST['mobile_no'])
    : '';
$studentId = isset($student['id']) ? (string) $student['id'] : '';
$studentName = isset($student['fullName']) ? trim((string) $student['fullName']) : '';
$studentEmail = isset($student['email']) ? trim((string) $student['email']) : '';

if ($eventId === false || $studentId === '' || strlen($studentId) > 100
    || $studentName === '' || strlen($studentName) > 100
    || !filter_var($studentEmail, FILTER_VALIDATE_EMAIL) || strlen($studentEmail) > 100
    || !preg_match('/^[0-9]{10}$/', $mobileNumber)) {
    respondToEventRegistration(422, 'Enter a valid 10-digit mobile number to register.');
}

try {
    require_once __DIR__ . '/config.php';
    $statement = $conn->prepare(
        'INSERT INTO event_registrations '
        . '(student_id, student_name, student_email, mobile_no, event_id, event_title, event_date) '
        . 'SELECT ?, ?, ?, ?, event_id, title, event_date FROM events '
        . 'WHERE event_id = ? AND event_date >= CURRENT_DATE()'
    );
    $statement->bind_param(
        'ssssi',
        $studentId,
        $studentName,
        $studentEmail,
        $mobileNumber,
        $eventId
    );
    $statement->execute();
    $registrationCreated = $statement->affected_rows === 1;
    $statement->close();

    if (!$registrationCreated) {
        respondToEventRegistration(400, 'Registration for this event is closed or the event could not be found.');
    }
} catch (mysqli_sql_exception $e) {
    if ((int) $e->getCode() === 1062) {
        respondToEventRegistration(409, 'You are already registered for this event.');
    }

    error_log('Event registration database error: ' . $e->getMessage());
    respondToEventRegistration(500, 'Your registration could not be saved. Please try again later.');
} catch (RuntimeException $e) {
    error_log('Event registration database connection error: ' . $e->getMessage());
    respondToEventRegistration(503, 'Event registration is temporarily unavailable. Please try again later.');
}

respondToEventRegistration(201, 'You are registered for this event.');
