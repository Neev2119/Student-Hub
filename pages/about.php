<?php require_once __DIR__ . '/../php/require-student-mode.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - StudentHub</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/about.css">
</head>
<body>
    <header class="hero">
        <div class="hero-inner">
            <div class="brand">
                <img class="logo" src="../images/logo.png" alt="StudentHub logo">
                <div>
                    <h1>StudentHub</h1>
                    <p>Helping students stay connected and organised</p>
                </div>
            </div>
            <a class="btn" href="login.html">Login</a>
        </div>
    </header>

    <nav class="top-nav">
        <a href="index.php">Home</a>
        <a href="about.php" aria-current="page">About</a>
        <a href="dashboard.php">Dashboard</a>
        <a href="attendance.php">Attendance</a>
        <a href="assignments.php">Assignments</a>
        <a href="courses.php">Courses</a>
        <a href="contact.php">Contact</a>
        <a href="feedback.php">Feedback</a>
    </nav>

    <main class="container page-card">
        <section class="page-intro">
            <span class="eyebrow">About StudentHub</span>
            <h2>Made for a simpler student experience.</h2>
            <p>StudentHub brings together the information students need most: classes, deadlines, attendance, support, and campus updates in one clear place.</p>
        </section>

        <section class="grid">
            <article class="card">
                <h3>Our goal</h3>
                <p>Reduce the stress of managing university life by making information easy to find and use.</p>
            </article>

            <article class="card">
                <h3>What students get</h3>
                <p>Quick access to schedules, course updates, assignments, and student support without navigating multiple systems.</p>
            </article>

            <article class="card">
                <h3>Built for campus life</h3>
                <p>Designed to keep students informed, organised, and connected throughout the semester.</p>
            </article>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-brand"><div class="footer-logo">S</div><div><h2>StudentHub</h2><p>Your simple campus portal for staying organised, informed, and connected.</p></div></div>
            <div class="footer-column"><h3>Explore</h3><a href="dashboard.php">Dashboard</a><a href="courses.php">Courses</a><a href="timetable.php">Timetable</a></div>
            <div class="footer-column"><h3>Student tools</h3><a href="attendance.php">Attendance</a><a href="assignments.php">Assignments</a><a href="faq.php">FAQ</a></div>
            <div class="footer-column"><h3>Need help?</h3><p>Student Services<br>Mon-Fri, 9:00 AM-5:00 PM</p><a href="contact.php">Contact support &rarr;</a></div>
        </div>
        <div class="footer-bottom"><span>&copy; 2026 StudentHub. All rights reserved.</span><span>Built for better student experiences.</span></div>
    </footer>
    <script src="../javascript/site.js" defer></script>
</body>
</html>
