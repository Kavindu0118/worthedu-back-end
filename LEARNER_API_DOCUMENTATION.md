# Learner Dashboard API Documentation

## 🎓 Overview

Complete backend API for the Learning Management System's Learner Dashboard. All endpoints require authentication using Bearer token in the `Authorization` header.

**Base URL:** `http://127.0.0.1:8000/api/learner`

**Authentication:** All requests must include:
```
Authorization: Bearer YOUR_API_TOKEN_HERE
```

---

## 📊 Dashboard & Statistics

### GET /dashboard
Get comprehensive dashboard overview with stats, progress data, and recommendations.

**Response:**
```json
{
  "success": true,
  "data": {
    "learner": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "avatar": "http://127.0.0.1:8000/storage/avatars/user1.jpg",
      "membershipType": "free"
    },
    "stats": {
      "enrolledCourses": 5,
      "completedCourses": 2,
      "totalHours": 47.5,
      "certificates": 2
    },
    "progressData": [
      {"date": "Dec 3", "hours": 2.5},
      {"date": "Dec 4", "hours": 3.0}
    ],
    "continueLearning": [...],
    "upcomingAssignments": [...],
    "recentNotifications": [...]
  }
}
```

### GET /stats
Get detailed learning statistics.

### GET /activity?days=7
Get activity logs for the last N days (default: 7).

---

## 👤 Profile Management

### GET /profile
Get current user profile.

### PUT /profile
Update user profile.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "bio": "Software developer and lifelong learner",
  "date_of_birth": "1990-01-15"
}
```

### POST /profile/avatar
Upload profile avatar (max 2MB, jpg/png/gif).

**Request:** `multipart/form-data` with `avatar` file

### DELETE /profile/avatar
Delete current profile avatar.

### PUT /profile/password
Change password.

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

---

## 📚 Course Management

### GET /courses?status=active
Get enrolled courses.

**Query Parameters:**
- `status` (optional): `active`, `completed`, `paused`, `dropped`

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Web Development Fundamentals",
      "description": "Learn the basics...",
      "thumbnail": "http://...",
      "category": "Programming",
      "level": "beginner",
      "duration": "40 hours",
      "instructor": {
        "id": 2,
        "name": "Jane Smith"
      },
      "enrollment": {
        "id": 5,
        "status": "active",
        "progress": 65.5,
        "enrolled_at": "2025-11-01T10:00:00Z",
        "last_accessed": "2025-12-09T14:30:00Z",
        "completed_at": null
      }
    }
  ]
}
```

### GET /courses/available?category=Programming&search=python
Get courses available for enrollment.

**Query Parameters:**
- `category` (optional): Filter by category
- `search` (optional): Search in title/description
- `level` (optional): `beginner`, `intermediate`, `advanced`

### GET /courses/{id}
Get detailed course information with modules and progress.

### POST /courses/{id}/enroll
Enroll in a course.

**Response:**
```json
{
  "success": true,
  "message": "Successfully enrolled in the course",
  "data": {
    "enrollment_id": 10,
    "course_id": 3,
    "course_title": "Python Programming",
    "enrolled_at": "2025-12-09T15:00:00Z"
  }
}
```

### GET /courses/{id}/progress
Get detailed progress breakdown for a course.

---

## 📖 Lesson Management

### GET /lessons/{id}
Get lesson/module content with navigation.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 15,
    "course_id": 3,
    "course_title": "Python Programming",
    "title": "Introduction to Variables",
    "description": "Learn about variables...",
    "type": "video",
    "content_url": "https://youtube.com/watch?v=...",
    "content_text": null,
    "duration": "15 minutes",
    "duration_minutes": 15,
    "order_index": 1,
    "is_mandatory": true,
    "progress": {
      "status": "in_progress",
      "started_at": "2025-12-09T14:00:00Z",
      "completed_at": null,
      "time_spent_minutes": 8,
      "last_position": "00:05:23"
    },
    "navigation": {
      "next_module": {"id": 16, "title": "Data Types"},
      "previous_module": null
    }
  }
}
```

### POST /lessons/{id}/start
Mark lesson as started (status: in_progress).

### PUT /lessons/{id}/progress
Update lesson progress (time spent, video position).

**Request Body:**
```json
{
  "time_spent_minutes": 10,
  "last_position": "00:07:45"
}
```

### POST /lessons/{id}/complete
Mark lesson as completed. Automatically updates course progress and may trigger certificate generation.

---

## 📝 Assignment Management

### GET /assignments?status=pending&course_id=3
Get list of assignments.

**Query Parameters:**
- `status` (optional): `pending`, `submitted`, `graded`
- `course_id` (optional): Filter by course

### GET /assignments/{id}
Get assignment details including submission if exists.

### POST /assignments/{id}/submit
Submit assignment with optional file upload.

**Request:** `multipart/form-data`
```
submission_text: "Here is my submission..."
file: [file upload]
```

**Response:**
```json
{
  "success": true,
  "message": "Assignment submitted successfully",
  "data": {
    "submission_id": 42,
    "assignment_id": 10,
    "submitted_at": "2025-12-09T16:30:00Z",
    "is_late": false
  }
}
```

### GET /assignments/{id}/submission
Get submission details with grading and feedback.

---

## 🎯 Quiz Management

### GET /quizzes?course_id=3&status=available
Get list of quizzes.

**Query Parameters:**
- `course_id` (optional): Filter by course
- `status` (optional): `available`, `completed`

### GET /quizzes/{id}
Get quiz details with previous attempts and availability.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 8,
    "title": "Python Basics Quiz",
    "description": "Test your knowledge...",
    "course_id": 3,
    "total_marks": 100,
    "passing_percentage": 70,
    "time_limit_minutes": 30,
    "max_attempts": 3,
    "can_attempt": true,
    "remaining_attempts": 2,
    "is_available": true,
    "attempts": [
      {
        "id": 25,
        "attempt_number": 1,
        "score": 65.5,
        "passed": false,
        "completed_at": "2025-12-08T10:00:00Z"
      }
    ]
  }
}
```

### POST /quizzes/{id}/start
Start a new quiz attempt.

**Response:**
```json
{
  "success": true,
  "message": "Quiz attempt started",
  "data": {
    "attempt_id": 26,
    "quiz_id": 8,
    "attempt_number": 2,
    "started_at": "2025-12-09T17:00:00Z",
    "time_limit_minutes": 30
  }
}
```

### PUT /quiz-attempts/{attemptId}/answer
Submit answer for a question.

**Request Body:**
```json
{
  "question_id": 45,
  "selected_option_ids": [120, 122]
}
```

### POST /quiz-attempts/{attemptId}/submit
Complete and submit quiz for grading.

**Response:**
```json
{
  "success": true,
  "message": "Quiz submitted successfully",
  "data": {
    "attempt_id": 26,
    "score": 85.5,
    "points_earned": 85.5,
    "total_points": 100,
    "time_taken_minutes": 25,
    "passed": true,
    "passing_percentage": 70
  }
}
```

### GET /quiz-attempts/{attemptId}
Get quiz attempt results.

---

## 🔔 Notification Management

### GET /notifications?read=false&limit=20
Get list of notifications.

**Query Parameters:**
- `read` (optional): `true`, `false`
- `limit` (optional): Number of notifications (default: 20)

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 150,
      "type": "assignment_graded",
      "title": "Assignment Graded",
      "message": "Your assignment 'Final Project' has been graded: 95/100",
      "related_id": 42,
      "related_type": "assignment",
      "read_at": null,
      "created_at": "2025-12-09T16:00:00Z"
    }
  ]
}
```

### GET /notifications/unread-count
Get count of unread notifications.

### POST /notifications/{id}/read
Mark single notification as read.

### POST /notifications/read-all
Mark all notifications as read.

### DELETE /notifications/{id}
Delete a notification.

---

## 🛠️ Helper Classes

### ProgressHelper

Utility class for course progress calculations and certificate generation.

**Methods:**
- `calculateCourseProgress($userId, $courseId)` - Returns 0-100 percentage
- `updateEnrollmentProgress($userId, $courseId)` - Updates enrollment and triggers completion
- `completeCourse($userId, $courseId)` - Marks complete and generates certificate
- `generateCertificate($userId, $courseId)` - Creates certificate record
- `getUserStatistics($userId)` - Returns overall learning stats

### ActivityLogger

Utility class for tracking daily learner activity.

**Methods:**
- `logActivity($userId, $type, $durationMinutes)` - Log activity (types: 'lesson', 'quiz', 'assignment', 'time')
- `getActivityLogs($userId, $days)` - Get activity logs for N days
- `getActivitySummary($userId, $days)` - Get aggregated statistics
- `getChartData($userId, $days)` - Get formatted data for charts
- `getStreak($userId)` - Get current/longest streak

---

## 🧪 Testing Examples

### PowerShell Testing

```powershell
# Set your token
$token = "1|YOUR_ACTUAL_TOKEN_HERE"
$headers = @{
    "Authorization" = "Bearer $token"
    "Accept" = "application/json"
}

# Test dashboard
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/learner/dashboard" `
    -Method GET -Headers $headers | ConvertTo-Json -Depth 10

# Test profile
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/learner/profile" `
    -Method GET -Headers $headers

# Test available courses
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/learner/courses/available?category=Programming" `
    -Method GET -Headers $headers

# Enroll in course
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/learner/courses/3/enroll" `
    -Method POST -Headers $headers

# Get enrolled courses
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/learner/courses?status=active" `
    -Method GET -Headers $headers

# Update profile
$body = @{
    name = "John Updated"
    bio = "New bio"
} | ConvertTo-Json

Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/learner/profile" `
    -Method PUT -Headers $headers -Body $body -ContentType "application/json"
```

---

## 🔒 Error Responses

All endpoints return consistent error formats:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**Common HTTP Status Codes:**
- `200` - Success
- `400` - Bad Request (validation errors, business logic violations)
- `401` - Unauthorized (invalid/missing token)
- `403` - Forbidden (not enrolled in course, access denied)
- `404` - Not Found (resource doesn't exist)
- `422` - Unprocessable Entity (validation failed)
- `500` - Server Error

---

## 📋 Implementation Notes

1. **Progress Calculation:** Course progress = (completed mandatory lessons / total mandatory lessons) × 100
2. **Certificate Generation:** Automatically triggered when enrollment reaches 100% progress
3. **Activity Logging:** Automatically logs daily activity on lesson completion, quiz submission, assignment submission
4. **Late Submissions:** Tracked with `is_late` flag based on `due_date` comparison
5. **Quiz Time Limits:** Enforced when submitting quiz; attempts abandoned if time exceeded
6. **File Uploads:** Stored in `storage/app/public/` with configurable size limits

---

## 🚀 Quick Start Guide

1. **Get authentication token** by logging in:
   ```
   POST /api/login
   Body: {"email": "user@example.com", "password": "password"}
   ```

2. **Test dashboard** to verify setup:
   ```
   GET /api/learner/dashboard
   Header: Authorization: Bearer YOUR_TOKEN
   ```

3. **Browse available courses**:
   ```
   GET /api/learner/courses/available
   ```

4. **Enroll in a course**:
   ```
   POST /api/learner/courses/{id}/enroll
   ```

5. **Start learning** by accessing lessons and marking progress!

---

**Documentation Version:** 1.0  
**Last Updated:** December 9, 2025  
**Total Endpoints:** 32
