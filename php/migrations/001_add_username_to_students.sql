ALTER TABLE students
    ADD COLUMN username VARCHAR(30) NULL UNIQUE AFTER id,
    ADD CONSTRAINT uq_students_email UNIQUE (email);
