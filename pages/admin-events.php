<?php
require_once __DIR__ . '/../php/require-login.php';
require_once __DIR__ . '/../php/audit-log.php';

if (($_SESSION['student']['role'] ?? 'student') !== 'admin') {
    header('Location: dashboard.php', true, 303);
    exit;
}
if (($_SESSION['student']['mode'] ?? 'choose') !== 'admin') {
    header('Location: select-mode.php', true, 303);
    exit;
}

function adminEventsEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminEventsRedirect()
{
    header('Location: admin-events.php', true, 303);
    exit;
}

function adminEventsStorePoster($upload)
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
    if (!isset($upload['tmp_name'], $upload['name'], $upload['size'])
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

function adminEventsRemovePoster($filename)
{
    if (!is_string($filename) || $filename === '') {
        return;
    }

    $path = dirname(__DIR__) . '/uploads/event-posters/' . basename($filename);
    if (is_file($path) && !unlink($path)) {
        error_log('Could not remove an event poster: ' . basename($filename));
    }
}

if (!isset($_SESSION['admin_events_csrf'])) {
    $_SESSION['admin_events_csrf'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['admin_events_message'] ?? '';
$messageType = $_SESSION['admin_events_message_type'] ?? 'success';
unset($_SESSION['admin_events_message'], $_SESSION['admin_events_message_type']);

$connectionError = '';
try {
    require_once __DIR__ . '/../php/config.php';
} catch (RuntimeException $e) {
    error_log('Event management connection error: ' . $e->getMessage());
    $connectionError = 'The event database is unavailable. Please check the database connection and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadedPoster = null;
    $transactionStarted = false;
    try {
        $token = $_POST['csrf_token'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['admin_events_csrf'], $token)) {
            throw new InvalidArgumentException('Your session expired. Refresh the page and try again.');
        }
        if ($connectionError !== '') {
            throw new RuntimeException($connectionError);
        }

        $actionValue = $_POST['action'] ?? '';
        $action = is_string($actionValue) ? $actionValue : '';
        $eventId = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);

        if ($action === 'delete') {
            if ($eventId === false) {
                throw new InvalidArgumentException('Select a valid event to delete.');
            }

            $conn->begin_transaction();
            $transactionStarted = true;
            $statement = $conn->prepare('SELECT image, title FROM events WHERE event_id = ? FOR UPDATE');
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $event = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$event) {
                throw new InvalidArgumentException('The selected event was not found.');
            }

            $statement = $conn->prepare('DELETE FROM event_registrations WHERE event_id = ?');
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $statement->close();

            $statement = $conn->prepare('DELETE FROM events WHERE event_id = ?');
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $statement->close();
            studentHubWriteAuditLog($conn, 'event.delete', 'event', $eventId, ['title' => $event['title']]);
            $conn->commit();
            $transactionStarted = false;
            adminEventsRemovePoster($event['image']);

            $_SESSION['admin_events_message'] = 'Event deleted successfully.';
            $_SESSION['admin_events_message_type'] = 'success';
            adminEventsRedirect();
        }

        if (!in_array($action, ['add', 'update'], true)) {
            throw new InvalidArgumentException('Choose a valid event management action.');
        }
        if ($action === 'update' && $eventId === false) {
            throw new InvalidArgumentException('Select a valid event to update.');
        }

        $titleValue = $_POST['title'] ?? '';
        $dateValue = $_POST['event_date'] ?? '';
        $timeValue = $_POST['event_time'] ?? '';
        $venueValue = $_POST['venue'] ?? '';
        $categoryValue = $_POST['category'] ?? '';
        $organizerValue = $_POST['organizer'] ?? '';
        $descriptionValue = $_POST['description'] ?? '';
        $title = is_string($titleValue) ? trim($titleValue) : '';
        $eventDate = is_string($dateValue) ? trim($dateValue) : '';
        $eventTime = is_string($timeValue) ? trim($timeValue) : '';
        $venue = is_string($venueValue) ? trim($venueValue) : '';
        $category = is_string($categoryValue) ? trim($categoryValue) : '';
        $organizer = is_string($organizerValue) ? trim($organizerValue) : '';
        $description = is_string($descriptionValue) ? trim($descriptionValue) : '';

        $dateObject = DateTime::createFromFormat('!Y-m-d', $eventDate);
        if ($title === '' || strlen($title) > 150) {
            throw new InvalidArgumentException('Event title is required and must be 150 characters or fewer.');
        }
        if (!$dateObject || $dateObject->format('Y-m-d') !== $eventDate) {
            throw new InvalidArgumentException('Enter a valid event date.');
        }
        if ($eventTime === '' || strlen($eventTime) > 20) {
            throw new InvalidArgumentException('Event time is required and must be 20 characters or fewer.');
        }
        if ($venue === '' || strlen($venue) > 150) {
            throw new InvalidArgumentException('Venue is required and must be 150 characters or fewer.');
        }
        if ($category === '' || strlen($category) > 50) {
            throw new InvalidArgumentException('Category is required and must be 50 characters or fewer.');
        }
        if ($organizer === '' || strlen($organizer) > 150) {
            throw new InvalidArgumentException('Organizer is required and must be 150 characters or fewer.');
        }
        if ($description === '' || strlen($description) > 10000) {
            throw new InvalidArgumentException('Description is required and must be 10,000 characters or fewer.');
        }

        if ($action === 'update') {
            $statement = $conn->prepare('SELECT image FROM events WHERE event_id = ? LIMIT 1');
            $statement->bind_param('i', $eventId);
            $statement->execute();
            $existingEvent = $statement->get_result()->fetch_assoc();
            $statement->close();
            if (!$existingEvent) {
                throw new InvalidArgumentException('The selected event was not found.');
            }
            $image = $existingEvent['image'];
        } else {
            $existingEvent = null;
            $image = null;
        }

        $uploadedPoster = adminEventsStorePoster($_FILES['poster'] ?? null);
        if ($uploadedPoster !== null) {
            $image = $uploadedPoster;
        } elseif ($action === 'update' && isset($_POST['remove_poster'])) {
            $image = null;
        }

        if ($action === 'add') {
            $conn->begin_transaction();
            $transactionStarted = true;
            $statement = $conn->prepare(
                'INSERT INTO events (title, event_date, event_time, venue, category, organizer, description, image) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $statement->bind_param(
                'ssssssss',
                $title,
                $eventDate,
                $eventTime,
                $venue,
                $category,
                $organizer,
                $description,
                $image
            );
            $statement->execute();
            $savedEventId = $conn->insert_id;
            $statement->close();
        } else {
            $conn->begin_transaction();
            $transactionStarted = true;
            $statement = $conn->prepare(
                'UPDATE events SET title = ?, event_date = ?, event_time = ?, venue = ?, category = ?, '
                . 'organizer = ?, description = ?, image = ? WHERE event_id = ?'
            );
            $statement->bind_param(
                'ssssssssi',
                $title,
                $eventDate,
                $eventTime,
                $venue,
                $category,
                $organizer,
                $description,
                $image,
                $eventId
            );
            $statement->execute();
            $statement->close();
        }

        studentHubWriteAuditLog(
            $conn,
            $action === 'add' ? 'event.create' : 'event.update',
            'event',
            $action === 'add' ? $savedEventId : $eventId,
            ['title' => $title]
        );
        $conn->commit();
        $transactionStarted = false;

        if ($action === 'update' && $existingEvent['image'] !== $image) {
            adminEventsRemovePoster($existingEvent['image']);
        }
        $_SESSION['admin_events_message'] = $action === 'add'
            ? 'Event added successfully.'
            : 'Event updated successfully.';
        $_SESSION['admin_events_message_type'] = 'success';
        adminEventsRedirect();
    } catch (InvalidArgumentException $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        adminEventsRemovePoster($uploadedPoster);
        $_SESSION['admin_events_message'] = $e->getMessage();
        $_SESSION['admin_events_message_type'] = 'error';
        adminEventsRedirect();
    } catch (mysqli_sql_exception $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        adminEventsRemovePoster($uploadedPoster);
        error_log('Event management database error: ' . $e->getMessage());
        $_SESSION['admin_events_message'] = 'The event could not be saved. Please check the event database and try again.';
        $_SESSION['admin_events_message_type'] = 'error';
        adminEventsRedirect();
    } catch (RuntimeException $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        adminEventsRemovePoster($uploadedPoster);
        error_log('Event management error: ' . $e->getMessage());
        $_SESSION['admin_events_message'] = $e->getMessage();
        $_SESSION['admin_events_message_type'] = 'error';
        adminEventsRedirect();
    }
}

$events = [];
$editEvent = null;
$databaseError = $connectionError;
if ($databaseError === '') {
    try {
        $result = $conn->query(
            'SELECT event_id, title, event_date, event_time, venue, category, organizer, description, image '
            . 'FROM events ORDER BY event_date ASC, event_id DESC'
        );
        while ($event = $result->fetch_assoc()) {
            $events[] = $event;
        }

        $editValue = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1]
        ]);
        if ($editValue !== false && $editValue !== null) {
            $statement = $conn->prepare(
                'SELECT event_id, title, event_date, event_time, venue, category, organizer, description, image '
                . 'FROM events WHERE event_id = ? LIMIT 1'
            );
            $statement->bind_param('i', $editValue);
            $statement->execute();
            $editEvent = $statement->get_result()->fetch_assoc() ?: null;
            $statement->close();
            if ($editEvent === null) {
                $message = 'The selected event was not found.';
                $messageType = 'error';
            }
        }
    } catch (mysqli_sql_exception $e) {
        error_log('Event management listing error: ' . $e->getMessage());
        $databaseError = 'Events could not be loaded. Check that the event migrations have been applied and try again.';
    }
}

$adminName = trim($_SESSION['student']['fullName'] ?? '');
if ($adminName === '') {
    $adminName = $_SESSION['student']['username'] ?? 'Admin';
}
$formEvent = $editEvent ?: [
    'event_id' => '',
    'title' => '',
    'event_date' => '',
    'event_time' => '',
    'venue' => '',
    'category' => '',
    'organizer' => '',
    'description' => '',
    'image' => null
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events - StudentHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="../css/admin-students.css">
    <link rel="stylesheet" href="../css/admin-events.css">
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <div class="brand">
                <img class="logo" src="../images/logo.png" alt="StudentHub logo">
                <div>
                    <h1>StudentHub Admin</h1>
                    <p>Event management</p>
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
                <p class="eyebrow">Administrator: <?= adminEventsEscape($adminName) ?></p>
                <h2>Manage events</h2>
                <p>Add, view, edit, and delete campus events and their posters.</p>
            </div>
            <a class="admin-back-link" href="admin.php">Admin home</a>
        </div>

        <?php if ($message !== ''): ?>
            <p id="adminEventMessage" class="admin-message <?= adminEventsEscape($messageType) ?>" role="<?= $messageType === 'error' ? 'alert' : 'status' ?>" aria-live="polite">
                <?= adminEventsEscape($message) ?>
            </p>
        <?php else: ?>
            <p id="adminEventMessage" class="admin-message" role="status" aria-live="polite" hidden></p>
        <?php endif; ?>
        <?php if ($databaseError !== ''): ?>
            <p class="admin-message error" role="alert"><?= adminEventsEscape($databaseError) ?></p>
        <?php endif; ?>

        <section class="admin-student-card" id="event-form">
            <h3><?= $editEvent ? 'Edit event' : 'Add an event' ?></h3>
            <form class="admin-student-form admin-event-form" method="post" action="admin-events.php#event-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= adminEventsEscape($_SESSION['admin_events_csrf']) ?>">
                <input type="hidden" name="action" value="<?= $editEvent ? 'update' : 'add' ?>">
                <input type="hidden" name="event_id" value="<?= $editEvent ? adminEventsEscape($formEvent['event_id']) : '' ?>">

                <label>
                    Event title
                    <input name="title" type="text" maxlength="150" required value="<?= adminEventsEscape($formEvent['title']) ?>">
                </label>
                <label>
                    Date
                    <input name="event_date" type="date" required value="<?= adminEventsEscape($formEvent['event_date']) ?>">
                </label>
                <label>
                    Time
                    <input name="event_time" type="text" maxlength="20" placeholder="e.g. 6:30 PM or TBD" required value="<?= adminEventsEscape($formEvent['event_time']) ?>">
                </label>
                <label>
                    Venue
                    <input name="venue" type="text" maxlength="150" required value="<?= adminEventsEscape($formEvent['venue']) ?>">
                </label>
                <label>
                    Category
                    <input name="category" type="text" maxlength="50" required value="<?= adminEventsEscape($formEvent['category']) ?>">
                </label>
                <label>
                    Organizer
                    <input name="organizer" type="text" maxlength="150" required value="<?= adminEventsEscape($formEvent['organizer']) ?>">
                </label>
                <label class="admin-event-description">
                    Description
                    <textarea name="description" maxlength="10000" rows="4" required><?= adminEventsEscape($formEvent['description']) ?></textarea>
                </label>
                <label class="admin-event-poster">
                    Poster (JPG, PNG, or WebP; maximum 5 MB)
                    <input name="poster" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                </label>
                <div id="adminEventPosterPreview" class="admin-event-current-poster" <?= !$editEvent || !$formEvent['image'] ? 'hidden' : '' ?>>
                    <img src="<?= $editEvent && $formEvent['image'] ? '../uploads/event-posters/' . rawurlencode(basename($formEvent['image'])) : '' ?>" alt="Current event poster">
                    <label><input type="checkbox" name="remove_poster" value="1"> Remove current poster</label>
                </div>

                <div class="admin-form-actions">
                    <button class="btn" type="submit"><?= $editEvent ? 'Save changes' : 'Add event' ?></button>
                    <?php if ($editEvent): ?>
                        <a class="admin-cancel-link" href="admin-events.php">Cancel edit</a>
                    <?php endif; ?>
                </div>
            </form>
            <p class="admin-event-upload-note">Poster uploads are limited to 5 MB and validated as JPG, PNG, or WebP images.</p>
        </section>

        <section class="admin-student-card">
            <div class="admin-records-heading">
                <div>
                    <h3>Event records</h3>
                    <p><?= number_format(count($events)) ?> event<?= count($events) === 1 ? '' : 's' ?></p>
                </div>
            </div>
            <div class="admin-student-table-wrap">
                <table class="admin-student-table admin-event-table" data-event-api="../php/events.php">
                    <thead>
                        <tr>
                            <th>Poster</th>
                            <th>Event</th>
                            <th>Date &amp; time</th>
                            <th>Venue</th>
                            <th>Category</th>
                            <th>Organizer</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="adminEventRecords">
                        <?php if (!$events): ?>
                            <tr><td colspan="7">No events have been added yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td>
                                        <?php if ($event['image']): ?>
                                            <img class="admin-event-thumbnail" src="../uploads/event-posters/<?= rawurlencode(basename($event['image'])) ?>" alt="">
                                        <?php else: ?>
                                            <span class="admin-event-no-poster">No poster</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= adminEventsEscape($event['title']) ?></strong>
                                        <span class="admin-event-description-preview"><?= adminEventsEscape($event['description']) ?></span>
                                    </td>
                                    <td><?= adminEventsEscape($event['event_date']) ?><br><?= adminEventsEscape($event['event_time']) ?></td>
                                    <td><?= adminEventsEscape($event['venue']) ?></td>
                                    <td><?= adminEventsEscape($event['category']) ?></td>
                                    <td><?= adminEventsEscape($event['organizer']) ?></td>
                                    <td>
                                        <div class="admin-row-actions">
                                            <a class="admin-edit-link" href="admin-events.php?edit=<?= adminEventsEscape($event['event_id']) ?>#event-form">Edit</a>
                                            <form method="post" action="admin-events.php" onsubmit="return confirm('Delete this event and its registrations? This cannot be undone.');">
                                                <input type="hidden" name="csrf_token" value="<?= adminEventsEscape($_SESSION['admin_events_csrf']) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="event_id" value="<?= adminEventsEscape($event['event_id']) ?>">
                                                <button class="admin-delete-button" type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    <script src="../javascript/site.js" defer></script>
    <script src="../javascript/admin-events.js?v=20261025" defer></script>
</body>
</html>
