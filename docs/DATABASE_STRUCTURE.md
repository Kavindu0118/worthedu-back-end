# Database Structure — learning-lms

This document summarizes the database tables, columns (types), and key relationships extracted from the project's migrations.

**Tables**

- **users**: id (PK), name (string), email (string, unique), email_verified_at (timestamp, nullable), password (string), remember_token, timestamps
  - Notes: base user table used by `learners`, `instructors`, `notifications`.

- **learners**: learner_id (PK), user_id (FK -> users.id, unique, cascade), first_name (string, nullable), last_name (string, nullable), date_of_birth (date, nullable), address (text, nullable), highest_qualification (enum: none|certificate|diploma|degree, default none), mobile_number (string, nullable), registration_date (date, nullable), timestamps

- **instructors**: instructor_id (PK), user_id (FK -> users.id, unique, cascade), first_name (string), last_name (string), date_of_birth (date), address (text), mobile_number (string), highest_qualification (enum: certificate|diploma|degree), subject_area (string), cv_path (string), note (text, nullable), timestamps

- **categories**: id (PK), name (string), description (string, nullable), timestamps

- **courses**: id (PK), instructor_id (FK -> instructors.instructor_id, cascade), title (string), description (text), category_id (FK -> categories.id, cascade), price (decimal 10,2), level (enum: beginner|intermediate|advanced), thumbnail (string, nullable), status (enum: draft|published|archived, default draft), timestamps

- **course_modules**: id (PK), course_id (FK -> courses.id, cascade), module_title (string), module_description (text, nullable), order_index (int, default 0), duration (string, nullable), timestamps
  - Notes: `lessons`, `module_quizzes`, `module_assignments` reference `course_modules` (module grouping for a course).

- **modules**: id (PK), course_id (FK -> courses.id, cascade), title (string), description (text, nullable), order_no (int), timestamps
  - Notes: present in the codebase but `lessons` reference `course_modules` — two module-like tables exist.

- **lessons**: id (PK), module_id (FK -> course_modules.id, cascade), title (string), content (longText, nullable), video_url (string, nullable), duration (string, nullable), order_no (int), timestamps

- **enrollments**: id (PK), learner_id (FK -> learners.learner_id, cascade), course_id (FK -> courses.id, cascade), enrolled_at (datetime), status (enum: active|completed|cancelled, default active), timestamps

- **assignments**: id (PK), course_id (FK -> courses.id, cascade), title (string), description (text), deadline (datetime), timestamps

- **module_assignments**: id (PK), module_id (FK -> course_modules.id, cascade), assignment_title (string), instructions (text, nullable), attachment_url (string, nullable), max_points (int, default 100), due_date (datetime, nullable), timestamps

- **submissions**: id (PK), assignment_id (FK -> assignments.id, cascade), learner_id (FK -> learners.learner_id, cascade), file_path (string), submitted_at (datetime), grade (int, nullable), feedback (text, nullable), timestamps

- **quizzes**: id (PK), course_id (FK -> courses.id, cascade), title (string), description (text, nullable), timestamps

- **module_quizzes**: id (PK), module_id (FK -> course_modules.id, cascade), quiz_title (string), quiz_description (text, nullable), quiz_data (json), total_points (int, default 0), time_limit (int, nullable, minutes), timestamps

- **questions**: id (PK), quiz_id (FK -> quizzes.id, cascade), question_text (text), question_type (enum: mcq|true_false|short_answer), timestamps

- **options**: id (PK), question_id (FK -> questions.id, cascade), option_text (string), is_correct (boolean, default false), timestamps

- **quiz_attempts**: id (PK), quiz_id (FK -> quizzes.id, cascade), learner_id (FK -> learners.learner_id, cascade), score (int), attempted_at (datetime), timestamps

- **course_reviews**: id (PK), course_id (FK -> courses.id, cascade), learner_id (FK -> learners.learner_id, cascade), rating (int), review_text (text, nullable), timestamps

- **notifications**: id (PK), user_id (FK -> users.id, cascade), title (string), message (text), is_read (boolean, default false), timestamps

**Other framework/system tables**

- **jobs**, **job_batches**, **failed_jobs**, **cache**, **cache_locks**, **password_reset_tokens**, **sessions** — standard Laravel/system tables created by initial migrations.

**Notes & relationships summary**
- Learners and instructors are one-to-one with `users` via `user_id`.
- Courses belong to an instructor and a category.
- `course_modules` groups content for a course; lessons, module-level quizzes and assignments reference it.
- Course-level `quizzes` and `assignments` exist alongside module-level variants (`module_quizzes`, `module_assignments`).
- Enrollments, submissions, quiz attempts, and course reviews link learners to course activities.

For the migrations that defined these tables, see the project migration files in [database/migrations](database/migrations).
