# Frontend Assignment Integration Guide

## Overview
This guide provides complete instructions for implementing the learner assignment preview and submission UI in the frontend application.

## API Endpoints

All endpoints require authentication via `Authorization: Bearer <token>` header.

### 1. List Assignments
```
GET /api/learner/assignments
```

**Query Parameters:**
- `status` (optional): Filter by status - `pending`, `submitted`, `graded`
- `course_id` (optional): Filter by specific course

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Week 1 Assignment",
      "description": "Complete the following tasks...",
      "course_id": 5,
      "course_title": "Introduction to Programming",
      "due_date": "2025-12-20T23:59:59.000000Z",
      "max_marks": 100,
      "allow_late_submission": true,
      "status": "pending",
      "submit_url": "http://localhost:8000/api/learner/assignments/1/submit",
      "submission": null
    }
  ]
}
```

### 2. Get Assignment Details
```
GET /api/learner/assignments/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Week 1 Assignment",
    "description": "Complete the following tasks...",
    "instructions": "Read chapters 1-3 and answer the questions below...",
    "course_id": 5,
    "course_title": "Introduction to Programming",
    "due_date": "2025-12-20T23:59:59.000000Z",
    "max_marks": 100,
    "allow_late_submission": true,
    "late_penalty_percent": 10,
    "max_file_size_mb": 10,
    "allowed_file_types": ["pdf", "docx", "txt"],
    "submit_url": "http://localhost:8000/api/learner/assignments/1/submit",
    "submission": null
  }
}
```

**With Existing Submission:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Week 1 Assignment",
    // ... other assignment fields
    "submission": {
      "id": 42,
      "submission_text": "Here are my answers...",
      "file_path": "http://localhost:8000/storage/assignments/filename.pdf",
      "file_name": "my_assignment.pdf",
      "file_size_kb": 1024.5,
      "submitted_at": "2025-12-15T10:30:00.000000Z",
      "status": "submitted",
      "marks_obtained": null,
      "feedback": null,
      "graded_at": null,
      "graded_by": null,
      "is_late": false
    }
  }
}
```

### 3. Submit Assignment
```
POST {submit_url}
```
Use the `submit_url` from the assignment details response.

**Request (FormData):**
```javascript
const formData = new FormData();
formData.append('submission_text', 'My answer text...');
formData.append('file', fileInputElement.files[0]); // optional
```

**Response:**
```json
{
  "success": true,
  "message": "Assignment submitted successfully",
  "data": {
    "submission_id": 42,
    "assignment_id": 1,
    "submitted_at": "2025-12-15T10:30:00.000000Z",
    "is_late": false
  }
}
```

**Error Responses:**
- **403 Forbidden**: Not enrolled in course
- **400 Bad Request**: Past due date and late submissions not allowed
- **422 Validation Error**: Invalid file type or size

### 4. Get Submission Details
```
GET /api/learner/assignments/{id}/submission
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 42,
    "assignment_id": 1,
    "assignment_title": "Week 1 Assignment",
    "submission_text": "My answers...",
    "file_path": "http://localhost:8000/storage/assignments/file.pdf",
    "file_name": "my_assignment.pdf",
    "file_size_kb": 1024.5,
    "submitted_at": "2025-12-15T10:30:00.000000Z",
    "status": "graded",
    "marks_obtained": 85,
    "max_marks": 100,
    "feedback": "Great work! Minor improvements needed in question 3.",
    "graded_at": "2025-12-16T14:20:00.000000Z",
    "graded_by": {
      "id": 3,
      "name": "Dr. Smith",
      "email": "smith@example.com"
    },
    "is_late": false
  }
}
```

## UI Requirements

### Assignment List Page
Display a list of all assignments for enrolled courses:
- **Title** and **Course Name**
- **Due Date** (formatted, with countdown if upcoming)
- **Status Badge**: 
  - Pending (yellow/orange)
  - Submitted (blue)
  - Graded (green)
- **Max Marks**
- Click to view details

**Filters:**
- Status dropdown (All, Pending, Submitted, Graded)
- Course dropdown (All Courses, specific courses)

### Assignment Detail/Preview Page

#### Header Section
- Assignment **Title**
- **Course Name** (clickable link to course)
- **Due Date** with countdown timer
- **Status Badge**
- **Max Marks** display

#### Instructions Section
Display the full `instructions` or `description` field with proper formatting (support markdown if available).

#### Submission Requirements Section
Show the following information clearly:
- **Allowed File Types**: Display as badges (e.g., PDF, DOCX, TXT)
- **Maximum File Size**: "Up to 10 MB"
- **Late Submission Policy**: 
  - If `allow_late_submission === true`: "Late submissions accepted with 10% penalty"
  - If `allow_late_submission === false`: "Late submissions not accepted"

#### Submission Form Section (if no submission exists)

**Text Submission:**
```html
<textarea 
  placeholder="Enter your answer here (optional)"
  rows="10"
></textarea>
```

**File Upload:**
```html
<input 
  type="file" 
  accept=".pdf,.docx,.txt" 
  onChange={handleFileChange}
/>
```

**Client-Side Validation:**
- Check file extension matches `allowed_file_types`
- Check file size <= `max_file_size_mb * 1024 * 1024` bytes
- Show validation errors inline
- Disable submit button if validation fails

**Due Date Check:**
- If past due and `allow_late_submission === false`: Show warning "This assignment is past due and no longer accepts submissions"
- If past due and `allow_late_submission === true`: Show warning "Submitting late will incur a {late_penalty_percent}% penalty"

**Submit Button:**
- Primary action button
- Disabled states: uploading, validation errors, past due (when late not allowed)
- Show loading spinner during submission

#### Existing Submission Section (if submission exists)

Display submission details:
- **Submitted At**: Formatted date/time
- **Late Submission**: Badge if `is_late === true`
- **Submission Text**: If provided
- **Attached File**: 
  - File name as download link
  - File size display
  - Download button/icon
- **Status**:
  - "Submitted - Awaiting grading"
  - "Graded - {marks_obtained}/{max_marks}"
  - "Returned - Needs revision"
- **Feedback**: Display instructor feedback if available
- **Graded By**: Instructor name if graded
- **Graded At**: Date/time if graded

**Re-submission:**
- Only allow if `status === 'returned'`
- Show message: "Your instructor has returned this assignment for revision"
- Show original submission + new submission form

## Implementation Example (React)

### Fetch Assignment Details
```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

function AssignmentDetail({ assignmentId, authToken }) {
  const [assignment, setAssignment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchAssignment();
  }, [assignmentId]);

  const fetchAssignment = async () => {
    try {
      setLoading(true);
      const response = await axios.get(
        `/api/learner/assignments/${assignmentId}`,
        {
          headers: { Authorization: `Bearer ${authToken}` }
        }
      );
      setAssignment(response.data.data);
      setError(null);
    } catch (err) {
      setError(err.response?.data?.message || 'Failed to load assignment');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div className="error">{error}</div>;
  if (!assignment) return null;

  return (
    <div className="assignment-detail">
      <AssignmentHeader assignment={assignment} />
      <AssignmentInstructions assignment={assignment} />
      <AssignmentRequirements assignment={assignment} />
      {assignment.submission ? (
        <SubmissionView submission={assignment.submission} />
      ) : (
        <SubmissionForm 
          assignment={assignment} 
          authToken={authToken}
          onSubmitSuccess={fetchAssignment}
        />
      )}
    </div>
  );
}
```

### File Upload with Validation
```javascript
function SubmissionForm({ assignment, authToken, onSubmitSuccess }) {
  const [submissionText, setSubmissionText] = useState('');
  const [file, setFile] = useState(null);
  const [fileError, setFileError] = useState(null);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState(null);

  const validateFile = (selectedFile) => {
    if (!selectedFile) return null;

    // Check file size
    const maxSizeBytes = assignment.max_file_size_mb * 1024 * 1024;
    if (selectedFile.size > maxSizeBytes) {
      return `File size must be less than ${assignment.max_file_size_mb} MB`;
    }

    // Check file type
    const allowedTypes = assignment.allowed_file_types;
    if (allowedTypes && allowedTypes.length > 0) {
      const fileExtension = selectedFile.name.split('.').pop().toLowerCase();
      if (!allowedTypes.includes(fileExtension)) {
        return `Only ${allowedTypes.join(', ')} files are allowed`;
      }
    }

    return null;
  };

  const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    if (!selectedFile) {
      setFile(null);
      setFileError(null);
      return;
    }

    const error = validateFile(selectedFile);
    if (error) {
      setFileError(error);
      setFile(null);
    } else {
      setFileError(null);
      setFile(selectedFile);
    }
  };

  const checkDueDate = () => {
    if (!assignment.due_date) return null;
    
    const now = new Date();
    const dueDate = new Date(assignment.due_date);
    
    if (now > dueDate) {
      if (!assignment.allow_late_submission) {
        return {
          type: 'error',
          message: 'This assignment is past due and no longer accepts submissions'
        };
      } else {
        return {
          type: 'warning',
          message: `Late submission will incur a ${assignment.late_penalty_percent}% penalty`
        };
      }
    }
    return null;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    // Check if anything to submit
    if (!submissionText.trim() && !file) {
      setSubmitError('Please provide either text submission or upload a file');
      return;
    }

    // Check due date
    const dueDateCheck = checkDueDate();
    if (dueDateCheck && dueDateCheck.type === 'error') {
      setSubmitError(dueDateCheck.message);
      return;
    }

    try {
      setSubmitting(true);
      setSubmitError(null);

      const formData = new FormData();
      if (submissionText.trim()) {
        formData.append('submission_text', submissionText);
      }
      if (file) {
        formData.append('file', file);
      }

      const response = await axios.post(
        assignment.submit_url,
        formData,
        {
          headers: {
            Authorization: `Bearer ${authToken}`,
            // Don't set Content-Type - let browser set it with boundary
          }
        }
      );

      // Success
      alert('Assignment submitted successfully!');
      onSubmitSuccess();
    } catch (err) {
      if (err.response?.status === 422) {
        const errors = err.response.data.errors;
        setSubmitError(Object.values(errors).flat().join(', '));
      } else {
        setSubmitError(err.response?.data?.message || 'Failed to submit assignment');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const dueDateCheck = checkDueDate();
  const canSubmit = !submitting && !fileError && 
                    (submissionText.trim() || file) &&
                    (!dueDateCheck || dueDateCheck.type !== 'error');

  return (
    <form onSubmit={handleSubmit} className="submission-form">
      <h3>Submit Your Assignment</h3>

      {/* Due Date Warning */}
      {dueDateCheck && (
        <div className={`alert alert-${dueDateCheck.type}`}>
          {dueDateCheck.message}
        </div>
      )}

      {/* Text Submission */}
      <div className="form-group">
        <label>Your Answer (Optional)</label>
        <textarea
          value={submissionText}
          onChange={(e) => setSubmissionText(e.target.value)}
          rows="10"
          placeholder="Enter your answer here..."
          disabled={submitting}
        />
      </div>

      {/* File Upload */}
      <div className="form-group">
        <label>Upload File (Optional)</label>
        <input
          type="file"
          accept={assignment.allowed_file_types?.map(t => `.${t}`).join(',')}
          onChange={handleFileChange}
          disabled={submitting}
        />
        {file && (
          <div className="file-info">
            Selected: {file.name} ({(file.size / 1024).toFixed(2)} KB)
          </div>
        )}
        {fileError && <div className="error">{fileError}</div>}
      </div>

      {/* Submit Error */}
      {submitError && <div className="error">{submitError}</div>}

      {/* Submit Button */}
      <button
        type="submit"
        disabled={!canSubmit}
        className="btn btn-primary"
      >
        {submitting ? 'Submitting...' : 'Submit Assignment'}
      </button>
    </form>
  );
}
```

### Display Existing Submission
```javascript
function SubmissionView({ submission }) {
  const getStatusBadge = (status) => {
    const badges = {
      submitted: { class: 'badge-info', text: 'Awaiting Grading' },
      graded: { class: 'badge-success', text: 'Graded' },
      returned: { class: 'badge-warning', text: 'Returned for Revision' }
    };
    return badges[status] || badges.submitted;
  };

  const badge = getStatusBadge(submission.status);

  return (
    <div className="submission-view">
      <h3>Your Submission</h3>

      {/* Status */}
      <div className={`badge ${badge.class}`}>
        {badge.text}
      </div>

      {/* Late Badge */}
      {submission.is_late && (
        <div className="badge badge-danger">Late Submission</div>
      )}

      {/* Submission Date */}
      <div className="info-row">
        <strong>Submitted:</strong>
        <span>{new Date(submission.submitted_at).toLocaleString()}</span>
      </div>

      {/* Text Submission */}
      {submission.submission_text && (
        <div className="submission-text">
          <h4>Answer:</h4>
          <p>{submission.submission_text}</p>
        </div>
      )}

      {/* File Download */}
      {submission.file_path && (
        <div className="submission-file">
          <h4>Attached File:</h4>
          <a href={submission.file_path} download className="file-link">
            📄 {submission.file_name} ({submission.file_size_kb} KB)
          </a>
        </div>
      )}

      {/* Grading Information */}
      {submission.status === 'graded' && (
        <div className="grading-info">
          <div className="marks">
            <strong>Score:</strong>
            <span className="score">
              {submission.marks_obtained} / {submission.max_marks}
            </span>
          </div>

          {submission.feedback && (
            <div className="feedback">
              <h4>Instructor Feedback:</h4>
              <p>{submission.feedback}</p>
            </div>
          )}

          {submission.graded_by && (
            <div className="grader-info">
              <small>
                Graded by {submission.graded_by.name} on{' '}
                {new Date(submission.graded_at).toLocaleString()}
              </small>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
```

## Vanilla JavaScript Example (No Framework)

```javascript
// Fetch and display assignment
async function loadAssignment(assignmentId) {
  const token = localStorage.getItem('authToken');
  
  try {
    const response = await fetch(`/api/learner/assignments/${assignmentId}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (!response.ok) throw new Error('Failed to load assignment');
    
    const result = await response.json();
    const assignment = result.data;
    
    displayAssignment(assignment);
  } catch (error) {
    document.getElementById('error').textContent = error.message;
  }
}

// Submit assignment
async function submitAssignment(assignmentId, submitUrl) {
  const token = localStorage.getItem('authToken');
  const form = document.getElementById('submissionForm');
  const formData = new FormData(form);
  
  try {
    const response = await fetch(submitUrl, {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` },
      body: formData
    });
    
    const result = await response.json();
    
    if (response.status === 422) {
      // Validation errors
      displayErrors(result.errors);
    } else if (!response.ok) {
      throw new Error(result.message || 'Submission failed');
    } else {
      alert('Assignment submitted successfully!');
      loadAssignment(assignmentId); // Reload to show submission
    }
  } catch (error) {
    document.getElementById('error').textContent = error.message;
  }
}
```

## Important Notes

### File Upload Headers
When submitting FormData with a file:
- **DO NOT** manually set `Content-Type` header
- Let the browser set it automatically with the correct `multipart/form-data` boundary
- Only include the `Authorization` header

### Error Handling
Handle these specific cases:
- **403**: User not enrolled - redirect to course enrollment page
- **400**: Past due without late submission - show error message, disable form
- **422**: Validation errors - display field-specific errors
- **404**: Assignment not found - show not found page
- **Network errors**: Show retry option

### Loading States
Provide feedback during:
- Initial page load
- File upload progress (if large files)
- Submission processing
- Refreshing after submission

### Mobile Responsiveness
- File input should work on mobile devices
- Text area should be comfortable for typing on small screens
- Due date countdown should be visible but not intrusive

## Testing Checklist

- [ ] List page loads and displays all assignments
- [ ] Filter by status works correctly
- [ ] Filter by course works correctly
- [ ] Assignment detail page shows all required information
- [ ] File upload validates file type correctly
- [ ] File upload validates file size correctly
- [ ] Due date warnings display correctly
- [ ] Can submit text-only answer
- [ ] Can submit file-only answer
- [ ] Can submit both text and file
- [ ] Loading states display during submission
- [ ] Success message shows after submission
- [ ] Page refreshes and shows submission details after submit
- [ ] Graded assignments display marks and feedback
- [ ] Late submission warning displays when past due
- [ ] Cannot submit when past due (if late not allowed)
- [ ] Download submitted file works
- [ ] Re-submission works for returned assignments
- [ ] Mobile layout is responsive
- [ ] Error messages display correctly

## Support

For backend API issues or questions, contact the backend team or check:
- `ASSIGNMENT_CREATION_COMPLETE.md` - Instructor assignment creation guide
- `routes/api.php` - API route definitions
- `app/Http/Controllers/LearnerAssignmentController.php` - API implementation
