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
- Login and registration screens
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
- Login
- Register

## Tech Stack

- HTML5
- CSS3
- JavaScript
- Responsive web design

## Project Structure

```text
StudentHub/
├── css/
│   └── style.css
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
│   ├── index.html
│   ├── dashboard.html
│   ├── attendance.html
│   ├── assignments.html
│   ├── courses.htm
│   ├── timetable.html
│   ├── faq.html
│   ├── resources.html
│   ├── contact.html
│   ├── feedback.html
│   ├── login.html
│   └── register.html
├── README.md
└── sitemap.md
```

## How to Run

The registration and contact forms use PHP, so open the project through WAMP/Apache instead of opening the HTML files directly.

### Steps

1. Start the Apache service in WAMP.
2. Open `http://localhost/WDF/Student-Hub/pages/index.html` in your browser.
3. Open the registration page at `http://localhost/WDF/Student-Hub/pages/register.html`.

## Notes

- Registration and contact submissions are saved to `data/form-submissions.json` by the PHP handler.
- It is meant for academic learning and requires Apache with PHP enabled for form submission.

## License

This project is for educational purposes and can be modified or extended as needed.

## Author

StudentHub Project
