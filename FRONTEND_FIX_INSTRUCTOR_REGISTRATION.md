# Frontend Fix: Instructor Registration Not Saving to Database

## 🚨 Issue
Instructor registration shows "Registration successful" but:
- ❌ Data is NOT saved to database
- ❌ Console shows HTML response instead of JSON
- ❌ API request goes to frontend dev server instead of backend

## 🔍 Root Cause
The API request URL is incorrect. The frontend is calling:
```
POST /api/register/instructor  
```

This resolves to:
```
http://localhost:5173/api/register/instructor  ← Frontend dev server (WRONG!)
```

Should be:
```
http://localhost:8000/api/register/instructor  ← Laravel backend (CORRECT!)
```

## 📊 Current Broken Response

**What you're receiving:**
```html
<!doctype html>
<html lang="en">
  <head>
    <script type="module">import { injectIntoGlobalHook }...
  </head>
  <body>
    <div id="root"></div>
  </body>
</html>
```

This is the **Vite dev server HTML**, not the API response!

**What you should receive:**
```json
{
  "success": true,
  "message": "Instructor registered successfully",
  "data": {
    "id": 5,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

---

## 🔧 Solution: Fix API Base URL

### Option 1: Environment Variable (RECOMMENDED)

**1. Create `.env` file in frontend root:**
```env
VITE_API_BASE_URL=http://localhost:8000
```

**2. Create `src/config/api.ts`:**
```typescript
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

export const API_ENDPOINTS = {
  REGISTER_INSTRUCTOR: '/api/register/instructor',
  REGISTER_LEARNER: '/api/register/learner',
  LOGIN: '/api/login',
  // Add other endpoints here
};
```

**3. Update InstructorRegistration.tsx:**

**❌ BROKEN CODE (Current):**
```tsx
const response = await fetch('/api/register/instructor', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify(formData),
});
```

**✅ FIXED CODE:**
```tsx
import { API_BASE_URL, API_ENDPOINTS } from '../config/api';

const response = await fetch(`${API_BASE_URL}${API_ENDPOINTS.REGISTER_INSTRUCTOR}`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify(formData),
});
```

### Option 2: Vite Proxy (Alternative)

**Update `vite.config.ts`:**
```typescript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})
```

With this approach, your current code will work because `/api/*` requests are proxied to Laravel.

---

## 🛠️ Complete Fixed Implementation

### Step 1: Setup API Configuration

**Create `src/config/api.ts`:**
```typescript
// API Base URL from environment variable
export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

// API Endpoints
export const API_ENDPOINTS = {
  // Authentication
  LOGIN: '/api/login',
  LOGOUT: '/api/logout',
  
  // Registration
  REGISTER_INSTRUCTOR: '/api/register/instructor',
  REGISTER_LEARNER: '/api/register/learner',
  
  // Courses
  GET_COURSES: '/api/learner/courses',
  GET_COURSE: (id: number) => `/api/learner/courses/${id}`,
  
  // Lessons
  GET_LESSON: (id: number) => `/api/learner/lessons/${id}`,
  
  // Add more endpoints as needed
};

// Helper function for API calls
export async function apiCall(endpoint: string, options: RequestInit = {}) {
  const url = `${API_BASE_URL}${endpoint}`;
  
  const defaultHeaders = {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  };
  
  const response = await fetch(url, {
    ...options,
    headers: {
      ...defaultHeaders,
      ...options.headers,
    },
  });
  
  // Check if response is JSON
  const contentType = response.headers.get('content-type');
  if (!contentType || !contentType.includes('application/json')) {
    throw new Error('Server returned non-JSON response. Check API URL.');
  }
  
  const data = await response.json();
  return { response, data };
}
```

### Step 2: Update InstructorRegistration.tsx

**❌ BROKEN VERSION:**
```tsx
const handleSubmit = async (e: React.FormEvent) => {
  e.preventDefault();
  setIsSubmitting(true);
  setError('');

  try {
    console.log('Submitting instructor registration...');
    
    const response = await fetch('/api/register/instructor', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(formData),
    });

    console.log('Response status:', response.status);
    const text = await response.text();
    console.log('Response text:', text);
    
    const data = text ? JSON.parse(text) : null;
    console.log('Response data:', data);
    
    // ... rest of code
  } catch (error) {
    // ... error handling
  }
};
```

**✅ FIXED VERSION:**
```tsx
import { API_BASE_URL, API_ENDPOINTS, apiCall } from '../config/api';

const handleSubmit = async (e: React.FormEvent) => {
  e.preventDefault();
  setIsSubmitting(true);
  setError('');

  try {
    console.log('Submitting instructor registration...');
    console.log('API URL:', `${API_BASE_URL}${API_ENDPOINTS.REGISTER_INSTRUCTOR}`);
    
    const { response, data } = await apiCall(API_ENDPOINTS.REGISTER_INSTRUCTOR, {
      method: 'POST',
      body: JSON.stringify(formData),
    });

    console.log('Response status:', response.status);
    console.log('Response data:', data);

    if (!response.ok) {
      throw new Error(data.message || 'Registration failed');
    }

    if (data.success) {
      console.log('Registration successful!');
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
  } catch (error: any) {
    console.error('Registration error:', error);
    setError(error.message || 'An error occurred during registration');
  } finally {
    setIsSubmitting(false);
  }
};
```

### Step 3: Update Login.tsx

**✅ FIXED LOGIN:**
```tsx
import { API_BASE_URL, API_ENDPOINTS, apiCall } from '../config/api';

const handleSubmit = async (e: React.FormEvent) => {
  e.preventDefault();
  setIsLoading(true);
  setError('');

  try {
    console.log('Attempting login...');
    console.log('API URL:', `${API_BASE_URL}${API_ENDPOINTS.LOGIN}`);

    const { response, data } = await apiCall(API_ENDPOINTS.LOGIN, {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    console.log('Login response:', data);

    if (data.success && data.token) {
      // Save token
      localStorage.setItem('token', data.token);
      localStorage.setItem('user', JSON.stringify(data.user));
      
      // Redirect based on role
      if (data.user.role === 'instructor') {
        navigate('/instructor/dashboard');
      } else {
        navigate('/learner/dashboard');
      }
    } else {
      setError(data.message || 'Login failed');
    }
  } catch (error: any) {
    console.error('Login error:', error);
    setError(error.message || 'Invalid email or password');
  } finally {
    setIsLoading(false);
  }
};
```

### Step 4: Update All Other API Calls

Search your codebase for these patterns and update:

**Find:**
```tsx
fetch('/api/...
fetch(`/api/...
```

**Replace with:**
```tsx
import { API_BASE_URL, API_ENDPOINTS, apiCall } from '../config/api';

// Use apiCall helper
const { response, data } = await apiCall(API_ENDPOINTS.SOME_ENDPOINT, options);
```

---

## 🧪 Testing Steps

### 1. Check Environment Variables
```bash
# Ensure .env file exists in frontend root
cat .env
# Should show: VITE_API_BASE_URL=http://localhost:8000
```

### 2. Verify API URL in Console
After making changes, open browser console and submit form. You should see:
```
API URL: http://localhost:8000/api/register/instructor
```

NOT:
```
API URL: http://localhost:5173/api/register/instructor  ← WRONG!
```

### 3. Check Network Tab
1. Open browser DevTools → Network tab
2. Submit registration form
3. Look for request to `register/instructor`
4. Check:
   - ✅ Request URL: `http://localhost:8000/api/register/instructor`
   - ✅ Status: 200 or 201
   - ✅ Response Type: `application/json`
   - ✅ Response contains `{"success": true, ...}`

### 4. Verify Database
```bash
# In Laravel backend
php artisan tinker
>>> \App\Models\User::latest()->first()
# Should show the newly registered user
```

---

## ✅ Verification Checklist

After implementing the fix:

- [ ] Created `.env` file with `VITE_API_BASE_URL`
- [ ] Created `src/config/api.ts` file
- [ ] Updated InstructorRegistration.tsx to use API_BASE_URL
- [ ] Updated Login.tsx to use API_BASE_URL
- [ ] Searched for all `fetch('/api/` and updated them
- [ ] Restarted Vite dev server (`npm run dev`)
- [ ] Console shows correct API URL (with :8000)
- [ ] Network tab shows JSON response, not HTML
- [ ] Registration creates user in database
- [ ] Login works and redirects correctly

---

## 🚨 Common Mistakes to Avoid

**❌ DON'T:**
```tsx
// Relative URL - goes to frontend server
fetch('/api/register/instructor')

// Hardcoded URL - not configurable
fetch('http://localhost:8000/api/register/instructor')

// Missing Content-Type header
fetch(url, { method: 'POST', body: JSON.stringify(data) })
```

**✅ DO:**
```tsx
// Use environment variable + config
import { API_BASE_URL, API_ENDPOINTS } from '../config/api';
fetch(`${API_BASE_URL}${API_ENDPOINTS.REGISTER_INSTRUCTOR}`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  body: JSON.stringify(data),
});
```

---

## 📝 Quick Summary

**Problem:** Frontend calls `/api/*` which goes to Vite (port 5173) instead of Laravel (port 8000)

**Solution:** Use full URL with environment variable

**Steps:**
1. Create `.env` with `VITE_API_BASE_URL=http://localhost:8000`
2. Create `src/config/api.ts` with base URL and endpoints
3. Update all fetch calls to use `${API_BASE_URL}${endpoint}`
4. Restart dev server
5. Test registration - should save to database

**Time:** 20-30 minutes to update all API calls

---

## 🎯 Priority: CRITICAL

This blocks all user registration and authentication. Fix immediately.

**Backend is working fine** - this is purely a frontend URL configuration issue.
