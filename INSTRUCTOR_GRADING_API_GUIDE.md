# Instructor Assignment Grading API Guide

## Overview
This guide provides complete documentation for the Instructor Assignment Grading System. Instructors can view student submissions, grade assignments with marks (0-100), assign letter grades (A-F), provide feedback, and mark submissions as graded.

## Table of Contents
1. [API Endpoints](#api-endpoints)
2. [Database Schema](#database-schema)
3. [Request/Response Examples](#request-response-examples)
4. [Frontend Integration](#frontend-integration)
5. [Testing](#testing)
6. [Grading System](#grading-system)

---

## API Endpoints

### Base URL
```
http://localhost/learning-lms/public/api/instructor
```

### Authentication
All endpoints require Bearer token authentication:
```
Authorization: Bearer <your_api_token>
```

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/assignments/{assignmentId}/submissions` | Get all submissions for an assignment |
| GET | `/submissions/{submissionId}` | Get detailed submission information |
| PUT | `/submissions/{submissionId}/grade` | Grade a submission |
| GET | `/submissions/{submissionId}/download` | Download submission file |
| GET | `/modules/{moduleId}/submissions` | Get submission statistics for all assignments in a module |

---

## Database Schema

### Assignment Submissions Table
```sql
assignment_submissions:
- id (primary key)
- assignment_id (foreign key to module_assignments)
- user_id (foreign key to users)
- submission_text (text)
- file_path (string)
- file_name (string)
- file_size_kb (integer)
- submitted_at (datetime)
- status (enum: submitted, graded, returned, resubmitted)
- marks_obtained (decimal 8,2)
- feedback (text)
- graded_by (foreign key to users)
- graded_at (datetime)
- is_late (boolean)
- created_at (timestamp)
- updated_at (timestamp)
```

### Module Assignments Table
```sql
module_assignments:
- id (primary key)
- module_id (foreign key to course_modules)
- assignment_title (string)
- instructions (text)
- attachment_url (string)
- max_points (integer, default 100)
- due_date (datetime)
- created_at (timestamp)
- updated_at (timestamp)
```

---

## Request/Response Examples

### 1. Get All Submissions for an Assignment

**Endpoint:** `GET /instructor/assignments/{assignmentId}/submissions`

**Request:**
```http
GET /api/instructor/assignments/1/submissions
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (200 OK):**
```json
{
  "success": true,
  "assignment": {
    "id": 1,
    "title": "Introduction to Laravel Assignment",
    "instructions": "Create a basic Laravel application...",
    "max_points": 100,
    "due_date": "2024-01-15 23:59:59",
    "module_title": "Getting Started with Laravel",
    "course_title": "Laravel Development Course"
  },
  "statistics": {
    "total_submissions": 12,
    "graded": 7,
    "pending": 5,
    "average_marks": 78.5
  },
  "submissions": [
    {
      "id": 1,
      "assignment_id": 1,
      "student_id": 5,
      "student_name": "John Doe",
      "student_username": "johndoe",
      "student_email": "john@example.com",
      "student_avatar": "avatars/john.jpg",
      "submission_text": "I have completed the assignment...",
      "file_path": "submissions/assignment_1_user_5.pdf",
      "file_name": "laravel_project.pdf",
      "file_size_kb": 2048,
      "submitted_at": "2024-01-14 18:30:00",
      "status": "submitted",
      "marks_obtained": null,
      "max_points": 100,
      "grade": null,
      "feedback": null,
      "graded_by": null,
      "graded_at": null,
      "is_late": false,
      "created_at": "2024-01-14 18:30:00",
      "updated_at": "2024-01-14 18:30:00"
    },
    {
      "id": 2,
      "assignment_id": 1,
      "student_id": 8,
      "student_name": "Jane Smith",
      "student_username": "janesmith",
      "student_email": "jane@example.com",
      "student_avatar": "avatars/jane.jpg",
      "submission_text": "Completed all requirements",
      "file_path": "submissions/assignment_1_user_8.pdf",
      "file_name": "my_laravel_app.pdf",
      "file_size_kb": 1536,
      "submitted_at": "2024-01-15 10:15:00",
      "status": "graded",
      "marks_obtained": 85.00,
      "max_points": 100,
      "grade": "B",
      "feedback": "Great work! Well-structured code and good documentation.",
      "graded_by": 2,
      "graded_at": "2024-01-16 14:20:00",
      "is_late": false,
      "created_at": "2024-01-15 10:15:00",
      "updated_at": "2024-01-16 14:20:00"
    }
  ]
}
```

---

### 2. Get Single Submission Details

**Endpoint:** `GET /instructor/submissions/{submissionId}`

**Request:**
```http
GET /api/instructor/submissions/1
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (200 OK):**
```json
{
  "success": true,
  "submission": {
    "id": 1,
    "assignment_id": 1,
    "assignment_title": "Introduction to Laravel Assignment",
    "assignment_instructions": "Create a basic Laravel application...",
    "max_points": 100,
    "due_date": "2024-01-15 23:59:59",
    "student": {
      "id": 5,
      "name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "avatar": "avatars/john.jpg",
      "phone": "+1234567890"
    },
    "submission_text": "I have completed the assignment with all required features...",
    "file_path": "submissions/assignment_1_user_5.pdf",
    "file_name": "laravel_project.pdf",
    "file_size_kb": 2048,
    "submitted_at": "2024-01-14 18:30:00",
    "status": "submitted",
    "marks_obtained": null,
    "percentage": null,
    "grade": null,
    "feedback": null,
    "graded_by": null,
    "graded_at": null,
    "is_late": false,
    "created_at": "2024-01-14 18:30:00",
    "updated_at": "2024-01-14 18:30:00"
  }
}
```

---

### 3. Grade a Submission

**Endpoint:** `PUT /instructor/submissions/{submissionId}/grade`

**Request:**
```http
PUT /api/instructor/submissions/1/grade
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
Content-Type: application/json

{
  "marks_obtained": 85,
  "feedback": "Excellent work! Your code is well-structured and follows Laravel best practices. The documentation is clear and comprehensive.",
  "grade": "B"
}
```

**Request Body Parameters:**
- `marks_obtained` (required, number): Score between 0 and max_points
- `feedback` (optional, string, max 2000 chars): Instructor feedback
- `grade` (optional, string): Letter grade (A, A-, B+, B, B-, C+, C, C-, D+, D, F)
  - If not provided, grade will be auto-calculated based on percentage

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Submission graded successfully",
  "submission": {
    "id": 1,
    "student_name": "John Doe",
    "student_email": "john@example.com",
    "assignment_title": "Introduction to Laravel Assignment",
    "marks_obtained": 85.00,
    "max_points": 100,
    "percentage": 85.00,
    "grade": "B",
    "feedback": "Excellent work! Your code is well-structured...",
    "status": "graded",
    "graded_at": "2024-01-17 10:30:00"
  }
}
```

**Validation Error Response (422):**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "marks_obtained": [
      "The marks obtained must be between 0 and 100."
    ]
  }
}
```

---

### 4. Download Submission File

**Endpoint:** `GET /instructor/submissions/{submissionId}/download`

**Request:**
```http
GET /api/instructor/submissions/1/download
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response:**
- **Success (200):** Binary file download with proper headers
  ```
  Content-Type: application/pdf (or appropriate MIME type)
  Content-Disposition: attachment; filename="laravel_project.pdf"
  ```
- **Error (404):** Submission or file not found

---

### 5. Get Module Submissions Overview

**Endpoint:** `GET /instructor/modules/{moduleId}/submissions`

**Request:**
```http
GET /api/instructor/modules/1/submissions
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

**Response (200 OK):**
```json
{
  "success": true,
  "module_id": 1,
  "assignments": [
    {
      "assignment_id": 1,
      "assignment_title": "Introduction to Laravel Assignment",
      "max_points": 100,
      "due_date": "2024-01-15 23:59:59",
      "total_submissions": 12,
      "graded_submissions": 7,
      "pending_submissions": 5,
      "average_marks": 78.5
    },
    {
      "assignment_id": 2,
      "assignment_title": "Database Design Assignment",
      "max_points": 100,
      "due_date": "2024-01-22 23:59:59",
      "total_submissions": 10,
      "graded_submissions": 10,
      "pending_submissions": 0,
      "average_marks": 82.3
    }
  ]
}
```

---

## Grading System

### Letter Grade Calculation

The system automatically calculates letter grades based on percentage:

| Percentage Range | Letter Grade |
|-----------------|--------------|
| 93% - 100% | A |
| 90% - 92.99% | A- |
| 87% - 89.99% | B+ |
| 83% - 86.99% | B |
| 80% - 82.99% | B- |
| 77% - 79.99% | C+ |
| 73% - 76.99% | C |
| 70% - 72.99% | C- |
| 67% - 69.99% | D+ |
| 60% - 66.99% | D |
| Below 60% | F |

**Note:** Instructors can override the auto-calculated grade by providing a custom `grade` parameter.

### Submission Status Flow

```
submitted → graded → returned → resubmitted → graded
```

- **submitted**: Initial state when student submits
- **graded**: Instructor has graded and provided feedback
- **returned**: Assignment returned for revisions (future feature)
- **resubmitted**: Student resubmitted after revisions (future feature)

---

## Frontend Integration

### React + TypeScript Example

```typescript
// types.ts
export interface Assignment {
  id: number;
  title: string;
  instructions: string;
  max_points: number;
  due_date: string;
  module_title: string;
  course_title: string;
}

export interface Submission {
  id: number;
  assignment_id: number;
  student_id: number;
  student_name: string;
  student_username: string;
  student_email: string;
  student_avatar: string | null;
  submission_text: string;
  file_path: string | null;
  file_name: string | null;
  submitted_at: string;
  status: 'submitted' | 'graded' | 'returned' | 'resubmitted';
  marks_obtained: number | null;
  max_points: number;
  grade: string | null;
  feedback: string | null;
  graded_at: string | null;
  is_late: boolean;
}

export interface SubmissionStats {
  total_submissions: number;
  graded: number;
  pending: number;
  average_marks: number;
}

export interface GradeRequest {
  marks_obtained: number;
  feedback?: string;
  grade?: string;
}

// api/submissions.ts
import axios from 'axios';

const API_BASE_URL = 'http://localhost/learning-lms/public/api/instructor';

const getAuthHeaders = () => ({
  headers: {
    'Authorization': `Bearer ${localStorage.getItem('api_token')}`,
    'Content-Type': 'application/json',
  },
});

// Get all submissions for an assignment
export const getAssignmentSubmissions = async (assignmentId: number) => {
  const response = await axios.get(
    `${API_BASE_URL}/assignments/${assignmentId}/submissions`,
    getAuthHeaders()
  );
  return response.data;
};

// Get single submission details
export const getSubmissionDetails = async (submissionId: number) => {
  const response = await axios.get(
    `${API_BASE_URL}/submissions/${submissionId}`,
    getAuthHeaders()
  );
  return response.data;
};

// Grade a submission
export const gradeSubmission = async (
  submissionId: number,
  gradeData: GradeRequest
) => {
  const response = await axios.put(
    `${API_BASE_URL}/submissions/${submissionId}/grade`,
    gradeData,
    getAuthHeaders()
  );
  return response.data;
};

// Download submission file
export const downloadSubmissionFile = async (submissionId: number) => {
  const response = await axios.get(
    `${API_BASE_URL}/submissions/${submissionId}/download`,
    {
      ...getAuthHeaders(),
      responseType: 'blob',
    }
  );
  
  // Create download link
  const url = window.URL.createObjectURL(new Blob([response.data]));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', 'submission_file');
  document.body.appendChild(link);
  link.click();
  link.remove();
};

// Get module submission overview
export const getModuleSubmissions = async (moduleId: number) => {
  const response = await axios.get(
    `${API_BASE_URL}/modules/${moduleId}/submissions`,
    getAuthHeaders()
  );
  return response.data;
};

// components/SubmissionList.tsx
import React, { useState, useEffect } from 'react';
import { getAssignmentSubmissions } from '../api/submissions';
import { Submission, SubmissionStats, Assignment } from '../types';

interface Props {
  assignmentId: number;
}

const SubmissionList: React.FC<Props> = ({ assignmentId }) => {
  const [loading, setLoading] = useState(true);
  const [assignment, setAssignment] = useState<Assignment | null>(null);
  const [submissions, setSubmissions] = useState<Submission[]>([]);
  const [stats, setStats] = useState<SubmissionStats | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchSubmissions();
  }, [assignmentId]);

  const fetchSubmissions = async () => {
    try {
      setLoading(true);
      const data = await getAssignmentSubmissions(assignmentId);
      
      if (data.success) {
        setAssignment(data.assignment);
        setSubmissions(data.submissions);
        setStats(data.statistics);
      } else {
        setError(data.message);
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to load submissions');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading submissions...</div>;
  if (error) return <div className="error">{error}</div>;

  return (
    <div className="submission-list">
      <div className="assignment-header">
        <h2>{assignment?.title}</h2>
        <p>Max Points: {assignment?.max_points}</p>
        <p>Due Date: {new Date(assignment?.due_date || '').toLocaleString()}</p>
      </div>

      <div className="statistics">
        <div className="stat-card">
          <h3>Total Submissions</h3>
          <p>{stats?.total_submissions}</p>
        </div>
        <div className="stat-card">
          <h3>Graded</h3>
          <p>{stats?.graded}</p>
        </div>
        <div className="stat-card">
          <h3>Pending</h3>
          <p>{stats?.pending}</p>
        </div>
        <div className="stat-card">
          <h3>Average Score</h3>
          <p>{stats?.average_marks?.toFixed(2) || 'N/A'}</p>
        </div>
      </div>

      <table className="submissions-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Submitted</th>
            <th>Status</th>
            <th>Score</th>
            <th>Grade</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {submissions.map((submission) => (
            <tr key={submission.id}>
              <td>
                <div className="student-info">
                  {submission.student_avatar && (
                    <img src={submission.student_avatar} alt={submission.student_name} />
                  )}
                  <div>
                    <div>{submission.student_name}</div>
                    <div className="email">{submission.student_email}</div>
                  </div>
                </div>
              </td>
              <td>{new Date(submission.submitted_at).toLocaleString()}</td>
              <td>
                <span className={`status-badge ${submission.status}`}>
                  {submission.status}
                </span>
                {submission.is_late && <span className="late-badge">Late</span>}
              </td>
              <td>
                {submission.marks_obtained !== null 
                  ? `${submission.marks_obtained}/${submission.max_points}`
                  : '-'}
              </td>
              <td>{submission.grade || '-'}</td>
              <td>
                <button onClick={() => viewSubmission(submission.id)}>
                  View
                </button>
                {submission.status === 'submitted' && (
                  <button onClick={() => gradeSubmission(submission.id)}>
                    Grade
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

// components/GradeForm.tsx
import React, { useState } from 'react';
import { gradeSubmission } from '../api/submissions';
import { GradeRequest } from '../types';

interface Props {
  submissionId: number;
  maxPoints: number;
  onSuccess: () => void;
}

const GradeForm: React.FC<Props> = ({ submissionId, maxPoints, onSuccess }) => {
  const [marks, setMarks] = useState<number>(0);
  const [feedback, setFeedback] = useState<string>('');
  const [grade, setGrade] = useState<string>('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      setLoading(true);
      setError(null);

      const gradeData: GradeRequest = {
        marks_obtained: marks,
        feedback: feedback || undefined,
        grade: grade || undefined,
      };

      const response = await gradeSubmission(submissionId, gradeData);
      
      if (response.success) {
        alert('Submission graded successfully!');
        onSuccess();
      } else {
        setError(response.message);
      }
    } catch (err: any) {
      setError(err.response?.data?.message || 'Failed to grade submission');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="grade-form">
      <h3>Grade Submission</h3>
      
      {error && <div className="error-message">{error}</div>}
      
      <div className="form-group">
        <label htmlFor="marks">Marks Obtained *</label>
        <input
          type="number"
          id="marks"
          min="0"
          max={maxPoints}
          step="0.01"
          value={marks}
          onChange={(e) => setMarks(parseFloat(e.target.value))}
          required
        />
        <small>Maximum: {maxPoints} points</small>
      </div>

      <div className="form-group">
        <label htmlFor="grade">Letter Grade (Optional)</label>
        <select
          id="grade"
          value={grade}
          onChange={(e) => setGrade(e.target.value)}
        >
          <option value="">Auto-calculate</option>
          <option value="A">A (93-100%)</option>
          <option value="A-">A- (90-92%)</option>
          <option value="B+">B+ (87-89%)</option>
          <option value="B">B (83-86%)</option>
          <option value="B-">B- (80-82%)</option>
          <option value="C+">C+ (77-79%)</option>
          <option value="C">C (73-76%)</option>
          <option value="C-">C- (70-72%)</option>
          <option value="D+">D+ (67-69%)</option>
          <option value="D">D (60-66%)</option>
          <option value="F">F (Below 60%)</option>
        </select>
      </div>

      <div className="form-group">
        <label htmlFor="feedback">Feedback (Optional)</label>
        <textarea
          id="feedback"
          rows={6}
          maxLength={2000}
          value={feedback}
          onChange={(e) => setFeedback(e.target.value)}
          placeholder="Provide feedback to the student..."
        />
        <small>{feedback.length}/2000 characters</small>
      </div>

      <button type="submit" disabled={loading}>
        {loading ? 'Submitting...' : 'Submit Grade'}
      </button>
    </form>
  );
};

export default GradeForm;
```

---

### Vanilla JavaScript Example

```javascript
// api.js
const API_BASE_URL = 'http://localhost/learning-lms/public/api/instructor';

function getAuthToken() {
  return localStorage.getItem('api_token');
}

// Get assignment submissions
async function getAssignmentSubmissions(assignmentId) {
  const response = await fetch(
    `${API_BASE_URL}/assignments/${assignmentId}/submissions`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json',
      },
    }
  );
  
  return await response.json();
}

// Get submission details
async function getSubmissionDetails(submissionId) {
  const response = await fetch(
    `${API_BASE_URL}/submissions/${submissionId}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json',
      },
    }
  );
  
  return await response.json();
}

// Grade submission
async function gradeSubmission(submissionId, gradeData) {
  const response = await fetch(
    `${API_BASE_URL}/submissions/${submissionId}/grade`,
    {
      method: 'PUT',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(gradeData),
    }
  );
  
  return await response.json();
}

// Download submission file
async function downloadSubmissionFile(submissionId) {
  const response = await fetch(
    `${API_BASE_URL}/submissions/${submissionId}/download`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${getAuthToken()}`,
      },
    }
  );
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = 'submission_file';
  document.body.appendChild(link);
  link.click();
  link.remove();
}

// Usage Example
document.getElementById('gradeForm').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const submissionId = document.getElementById('submissionId').value;
  const marks = parseFloat(document.getElementById('marks').value);
  const feedback = document.getElementById('feedback').value;
  const grade = document.getElementById('grade').value;
  
  const gradeData = {
    marks_obtained: marks,
    feedback: feedback || undefined,
    grade: grade || undefined,
  };
  
  try {
    const result = await gradeSubmission(submissionId, gradeData);
    
    if (result.success) {
      alert('Submission graded successfully!');
      console.log(result.submission);
    } else {
      alert('Error: ' + result.message);
    }
  } catch (error) {
    console.error('Grading failed:', error);
    alert('Failed to grade submission');
  }
});
```

---

## Testing

### PHP Test Script

Create `test_grading_api.php`:

```php
<?php
$apiToken = 'YOUR_API_TOKEN_HERE';
$baseUrl = 'http://localhost/learning-lms/public/api/instructor';

// Test 1: Get Assignment Submissions
echo "Test 1: Get Assignment Submissions\n";
echo "=====================================\n";

$assignmentId = 1;
$ch = curl_init("$baseUrl/assignments/$assignmentId/submissions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
print_r(json_decode($response, true));
echo "\n\n";

// Test 2: Get Submission Details
echo "Test 2: Get Submission Details\n";
echo "================================\n";

$submissionId = 1;
$ch = curl_init("$baseUrl/submissions/$submissionId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
print_r(json_decode($response, true));
echo "\n\n";

// Test 3: Grade Submission
echo "Test 3: Grade Submission\n";
echo "=========================\n";

$submissionId = 1;
$gradeData = [
    'marks_obtained' => 85,
    'feedback' => 'Excellent work! Well-structured code and comprehensive documentation.',
    'grade' => 'B',
];

$ch = curl_init("$baseUrl/submissions/$submissionId/grade");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($gradeData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
print_r(json_decode($response, true));
echo "\n\n";

// Test 4: Get Module Submissions
echo "Test 4: Get Module Submissions\n";
echo "================================\n";

$moduleId = 1;
$ch = curl_init("$baseUrl/modules/$moduleId/submissions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $apiToken,
    'Content-Type: application/json',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response:\n";
print_r(json_decode($response, true));
```

### HTML Test Interface

Create `test_grading.html`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor Grading API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        h2 {
            color: #333;
        }
        input, textarea, select, button {
            margin: 10px 0;
            padding: 8px;
            width: 100%;
            max-width: 500px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        button:hover {
            background-color: #0056b3;
        }
        .response {
            background-color: #f5f5f5;
            padding: 10px;
            margin-top: 10px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .success {
            border-left: 4px solid #28a745;
        }
        .error {
            border-left: 4px solid #dc3545;
        }
    </style>
</head>
<body>
    <h1>Instructor Grading API Test Interface</h1>

    <!-- API Token Input -->
    <div class="test-section">
        <h2>API Configuration</h2>
        <label>API Token:</label>
        <input type="text" id="apiToken" placeholder="Enter your API token">
        <button onclick="saveToken()">Save Token</button>
    </div>

    <!-- Test 1: Get Assignment Submissions -->
    <div class="test-section">
        <h2>1. Get Assignment Submissions</h2>
        <label>Assignment ID:</label>
        <input type="number" id="assignmentId1" value="1">
        <button onclick="getAssignmentSubmissions()">Get Submissions</button>
        <div id="response1" class="response"></div>
    </div>

    <!-- Test 2: Get Submission Details -->
    <div class="test-section">
        <h2>2. Get Submission Details</h2>
        <label>Submission ID:</label>
        <input type="number" id="submissionId1" value="1">
        <button onclick="getSubmissionDetails()">Get Details</button>
        <div id="response2" class="response"></div>
    </div>

    <!-- Test 3: Grade Submission -->
    <div class="test-section">
        <h2>3. Grade Submission</h2>
        <label>Submission ID:</label>
        <input type="number" id="submissionId2" value="1">
        
        <label>Marks Obtained:</label>
        <input type="number" id="marks" value="85" step="0.01">
        
        <label>Grade (Optional):</label>
        <select id="grade">
            <option value="">Auto-calculate</option>
            <option value="A">A</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B" selected>B</option>
            <option value="B-">B-</option>
            <option value="C+">C+</option>
            <option value="C">C</option>
            <option value="C-">C-</option>
            <option value="D+">D+</option>
            <option value="D">D</option>
            <option value="F">F</option>
        </select>
        
        <label>Feedback:</label>
        <textarea id="feedback" rows="4">Excellent work! Well-structured code and comprehensive documentation.</textarea>
        
        <button onclick="gradeSubmission()">Submit Grade</button>
        <div id="response3" class="response"></div>
    </div>

    <!-- Test 4: Get Module Submissions -->
    <div class="test-section">
        <h2>4. Get Module Submissions Overview</h2>
        <label>Module ID:</label>
        <input type="number" id="moduleId" value="1">
        <button onclick="getModuleSubmissions()">Get Overview</button>
        <div id="response4" class="response"></div>
    </div>

    <!-- Test 5: Download Submission File -->
    <div class="test-section">
        <h2>5. Download Submission File</h2>
        <label>Submission ID:</label>
        <input type="number" id="submissionId3" value="1">
        <button onclick="downloadSubmissionFile()">Download File</button>
        <div id="response5" class="response"></div>
    </div>

    <script>
        const API_BASE_URL = 'http://localhost/learning-lms/public/api/instructor';

        function saveToken() {
            const token = document.getElementById('apiToken').value;
            localStorage.setItem('api_token', token);
            alert('API token saved!');
        }

        function getAuthToken() {
            const token = localStorage.getItem('api_token') || document.getElementById('apiToken').value;
            if (!token) {
                alert('Please enter and save your API token first!');
                return null;
            }
            return token;
        }

        async function getAssignmentSubmissions() {
            const token = getAuthToken();
            if (!token) return;

            const assignmentId = document.getElementById('assignmentId1').value;
            const responseDiv = document.getElementById('response1');
            
            try {
                const response = await fetch(`${API_BASE_URL}/assignments/${assignmentId}/submissions`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                });
                
                const data = await response.json();
                responseDiv.textContent = JSON.stringify(data, null, 2);
                responseDiv.className = response.ok ? 'response success' : 'response error';
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
                responseDiv.className = 'response error';
            }
        }

        async function getSubmissionDetails() {
            const token = getAuthToken();
            if (!token) return;

            const submissionId = document.getElementById('submissionId1').value;
            const responseDiv = document.getElementById('response2');
            
            try {
                const response = await fetch(`${API_BASE_URL}/submissions/${submissionId}`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                });
                
                const data = await response.json();
                responseDiv.textContent = JSON.stringify(data, null, 2);
                responseDiv.className = response.ok ? 'response success' : 'response error';
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
                responseDiv.className = 'response error';
            }
        }

        async function gradeSubmission() {
            const token = getAuthToken();
            if (!token) return;

            const submissionId = document.getElementById('submissionId2').value;
            const marks = parseFloat(document.getElementById('marks').value);
            const grade = document.getElementById('grade').value;
            const feedback = document.getElementById('feedback').value;
            const responseDiv = document.getElementById('response3');
            
            const gradeData = {
                marks_obtained: marks,
                feedback: feedback || undefined,
                grade: grade || undefined,
            };
            
            try {
                const response = await fetch(`${API_BASE_URL}/submissions/${submissionId}/grade`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(gradeData),
                });
                
                const data = await response.json();
                responseDiv.textContent = JSON.stringify(data, null, 2);
                responseDiv.className = response.ok ? 'response success' : 'response error';
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
                responseDiv.className = 'response error';
            }
        }

        async function getModuleSubmissions() {
            const token = getAuthToken();
            if (!token) return;

            const moduleId = document.getElementById('moduleId').value;
            const responseDiv = document.getElementById('response4');
            
            try {
                const response = await fetch(`${API_BASE_URL}/modules/${moduleId}/submissions`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                        'Content-Type': 'application/json',
                    },
                });
                
                const data = await response.json();
                responseDiv.textContent = JSON.stringify(data, null, 2);
                responseDiv.className = response.ok ? 'response success' : 'response error';
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
                responseDiv.className = 'response error';
            }
        }

        async function downloadSubmissionFile() {
            const token = getAuthToken();
            if (!token) return;

            const submissionId = document.getElementById('submissionId3').value;
            const responseDiv = document.getElementById('response5');
            
            try {
                const response = await fetch(`${API_BASE_URL}/submissions/${submissionId}/download`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${token}`,
                    },
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'submission_file';
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                    window.URL.revokeObjectURL(url);
                    
                    responseDiv.textContent = 'File download started successfully!';
                    responseDiv.className = 'response success';
                } else {
                    const data = await response.json();
                    responseDiv.textContent = JSON.stringify(data, null, 2);
                    responseDiv.className = 'response error';
                }
            } catch (error) {
                responseDiv.textContent = 'Error: ' + error.message;
                responseDiv.className = 'response error';
            }
        }

        // Load saved token on page load
        window.onload = function() {
            const savedToken = localStorage.getItem('api_token');
            if (savedToken) {
                document.getElementById('apiToken').value = savedToken;
            }
        };
    </script>
</body>
</html>
```

---

## cURL Examples

### Get Assignment Submissions
```bash
curl -X GET \
  'http://localhost/learning-lms/public/api/instructor/assignments/1/submissions' \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json'
```

### Get Submission Details
```bash
curl -X GET \
  'http://localhost/learning-lms/public/api/instructor/submissions/1' \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json'
```

### Grade Submission
```bash
curl -X PUT \
  'http://localhost/learning-lms/public/api/instructor/submissions/1/grade' \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "marks_obtained": 85,
    "feedback": "Excellent work! Well-structured and documented.",
    "grade": "B"
  }'
```

### Download Submission File
```bash
curl -X GET \
  'http://localhost/learning-lms/public/api/instructor/submissions/1/download' \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  --output submission_file.pdf
```

### Get Module Submissions
```bash
curl -X GET \
  'http://localhost/learning-lms/public/api/instructor/modules/1/submissions' \
  -H 'Authorization: Bearer YOUR_API_TOKEN' \
  -H 'Content-Type: application/json'
```

---

## Error Handling

### Common Error Responses

**404 Not Found:**
```json
{
  "success": false,
  "message": "Submission not found"
}
```

**422 Validation Error:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "marks_obtained": [
      "The marks obtained must be between 0 and 100."
    ]
  }
}
```

**500 Internal Server Error:**
```json
{
  "success": false,
  "message": "Error grading submission",
  "error": "Detailed error message"
}
```

**401 Unauthorized:**
```json
{
  "message": "Unauthenticated."
}
```

---

## Quick Start Guide

### 1. Get Your API Token
```bash
# Login to get token
curl -X POST http://localhost/learning-lms/public/api/login \
  -H 'Content-Type: application/json' \
  -d '{"username": "instructor@example.com", "password": "password"}'
```

### 2. Test Basic Endpoint
```bash
# Get submissions for assignment ID 1
curl -X GET \
  'http://localhost/learning-lms/public/api/instructor/assignments/1/submissions' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE'
```

### 3. Grade Your First Submission
```bash
curl -X PUT \
  'http://localhost/learning-lms/public/api/instructor/submissions/1/grade' \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE' \
  -H 'Content-Type: application/json' \
  -d '{"marks_obtained": 85, "feedback": "Great work!"}'
```

---

## Support & Troubleshooting

### Common Issues

1. **401 Unauthorized Error**
   - Check if your API token is valid
   - Ensure the token is included in the Authorization header
   - Token format: `Bearer <your_token>`

2. **404 Not Found Error**
   - Verify the submission/assignment ID exists
   - Check database for correct IDs

3. **422 Validation Error**
   - Ensure marks_obtained is within 0 and max_points range
   - Check that feedback doesn't exceed 2000 characters

4. **CORS Issues**
   - CORS is configured in the Laravel backend
   - Check browser console for specific CORS errors

### Database Check
```sql
-- Check submissions
SELECT * FROM assignment_submissions WHERE id = 1;

-- Check assignments
SELECT * FROM module_assignments WHERE id = 1;

-- Check submission counts by status
SELECT status, COUNT(*) as count 
FROM assignment_submissions 
GROUP BY status;
```

---

## Summary

The Instructor Grading System provides:
- ✅ View all submissions for assignments
- ✅ Grade submissions with marks (0-100)
- ✅ Auto-calculate or manually assign letter grades
- ✅ Provide detailed feedback to students
- ✅ Track submission statistics
- ✅ Download submission files
- ✅ Module-level submission overview

All endpoints are production-ready and tested. Frontend integration examples provided in React/TypeScript and vanilla JavaScript.

**For additional support or questions, please refer to the Laravel documentation or contact your development team.**
