# StudentHub – Sitemap

This sitemap is based on the actual pages and navigation structure present in the StudentHub project.

## 1. Main Website Hierarchy

```text
StudentHub
│
├── Home
│   └── pages/index.php
│
├── Authentication
│   ├── Login
│   │   └── pages/login.html
│   └── Register
│       └── pages/register.html
│
├── Student Portal
│   ├── Dashboard
│   │   └── pages/dashboard.php
│   ├── Attendance
│   │   └── pages/attendance.php
│   ├── Assignments
│   │   └── pages/assignments.php
│   ├── Courses
│   │   └── pages/courses.php
│   ├── Timetable
│   │   └── pages/timetable.php
│   ├── About
│   │   └── pages/about.php
│   ├── Resources
│   │   └── pages/resources.php
│   └── Feedback
│       └── pages/feedback.php
│
├── Administrator Portal (admin mode only)
│   ├── Workspace selector (admin accounts)
│   │   └── pages/select-mode.php
│   ├── Dashboard
│   │   └── pages/admin.php
│   ├── Manage students
│   │   └── pages/admin-students.php
│   ├── Manage events
│   │   └── pages/admin-events.php
│   └── Audit log
│       └── pages/admin-audit-logs.php
│
├── Support
│   ├── Contact
│   │   └── pages/contact.php
│   └── FAQ
│       └── pages/faq.php
│
└── General
    └── Home Entry Page
        └── pages/index.php
```

## Access Rules

Home, Login, and Register are available to visitors. Portal pages require an
authenticated session. Admin accounts select a workspace after login; student
pages require student mode and administrator pages require administrator mode.
Visitors who request a protected page or submit the contact form directly are
redirected to Login.

## 2. Main Navigation Flow

```text
Visitor
  ├── Home
  ├── Login
  └── Register

After login:
  Admin account
  ├── Select workspace (admin accounts only)
  │   ├── Administrator dashboard
  │   └── Student dashboard
  Student account
  └── Student dashboard
  
Student workspace
  ├── Attendance
  ├── Assignments
  ├── Courses
  ├── Timetable
  ├── About
  ├── Resources
  ├── Contact
  ├── FAQ
  └── Feedback

Administrator workspace
  ├── Dashboard and registration statistics
  ├── Manage students
  ├── Manage events
  └── Audit log
```

Admin accounts choose their workspace after login and can switch modes from the
workspace navigation. Regular student accounts continue directly to the student
dashboard.

## 3. Page Descriptions

### Home
`pages/index.php`

Main landing page of the StudentHub portal. It includes the introduction, navigation links, student-focused highlights, and access to important sections such as dashboard, attendance, assignments, and support.

### Dashboard
`pages/dashboard.php`

Student dashboard overview page that shows the student profile, academic progress, and quick links to key areas such as attendance and assignments.

### Attendance
`pages/attendance.php`

Displays attendance summary, subject-wise attendance records, and a general view of attendance performance.

### Assignments
`pages/assignments.php`

Shows assignment information, due dates, priority tasks, and overall academic workload tracking.

### Courses
`pages/courses.php`

Displays the list of active or enrolled courses and key academic overview details.

### Timetable
`pages/timetable.php`

Shows class schedule and weekly timetable information for the student.

### About
`pages/about.php`

Provides information about the StudentHub portal.

### Resources
`pages/resources.php`

Shows campus events, student records, and FAQs from the resources browser.

### Login
`pages/login.html`

Login page for students to sign into the portal.

### Register
`pages/register.html`

Registration page for new users to create an account.

### Contact
`pages/contact.php`

Provides communication and enquiry details for student support or campus assistance.

### Feedback
`pages/feedback.php`

Allows students to submit feedback or share their experience with the platform.

### FAQ
`pages/faq.php`

Contains frequently asked questions and general support information for students.

## 4. Current Pages

| # | Page | File |
|---:|---|---|
| 1 | Home | `pages/index.php` |
| 2 | Dashboard | `pages/dashboard.php` |
| 3 | Attendance | `pages/attendance.php` |
| 4 | Assignments | `pages/assignments.php` |
| 5 | Courses | `pages/courses.php` |
| 6 | Timetable | `pages/timetable.php` |
| 7 | About | `pages/about.php` |
| 8 | Resources | `pages/resources.php` |
| 9 | Login | `pages/login.html` |
| 10 | Register | `pages/register.html` |
| 11 | Contact | `pages/contact.php` |
| 12 | Feedback | `pages/feedback.php` |
| 13 | FAQ | `pages/faq.php` |

## 5. Recommended Future Expansion Sitemap

The current project is already a strong student portal, and these pages could be added later for a more complete academic platform:

```text
StudentHub
│
├── Home
├── Dashboard
├── Attendance
├── Assignments
├── Courses
├── Timetable
├── About
├── Resources
│   ├── Notes
│   ├── Papers
│   ├── Videos
│   └── E-books
├── Events
├── Notifications
├── Profile
│   └── Edit Profile
├── Feedback
├── Contact
├── FAQ
├── Login
├── Register
├── Privacy Policy
├── Terms & Conditions
└── Admin Panel
```

## 6. User Flow

```text
Visitor
  │
  ▼
Home
  │
  ├──► Login
  └──► Register

After login:
  Login
    │
    ▼
  Dashboard
    ├──► Attendance
    ├──► Assignments
    ├──► Courses
    ├──► Timetable
    ├──► Resources
    ├──► Feedback
    └──► Contact
```

## 7. Summary

The StudentHub website already contains a complete student portal structure, with the main pages focused on academic management, support, and communication. The sitemap above reflects the real project files and is suitable for documentation, project presentation, and portal planning.
