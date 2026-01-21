# Frontend Implementation Prompt: Complete Test Feature

## Project Overview

You are implementing a complete **Test/Exam Feature** for a Learning Management System (LMS). This feature allows:
- **Instructors** to create, manage, grade tests, and publish results
- **Learners/Students** to view, take, submit tests, and view results

The backend is built with **Laravel 11** and is fully implemented. Your task is to build the React/TypeScript frontend components.

---

## Authentication

All API requests require authentication via Bearer token:

```typescript
// API configuration
const API_BASE_URL = '/api';

const getAuthHeaders = () => ({
  'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
});

// Example fetch wrapper
const apiRequest = async (endpoint: string, options: RequestInit = {}) => {
  const response = await fetch(`${API_BASE_URL}${endpoint}`, {
    ...options,
    headers: {
      ...getAuthHeaders(),
      ...options.headers
    }
  });
  return response.json();
};
```

---

## Data Types / Interfaces

```typescript
// ===== TEST TYPES =====
interface Test {
  id: number;
  module_id: number;
  course_id: number;
  test_title: string;
  test_description: string;
  instructions: string | null;
  start_date: string; // ISO datetime
  end_date: string; // ISO datetime
  time_limit: number | null; // minutes
  max_attempts: number;
  total_marks: number;
  passing_marks: number | null;
  status: 'draft' | 'scheduled' | 'active' | 'closed';
  visibility_status: string;
  results_published: boolean;
  created_at: string;
  updated_at: string;
  questions?: TestQuestion[];
  module?: { id: number; module_title: string };
  course?: { id: number; title: string };
  submissions_count?: number;
  graded_count?: number;
}

interface TestQuestion {
  id: number;
  test_id: number;
  question: string;
  type: 'mcq' | 'short_answer' | 'long_answer' | 'file_upload';
  points: number;
  options: string[] | null; // For MCQ
  correct_answer: string | null; // For MCQ
  allowed_file_types: string[] | null;
  max_file_size: number | null;
  max_characters: number | null;
  order_index: number;
}

// ===== SUBMISSION TYPES =====
interface TestSubmission {
  id: number;
  test_id: number;
  student_id: number;
  submitted_at: string | null;
  submission_status: 'in_progress' | 'submitted' | 'late';
  attempt_number: number;
  started_at: string;
  time_taken: number | null; // minutes
  total_score: number | null;
  grading_status: 'pending' | 'graded' | 'published';
  graded_at: string | null;
  graded_by: number | null;
  instructor_feedback: string | null;
  student?: { id: number; name: string; email: string };
  answers?: TestAnswer[];
  test?: Test;
}

interface TestAnswer {
  id: number;
  submission_id: number;
  question_id: number;
  question_type: 'mcq' | 'short_answer' | 'long_answer' | 'file_upload';
  selected_option: string | null; // For MCQ
  text_answer: string | null;
  file_url: string | null;
  file_name: string | null;
  max_points: number;
  points_awarded: number | null;
  is_correct: boolean | null;
  feedback: string | null;
  question?: TestQuestion;
}

// ===== STATISTICS TYPES =====
interface TestStatistics {
  total_submissions: number;
  submitted_count: number;
  in_progress_count: number;
  graded_count: number;
  pending_grading: number;
  average_score: number | null;
  total_marks: number;
}

interface DetailedStatistics {
  test_id: number;
  total_students_enrolled: number;
  total_submissions: number;
  submitted_count: number;
  pending_count: number;
  late_submissions: number;
  average_score: number;
  highest_score: number;
  lowest_score: number;
  pass_rate: number | null;
}
```

---

## API Endpoints

### Instructor Endpoints (prefix: `/api/instructor`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/courses/{courseId}/tests` | List all tests for a course |
| POST | `/tests` | Create a new test |
| GET | `/tests/{testId}` | Get test details with questions |
| PUT | `/tests/{testId}` | Update a test |
| DELETE | `/tests/{testId}` | Delete a test |
| GET | `/tests/{testId}/submissions` | Get all submissions for a test |
| GET | `/test-submissions/{submissionId}` | Get detailed submission |
| POST | `/test-submissions/{submissionId}/grade` | Grade a submission |
| POST | `/tests/{testId}/publish-results` | Publish/unpublish results |
| GET | `/tests/{testId}/statistics` | Get test statistics |

### Student/Learner Endpoints (prefix: `/api/student` or `/api/learner`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/tests/{testId}` | Get test info for student |
| POST | `/tests/{testId}/start` | Start a test attempt |
| POST | `/test-submissions/{submissionId}/submit` | Submit completed test |
| POST | `/submissions/{submissionId}/submit` | Alternative submit route |
| POST | `/test-submissions/{submissionId}/autosave` | Auto-save answers |
| POST | `/test-submissions/{submissionId}/upload` | Upload file for answer |
| GET | `/tests/{testId}/results` | Get test results |

---

## API Response Structures

### 1. Get Tests for Course (Instructor)
**GET** `/api/instructor/courses/{courseId}/tests`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "module_id": 5,
      "course_id": 3,
      "test_title": "Midterm Exam",
      "test_description": "Covers chapters 1-5",
      "start_date": "2026-01-20T09:00:00.000000Z",
      "end_date": "2026-01-20T11:00:00.000000Z",
      "time_limit": 120,
      "max_attempts": 1,
      "total_marks": 100,
      "passing_marks": 50,
      "status": "active",
      "results_published": false,
      "submissions_count": 15,
      "graded_count": 10,
      "module": { "id": 5, "module_title": "Module 5" },
      "course": { "id": 3, "title": "Introduction to Programming" }
    }
  ]
}
```

### 2. Create Test (Instructor)
**POST** `/api/instructor/tests`

**Request Body:**
```json
{
  "module_id": 5,
  "test_title": "Final Exam",
  "test_description": "Comprehensive final exam",
  "instructions": "Answer all questions. No calculators allowed.",
  "start_date": "2026-02-01T09:00:00Z",
  "end_date": "2026-02-01T12:00:00Z",
  "time_limit": 180,
  "max_attempts": 1,
  "total_marks": 100,
  "passing_marks": 50,
  "questions": [
    {
      "question": "What is 2 + 2?",
      "type": "mcq",
      "points": 5,
      "options": ["3", "4", "5", "6"],
      "correct_answer": "4",
      "order_index": 1
    },
    {
      "question": "Explain the concept of inheritance in OOP.",
      "type": "long_answer",
      "points": 20,
      "max_characters": 2000,
      "order_index": 2
    },
    {
      "question": "Upload your project file",
      "type": "file_upload",
      "points": 25,
      "allowed_file_types": ["pdf", "zip", "docx"],
      "max_file_size": 10240,
      "order_index": 3
    }
  ]
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "test_title": "Final Exam",
    "status": "scheduled",
    "questions": [...]
  }
}
```

### 3. Get Test Submissions (Instructor)
**GET** `/api/instructor/tests/{testId}/submissions`

```json
{
  "success": true,
  "data": {
    "test": {
      "id": 1,
      "test_title": "Midterm Exam",
      "total_marks": 100,
      "passing_marks": 50,
      "questions": [...]
    },
    "submissions": [
      {
        "id": 1,
        "student_id": 5,
        "submission_status": "submitted",
        "attempt_number": 1,
        "started_at": "2026-01-20T09:05:00.000000Z",
        "submitted_at": "2026-01-20T10:45:00.000000Z",
        "time_taken": 100,
        "total_score": 85,
        "grading_status": "graded",
        "student": {
          "id": 5,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "answers": [...]
      }
    ],
    "statistics": {
      "total_submissions": 15,
      "submitted_count": 14,
      "in_progress_count": 1,
      "graded_count": 10,
      "pending_grading": 4,
      "average_score": 72.5,
      "total_marks": 100
    }
  }
}
```

### 4. Grade Submission (Instructor)
**POST** `/api/instructor/test-submissions/{submissionId}/grade`

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "points_awarded": 5,
      "feedback": "Correct!"
    },
    {
      "question_id": 2,
      "points_awarded": 15,
      "feedback": "Good explanation but missing some key points."
    }
  ],
  "instructor_feedback": "Good effort overall. Review chapter 3 for improvement.",
  "publish_results": false
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "total_score": 85,
    "grading_status": "graded",
    "graded_at": "2026-01-21T14:30:00.000000Z"
  }
}
```

### 5. Publish Results (Instructor)
**POST** `/api/instructor/tests/{testId}/publish-results`

**Request Body:**
```json
{
  "publish": true
}
```

### 6. Get Test for Student
**GET** `/api/student/tests/{testId}`

```json
{
  "success": true,
  "data": {
    "test": {
      "id": 1,
      "test_title": "Midterm Exam",
      "test_description": "Covers chapters 1-5",
      "instructions": "Answer all questions carefully",
      "start_date": "2026-01-20T09:00:00.000000Z",
      "end_date": "2026-01-20T11:00:00.000000Z",
      "time_limit": 120,
      "max_attempts": 2,
      "total_marks": 100,
      "questions": [...]
    },
    "can_attempt": true,
    "reason": null,
    "remaining_attempts": 2,
    "current_submission": null
  }
}
```

### 7. Start Test (Student)
**POST** `/api/student/tests/{testId}/start`

```json
{
  "success": true,
  "data": {
    "id": 25,
    "test_id": 1,
    "student_id": 5,
    "attempt_number": 1,
    "started_at": "2026-01-20T09:15:00.000000Z",
    "submission_status": "in_progress",
    "grading_status": "pending"
  }
}
```

### 8. Submit Test (Student)
**POST** `/api/student/test-submissions/{submissionId}/submit`
or **POST** `/api/student/submissions/{submissionId}/submit`

**Request Body:**
```json
{
  "answers": [
    {
      "question_id": 1,
      "question_type": "mcq",
      "selected_option": "4",
      "max_points": 5
    },
    {
      "question_id": 2,
      "question_type": "long_answer",
      "text_answer": "Inheritance is a fundamental OOP concept...",
      "max_points": 20
    },
    {
      "question_id": 3,
      "question_type": "file_upload",
      "file_url": "/storage/test-answers/abc123.pdf",
      "file_name": "my-project.pdf",
      "max_points": 25
    }
  ]
}
```

### 9. Get Results (Student)
**GET** `/api/student/tests/{testId}/results`

**When results NOT published:**
```json
{
  "success": true,
  "data": {
    "test": {
      "id": 1,
      "test_title": "Midterm Exam",
      "total_marks": 100,
      "passing_marks": 50
    },
    "submission": {
      "id": 25,
      "submitted_at": "2026-01-20T10:45:00.000000Z",
      "submission_status": "submitted",
      "grading_status": "graded"
    },
    "results_published": false,
    "message": "Results will be available once your instructor publishes them."
  }
}
```

**When results ARE published:**
```json
{
  "success": true,
  "data": {
    "test": {
      "id": 1,
      "test_title": "Midterm Exam",
      "total_marks": 100,
      "passing_marks": 50
    },
    "submission": {
      "id": 25,
      "submitted_at": "2026-01-20T10:45:00.000000Z",
      "total_score": 85,
      "grading_status": "published"
    },
    "results_published": true,
    "score": 85,
    "percentage": 85.0,
    "passed": true,
    "instructor_feedback": "Great work!",
    "answers": [
      {
        "id": 1,
        "question_id": 1,
        "selected_option": "4",
        "points_awarded": 5,
        "is_correct": true,
        "feedback": "Correct!",
        "question": {
          "question": "What is 2 + 2?",
          "type": "mcq",
          "points": 5,
          "correct_answer": "4"
        }
      }
    ]
  }
}
```

---

## Component Requirements

### 1. Instructor Components

#### 1.1 TestList Component
- Display all tests for a course in a table/card format
- Show: title, status, dates, submissions count, graded count
- Status badges: draft, scheduled, active, closed
- Actions: View, Edit, Delete, View Submissions
- "Create New Test" button

#### 1.2 TestForm Component (Create/Edit)
- Form fields for all test properties
- Dynamic question builder:
  - Add/remove questions
  - Question type selector (MCQ, Short Answer, Long Answer, File Upload)
  - MCQ: option inputs with correct answer selection
  - File upload: allowed types and max size
  - Points per question
  - Drag-and-drop reordering
- Date/time pickers for start/end dates
- Validation before submit

#### 1.3 TestSubmissions Component
- List all submissions for a test
- **CRITICAL: Initialize state with empty arrays to prevent .map() errors**
- Statistics summary at top
- Table with columns: Student Name, Email, Status, Score, Grading Status, Actions
- Filters: All, Pending Grading, Graded
- Actions: View Details, Grade

```typescript
// IMPORTANT: Proper state initialization
const [submissions, setSubmissions] = useState<TestSubmission[]>([]);
const [test, setTest] = useState<Test | null>(null);
const [statistics, setStatistics] = useState<TestStatistics | null>(null);
const [loading, setLoading] = useState(true);

// Fetch data
useEffect(() => {
  const fetchSubmissions = async () => {
    setLoading(true);
    try {
      const response = await apiRequest(`/instructor/tests/${testId}/submissions`);
      if (response.success) {
        setTest(response.data.test);
        setSubmissions(response.data.submissions || []);
        setStatistics(response.data.statistics);
      }
    } catch (error) {
      console.error('Error fetching submissions:', error);
    } finally {
      setLoading(false);
    }
  };
  fetchSubmissions();
}, [testId]);

// Always check loading state before rendering lists
if (loading) return <LoadingSpinner />;

// Safe mapping
{submissions.map(submission => (
  <SubmissionRow key={submission.id} submission={submission} />
))}
```

#### 1.4 GradingForm Component
- Display student's submission with all answers
- For each answer:
  - Show question and student's response
  - Points input (0 to max_points)
  - Feedback textarea
  - For MCQ: show correct answer, auto-graded indicator
- Overall feedback textarea
- "Save as Draft" and "Grade & Publish" buttons
- Total score auto-calculation

#### 1.5 PublishResults Component
- Toggle switch to publish/unpublish results for entire test
- Warning modal before publishing
- Show count of graded vs pending submissions

---

### 2. Student/Learner Components

#### 2.1 TestInfo Component
- Display test details before starting
- Show: title, description, instructions, time limit, attempts remaining
- Start button (disabled if can't attempt)
- Show reason if can't attempt (deadline passed, max attempts reached)

#### 2.2 TestTaking Component
- Timer showing remaining time (if time_limit set)
- Question navigation sidebar
- Question display with appropriate input:
  - MCQ: Radio buttons for options
  - Short Answer: Text input
  - Long Answer: Textarea with character counter
  - File Upload: File input with drag-drop
- Auto-save every 30 seconds
- Submit button with confirmation modal
- Warning when time is almost up

```typescript
// Timer logic
const [timeRemaining, setTimeRemaining] = useState<number | null>(null);

useEffect(() => {
  if (!submission?.started_at || !test?.time_limit) return;
  
  const startTime = new Date(submission.started_at).getTime();
  const endTime = startTime + (test.time_limit * 60 * 1000);
  
  const interval = setInterval(() => {
    const now = Date.now();
    const remaining = Math.max(0, Math.floor((endTime - now) / 1000));
    setTimeRemaining(remaining);
    
    if (remaining === 0) {
      clearInterval(interval);
      handleAutoSubmit();
    }
  }, 1000);
  
  return () => clearInterval(interval);
}, [submission, test]);
```

#### 2.3 TestResults Component
- Check if results are published
- If not published: show "Awaiting results" message
- If published:
  - Score summary (score, percentage, pass/fail)
  - Instructor feedback
  - Question-by-question breakdown:
    - Question text
    - Your answer
    - Correct answer (for MCQ)
    - Points awarded
    - Individual feedback

---

## State Management Patterns

### Avoiding Common Errors

**ERROR: "Cannot read properties of undefined (reading 'map')"**

This occurs when trying to call `.map()` on undefined data. Always:

1. Initialize arrays as empty arrays:
```typescript
const [items, setItems] = useState<Item[]>([]);
```

2. Check loading state before rendering:
```typescript
if (loading) return <LoadingSpinner />;
```

3. Use optional chaining and fallbacks:
```typescript
{(data?.items || []).map(item => ...)}
```

4. Validate API response structure:
```typescript
if (response.success && Array.isArray(response.data.submissions)) {
  setSubmissions(response.data.submissions);
}
```

---

## File Upload Handling

```typescript
const uploadFile = async (submissionId: number, questionId: number, file: File) => {
  const formData = new FormData();
  formData.append('file', file);
  formData.append('question_id', questionId.toString());
  
  const response = await fetch(`/api/student/test-submissions/${submissionId}/upload`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('api_token')}`
      // Don't set Content-Type for FormData
    },
    body: formData
  });
  
  const data = await response.json();
  
  if (data.success) {
    return {
      file_url: data.data.file_url,
      file_name: data.data.file_name
    };
  }
  throw new Error(data.message);
};
```

---

## Auto-Save Implementation

```typescript
const autoSave = useCallback(async () => {
  if (!submissionId || !hasUnsavedChanges) return;
  
  try {
    await apiRequest(`/student/test-submissions/${submissionId}/autosave`, {
      method: 'POST',
      body: JSON.stringify({ answers: currentAnswers })
    });
    setLastSaved(new Date());
    setHasUnsavedChanges(false);
  } catch (error) {
    console.error('Auto-save failed:', error);
  }
}, [submissionId, currentAnswers, hasUnsavedChanges]);

// Auto-save every 30 seconds
useEffect(() => {
  const interval = setInterval(autoSave, 30000);
  return () => clearInterval(interval);
}, [autoSave]);
```

---

## Error Handling

```typescript
interface ApiError {
  success: false;
  message: string;
}

const handleApiError = (error: ApiError | Error) => {
  if ('message' in error) {
    toast.error(error.message);
  }
  
  // Handle specific errors
  if (error.message === 'Unauthorized - not an instructor') {
    router.push('/login');
  }
};
```

---

## Routing Structure

```
/instructor
  /courses/:courseId/tests          - TestList
  /tests/create                     - TestForm (create mode)
  /tests/:testId                    - TestForm (edit mode)
  /tests/:testId/submissions        - TestSubmissions
  /test-submissions/:submissionId   - GradingForm

/student (or /learner)
  /tests/:testId                    - TestInfo
  /tests/:testId/take               - TestTaking
  /tests/:testId/results            - TestResults
```

---

## UI/UX Guidelines

1. **Status Badges**:
   - Draft: Gray
   - Scheduled: Blue
   - Active: Green
   - Closed: Red
   - Grading Pending: Yellow
   - Graded: Blue
   - Published: Green

2. **Progress Indicators**:
   - Show submission progress in TestTaking
   - Show grading progress in TestSubmissions

3. **Confirmations**:
   - Confirm before submitting test
   - Confirm before publishing results
   - Confirm before deleting test

4. **Accessibility**:
   - Keyboard navigation for MCQ options
   - Screen reader support for timer
   - Focus management in modals

---

## Example Component: TestSubmissions

```typescript
import React, { useState, useEffect } from 'react';

interface TestSubmissionsProps {
  testId: number;
}

const TestSubmissions: React.FC<TestSubmissionsProps> = ({ testId }) => {
  // CRITICAL: Initialize with proper default values
  const [test, setTest] = useState<Test | null>(null);
  const [submissions, setSubmissions] = useState<TestSubmission[]>([]);
  const [statistics, setStatistics] = useState<TestStatistics | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [filter, setFilter] = useState<'all' | 'pending' | 'graded'>('all');

  useEffect(() => {
    const fetchData = async () => {
      setLoading(true);
      setError(null);
      
      try {
        const response = await fetch(`/api/instructor/tests/${testId}/submissions`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
            'Accept': 'application/json'
          }
        });
        
        const data = await response.json();
        
        if (data.success) {
          setTest(data.data.test);
          setSubmissions(data.data.submissions || []);
          setStatistics(data.data.statistics);
        } else {
          setError(data.message || 'Failed to load submissions');
        }
      } catch (err) {
        setError('Network error. Please try again.');
        console.error('Fetch error:', err);
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, [testId]);

  // Filter submissions
  const filteredSubmissions = submissions.filter(s => {
    if (filter === 'pending') return s.grading_status === 'pending';
    if (filter === 'graded') return ['graded', 'published'].includes(s.grading_status);
    return true;
  });

  // Loading state
  if (loading) {
    return (
      <div className="flex justify-center items-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" />
      </div>
    );
  }

  // Error state
  if (error) {
    return (
      <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {error}
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold">{test?.test_title} - Submissions</h1>
        <button 
          onClick={() => handlePublishResults(!test?.results_published)}
          className="btn btn-primary"
        >
          {test?.results_published ? 'Unpublish Results' : 'Publish All Results'}
        </button>
      </div>

      {/* Statistics */}
      {statistics && (
        <div className="grid grid-cols-4 gap-4">
          <StatCard label="Total Submissions" value={statistics.total_submissions} />
          <StatCard label="Pending Grading" value={statistics.pending_grading} />
          <StatCard label="Graded" value={statistics.graded_count} />
          <StatCard label="Average Score" value={`${statistics.average_score || 0}/${statistics.total_marks}`} />
        </div>
      )}

      {/* Filter Tabs */}
      <div className="flex space-x-4 border-b">
        {['all', 'pending', 'graded'].map(f => (
          <button
            key={f}
            onClick={() => setFilter(f as any)}
            className={`px-4 py-2 ${filter === f ? 'border-b-2 border-blue-500 font-semibold' : ''}`}
          >
            {f.charAt(0).toUpperCase() + f.slice(1)}
          </button>
        ))}
      </div>

      {/* Submissions Table */}
      <table className="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th>Student</th>
            <th>Status</th>
            <th>Submitted At</th>
            <th>Score</th>
            <th>Grading Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {filteredSubmissions.length === 0 ? (
            <tr>
              <td colSpan={6} className="text-center py-8 text-gray-500">
                No submissions found
              </td>
            </tr>
          ) : (
            filteredSubmissions.map(submission => (
              <tr key={submission.id}>
                <td>
                  <div>{submission.student?.name}</div>
                  <div className="text-sm text-gray-500">{submission.student?.email}</div>
                </td>
                <td>
                  <StatusBadge status={submission.submission_status} />
                </td>
                <td>
                  {submission.submitted_at 
                    ? new Date(submission.submitted_at).toLocaleString() 
                    : 'In Progress'}
                </td>
                <td>
                  {submission.total_score !== null 
                    ? `${submission.total_score}/${test?.total_marks}` 
                    : '-'}
                </td>
                <td>
                  <GradingBadge status={submission.grading_status} />
                </td>
                <td>
                  <button 
                    onClick={() => navigateToGrading(submission.id)}
                    className="text-blue-600 hover:underline"
                  >
                    {submission.grading_status === 'pending' ? 'Grade' : 'View/Edit'}
                  </button>
                </td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
};

export default TestSubmissions;
```

---

## Checklist for Implementation

### Instructor Features
- [ ] List tests for a course
- [ ] Create test with questions
- [ ] Edit existing test
- [ ] Delete test
- [ ] View all submissions
- [ ] Grade individual submission
- [ ] Publish/unpublish results
- [ ] View statistics

### Student Features
- [ ] View test details
- [ ] Start test (create submission)
- [ ] Answer questions (all types)
- [ ] Auto-save progress
- [ ] Upload files
- [ ] Submit test
- [ ] View results (when published)

### Error Handling
- [ ] Loading states
- [ ] Empty states
- [ ] API error messages
- [ ] Network error recovery
- [ ] Session timeout handling

### Testing
- [ ] Test creation flow
- [ ] Test taking flow
- [ ] Grading flow
- [ ] Edge cases (expired test, max attempts)

---

## Notes

1. The backend auto-grades MCQ questions on submission
2. Results are only visible to students when `results_published` is true OR `grading_status` is 'published'
3. File uploads have a 10MB limit
4. Tests with only MCQ questions are auto-graded completely
5. The `time_limit` is in minutes; null means no time limit
6. Both `/api/student/` and `/api/learner/` prefixes work for student endpoints
