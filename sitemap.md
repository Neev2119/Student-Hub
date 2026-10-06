# StudentHub – Sitemap

This sitemap is based on the actual HTML pages and navigation structure present in the StudentHub project.

## 1. Main Website Hierarchy

```text
StudentHub
│
├── Home
│   └── pages/index.html
│
├── Authentication
│   ├── Login
│   │   └── pages/login.html
│   └── Register
│       └── pages/register.html
│
├── Student Portal
│   ├── Dashboard
│   │   └── pages/dashboard.html
│   ├── Attendance
│   │   └── pages/attendance.html
│   ├── Assignments
│   │   └── pages/assignments.html
│   ├── Courses
│   │   └── pages/courses.htm
│   ├── Timetable
│   │   └── pages/timetable.html
│   └── Feedback
│       └── pages/feedback.html
│
├── Support
│   ├── Contact
│   │   └── pages/contact.html
│   └── FAQ
│       └── pages/faq.html
│
└── General
    └── Home Entry Page
        └── pages/index.html
```

## 2. Main Navigation Flow

```text
                           ┌───────────────┐
                           │     HOME      │
                           │ index.html    │
                           └──────┬────────┘
                                  │
          ┌───────────────────────┼────────────────────────┐
          ▼                       ▼                        ▼
     Dashboard             Attendance               Assignments
     pages/dashboard.html   pages/attendance.html   pages/assignments.html
          │                       │                        │
          │                       │                        │
          └──────────────┬────────┴───────────────┬──────────┘
                         ▼                        ▼
                    Courses                 Timetable
                 pages/courses.htm       pages/timetable.html
                         │
                         ▼
                     Login / Register
                 pages/login.html / register.html
                         │
                         ▼
                     Contact / FAQ / Feedback
                 pages/contact.html / faq.html / feedback.html
```

## 3. Page Descriptions

### Home
`pages/index.html`

Main landing page of the StudentHub portal. It includes the introduction, navigation links, student-focused highlights, and access to important sections such as dashboard, attendance, assignments, and support.

### Dashboard
`pages/dashboard.html`

Student dashboard overview page that shows the student profile, academic progress, and quick links to key areas such as attendance and assignments.

### Attendance
`pages/attendance.html`

Displays attendance summary, subject-wise attendance records, and a general view of attendance performance.

### Assignments
`pages/assignments.html`

Shows assignment information, due dates, priority tasks, and overall academic workload tracking.

### Courses
`pages/courses.htm`

Displays the list of active or enrolled courses and key academic overview details.

### Timetable
`pages/timetable.html`

Shows class schedule and weekly timetable information for the student.

### Login
`pages/login.html`

Login page for students to sign into the portal.

### Register
`pages/register.html`

Registration page for new users to create an account.

### Contact
`pages/contact.html`

Provides communication and enquiry details for student support or campus assistance.

### Feedback
`pages/feedback.html`

Allows students to submit feedback or share their experience with the platform.

### FAQ
`pages/faq.html`

Contains frequently asked questions and general support information for students.

## 4. Existing HTML Pages

| # | Page | File |
|---:|---|---|
| 1 | Home | `pages/index.html` |
| 2 | Dashboard | `pages/dashboard.html` |
| 3 | Attendance | `pages/attendance.html` |
| 4 | Assignments | `pages/assignments.html` |
| 5 | Courses | `pages/courses.htm` |
| 6 | Timetable | `pages/timetable.html` |
| 7 | Login | `pages/login.html` |
| 8 | Register | `pages/register.html` |
| 9 | Contact | `pages/contact.html` |
| 10 | Feedback | `pages/feedback.html` |
| 11 | FAQ | `pages/faq.html` |

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
  ├──► Dashboard
  ├──► Attendance
  ├──► Assignments
  ├──► Courses
  ├──► Timetable
  ├──► Contact
  ├──► FAQ
  ├──► Feedback
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
    ├──► Feedback
    └──► Contact
```

## 7. Summary

The StudentHub website already contains a complete student portal structure, with the main pages focused on academic management, support, and communication. The sitemap above reflects the real project files and is suitable for documentation, project presentation, and portal planning.
