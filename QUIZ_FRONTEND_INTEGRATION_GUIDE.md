# Frontend Integration Guide - Quiz Feature

## Overview
The quiz backend API is fully functional. This guide will help you integrate quiz functionality into the learner dashboard.

## Authentication
All quiz endpoints require Bearer token authentication:

```javascript
headers: {
  'Authorization': 'Bearer YOUR_TOKEN_HERE',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

**Important:** Users must be enrolled in a course to access its quizzes (403 error if not enrolled).

---

## API Endpoints

### 1. List All Quizzes
**GET** `/api/learner/quizzes`

**Query Parameters:**
- `course_id` (optional) - Filter by course ID
- `status` (optional) - Filter by status: `available` or `completed`

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "title": "HTML Basics",
      "description": "Check the basic knowledge of HTML",
      "course_id": 1,
      "course_title": "CDEF",
      "total_marks": 100,
      "passing_percentage": 70.00,
      "time_limit_minutes": 5,
      "max_attempts": 1,
      "attempts_count": 0,
      "completed_attempts": 0,
      "best_score": null,
      "last_attempt": null
    }
  ]
}
```

**Frontend Implementation:**
```javascript
// Example: Fetch all quizzes for learner dashboard
async function fetchQuizzes(courseId = null, status = null) {
  const params = new URLSearchParams();
  if (courseId) params.append('course_id', courseId);
  if (status) params.append('status', status);
  
  const response = await fetch(
    `${API_BASE_URL}/learner/quizzes?${params.toString()}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  
  if (!response.ok) {
    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
  }
  
  const data = await response.json();
  return data.data; // Array of quizzes
}
```

---

### 2. Get Quiz Details
**GET** `/api/learner/quizzes/{id}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "HTML Basics",
    "description": "Check the basic knowledge of HTML",
    "course_id": 1,
    "course_title": "CDEF",
    "total_marks": 100,
    "passing_percentage": "70.00",
    "time_limit_minutes": 5,
    "max_attempts": 1,
    "show_correct_answers": true,
    "randomize_questions": false,
    "available_from": null,
    "available_until": null,
    "can_attempt": true,
    "remaining_attempts": 1,
    "is_available": true,
    "attempts": []
  }
}
```

**Frontend Implementation:**
```javascript
// Example: Get quiz details before starting
async function getQuizDetails(quizId) {
  const response = await fetch(
    `${API_BASE_URL}/learner/quizzes/${quizId}`,
    {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    }
  );
  
  if (!response.ok) {
    if (response.status === 403) {
      throw new Error('You must enroll in this course first');
    }
    throw new Error(`Failed to load quiz details`);
  }
  
  const data = await response.json();
  return data.data;
}
```

---

### 3. Start Quiz Attempt
**POST** `/api/learner/quizzes/{id}/start`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "attempt_id": 1,
    "quiz_id": 2,
    "attempt_number": 1,
    "started_at": "2025-12-12T10:30:00.000000Z",
    "expires_at": "2025-12-12T10:35:00.000000Z",
    "questions": [
      {
        "id": 1,
        "question_text": "What does HTML stand for?",
        "question_type": "multiple_choice",
        "points": 10,
        "options": [
          {"id": 1, "option_text": "Hyper Text Markup Language"},
          {"id": 2, "option_text": "Home Tool Markup Language"},
          {"id": 3, "option_text": "Hyperlinks and Text Markup Language"}
        ]
      }
    ],
    "time_limit_minutes": 5
  }
}
```

**Frontend Implementation:**
```javascript
// Example: Start a new quiz attempt
async function startQuiz(quizId) {
  const response = await fetch(
    `${API_BASE_URL}/learner/quizzes/${quizId}/start`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    }
  );
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to start quiz');
  }
  
  const data = await response.json();
  return data.data;
}
```

---

### 4. Submit Answer
**PUT** `/api/learner/quiz-attempts/{attemptId}/answer`

**Request Body:**
```json
{
  "question_id": 1,
  "answer": "option_1" // or array for multiple answers
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Answer recorded successfully"
}
```

**Frontend Implementation:**
```javascript
// Example: Submit answer to a question
async function submitAnswer(attemptId, questionId, answer) {
  const response = await fetch(
    `${API_BASE_URL}/learner/quiz-attempts/${attemptId}/answer`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        question_id: questionId,
        answer: answer
      })
    }
  );
  
  if (!response.ok) {
    throw new Error('Failed to submit answer');
  }
  
  return await response.json();
}
```

---

### 5. Submit Quiz (Final Submission)
**POST** `/api/learner/quiz-attempts/{attemptId}/submit`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "attempt_id": 1,
    "score": 85.5,
    "points_earned": 85,
    "total_points": 100,
    "passed": true,
    "time_taken_minutes": 4,
    "completed_at": "2025-12-12T10:34:00.000000Z",
    "feedback": "Great job! You passed the quiz.",
    "answers": [
      {
        "question_id": 1,
        "user_answer": "option_1",
        "correct_answer": "option_1",
        "is_correct": true,
        "points_earned": 10,
        "points_possible": 10
      }
    ]
  }
}
```

**Frontend Implementation:**
```javascript
// Example: Submit the entire quiz
async function submitQuiz(attemptId) {
  const response = await fetch(
    `${API_BASE_URL}/learner/quiz-attempts/${attemptId}/submit`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    }
  );
  
  if (!response.ok) {
    throw new Error('Failed to submit quiz');
  }
  
  const data = await response.json();
  return data.data;
}
```

---

### 6. Get Attempt Details
**GET** `/api/learner/quiz-attempts/{attemptId}`

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "quiz_id": 2,
    "quiz_title": "HTML Basics",
    "attempt_number": 1,
    "status": "completed",
    "started_at": "2025-12-12T10:30:00.000000Z",
    "completed_at": "2025-12-12T10:34:00.000000Z",
    "score": 85.5,
    "passed": true,
    "answers": [...]
  }
}
```

---

## Error Handling

### HTTP Status Codes
- **200** - Success
- **401** - Unauthorized (invalid/missing token)
- **403** - Forbidden (not enrolled in course, max attempts reached, quiz not available)
- **404** - Not found (quiz/attempt doesn't exist)
- **422** - Validation error (invalid data)
- **500** - Server error

### Error Response Format
```json
{
  "success": false,
  "message": "Error description here"
}
```

### Frontend Error Handling Example
```javascript
async function handleQuizAction(action) {
  try {
    const result = await action();
    return { success: true, data: result };
  } catch (error) {
    if (error.response) {
      // HTTP error
      const status = error.response.status;
      const data = await error.response.json();
      
      switch (status) {
        case 401:
          // Redirect to login
          window.location.href = '/login';
          break;
        case 403:
          // Show enrollment prompt or max attempts message
          showError(data.message);
          break;
        case 404:
          showError('Quiz not found');
          break;
        default:
          showError(data.message || 'An error occurred');
      }
    } else {
      // Network error
      showError('Network error. Please check your connection.');
    }
    return { success: false, error: error.message };
  }
}
```

---

## UI/UX Recommendations

### Quiz List Page
1. **Display quiz cards** with:
   - Title and description
   - Course name
   - Time limit badge
   - Attempt count (e.g., "0/3 attempts")
   - Status badge (Available, Completed, Passed, Failed)
   - "Start Quiz" or "Retake" button

2. **Filter options:**
   - By course
   - By status (available/completed)

3. **Empty state:**
   - Show message if no quizzes available
   - Suggest enrolling in courses

### Quiz Details/Start Page
1. **Show before starting:**
   - Quiz title and description
   - Course name
   - Total marks/points
   - Passing percentage
   - Time limit
   - Remaining attempts
   - Previous attempt scores

2. **Display warnings:**
   - "You have X attempts remaining"
   - "Time limit: Y minutes"
   - "You must score at least Z% to pass"

3. **Start button:**
   - Disabled if no attempts remaining
   - Disabled if quiz not available yet
   - Show "Start Quiz" or "Retake Quiz"

### Quiz Taking Interface
1. **Header:**
   - Quiz title
   - Timer (countdown)
   - Question counter (e.g., "Question 3 of 10")
   - Progress bar

2. **Question display:**
   - Question number and text
   - Points value
   - Answer options (radio/checkbox/textarea)
   - "Previous" and "Next" buttons
   - "Submit Quiz" button on last question

3. **Timer:**
   - Show remaining time prominently
   - Warning when 5 minutes remaining
   - Auto-submit when time expires

4. **Auto-save:**
   - Save answer immediately when selected
   - Show "Saving..." indicator

### Results Page
1. **Score display:**
   - Large score number
   - Pass/Fail badge
   - Percentage
   - Time taken

2. **Question review:**
   - Show each question
   - User's answer
   - Correct answer (if enabled)
   - Points earned vs possible

3. **Actions:**
   - "Retake Quiz" button (if attempts remaining)
   - "Back to Quizzes" button
   - "Review Answers" button

---

## State Management Example (React)

```javascript
import { useState, useEffect } from 'react';

function QuizPage() {
  const [quizzes, setQuizzes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  
  useEffect(() => {
    loadQuizzes();
  }, []);
  
  async function loadQuizzes() {
    setLoading(true);
    setError(null);
    
    try {
      const data = await fetchQuizzes();
      setQuizzes(data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  }
  
  if (loading) return <div>Loading quizzes...</div>;
  if (error) return <div>Error: {error}</div>;
  if (quizzes.length === 0) return <div>No quizzes available</div>;
  
  return (
    <div>
      {quizzes.map(quiz => (
        <QuizCard key={quiz.id} quiz={quiz} />
      ))}
    </div>
  );
}
```

---

## Testing Checklist

- [ ] Quiz list loads and displays correctly
- [ ] Filter by course works
- [ ] Filter by status works
- [ ] Quiz details page loads
- [ ] Can start a quiz
- [ ] Timer counts down correctly
- [ ] Answers are saved automatically
- [ ] Can navigate between questions
- [ ] Submit button works
- [ ] Results page displays correctly
- [ ] Can retake quiz (if attempts remaining)
- [ ] Max attempts limitation works
- [ ] Time expiration auto-submits
- [ ] Error messages display correctly
- [ ] 403 error redirects to enrollment
- [ ] 401 error redirects to login

---

## Common Issues & Solutions

### Issue: "Pending" status never changes
**Solution:** Check if the API request is actually being made. Use browser DevTools Network tab to verify.

### Issue: 401 Unauthorized
**Solution:** 
1. Verify token is being sent correctly
2. Token format: `Bearer TOKEN_STRING`
3. Token must be hashed in database (use `generate_tokens.php`)

### Issue: 403 Forbidden
**Solution:** User must be enrolled in the course. Check enrollment status first.

### Issue: Empty quiz list
**Solution:**
1. Verify user is enrolled in courses with quizzes
2. Check if quizzes exist in those courses
3. Verify API endpoint is correct

### Issue: Timer not working
**Solution:** 
1. Parse `expires_at` timestamp from start response
2. Calculate remaining time: `expiresAt - currentTime`
3. Auto-submit when time reaches 0

---

## Backend Status Summary

✅ **Working Endpoints:**
- GET `/api/learner/quizzes` - List quizzes
- GET `/api/learner/quizzes/{id}` - Quiz details
- POST `/api/learner/quizzes/{id}/start` - Start quiz
- PUT `/api/learner/quiz-attempts/{attemptId}/answer` - Submit answer
- POST `/api/learner/quiz-attempts/{attemptId}/submit` - Submit quiz
- GET `/api/learner/quiz-attempts/{attemptId}` - Get attempt details

✅ **Authentication:** Bearer token with SHA256 hashing
✅ **Authorization:** Enrollment check working
✅ **Data Models:** All relationships properly set up
✅ **Error Handling:** Proper HTTP status codes and messages

---

## Next Steps for Frontend

1. **Replace "pending" placeholder** with actual API calls
2. **Implement error handling** for 401, 403, 404 responses
3. **Add loading states** while fetching data
4. **Build quiz taking interface** with timer
5. **Test with real user authentication** from your login flow
6. **Handle edge cases** (no attempts left, expired quiz, etc.)

---

## Support

If you encounter issues:
1. Check browser console for JavaScript errors
2. Check Network tab for API responses
3. Verify token is correct and not expired
4. Ensure user is enrolled in the course
5. Check Laravel logs: `storage/logs/laravel.log`

The backend API is fully functional and ready for integration! 🚀
