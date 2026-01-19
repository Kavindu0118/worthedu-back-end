# Test Cases — learning-lms

This document lists concise, actionable test cases for the system's main features (controllers, models, and core flows). Each case contains: Purpose, Preconditions, Steps, and Expected result.

Authentication & User Management
- Login (valid credentials)
  - Purpose: Verify successful login and session/token generation.
  - Preconditions: Existing `users` row with known password.
  - Steps: POST `/api/login` with email+password.
  - Expected: 200 OK, JSON contains `token` or session cookie, user details.

- Login (invalid credentials)
  - Purpose: Reject wrong password.
  - Preconditions: Existing user.
  - Steps: POST `/api/login` with invalid password.
  - Expected: 401 Unauthorized with error message.

- Register Learner
  - Purpose: Create learner linked to `users`.
  - Preconditions: Unique email.
  - Steps: POST `/api/register/learner` with user info + learner fields.
  - Expected: 201 Created, `learners` row created, `users` row created, response contains learner id.

- Register Instructor (file upload / CV)
  - Purpose: Instructor creation and CV handling.
  - Preconditions: Unique email; multipart upload supported.
  - Steps: POST `/api/register/instructor` with user + instructor fields + CV file.
  - Expected: 201 Created, `instructors` row with `cv` or `cv_path` stored, user linked.


Courses & Catalog
- Create Course (valid instructor)
  - Purpose: Ensure instructor can create a course.
  - Preconditions: Authenticated instructor user.
  - Steps: POST `/api/courses` with title, description, category_id, price, level.
  - Expected: 201 Created, `courses` row has correct values and `instructor_id` set.

- Create Course (invalid category)
  - Purpose: Validate FK constraint/validation.
  - Preconditions: Authenticated instructor.
  - Steps: POST with non-existent `category_id`.
  - Expected: 422 validation error or 400/404 depending on controller logic.

- List Courses
  - Purpose: Course listing and pagination.
  - Preconditions: Several courses exist.
  - Steps: GET `/api/courses` optionally with `page` and filters.
  - Expected: 200 OK, paginated list, includes `instructor` relationship when requested.


Modules, Course Modules & Lessons
- Add Course Module
  - Purpose: Grouping content under a course.
  - Preconditions: Authenticated instructor for the course.
  - Steps: POST `/api/courses/{id}/modules` with `module_title` and optional `duration`.
  - Expected: 201, `course_modules` created and belongs to the course.

- Add Lesson to Course Module
  - Purpose: Lesson creation and link to `course_modules`.
  - Preconditions: Module exists.
  - Steps: POST `/api/course-modules/{id}/lessons` with title + content.
  - Expected: 201, `lessons` row created with `module_id` set.

- Lesson content retrieval
  - Purpose: Learner sees lesson content.
  - Preconditions: Learner enrolled and lesson exists.
  - Steps: GET `/api/lessons/{id}`.
  - Expected: 200, includes `content`, `video_url` if present.


Enrollment & Access Control
- Enroll in Course (happy path)
  - Purpose: Create an enrollment linking learner and course.
  - Preconditions: Authenticated learner; course exists.
  - Steps: POST `/api/enrollments` with `course_id`.
  - Expected: 201, `enrollments` created, learner can access protected content.

- Enroll duplicate prevention
  - Purpose: Avoid duplicate enrollments.
  - Preconditions: Learner already enrolled.
  - Steps: POST `/api/enrollments` again.
  - Expected: 409 Conflict or 422 with descriptive message.


Assignments & Submissions
- Create Assignment (course-level)
  - Purpose: Ensure assignment creation for a course.
  - Preconditions: Instructor owns the course.
  - Steps: POST `/api/courses/{id}/assignments` with title, description, deadline.
  - Expected: 201, `assignments` created and associated.

- Submit Assignment
  - Purpose: Learner can upload submission.
  - Preconditions: Assignment exists, learner enrolled.
  - Steps: POST `/api/assignments/{id}/submissions` with file upload or `file_path`.
  - Expected: 201, `submissions` row created with `learner_id`, `submitted_at` set.

- Grade Submission
  - Purpose: Instructor can grade and add feedback.
  - Preconditions: Submission exists and instructor owns course.
  - Steps: PUT/PATCH `/api/submissions/{id}` with `grade` and `feedback`.
  - Expected: 200, `grade` stored, notification optionally created.


Quizzes & Attempts
- Create Quiz (module-level)
  - Purpose: Add module quiz with JSON payload.
  - Preconditions: Module exists.
  - Steps: POST `/api/module-quizzes` with `quiz_data` JSON, `total_points`.
  - Expected: 201, `module_quizzes` created with correct JSON stored.

- Attempt Quiz
  - Purpose: Learner completes quiz and score is recorded.
  - Preconditions: Learner enrolled, quiz exists.
  - Steps: POST `/api/quizzes/{id}/attempts` with answers.
  - Expected: 201, `quiz_attempts` created with `score` and `attempted_at`.

- Invalid answer handling
  - Purpose: Reject malformed answers or missing required fields.
  - Steps: POST attempt with missing options or invalid option ids.
  - Expected: 422 validation errors with messages.


Reviews & Ratings
- Submit Course Review
  - Purpose: Learner can rate and review a course.
  - Preconditions: Learner enrolled or completed course.
  - Steps: POST `/api/courses/{id}/reviews` with `rating` and optional `review_text`.
  - Expected: 201, `course_reviews` row created; aggregate rating updated if implemented.


Notifications
- Create Notification (system)
  - Purpose: Add notification for a user.
  - Preconditions: Admin or system-triggered action.
  - Steps: POST `/api/notifications` with `user_id`, `title`, `message`.
  - Expected: 201, row in `notifications` with `is_read = false`.

- Mark Notification Read
  - Purpose: Learner marks a notification as read.
  - Preconditions: Notification exists for user.
  - Steps: PATCH `/api/notifications/{id}/read`.
  - Expected: 200, `is_read` becomes true.


Payments & Stripe
- Create Payment Intent / Checkout Session
  - Purpose: Ensure payment flow triggers Stripe and returns client token/session.
  - Preconditions: Course with non-zero price.
  - Steps: POST to payment endpoint with `course_id`.
  - Expected: 200/201, response contains Stripe session id or client secret.

- Handle Stripe Webhook
  - Purpose: Webhook processing (payment succeeded/cancelled).
  - Preconditions: Valid Stripe signature or test webhook.
  - Steps: POST to `/stripe/webhook` with event payload.
  - Expected: 200 OK and system state updated (enrollment created or payment recorded).


API & Edge Cases
- Unauthorized access to protected endpoints
  - Purpose: Verify middleware protects routes.
  - Steps: Call protected endpoint without token.
  - Expected: 401 Unauthorized.

- Missing required fields
  - Purpose: Confirm controller validation works.
  - Steps: POST missing required fields (e.g., create course without title).
  - Expected: 422 with validation details.

- Foreign key constraints and cascade delete
  - Purpose: Confirm cascade deletes (e.g., deleting a `course` removes `course_modules`, lessons, module items).
  - Steps: Delete a course and check dependent rows.
  - Expected: Dependent rows removed (or appropriate soft-delete behavior documented).


Test Data & Environment Notes
- Use model factories for `User`, `Learner`, `Instructor`, `Course`, `CourseModule`, `Lesson`, `Assignment`, `ModuleAssignment`, `ModuleQuiz`.
- Seed minimal categories and an instructor user for course creation tests.
- For file uploads, use temporary files and the framework's `UploadedFile::fake()` helpers.
- For Stripe webhook tests, either mock Stripe SDK calls or use signed test payloads.

Suggested Priorities
- High: Auth flows, enrollments, assignment submissions, payments, access control.
- Medium: Module/lesson CRUD, quizzes, course reviews.
- Low: Notifications content formatting, optional fields behavior.

If you'd like, I can:
- Generate PHPUnit feature tests implementing a subset of these cases, or
- Produce Postman collection / curl snippets for API-level tests, or
- Generate an ER diagram from migrations.
