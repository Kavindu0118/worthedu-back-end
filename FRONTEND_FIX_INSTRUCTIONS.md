# Frontend API Integration Fixes

## Issues Summary
1. **Course updates** - Changes don't persist in UI (backend works, frontend not sending PUT requests)
2. **Assignment creation** - "Failed to fetch" errors (connection/CORS/Content-Type issues)

Both issues stem from frontend API integration problems, NOT backend issues.

---

## 🔥 URGENT: Fix "Failed to fetch" on Assignment Creation

**Error:** `TypeError: Failed to fetch at fetchWithAuth (auth.ts:25:26) at addAssignment (courseApi.ts:245:26)`

**Root Causes:**
1. Wrong API base URL (using `localhost` instead of `127.0.0.1`)
2. Incorrect Content-Type header with FormData
3. Missing or malformed Authorization header

**Quick Fix:**

```typescript
// courseApi.ts - addAssignment function

export const addAssignment = async (moduleId: number, formData: FormData) => {
  // CRITICAL: Use 127.0.0.1, NOT localhost
  const API_BASE_URL = 'http://127.0.0.1:8000/api';
  
  // Get token from your storage (localStorage, context, etc.)
  const token = localStorage.getItem('auth_token'); // Adjust to your token storage
  
  const response = await fetch(
    `${API_BASE_URL}/instructor/modules/${moduleId}/assignments`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        // Do NOT set Content-Type! Browser will set it with boundary for FormData
      },
      body: formData // FormData object with assignment fields
    }
  );
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return response.json();
};
```

**Checklist:**
- [ ] API URL is `http://127.0.0.1:8000/api` (not localhost)
- [ ] No Content-Type header when sending FormData
- [ ] Authorization header includes `Bearer ` prefix
- [ ] FormData contains: assignment_title, instructions, max_points, due_date, attachment (optional)
- [ ] Server is running: `php artisan serve`

---

## Root Cause (Course Updates)
The React frontend is **NOT sending PUT requests** to the update API endpoint. Server logs show only GET requests, meaning the update function is either:
- Not being called at all
- Making GET requests instead of PUT
- Missing authorization headers
- Not updating local state after successful response

## Backend API Details (VERIFIED WORKING)

### Endpoint
```
PUT http://127.0.0.1:8000/api/instructor/courses/{id}
```

### Required Headers
```javascript
{
  'Authorization': 'Bearer YOUR_TOKEN_HERE',
  'Content-Type': 'application/json'
}
```

### Request Body (JSON)
```json
{
  "title": "Updated Course Title",
  "category": "Updated Category",
  "description": "Updated description text",
  "price": 299,
  "level": "intermediate",
  "duration": "8 weeks",
  "status": "published"
}
```

**Note:** Send only the fields you want to update. Validation uses `sometimes|required` so all fields are optional.

### Response Format
```json
{
  "data": {
    "id": 1,
    "instructor_id": 3,
    "title": "Updated Course Title",
    "category": "Updated Category",
    "description": "Updated description text",
    "price": 299,
    "level": "intermediate",
    "duration": "8 weeks",
    "thumbnail": null,
    "status": "published",
    "student_count": 0,
    "modules_count": 1,
    "created_at": "2025-11-27T19:41:24.000000Z",
    "updated_at": "2025-12-09T06:39:27.000000Z",
    "modules": [...]
  }
}
```

**CRITICAL:** The course object is nested inside `response.data.data` (not just `response.data`)

## Required Frontend Changes

### 1. Fix API Call (Use PUT Method)

**Find your course update function** (likely in a service file or component) and ensure it uses **PUT**:

```typescript
// ❌ WRONG - This won't update anything
const updateCourse = async (courseId: number, courseData: any) => {
  const response = await api.get(`/api/instructor/courses/${courseId}`);
  return response.data;
};

// ✅ CORRECT - Use PUT method
const updateCourse = async (courseId: number, courseData: any) => {
  const response = await api.put(
    `/api/instructor/courses/${courseId}`,
    courseData,
    {
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      }
    }
  );
  return response.data;
};
```

### 2. Update Local State with Response

**After successful update, use the response to update your local state:**

```typescript
// ❌ WRONG - State not updated
const handleUpdateCourse = async (formData: any) => {
  await updateCourse(courseId, formData);
  // UI still shows old data
};

// ✅ CORRECT - Update state with response
const handleUpdateCourse = async (formData: any) => {
  const response = await updateCourse(courseId, formData);
  
  // CRITICAL: Access response.data.data (not response.data)
  if (response?.data) {
    setCourse(response.data); // Update single course view
    // OR
    setCourses(prev => prev.map(c => 
      c.id === courseId ? response.data : c
    )); // Update course list
  }
};
```

### 3. Common Issues to Check

#### Issue A: Form submission not calling update
```typescript
// ❌ WRONG - Form might be calling GET or nothing
const onSubmit = (data: any) => {
  getCourse(courseId); // This just fetches, doesn't update
};

// ✅ CORRECT - Call the update function
const onSubmit = async (data: any) => {
  await handleUpdateCourse(data);
};
```

#### Issue B: Authorization header missing
```typescript
// Check your axios/fetch instance configuration
const api = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  }
});

// Add token interceptor
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token'); // Or your token storage
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

#### Issue C: Wrong API base URL
```typescript
// Make sure your API base URL matches the Laravel server
// Should be: http://127.0.0.1:8000 or http://localhost:8000
const API_BASE_URL = 'http://127.0.0.1:8000';
```

## Testing Your Fix

### 1. Open Browser DevTools
- Go to Network tab
- Filter by "XHR" or "Fetch"

### 2. Try updating a course
- You should see a **PUT** request to `/api/instructor/courses/{id}`
- Check Request Headers: Should include `Authorization: Bearer ...`
- Check Request Payload: Should contain your form data as JSON
- Check Response: Should have status 200 and return updated course in `data.data`

### 3. Verify state updates
- After successful update, the UI should immediately reflect changes
- No need to refresh or re-fetch the course

## Example Complete Implementation

```typescript
// services/courseService.ts
import axios from 'axios';

const API_BASE_URL = 'http://127.0.0.1:8000/api';

const getToken = () => localStorage.getItem('auth_token');

export const updateCourse = async (courseId: number, courseData: any) => {
  const response = await axios.put(
    `${API_BASE_URL}/instructor/courses/${courseId}`,
    courseData,
    {
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Content-Type': 'application/json'
      }
    }
  );
  return response.data; // Returns { data: courseObject }
};

// components/EditCourse.tsx
import { useState } from 'react';
import { updateCourse } from '../services/courseService';

const EditCourse = ({ course, onUpdate }) => {
  const [formData, setFormData] = useState({
    title: course.title,
    category: course.category,
    description: course.description,
    price: course.price,
    level: course.level,
    duration: course.duration,
    status: course.status
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    try {
      const response = await updateCourse(course.id, formData);
      
      // CRITICAL: Use response.data to update local state
      if (response?.data) {
        onUpdate(response.data); // Pass updated course to parent
        alert('Course updated successfully!');
      }
    } catch (error) {
      console.error('Update failed:', error);
      alert('Failed to update course');
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        value={formData.title}
        onChange={e => setFormData({...formData, title: e.target.value})}
      />
      {/* Other form fields */}
      <button type="submit">Update Course</button>
    </form>
  );
};
```

## Verification Checklist

- [ ] Update function uses **PUT** method (not GET)
- [ ] Authorization header includes Bearer token
- [ ] Request body contains course data as JSON
- [ ] Response data is accessed via `response.data.data`
- [ ] Local state (course/courses) is updated with response
- [ ] DevTools Network tab shows PUT request with status 200
- [ ] UI reflects changes immediately after update

## Troubleshooting "Failed to fetch" Errors

If you get **"TypeError: Failed to fetch"** errors (like on assignment creation):

### 1. Check if server is running
```powershell
# In PowerShell, check if Laravel server is up
Invoke-WebRequest -Uri "http://127.0.0.1:8000/api/me" -Method GET
```

### 2. Verify API base URL in frontend
```typescript
// Should match your Laravel server
const API_BASE_URL = 'http://127.0.0.1:8000/api'; // NOT localhost:8000
```

### 3. Check browser console Network tab
- Look for the failed request
- Check if it shows "Failed to fetch" or actual HTTP error (403, 422, 500)
- **Failed to fetch** = Connection issue (wrong URL, server down, CORS)
- **HTTP error code** = Request reached server but was rejected

### 4. Common causes:
- **Mixed content**: HTTPS frontend trying to call HTTP backend
- **Wrong port**: Using 8000 when server is on 3000, or vice versa
- **CORS**: Blocked by browser (check console for CORS-specific messages)
- **Server stopped**: Laravel `php artisan serve` process died

### 5. Check Laravel logs
```powershell
# View recent errors
Get-Content storage/logs/laravel.log -Tail 50
```

## Assignment Creation Specific

The assignment endpoint uses **multipart/form-data** for file uploads:

```typescript
const formData = new FormData();
formData.append('assignment_title', title);
formData.append('instructions', instructions);
formData.append('max_points', maxPoints.toString());
formData.append('due_date', dueDate);
if (file) {
  formData.append('attachment', file);
}

// Don't set Content-Type header - browser will set it automatically with boundary
const response = await axios.post(
  `${API_BASE_URL}/instructor/modules/${moduleId}/assignments`,
  formData,
  {
    headers: {
      'Authorization': `Bearer ${token}`
      // No Content-Type header!
    }
  }
);
```

## Need Help?

If issues persist after implementing these fixes:
1. Check browser console for errors
2. Check Network tab for failed requests
3. Verify token is valid and not expired
4. Confirm API base URL matches Laravel server
5. Check Laravel logs: `storage/logs/laravel.log`
6. Verify server is running: `php artisan serve`
7. Test with cURL or Postman to isolate frontend vs backend issues

---

**Backend Status:** ✅ Fully functional and tested
**Issue Location:** Frontend React code
**Fix Complexity:** Simple - just need to use correct HTTP methods, update state, and match API URLs
