# Test Feature - API Documentation

## ✅ Implementation Status: COMPLETE

All backend components have been successfully implemented and deployed.

---

## Database Tables

✅ **tests** - Main test table with configuration  
✅ **test_questions** - Questions for each test (MCQ, descriptive, file upload)  
✅ **test_submissions** - Student test submissions  
✅ **test_answers** - Individual answers for each question  

---

## API Endpoints

### Instructor Endpoints (Prefix: `/api/instructor`)

#### 1. Get Tests by Course
```
GET /api/instructor/courses/{courseId}/tests
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "test_title": "Midterm Exam",
      "module_title": "Module 1",
      "course_title": "Web Development",
      "start_date": "2024-03-15T10:00:00Z",
      "end_date": "2024-03-15T12:00:00Z",
      "total_marks": 100,
      "status": "active",
      "submissions_count": 25,
      "graded_count": 10
    }
  ]
}
```

#### 2. Get Test Details
```
GET /api/instructor/tests/{testId}
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "test_title": "Midterm Exam",
    "test_description": "Assessment covering modules 1-5",
    "start_date": "2024-03-15T10:00:00Z",
    "questions": [
      {
        "id": 1,
        "question": "What is React?",
        "type": "mcq",
        "points": 5,
        "options": ["A library", "A framework"],
        "correct_answer": "A library"
      }
    ]
  }
}
```

#### 3. Create Test
```
POST /api/instructor/tests
Authorization: Bearer {token}
Content-Type: application/json

Request:
{
  "module_id": 1,
  "test_title": "Final Exam",
  "test_description": "Comprehensive examination",
  "instructions": "Read carefully...",
  "start_date": "2024-03-20T10:00:00",
  "end_date": "2024-03-20T12:00:00",
  "time_limit": 120,
  "max_attempts": 1,
  "total_marks": 100,
  "passing_marks": 60,
  "questions": [
    {
      "question": "Explain MVC pattern",
      "type": "descriptive",
      "points": 10,
      "max_characters": 1000,
      "order_index": 0
    }
  ]
}

Response 201:
{
  "success": true,
  "data": { /* test object with questions */ }
}
```

#### 4. Update Test
```
PUT /api/instructor/tests/{testId}
Authorization: Bearer {token}

Request: (Same structure as create, partial updates allowed)

Response 200:
{
  "success": true,
  "data": { /* updated test */ }
}
```

#### 5. Delete Test
```
DELETE /api/instructor/tests/{testId}
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "message": "Test deleted"
}
```

#### 6. Get Test Submissions
```
GET /api/instructor/tests/{testId}/submissions
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "student_id": 10,
      "student_name": "John Doe",
      "submitted_at": "2024-03-15T11:45:00Z",
      "submission_status": "submitted",
      "total_score": 85,
      "grading_status": "graded"
    }
  ]
}
```

#### 7. Get Submission Details
```
GET /api/instructor/test-submissions/{submissionId}
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "student_name": "John Doe",
    "answers": [
      {
        "question_id": 1,
        "question_type": "mcq",
        "selected_option": "A library",
        "points_awarded": 5,
        "is_correct": true
      }
    ]
  }
}
```

#### 8. Grade Submission
```
POST /api/instructor/test-submissions/{submissionId}/grade
Authorization: Bearer {token}

Request:
{
  "answers": [
    {
      "question_id": 2,
      "points_awarded": 8,
      "feedback": "Good work but incomplete"
    }
  ],
  "instructor_feedback": "Overall excellent",
  "publish_results": true
}

Response 200:
{
  "success": true,
  "data": { /* updated submission */ }
}
```

#### 9. Publish Results
```
POST /api/instructor/tests/{testId}/publish-results
Authorization: Bearer {token}

Request:
{
  "publish": true
}

Response 200:
{
  "success": true,
  "data": { /* test with updated status */ }
}
```

#### 10. Get Statistics
```
GET /api/instructor/tests/{testId}/statistics
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": {
    "test_id": 1,
    "total_students_enrolled": 30,
    "total_submissions": 28,
    "average_score": 78.5,
    "highest_score": 95,
    "pass_rate": 85.7
  }
}
```

---

### Student/Learner Endpoints (Prefix: `/api/learner`)

#### 1. Get Test View
```
GET /api/learner/tests/{testId}
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": {
    "test": { /* full test with questions */ },
    "can_attempt": true,
    "reason": null,
    "remaining_attempts": 1,
    "current_submission": null
  }
}
```

#### 2. Start Test
```
POST /api/learner/tests/{testId}/start
Authorization: Bearer {token}

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "test_id": 1,
    "student_id": 10,
    "submission_status": "in_progress",
    "started_at": "2024-03-15T10:05:00Z"
  }
}
```

#### 3. Submit Test
```
POST /api/learner/test-submissions/{submissionId}/submit
Authorization: Bearer {token}

Request:
{
  "answers": [
    {
      "question_id": 1,
      "question_type": "mcq",
      "selected_option": "A library",
      "max_points": 5
    },
    {
      "question_id": 2,
      "question_type": "descriptive",
      "text_answer": "MVC stands for...",
      "max_points": 10
    }
  ]
}

Response 200:
{
  "success": true,
  "data": {
    "id": 1,
    "submission_status": "submitted",
    "time_taken": 100
  }
}
```

#### 4. Upload File
```
POST /api/learner/test-submissions/{submissionId}/upload
Authorization: Bearer {token}
Content-Type: multipart/form-data

Form Data:
- file: [File]
- question_id: 3

Response 200:
{
  "success": true,
  "data": {
    "file_url": "/storage/test-answers/file.pdf",
    "file_name": "answer.pdf"
  }
}
```

#### 5. Auto-save Progress
```
POST /api/learner/test-submissions/{submissionId}/autosave
Authorization: Bearer {token}

Request:
{
  "answers": [ /* same format as submit */ ]
}

Response 200:
{
  "success": true,
  "data": {
    "saved_at": "2024-03-15T10:30:00Z"
  }
}
```

#### 6. Get Results
```
GET /api/learner/tests/{testId}/results
Authorization: Bearer {token}

Response 200 (if published):
{
  "success": true,
  "data": {
    "total_score": 85,
    "instructor_feedback": "Good work!",
    "answers": [
      {
        "question_id": 1,
        "points_awarded": 5,
        "feedback": null
      }
    ]
  }
}

Response 403 (if not published):
{
  "success": false,
  "message": "Results not published yet"
}
```

---

## Business Logic Features

### Auto-grading
- MCQ questions are automatically graded when submitted
- Correct answers receive full points
- Total score calculated automatically for all-MCQ tests

### Test Status Management
- **draft**: Initial state
- **scheduled**: Before start_date
- **active**: Between start_date and end_date
- **closed**: After end_date

### Validation Rules
- Students must be enrolled in course
- Attempt limits enforced
- Time limits tracked
- Late submission detection
- File type/size validation

### Authorization
- Instructors can only manage their own courses' tests
- Students can only access tests for enrolled courses
- All endpoints require authentication

---

## Question Types

### 1. MCQ (Multiple Choice)
```json
{
  "type": "mcq",
  "question": "What is React?",
  "options": ["A library", "A framework", "A language"],
  "correct_answer": "A library",
  "points": 5
}
```

### 2. Descriptive
```json
{
  "type": "descriptive",
  "question": "Explain MVC pattern",
  "max_characters": 1000,
  "points": 10
}
```

### 3. File Upload
```json
{
  "type": "file_upload",
  "question": "Upload your project",
  "allowed_file_types": ["pdf", "zip"],
  "max_file_size": 10,
  "points": 20
}
```

---

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized"
}
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Not enrolled in this course"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Test not found"
}
```

### 422 Validation Error
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "test_title": ["The test title field is required."]
  }
}
```

### 500 Server Error
```json
{
  "success": false,
  "message": "Error creating test",
  "error": "Database connection failed"
}
```

---

## Testing the API

### Using cURL

```bash
# Get tests for a course
curl -X GET "http://localhost/learning-lms/public/api/instructor/courses/1/tests" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Create a test
curl -X POST "http://localhost/learning-lms/public/api/instructor/tests" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "module_id": 1,
    "test_title": "Sample Test",
    "test_description": "Description",
    "start_date": "2026-01-25T10:00:00",
    "end_date": "2026-01-25T12:00:00",
    "max_attempts": 1,
    "total_marks": 100,
    "questions": []
  }'
```

---

## Implementation Complete! ✅

All endpoints are functional and ready for frontend integration.
