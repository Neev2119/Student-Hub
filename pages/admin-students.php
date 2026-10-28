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

$connectionError = '';
try {
    require_once __DIR__ . '/../php/config.php';
} catch (RuntimeException $e) {
    error_log('Student management connection error: ' . $e->getMessage());
    $connectionError = 'The student database is unavailable. Please check the database connection and try again.';
}

function adminStudentsEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function adminStudentsRedirect()
{
    header('Location: admin-students.php', true, 303);
    exit;
}

if (!isset($_SESSION['admin_students_csrf'])) {
    $_SESSION['admin_students_csrf'] = bin2hex(random_bytes(32));
}

$message = $_SESSION['admin_students_message'] ?? '';
$messageType = $_SESSION['admin_students_message_type'] ?? 'success';
unset($_SESSION['admin_students_message'], $_SESSION['admin_students_message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $transactionStarted = false;
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['admin_students_csrf'], $token)) {
        $_SESSION['admin_students_message'] = 'Your session expired. Refresh the page and try again.';
        $_SESSION['admin_students_message_type'] = 'error';
        adminStudentsRedirect();
    }
    if ($connectionError !== '') {
        $_SESSION['admin_students_message'] = $connectionError;
        $_SESSION['admin_students_message_type'] = 'error';
        adminStudentsRedirect();
    }

    $action = $_POST['action'] ?? '';
    $studentId = filter_var($_POST['student_id'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);

    try {
        if ($action === 'delete') {
            if ($studentId === false) {
                throw new InvalidArgumentException('Select a valid student record to delete.');
            }
            if ((string) $studentId === (string) ($_SESSION['student']['id'] ?? '')) {
                throw new InvalidArgumentException('You cannot delete your own administrator account.');
            }

            $conn->begin_transaction();
            $transactionStarted = true;
            $statement = $conn->prepare('DELETE FROM students WHERE id = ?');
            $statement->bind_param('i', $studentId);
            $statement->execute();
            $deleted = $statement->affected_rows === 1;
            $statement->close();

            if (!$deleted) {
                throw new InvalidArgumentException('Student record was not found.');
            }

            studentHubWriteAuditLog($conn, 'student.delete', 'student', $studentId);
            $conn->commit();
            $transactionStarted = false;
            $_SESSION['admin_students_message'] = 'Student record deleted successfully.';
            $_SESSION['admin_students_message_type'] = 'success';
            adminStudentsRedirect();
        }

        $usernameValue = $_POST['username'] ?? '';
        $nameValue = $_POST['full_name'] ?? '';
        $emailValue = $_POST['email'] ?? '';
        $phoneValue = $_POST['phone'] ?? '';
        $genderValue = $_POST['gender'] ?? '';
        $roleValue = $_POST['role'] ?? '';
        $passwordValue = $_POST['password'] ?? '';

        $username = is_string($usernameValue) ? strtolower(trim($usernameValue)) : '';
        $fullName = is_string($nameValue) ? trim($nameValue) : '';
        $email = is_string($emailValue) ? strtolower(trim($emailValue)) : '';
        $phone = is_string($phoneValue) ? trim($phoneValue) : '';
        $gender = is_string($genderValue) ? $genderValue : '';
        $role = is_string($roleValue) ? $roleValue : '';
        $password = is_string($passwordValue) ? $passwordValue : '';

        if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
            throw new InvalidArgumentException('Username must be 3 to 30 letters, numbers, or underscores.');
        }
        if (!preg_match("/^[A-Za-z]+(?:[ '-][A-Za-z]+)+$/", $fullName) || strlen($fullName) > 100) {
            throw new InvalidArgumentException('Enter a valid first and last name (maximum 100 characters).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('Enter a valid email address (maximum 254 characters).');
        }
        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            throw new InvalidArgumentException('Phone number must contain exactly 10 digits.');
        }
        if (!in_array($gender, ['female', 'male'], true)) {
            throw new InvalidArgumentException('Select a valid gender.');
        }
        if (!in_array($role, ['student', 'admin'], true)) {
            throw new InvalidArgumentException('Select a valid account role.');
        }

        if ($action === 'add') {
            if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}$/', $password)) {
                throw new InvalidArgumentException('Password must be at least 8 characters and include a letter, number, and special character.');
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $conn->prepare(
                'INSERT INTO students (username, full_name, email, phone, gender, password, role) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $conn->begin_transaction();
            $transactionStarted = true;
            $statement->bind_param(
                'sssssss',
                $username,
                $fullName,
                $email,
                $phone,
                $gender,
                $passwordHash,
                $role
            );
            $statement->execute();
            $createdStudentId = $conn->insert_id;
            $statement->close();

            studentHubWriteAuditLog($conn, 'student.create', 'student', $createdStudentId, ['role' => $role]);
            $conn->commit();
            $transactionStarted = false;
            $_SESSION['admin_students_message'] = 'Student account added successfully.';
            $_SESSION['admin_students_message_type'] = 'success';
            adminStudentsRedirect();
        }

        if ($action === 'update') {
            if ($studentId === false) {
                throw new InvalidArgumentException('Select a valid student record to update.');
            }
            if ((string) $studentId === (string) ($_SESSION['student']['id'] ?? '') && $role !== 'admin') {
                throw new InvalidArgumentException('You cannot remove your own administrator access.');
            }
            if ($password !== '' && !preg_match('/^(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}$/', $password)) {
                throw new InvalidArgumentException('New password must be at least 8 characters and include a letter, number, and special character.');
            }

            $conn->begin_transaction();
            $transactionStarted = true;
            if ($password === '') {
                $statement = $conn->prepare(
                    'UPDATE students SET username = ?, full_name = ?, email = ?, phone = ?, gender = ?, role = ? '
                    . 'WHERE id = ?'
                );
                $statement->bind_param(
                    'ssssssi',
                    $username,
                    $fullName,
                    $email,
                    $phone,
                    $gender,
                    $role,
                    $studentId
                );
            } else {
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $statement = $conn->prepare(
                    'UPDATE students SET username = ?, full_name = ?, email = ?, phone = ?, gender = ?, role = ?, password = ? '
                    . 'WHERE id = ?'
                );
                $statement->bind_param(
                    'sssssssi',
                    $username,
                    $fullName,
                    $email,
                    $phone,
                    $gender,
                    $role,
                    $passwordHash,
                    $studentId
                );
            }
            $statement->execute();
            $statement->close();

            studentHubWriteAuditLog($conn, 'student.update', 'student', $studentId, ['role' => $role]);
            $conn->commit();
            $transactionStarted = false;
            $_SESSION['admin_students_message'] = 'Student record updated successfully.';
            $_SESSION['admin_students_message_type'] = 'success';
            adminStudentsRedirect();
        }

        throw new InvalidArgumentException('Choose a valid student management action.');
    } catch (InvalidArgumentException $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        $_SESSION['admin_students_message'] = $e->getMessage();
        $_SESSION['admin_students_message_type'] = 'error';
    } catch (mysqli_sql_exception $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('Student management database error: ' . $e->getMessage());
        $_SESSION['admin_students_message'] = (int) $e->getCode() === 1062
            ? 'That email address or username is already in use.'
            : 'The student record could not be saved. Check the database setup and try again.';
        $_SESSION['admin_students_message_type'] = 'error';
    } catch (RuntimeException $e) {
        if ($transactionStarted) {
            $conn->rollback();
        }
        error_log('Student management runtime error: ' . $e->getMessage());
        $_SESSION['admin_students_message'] = 'The student record could not be saved. Please try again.';
        $_SESSION['admin_students_message_type'] = 'error';
    }

    adminStudentsRedirect();
}

$searchValue = $_GET['search'] ?? '';
$genderValue = $_GET['gender'] ?? '';
$roleValue = $_GET['role'] ?? '';
$search = is_string($searchValue) ? trim($searchValue) : '';
$genderFilter = is_string($genderValue) ? $genderValue : '';
$roleFilter = is_string($roleValue) ? $roleValue : '';
$pageValue = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1]
]);
$currentPage = $pageValue === false ? 1 : $pageValue;
$pageSize = 25;
$totalStudents = 0;
$students = [];
$databaseError = $connectionError;
$editStudent = null;

if (strlen($search) > 100) {
    $search = substr($search, 0, 100);
}
if (!in_array($genderFilter, ['', 'female', 'male'], true)) {
    $genderFilter = '';
}
if (!in_array($roleFilter, ['', 'student', 'admin'], true)) {
    $roleFilter = '';
}

if ($databaseError !== '') {
    $totalPages = 1;
} else {
try {
    $statement = $conn->prepare(
        "SELECT COUNT(*) AS total FROM students "
        . "WHERE (? = '' OR full_name LIKE CONCAT('%', ?, '%') "
        . "OR email LIKE CONCAT('%', ?, '%') OR username LIKE CONCAT('%', ?, '%')) "
        . "AND (? = '' OR gender = ?) AND (? = '' OR role = ?)"
    );
    $statement->bind_param(
        'ssssssss',
        $search,
        $search,
        $search,
        $search,
        $genderFilter,
        $genderFilter,
        $roleFilter,
        $roleFilter
    );
    $statement->execute();
    $totalStudents = (int) $statement->get_result()->fetch_assoc()['total'];
    $statement->close();

    $totalPages = max(1, (int) ceil($totalStudents / $pageSize));
    $currentPage = min($currentPage, $totalPages);
    $offset = ($currentPage - 1) * $pageSize;

    $statement = $conn->prepare(
        "SELECT id, username, full_name, email, phone, gender, role, created_at FROM students "
        . "WHERE (? = '' OR full_name LIKE CONCAT('%', ?, '%') "
        . "OR email LIKE CONCAT('%', ?, '%') OR username LIKE CONCAT('%', ?, '%')) "
        . "AND (? = '' OR gender = ?) AND (? = '' OR role = ?) "
        . 'ORDER BY id DESC LIMIT ? OFFSET ?'
    );
    $statement->bind_param(
        'ssssssssii',
        $search,
        $search,
        $search,
        $search,
        $genderFilter,
        $genderFilter,
        $roleFilter,
        $roleFilter,
        $pageSize,
        $offset
    );
    $statement->execute();
    $result = $statement->get_result();
    while ($student = $result->fetch_assoc()) {
        $students[] = $student;
    }
    $statement->close();

    $editValue = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1]
    ]);
    if ($editValue !== false && $editValue !== null) {
        $statement = $conn->prepare(
            'SELECT id, username, full_name, email, phone, gender, role FROM students WHERE id = ? LIMIT 1'
        );
        $statement->bind_param('i', $editValue);
        $statement->execute();
        $editStudent = $statement->get_result()->fetch_assoc() ?: null;
        $statement->close();
        if ($editStudent === null) {
            $message = 'The selected student record was not found.';
            $messageType = 'error';
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log('Student management listing error: ' . $e->getMessage());
    $databaseError = 'Student records could not be loaded. Check that the students table has the expected columns and try again.';
    $totalPages = 1;
}
}

$adminName = trim($_SESSION['student']['fullName'] ?? '');
if ($adminName === '') {
    $adminName = $_SESSION['student']['username'] ?? 'Admin';
}
$formStudent = $editStudent ?: [
    'id' => '',
    'username' => '',
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'gender' => '',
    'role' => 'student'
];
$formAction = $editStudent ? 'update' : 'add';
$totalPages = $totalPages ?? max(1, (int) ceil($totalStudents / $pageSize));
$queryParameters = [
    'search' => $search,
    'gender' => $genderFilter,
    'role' => $roleFilter
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - StudentHub</title>
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
                    <p>Student management</p>
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
                <p class="eyebrow">Administrator: <?= adminStudentsEscape($adminName) ?></p>
                <h2>Manage students</h2>
                <p>Add, view, edit, delete, search, and filter student records.</p>
            </div>
            <a class="admin-back-link" href="admin.php">Admin home</a>
        </div>

        <?php if ($message !== ''): ?>
            <p class="admin-message <?= adminStudentsEscape($messageType) ?>" role="<?= $messageType === 'error' ? 'alert' : 'status' ?>">
                <?= adminStudentsEscape($message) ?>
            </p>
        <?php endif; ?>
        <?php if ($databaseError !== ''): ?>
            <p class="admin-message error" role="alert"><?= adminStudentsEscape($databaseError) ?></p>
        <?php endif; ?>

        <section class="admin-student-card" id="student-form">
            <h3><?= $editStudent ? 'Edit student record' : 'Add a student' ?></h3>
            <form class="admin-student-form" method="post" action="admin-students.php#student-form">
                <input type="hidden" name="csrf_token" value="<?= adminStudentsEscape($_SESSION['admin_students_csrf']) ?>">
                <input type="hidden" name="action" value="<?= adminStudentsEscape($formAction) ?>">
                <?php if ($editStudent): ?>
                    <input type="hidden" name="student_id" value="<?= adminStudentsEscape($formStudent['id']) ?>">
                <?php endif; ?>

                <label>
                    Username
                    <input name="username" type="text" minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" required value="<?= adminStudentsEscape($formStudent['username']) ?>">
                </label>
                <label>
                    Full name
                    <input name="full_name" type="text" maxlength="100" required value="<?= adminStudentsEscape($formStudent['full_name']) ?>">
                </label>
                <label>
                    Email
                    <input name="email" type="email" maxlength="254" required value="<?= adminStudentsEscape($formStudent['email']) ?>">
                </label>
                <label>
                    Phone
                    <input name="phone" type="tel" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" required value="<?= adminStudentsEscape($formStudent['phone']) ?>">
                </label>
                <label>
                    Gender
                    <select name="gender" required>
                        <option value="">Select gender</option>
                        <option value="female" <?= $formStudent['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="male" <?= $formStudent['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                    </select>
                </label>
                <label>
                    Role
                    <select name="role" required>
                        <option value="student" <?= $formStudent['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="admin" <?= $formStudent['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                    </select>
                </label>
                <label class="admin-password-field">
                    <?= $editStudent ? 'New password (leave blank to keep current)' : 'Password' ?>
                    <input name="password" type="password" <?= $editStudent ? '' : 'required' ?> minlength="8" autocomplete="new-password" pattern="(?=.*[A-Za-z])(?=.*[0-9])(?=.*[@$!%*?&]).{8,}">
                </label>
                <div class="admin-form-actions">
                    <button class="btn" type="submit"><?= $editStudent ? 'Save changes' : 'Add student' ?></button>
                    <?php if ($editStudent): ?>
                        <a class="admin-cancel-link" href="admin-students.php">Cancel edit</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="admin-student-card">
            <div class="admin-records-heading">
                <div>
                    <h3>Student records</h3>
                    <p><?= number_format($totalStudents) ?> matching record<?= $totalStudents === 1 ? '' : 's' ?></p>
                </div>
                <form class="admin-student-filters" method="get" action="admin-students.php">
                    <label>
                        Search
                        <input type="search" name="search" maxlength="100" placeholder="Name, username, or email" value="<?= adminStudentsEscape($search) ?>">
                    </label>
                    <label>
                        Gender
                        <select name="gender">
                            <option value="">All genders</option>
                            <option value="female" <?= $genderFilter === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="male" <?= $genderFilter === 'male' ? 'selected' : '' ?>>Male</option>
                        </select>
                    </label>
                    <label>
                        Role
                        <select name="role">
                            <option value="">All roles</option>
                            <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>Student</option>
                            <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </label>
                    <button class="btn" type="submit">Apply filters</button>
                    <a class="admin-cancel-link" href="admin-students.php">Clear</a>
                </form>
            </div>

            <div class="admin-student-table-wrap">
                <table class="admin-student-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$students): ?>
                            <tr><td colspan="9">No student records match these filters.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?= adminStudentsEscape($student['id']) ?></td>
                                    <td><?= adminStudentsEscape($student['full_name']) ?></td>
                                    <td><?= adminStudentsEscape($student['username']) ?></td>
                                    <td><?= adminStudentsEscape($student['email']) ?></td>
                                    <td><?= adminStudentsEscape($student['phone']) ?></td>
                                    <td><?= adminStudentsEscape(ucfirst($student['gender'])) ?></td>
                                    <td><?= adminStudentsEscape(ucfirst($student['role'])) ?></td>
                                    <td><?= adminStudentsEscape($student['created_at']) ?></td>
                                    <td>
                                        <div class="admin-row-actions">
                                            <a class="admin-edit-link" href="admin-students.php?<?= adminStudentsEscape(http_build_query(array_merge($queryParameters, ['page' => $currentPage, 'edit' => $student['id']]))) ?>#student-form">Edit</a>
                                            <?php if ((string) $student['id'] !== (string) ($_SESSION['student']['id'] ?? '')): ?>
                                                <form method="post" action="admin-students.php" onsubmit="return confirm('Delete this student record? This cannot be undone.');">
                                                    <input type="hidden" name="csrf_token" value="<?= adminStudentsEscape($_SESSION['admin_students_csrf']) ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="student_id" value="<?= adminStudentsEscape($student['id']) ?>">
                                                    <button class="admin-delete-button" type="submit">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <nav class="admin-pagination" aria-label="Student records pages">
                    <?php if ($currentPage > 1): ?>
                        <a href="admin-students.php?<?= adminStudentsEscape(http_build_query(array_merge($queryParameters, ['page' => $currentPage - 1]))) ?>">Previous</a>
                    <?php endif; ?>
                    <span>Page <?= adminStudentsEscape($currentPage) ?> of <?= adminStudentsEscape($totalPages) ?></span>
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="admin-students.php?<?= adminStudentsEscape(http_build_query(array_merge($queryParameters, ['page' => $currentPage + 1]))) ?>">Next</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    </main>
    <script src="../javascript/site.js" defer></script>
</body>
</html>
