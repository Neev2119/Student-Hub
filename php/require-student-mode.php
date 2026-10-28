<?php
require_once __DIR__ . '/require-login.php';

if (($_SESSION['student']['role'] ?? 'student') === 'admin'
    && ($_SESSION['student']['mode'] ?? 'choose') !== 'student') {
    header('Location: ../pages/select-mode.php', true, 303);
    exit;
}
