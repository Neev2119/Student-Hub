ALTER TABLE students
    ADD COLUMN role ENUM('student', 'admin') NOT NULL DEFAULT 'student' AFTER password;
