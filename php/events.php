<?php
require_once __DIR__ . '/require-login.php';
require_once __DIR__ . '/audit-log.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function eventsApiRespond($statusCode, $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    exit;
}

function eventsApiPosterUrl($filename)
{
    return is_string($filename) && $filename !== ''
        ? '../uploads/event-posters/' . rawurlencode(basename($filename))
        : null;
}

function eventsApiStorePoster($upload)
{
    if (!is_array($upload) || !isset($upload['error']) || $upload['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($upload['error'] !== UPLOAD_ERR_OK) {
        if ($upload['error'] === UPLOAD_ERR_INI_SIZE || $upload['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new InvalidArgumentException('Poster files must be no larger than 5 MB.');
        }
        throw new InvalidArgumentException('The poster could not be uploaded. Please choose another file.');
    }
    if (!isset($upload['tmp_name'], $upload['name'])
        || !is_string($upload['tmp_name']) || !is_uploaded_file($upload['tmp_name'])) {
        throw new InvalidArgumentException('Choose a valid poster image to upload.');
    }

    $fileSize = filesize($upload['tmp_name']);
    if ($fileSize === false || $fileSize < 1 || $fileSize > 5 * 1024 * 1024) {
        throw new InvalidArgumentException('Poster files must be no larger than 5 MB.');
    }

    $extension = strtolower(pathinfo($upload['name'], PATHINFO_EXTENSION));
    $allowedTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];
    if (!isset($allowedTypes[$extension])) {
        throw new InvalidArgumentException('Upload a JPG, PNG, or WebP poster image.');
    }

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($upload['tmp_name']);
    $imageInfo = @getimagesize($upload['tmp_name']);
    if ($mimeType !== $allowedTypes[$extension] || $imageInfo === false || $imageInfo['mime'] !== $mimeType) {
        throw new InvalidArgumentException('The poster file contents do not match a supported image type.');
    }

    $uploadDirectory = dirname(__DIR__) . '/uploads/event-posters';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
        throw new RuntimeException('The event poster upload directory could not be created.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    if (!move_uploaded_file($upload['tmp_name'], $uploadDirectory . '/' . $filename)) {
        throw new RuntimeException('The event poster could not be saved.');
    }

    return $filename;
}

function eventsApiRemovePoster($filename)
{
    if (!is_string($filename) || $filename === '') {
        return;
    }

    $path = dirname(__DIR__) . '/uploads/event-posters/' . basename($filename);
    if (is_file($path) && !unlink($path)) {
        error_log('Could not remove an event poster: ' . basename($filename));
    }
}

function eventsApiGetInput()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        return $_POST;
    }

    $body = file_get_contents('php://input');
    $input = json_decode($body, true);
    if (!is_array($input)) {
        eventsApiRespond(400, ['message' => 'The request body must contain valid JSON.']);
    }

    return $input;
}

function eventsApiValidateEvent($input)
{
    $fields = ['title', 'event_date', 'event_time', 'venue', 'category', 'organizer', 'description'];
    $event = [];
    foreach ($fields as $field) {
        $value = $input[$field] ?? '';
        $event[$field] = is_string($value) ? trim($value) : '';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $event['event_date']);
    $lengths = [
        'title' => 150,
        'event_time' => 20,
        'venue' => 150,
        'category' => 50,
        'organizer' => 150,
        'description' => 10000
    ];
    foreach ($lengths as $field => $maximum) {
        if ($event[$field] === '' || strlen($event[$field]) > $maximum) {
            throw new InvalidArgumentException(
                ucfirst(str_replace('_', ' ', $field)) . ' is required and must be ' . number_format($maximum) . ' characters or fewer.'
            );
        }
    }
    if (!$date || $date->format('Y-m-d') !== $event['event_date']) {
        throw new InvalidArgumentException('Enter a valid event date.');
    }

    return $event;
}

$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE'], true)) {
    header('Allow: GET, POST, PUT, DELETE');
    eventsApiRespond(405, ['message' => 'This request method is not supported.']);
}

if ($method !== 'GET') {
    if (($_SESSION['student']['role'] ?? 'student') !== 'admin'
        || ($_SESSION['student']['mode'] ?? 'choose') !== 'admin') {
        eventsApiRespond(403, ['message' => 'Administrator access is required to manage events.']);
    }

    $submittedToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $sessionToken = $_SESSION['admin_events_csrf'] ?? '';
    if (!is_string($submittedToken) || !is_string($sessionToken)
        || $sessionToken === '' || !hash_equals($sessionToken, $submittedToken)) {
        eventsApiRespond(403, ['message' => 'Your session expired. Refresh the page and try again.']);
    }
}

$transactionStarted = false;
try {
    require_once __DIR__ . '/config.php';

    if ($method === 'GET') {
        $result = $conn->query(
            'SELECT event_id, title, event_date, event_time, venue, category, description, image '
            . 'FROM events ORDER BY event_date ASC, event_id DESC'
        );
        $events = [];
        while ($event = $result->fetch_assoc()) {
            $events[] = [
                'id' => (int) $event['event_id'],
                'title' => $event['title'],
                'category' => $event['category'],
                'date' => $event['event_date'],
                'time' => $event['event_time'],
                'location' => $event['venue'],
                'organizer' => $event['organizer'] ?? '',
                'description' => $event['description'],
                'image' => eventsApiPosterUrl($event['image'])
            ];
        }
        eventsApiRespond(200, $events);
    }

    $input = eventsApiGetInput();
    $eventId = filter_var($input['event_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    if ($method === 'DELETE') {
        if ($eventId === false) {
            eventsApiRespond(422, ['message' => 'Select a valid event to delete.']);
        }

        $conn->begin_transaction();
        $transactionStarted = true;
        $statement = $conn->prepare('SELECT image, title FROM events WHERE event_id = ? FOR UPDATE');
        $statement->bind_param('i', $eventId);
        $statement->execute();
        $existingEvent = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$existingEvent) {
            $conn->rollback();
            $transactionStarted = false;
            eventsApiRespond(404, ['message' => 'The selected event was not found.']);
        }

        $statement = $conn->prepare('DELETE FROM event_registrations WHERE event_id = ?');
        $statement->bind_param('i', $eventId);
        $statement->execute();
        $statement->close();

        $statement = $conn->prepare('DELETE FROM events WHERE event_id = ?');
        $statement->bind_param('i', $eventId);
        $statement->execute();
        $statement->close();
        studentHubWriteAuditLog($conn, 'event.delete', 'event', $eventId, ['title' => $existingEvent['title']]);
        $conn->commit();
        $transactionStarted = false;
        eventsApiRemovePoster($existingEvent['image']);
        eventsApiRespond(200, ['message' => 'Event deleted successfully.']);
    }

    if ($method === 'POST' && !in_array($input['action'] ?? '', ['add', 'update'], true)) {
        eventsApiRespond(422, ['message' => 'Choose a valid event management action.']);
    }

    if ($method === 'PUT' && $eventId === false) {
        eventsApiRespond(422, ['message' => 'Select a valid event to update.']);
    }

    $event = eventsApiValidateEvent($input);
    $existingEvent = null;
    $image = null;
    if ($method === 'PUT' || (($input['action'] ?? '') === 'update')) {
        if ($eventId === false) {
            eventsApiRespond(422, ['message' => 'Select a valid event to update.']);
        }
        $statement = $conn->prepare('SELECT image, title FROM events WHERE event_id = ? LIMIT 1');
        $statement->bind_param('i', $eventId);
        $statement->execute();
        $existingEvent = $statement->get_result()->fetch_assoc();
        $statement->close();
        if (!$existingEvent) {
            eventsApiRespond(404, ['message' => 'The selected event was not found.']);
        }
        $image = $existingEvent['image'];
    }

    $uploadedPoster = eventsApiStorePoster($_FILES['poster'] ?? null);
    if ($uploadedPoster !== null) {
        $image = $uploadedPoster;
    } elseif (isset($input['remove_poster'])) {
        $image = null;
    }

    if ($method === 'PUT' || (($input['action'] ?? '') === 'update')) {
        $conn->begin_transaction();
        $transactionStarted = true;
        $statement = $conn->prepare(
            'UPDATE events SET title = ?, event_date = ?, event_time = ?, venue = ?, category = ?, '
            . 'organizer = ?, description = ?, image = ? WHERE event_id = ?'
        );
        $statement->bind_param(
            'ssssssssi',
            $event['title'],
            $event['event_date'],
            $event['event_time'],
            $event['venue'],
            $event['category'],
            $event['organizer'],
            $event['description'],
            $image,
            $eventId
        );
        $statement->execute();
        $statement->close();
        studentHubWriteAuditLog($conn, 'event.update', 'event', $eventId, ['title' => $event['title']]);
        $conn->commit();
        $transactionStarted = false;
        if ($existingEvent['image'] !== $image) {
            eventsApiRemovePoster($existingEvent['image']);
        }
        eventsApiRespond(200, ['message' => 'Event updated successfully.']);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST, PUT, DELETE');
        eventsApiRespond(405, ['message' => 'This request method is not supported.']);
    }

    $conn->begin_transaction();
    $transactionStarted = true;
    $statement = $conn->prepare(
        'INSERT INTO events (title, event_date, event_time, venue, category, organizer, description, image) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $statement->bind_param(
        'ssssssss',
        $event['title'],
        $event['event_date'],
        $event['event_time'],
        $event['venue'],
        $event['category'],
        $event['organizer'],
        $event['description'],
        $image
    );
    $statement->execute();
    $newEventId = $conn->insert_id;
    $statement->close();
    studentHubWriteAuditLog($conn, 'event.create', 'event', $newEventId, ['title' => $event['title']]);
    $conn->commit();
    $transactionStarted = false;
    eventsApiRespond(201, ['message' => 'Event added successfully.', 'id' => $newEventId]);
} catch (InvalidArgumentException $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if (isset($uploadedPoster)) {
        eventsApiRemovePoster($uploadedPoster);
    }
    eventsApiRespond(422, ['message' => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if (isset($uploadedPoster)) {
        eventsApiRemovePoster($uploadedPoster);
    }
    error_log('Event API database error: ' . $e->getMessage());
    eventsApiRespond(500, ['message' => 'The event could not be saved. Please try again later.']);
} catch (RuntimeException $e) {
    if ($transactionStarted) {
        $conn->rollback();
    }
    if (isset($uploadedPoster)) {
        eventsApiRemovePoster($uploadedPoster);
    }
    error_log('Event API error: ' . $e->getMessage());
    eventsApiRespond(500, ['message' => $e->getMessage()]);
}
