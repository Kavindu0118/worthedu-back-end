# Frontend Task: Fix API Base URL Configuration

## Problem
Instructor registration and login are calling the frontend dev server (localhost:5173) instead of the Laravel backend (localhost:8000). This causes:
- Registration shows "success" but doesn't save to database
- Console shows HTML response instead of JSON
- All API calls are failing silently

## Task
Update the frontend to use the correct API base URL for all API requests.

## Implementation Steps

### 1. Create Environment Configuration
Create a `.env` file in the frontend root directory:
```env
VITE_API_BASE_URL=http://localhost:8000
```

### 2. Create API Configuration File
Create `src/config/api.ts`:
```typescript
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

export const API_ENDPOINTS = {
  LOGIN: '/api/login',
  LOGOUT: '/api/logout',
  REGISTER_INSTRUCTOR: '/api/register/instructor',
  REGISTER_LEARNER: '/api/register/learner',
  GET_COURSES: '/api/learner/courses',
  GET_COURSE: (id: number) => `/api/learner/courses/${id}`,
  GET_LESSON: (id: number) => `/api/learner/lessons/${id}`,
};

export async function apiCall(endpoint: string, options: RequestInit = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const defaultHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
  
  const token = localStorage.getItem('token');
  if (token) {
    defaultHeaders['Authorization'] = `Bearer ${token}`;
  }
  
  const response = await fetch(url, {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  });
  
  const contentType = response.headers.get('content-type');
  if (!contentType || !contentType.includes('application/json')) {
    throw new Error('Server returned non-JSON response. Check API URL.');
  }
  
  const data = await response.json();
  return { response, data };
}
```

### 3. Update InstructorRegistration.tsx
Replace the fetch call in the handleSubmit function:

**FIND:**
```typescript
const response = await fetch('/api/register/instructor', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify(formData),
});
```

**REPLACE WITH:**
```typescript
import { API_BASE_URL, API_ENDPOINTS, apiCall } from '../config/api';

// Inside handleSubmit function:
const { response, data } = await apiCall(API_ENDPOINTS.REGISTER_INSTRUCTOR, {
  method: 'POST',
  body: JSON.stringify(formData),
});

if (!response.ok) {
  throw new Error(data.message || 'Registration failed');
}

if (data.success) {
  alert('Instructor registered successfully!');
  // Clear form
  setFormData({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    qualifications: '',
    bio: '',
    expertise: '',
  });
} else {
  setError(data.message || 'Registration failed');
}
```

### 4. Update Login.tsx
Replace the fetch call in the handleSubmit function:

**FIND:**
```typescript
const response = await fetch('/api/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify({ email, password }),
});
```

**REPLACE WITH:**
```typescript
import { API_BASE_URL, API_ENDPOINTS, apiCall } from '../config/api';

// Inside handleSubmit function:
const { response, data } = await apiCall(API_ENDPOINTS.LOGIN, {
  method: 'POST',
  body: JSON.stringify({ email, password }),
});

if (data.success && data.token) {
  localStorage.setItem('token', data.token);
  localStorage.setItem('user', JSON.stringify(data.user));
  
  if (data.user.role === 'instructor') {
    navigate('/instructor/dashboard');
  } else {
    navigate('/learner/dashboard');
  }
} else {
  setError(data.message || 'Login failed');
}
```

### 5. Update LearnerRegistration.tsx
Similar to instructor registration, update the fetch call:

**FIND:**
```typescript
fetch('/api/register/learner',
```

**REPLACE WITH:**
```typescript
import { API_BASE_URL, API_ENDPOINTS, apiCall } from '../config/api';

const { response, data } = await apiCall(API_ENDPOINTS.REGISTER_LEARNER, {
  method: 'POST',
  body: JSON.stringify(formData),
});
```

### 6. Update All Other API Calls
Search the codebase for all instances of:
```typescript
fetch('/api/
```

And replace them with:
```typescript
fetch(`${API_BASE_URL}/api/
```

Or better yet, use the `apiCall` helper function.

**Files likely to have API calls:**
- CourseDetails.tsx
- LearnerDashboard.tsx
- InstructorDashboard.tsx
- Any component that fetches courses, lessons, or user data

### 7. Restart Dev Server
After making changes:
```bash
npm run dev
```

The Vite server needs to restart to load the new environment variable.

## Testing Checklist
After implementation:
- [ ] Browser console shows API URL with `:8000` port
- [ ] Network tab shows JSON responses, not HTML
- [ ] Registration creates user in database
- [ ] Login redirects to dashboard
- [ ] No "Unauthorized" errors in console
- [ ] All API calls return proper JSON data

## Expected Console Output
✅ **After fix:**
```
Submitting instructor registration...
API URL: http://localhost:8000/api/register/instructor
Response status: 201
Response data: {success: true, message: "Instructor registered successfully", data: {...}}
Registration successful!
```

❌ **Before fix:**
```
Response status: 200
Response text: <!doctype html>...  ← HTML instead of JSON
Response data: null
```

## Time Estimate
30-45 minutes to update all API calls and test thoroughly.

## Priority
🚨 **CRITICAL** - Blocks all user registration and authentication.
