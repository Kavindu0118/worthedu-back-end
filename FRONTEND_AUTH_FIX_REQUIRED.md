# URGENT: Frontend Auth Fix Required for Assignment Submission

## Issue
Assignment submission is failing with **401 Unauthorized** error:
```
POST http://127.0.0.1:8000/api/learner/assignments/4/submit 401 (Unauthorized)
```

## Root Cause
The `submitAssignment` function in `learnerApi.ts` (line ~454) is **not sending the Authorization header** when making the POST request with FormData.

## Required Fix

### Location: `learnerApi.ts` (around line 454)

**BEFORE (Broken):**
```typescript
export async function submitAssignment(assignmentId: number, data: FormData) {
  const response = await fetch(
    `${API_BASE_URL}/api/learner/assignments/${assignmentId}/submit`,
    {
      method: 'POST',
      body: data,
    }
  );
  
  if (!response.ok) {
    throw new Error('Unauthorized');
  }
  
  return response.json();
}
```

**AFTER (Fixed):**
```typescript
export async function submitAssignment(assignmentId: number, data: FormData) {
  // Get the auth token from your storage (adjust based on your auth setup)
  const token = localStorage.getItem('authToken');
  // OR: const token = sessionStorage.getItem('token');
  // OR: const token = useAuthStore.getState().token; // if using Zustand
  // OR: const token = store.getState().auth.token; // if using Redux
  
  if (!token) {
    throw new Error('No authentication token found');
  }
  
  const response = await fetch(
    `${API_BASE_URL}/api/learner/assignments/${assignmentId}/submit`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        // CRITICAL: Do NOT set Content-Type for FormData
        // The browser must set it automatically with the multipart boundary
      },
      body: data,
    }
  );
  
  // Handle specific error cases
  if (response.status === 401) {
    throw new Error('Unauthorized - Please log in again');
  }
  
  if (response.status === 422) {
    const error = await response.json();
    throw new Error(error.message || 'Validation error');
  }
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to submit assignment');
  }
  
  return response.json();
}
```

## Alternative: If Using Axios

If your project uses Axios instead of fetch, the fix is similar:

```typescript
import axios from 'axios';

export async function submitAssignment(assignmentId: number, data: FormData) {
  const token = localStorage.getItem('authToken');
  
  if (!token) {
    throw new Error('No authentication token found');
  }
  
  try {
    const response = await axios.post(
      `/api/learner/assignments/${assignmentId}/submit`,
      data,
      {
        headers: {
          'Authorization': `Bearer ${token}`,
          // Do NOT set Content-Type - Axios handles it for FormData
        },
      }
    );
    
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      if (error.response?.status === 401) {
        throw new Error('Unauthorized - Please log in again');
      }
      if (error.response?.status === 422) {
        throw new Error(error.response.data.message || 'Validation error');
      }
      throw new Error(error.response?.data?.message || 'Failed to submit assignment');
    }
    throw error;
  }
}
```

## Better Solution: Global Axios Interceptor

**Recommended:** Set up an Axios interceptor to automatically add auth headers to ALL requests:

### Create `src/utils/apiClient.ts`:
```typescript
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  timeout: 30000,
});

// Request interceptor - automatically add auth token to all requests
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('authToken');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Response interceptor - handle common errors globally
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Redirect to login or clear auth state
      localStorage.removeItem('authToken');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

### Then update `learnerApi.ts`:
```typescript
import apiClient from './utils/apiClient';

export async function submitAssignment(assignmentId: number, data: FormData) {
  try {
    const response = await apiClient.post(
      `/api/learner/assignments/${assignmentId}/submit`,
      data
      // Auth header is added automatically by interceptor
    );
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      throw new Error(error.response?.data?.message || 'Failed to submit assignment');
    }
    throw error;
  }
}
```

## Critical Notes

### 1. **Never Set Content-Type for FormData**
When sending FormData (especially with file uploads), the browser MUST set the `Content-Type` header automatically. It will be:
```
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...
```

If you manually set `Content-Type`, the upload will fail because the boundary parameter will be missing.

### 2. **Always Use Bearer Token Format**
The backend expects:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

Do NOT send:
- `Authorization: YOUR_TOKEN_HERE` ❌
- `Token: YOUR_TOKEN_HERE` ❌
- `X-Auth-Token: YOUR_TOKEN_HERE` ❌

### 3. **Token Storage Location**
Adjust the token retrieval based on your auth implementation:

**If using Context/State:**
```typescript
import { useAuth } from '@/contexts/AuthContext';

const { token } = useAuth();
```

**If using Zustand:**
```typescript
import useAuthStore from '@/store/authStore';

const token = useAuthStore.getState().token;
```

**If using Redux:**
```typescript
import { store } from '@/store';

const token = store.getState().auth.token;
```

**If using localStorage:**
```typescript
const token = localStorage.getItem('authToken');
```

### 4. **Check Other API Calls**
This same issue might affect other protected endpoints. Review all API calls in `learnerApi.ts` to ensure they include the Authorization header:

- ✅ GET `/api/learner/assignments`
- ✅ GET `/api/learner/assignments/{id}`
- ✅ POST `/api/learner/assignments/{id}/submit` ← **Fix this one**
- ✅ GET `/api/learner/assignments/{id}/submission`
- ✅ All quiz endpoints
- ✅ Dashboard endpoints
- ✅ Profile endpoints

## Testing After Fix

1. **Check browser DevTools Network tab:**
   - Open DevTools (F12)
   - Go to Network tab
   - Submit an assignment
   - Click on the `submit` request
   - Verify Headers include: `Authorization: Bearer <token>`

2. **Test these scenarios:**
   - Submit with text only
   - Submit with file only
   - Submit with both text and file
   - Submit when not logged in (should show proper error)
   - Submit after token expires (should redirect to login)

## Quick Test Script

Add this to your browser console to test if auth is working:

```javascript
// Test if token exists
const token = localStorage.getItem('authToken');
console.log('Token:', token ? 'Found' : 'Missing');

// Test API call
fetch('http://127.0.0.1:8000/api/learner/assignments', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
})
  .then(res => res.json())
  .then(data => console.log('API Response:', data))
  .catch(err => console.error('API Error:', err));
```

## Backend Verification (Already Working)

The backend is correctly configured:
- ✅ Routes are protected with `ApiTokenAuth` middleware
- ✅ Accepts `Authorization: Bearer <token>` format
- ✅ Returns 401 when token is missing or invalid
- ✅ All assignment endpoints are accessible at `/api/learner/assignments/*`

**The issue is purely on the frontend side - missing Authorization header in the API call.**

## Need Help?

If you're still seeing 401 errors after implementing this fix:

1. **Verify token exists:**
   ```javascript
   console.log('Token:', localStorage.getItem('authToken'));
   ```

2. **Check token is being sent:**
   - Open Network tab
   - Submit assignment
   - Check Request Headers for `Authorization: Bearer ...`

3. **Test with curl:**
   ```bash
   curl -X POST http://127.0.0.1:8000/api/learner/assignments/4/submit \
     -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -F "submission_text=Test answer" \
     -F "file=@/path/to/file.pdf"
   ```

4. **Verify token is valid:**
   ```bash
   curl http://127.0.0.1:8000/api/me \
     -H "Authorization: Bearer YOUR_TOKEN_HERE"
   ```

   Should return user details if token is valid.

## Summary

**Fix Required:** Add `Authorization: Bearer ${token}` header to the FormData POST request in `learnerApi.ts`

**Time to Fix:** 2-5 minutes

**Impact:** Assignment submission will work immediately after the fix

**Recommended:** Implement global Axios interceptor to prevent similar issues with other endpoints
