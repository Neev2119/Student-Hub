# StudentHub

StudentHub is a simple campus portal for students to manage courses, attendance, assignments, timetable details, and academic resources from one place.

## Overview

This project is a front-end academic website designed to help students check important information quickly and stay organised. It keeps the layout clean, mobile-friendly, and easy to browse across multiple pages.

## Features

- Dashboard overview with summary cards
- Attendance and academic tracking
- Assignment and deadline information
- Course and timetable access
- FAQ and support sections
- Contact and feedback forms
- Login and registration screens backed by MySQL
- Session-protected student pages
- Responsive layout for desktops and mobile devices

## Pages Included

- Home
- Dashboard
- Attendance
- Assignments
- Courses
- Timetable
- FAQ
- Resources
- Contact
- Feedback
- About
- Practical 7 PHP form-processing demonstration (`pages/practical7.php`)
- Login
- Register

## Tech Stack

- HTML5
- CSS3
- JavaScript
- PHP
- Bootstrap 5 (lightly used on the admin dashboard)
- Responsive web design

## Project Structure

```text
StudentHub/
├── css/
│   ├── style.css
│   ├── auth.css
│   ├── index.css
│   ├── dashboard.css
│   ├── attendance.css
│   ├── assignments.css
│   ├── courses.css
│   ├── resources.css
│   ├── contact.css
│   ├── faq.css
│   ├── feedback.css
│   ├── about.css
│   └── register.css
├── data/
│   ├── events.json
│   ├── faqs.json
│   └── students.json
├── docs/
├── images/
├── javascript/
│   ├── login.js
│   ├── register.js
│   ├── resources.js
│   └── site.js
├── pages/
│   ├── index.php
│   ├── dashboard.php
│   ├── attendance.php
│   ├── assignments.php
│   ├── courses.php
│   ├── timetable.php
│   ├── about.php
│   ├── faq.php
│   ├── resources.php
│   ├── contact.php
│   ├── feedback.php
│   ├── login.html
│   └── register.html
├── README.md
└── sitemap.md
```

## How to Run

The forms use PHP, and registration also requires the MySQL `student_hub` database.

### Steps

1. Start Apache and MySQL in WAMP.
2. Run the SQL migrations in `php/migrations/000_create_students.sql` through `php/migrations/005_seed_students.sql` against `student_hub`, in numeric order. Migration `000` creates the base `students` table if needed; migrations `001` and `003` add usernames and roles; migration `005` inserts 20 sample student accounts. Migration `001` adds unique constraints for usernames and email addresses. If it was already applied before this constraint was added, check for duplicate email addresses, then run `ALTER TABLE students ADD CONSTRAINT uq_students_email UNIQUE (email);` once. Do not rerun migrations `001` or `003` if their columns already exist.
4. Open `http://localhost/WDF/Student-Hub/pages/index.php` in your browser.
5. Open the registration page at `http://localhost/WDF/Student-Hub/pages/register.html`.
6. Open `http://localhost/WDF/Student-Hub/pages/practical7.php` to test the standalone PHP form-processing practical. Valid messages are saved to `data/practical7-submissions.json`.

## Notes

- Registrations are inserted into `student_hub.students`; passwords are stored as password hashes. The handler also maintains JSON/CSV/text backups.
- The 20 sample accounts use `student01` through `student20` as usernames and `student01@example.com` through `student20@example.com` as emails. Their shared sample password is `Student@123`; change or remove these demo accounts before using real student data. Migration `005` uses `INSERT IGNORE`, so it can be rerun without replacing existing account details.
- Migration `002` creates `events` and `event_registrations`, then seeds all events from `data/events.json`. Since that JSON does not provide event times or organizers, seeded events use `TBD` for `event_time` and `StudentHub` for `organizer`; images remain `NULL` until provided. Signed-in students can register for an event from the Campus information page; registrations are saved in `event_registrations`.
- Login accepts email and password. Contact submissions are saved to `data/form-submissions.json`.
- Sessions use HTTP-only, SameSite cookies and expire after 30 minutes of inactivity. New registrations and legacy accounts are students; administrators must be promoted explicitly in MySQL, for example with `UPDATE students SET role = 'admin' WHERE email = 'admin@example.com';`. After login, admins choose between the separate admin and student workspaces at `pages/select-mode.php`; regular students go to `pages/dashboard.php`.
- Admins can add, view, edit, delete, search, and filter student accounts at `pages/admin-students.php`. Every student-management database operation uses prepared MySQLi statements; the page validates input and requires an admin session and CSRF token.
- Admins can add, view, edit, and delete events at `pages/admin-events.php`. Event posters accept JPG, PNG, and WebP images up to 5 MB; the event browser loads database events and posters from `php/events.php`. Event uploads are stored under `uploads/event-posters/`, so the web server must be able to write to `uploads/`. Set PHP `upload_max_filesize` to at least `5M` and `post_max_size` above `5M` to accept the full poster limit.
- The admin dashboard at `pages/admin.php` displays database totals, a six-month chart of student-account and event registrations, and the latest student-account and event registrations. The navigation menu adds admin links for signed-in administrators. Student and event create, update, and delete operations are written to `audit_logs` in the same database transaction; administrators can review them at `pages/admin-audit-logs.php`.
- Audit-log verification: after applying migration `004`, use a test administrator to add, edit, and delete a test student and event, then confirm each `student.create`, `student.update`, `student.delete`, `event.create`, `event.update`, and `event.delete` action appears in the audit-log page with the correct actor and record ID. Confirm an unauthenticated user or a student cannot access the admin pages. Use disposable test records for delete checks.
- The admin event page uses Fetch API requests to list events (`GET`), create events (`POST` with `action=add`), update events (`POST` with `action=update`), and delete events (`DELETE`). Write requests require the admin session and its CSRF token; add and update responses do not reload the page.
- Legacy portal links ending in `.html` or `.htm` are routed to their matching PHP pages.
- Home, Login, and Register are public. Other portal pages and contact submissions require an active authenticated session.
- It is meant for academic learning and requires Apache with PHP enabled for form submission.

## License

This project is for educational purposes and can be modified or extended as needed.

## Author

StudentHub Project
