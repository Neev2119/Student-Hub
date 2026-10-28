CREATE TABLE IF NOT EXISTS events (
    event_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    event_time VARCHAR(20) NOT NULL,
    venue VARCHAR(150) NOT NULL,
    category VARCHAR(50) NOT NULL,
    organizer VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(255) NULL DEFAULT NULL,
    PRIMARY KEY (event_id)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS event_registrations (
    registration_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id VARCHAR(100) NOT NULL,
    student_name VARCHAR(100) NULL DEFAULT NULL,
    student_email VARCHAR(100) NULL DEFAULT NULL,
    mobile_no VARCHAR(10) NULL DEFAULT NULL,
    event_id INT UNSIGNED NOT NULL,
    event_title VARCHAR(150) NOT NULL,
    event_date DATE NOT NULL,
    registration_status ENUM('registered', 'cancelled', 'attended') NOT NULL DEFAULT 'registered',
    time_stamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (registration_id),
    UNIQUE KEY uq_event_registrations_student_event (student_id, event_id),
    KEY idx_event_registrations_status (registration_status),
    CONSTRAINT fk_event_registrations_event
        FOREIGN KEY (event_id) REFERENCES events (event_id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;

INSERT INTO events (
    event_id,
    title,
    event_date,
    event_time,
    venue,
    category,
    organizer,
    description,
    image
) VALUES
    (1, 'Innovation Showcase', '2026-09-24', 'TBD', 'Innovation Hall', 'Academic', 'StudentHub', 'See student projects, prototypes, and research ideas from across campus.', NULL),
    (2, 'Inter-college Football Trials', '2026-09-27', 'TBD', 'Main Ground', 'Sports', 'StudentHub', 'Open trials for students who want to represent StudentHub at the inter-college tournament.', NULL),
    (3, 'Career Planning Workshop', '2026-10-02', 'TBD', 'Seminar Room 2', 'Career', 'StudentHub', 'Build a practical plan for internships, portfolios, and your first graduate role.', NULL),
    (4, 'Open Mic Evening', '2026-10-05', 'TBD', 'Student Commons', 'Culture', 'StudentHub', 'An informal evening for music, poetry, comedy, and student performances.', NULL),
    (5, 'Library Research Clinic', '2026-10-08', 'TBD', 'Central Library', 'Academic', 'StudentHub', 'Learn how to find reliable sources and organise research for your next assignment.', NULL),
    (6, 'Wellbeing Check-in', '2026-10-12', 'TBD', 'Wellbeing Centre', 'Wellbeing', 'StudentHub', 'Drop in for a confidential conversation about study pressure and student wellbeing.', NULL),
    (7, 'Student Leadership Forum', '2026-10-15', 'TBD', 'Lecture Theatre A', 'Community', 'StudentHub', 'Meet student representatives and share ideas for improving campus life.', NULL),
    (8, 'Coding Hackathon', '2026-10-18', 'TBD', 'Digital Lab', 'Technology', 'StudentHub', 'Work in teams to build a useful solution to a real student challenge.', NULL),
    (9, 'Photography Walk', '2026-10-21', 'TBD', 'East Courtyard', 'Culture', 'StudentHub', 'Explore campus and practise visual storytelling with fellow photography enthusiasts.', NULL),
    (10, 'Entrepreneurship Pitch Day', '2026-10-24', 'TBD', 'Business Centre', 'Career', 'StudentHub', 'Present your business idea to mentors and receive practical feedback.', NULL),
    (11, 'Basketball League Finals', '2026-10-28', 'TBD', 'Indoor Arena', 'Sports', 'StudentHub', 'Support the finalist teams in the annual StudentHub basketball league.', NULL),
    (12, 'Language Exchange Meet', '2026-11-01', 'TBD', 'Student Commons', 'Community', 'StudentHub', 'Meet students from different backgrounds and practise a new language together.', NULL),
    (13, 'Public Speaking Masterclass', '2026-11-04', 'TBD', 'Seminar Room 1', 'Academic', 'StudentHub', 'Build confidence, structure stronger presentations, and speak with clarity.', NULL),
    (14, 'Mental Wellness Session', '2026-11-07', 'TBD', 'Wellbeing Centre', 'Wellbeing', 'StudentHub', 'Learn simple strategies for managing stress, focus, and healthy study habits.', NULL),
    (15, 'Alumni Networking Evening', '2026-11-10', 'TBD', 'Conference Hall', 'Career', 'StudentHub', 'Connect with alumni and hear how they moved from campus into their careers.', NULL)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    event_date = VALUES(event_date),
    event_time = VALUES(event_time),
    venue = VALUES(venue),
    category = VALUES(category),
    organizer = VALUES(organizer),
    description = VALUES(description),
    image = VALUES(image);
