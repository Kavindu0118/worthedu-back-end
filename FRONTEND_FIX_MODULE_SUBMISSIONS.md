# 🔧 Frontend Fix Required: Module Submissions 405 Error

## 🐛 Issue
You're getting a **405 Method Not Allowed** error when trying to fetch module submissions because the frontend is calling the **wrong URL**.

### Current Incorrect Request:
```
GET http://localhost:5173/api/instructor/modules/5
```

### Problems:
1. ❌ **Wrong server** - `localhost:5173` is your Vite dev server, not the Laravel backend
2. ❌ **Missing suffix** - needs `/submissions` at the end
3. ❌ **Missing auth header** - needs Bearer token

---

## ✅ Solution: Fix the Frontend URL

### Option 1: Direct URL Fix (Quick Solution)

Update your `ModuleSubmissions.tsx` file (around line 44):

**CHANGE FROM:**
```typescript
const response = await fetch(`/api/instructor/modules/${moduleId}`);
```

**CHANGE TO:**
```typescript
const token = localStorage.getItem('api_token'); // or however you store your token

const response = await fetch(
  `http://localhost/learning-lms/public/api/instructor/modules/${moduleId}/submissions`,
  {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  }
);
```

### Option 2: Configure Vite Proxy (Better Solution)

**Step 1:** Update `vite.config.ts`:

```typescript
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://localhost/learning-lms/public',
        changeOrigin: true,
        secure: false
      }
    }
  }
})
```

**Step 2:** Update `ModuleSubmissions.tsx`:

```typescript
const token = localStorage.getItem('api_token');

const response = await fetch(`/api/instructor/modules/${moduleId}/submissions`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
});
```

**Step 3:** Restart your Vite dev server:
```bash
npm run dev
```

---

## 📝 Complete Code Example

Here's the complete fix for your `ModuleSubmissions.tsx` component:

```typescript
import { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';

interface AssignmentStats {
  assignment_id: number;
  assignment_title: string;
  max_points: number;
  due_date: string;
  total_submissions: number;
  graded_submissions: number;
  pending_submissions: number;
  average_marks: number | null;
}

export default function ModuleSubmissions() {
  const { moduleId } = useParams();
  const [assignments, setAssignments] = useState<AssignmentStats[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchModuleSubmissions();
  }, [moduleId]);

  const fetchModuleSubmissions = async () => {
    try {
      setLoading(true);
      setError(null);

      // Get the auth token
      const token = localStorage.getItem('api_token');
      
      if (!token) {
        setError('Not authenticated. Please log in.');
        setLoading(false);
        return;
      }

      // FIXED: Correct URL with /submissions suffix and proper base URL
      const response = await fetch(
        `http://localhost/learning-lms/public/api/instructor/modules/${moduleId}/submissions`,
        {
          method: 'GET',
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          }
        }
      );

      if (!response.ok) {
        if (response.status === 401) {
          throw new Error('Unauthorized. Please log in again.');
        }
        if (response.status === 404) {
          throw new Error('Module not found or has no assignments.');
        }
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const data = await response.json();

      if (data.success) {
        setAssignments(data.assignments);
      } else {
        setError(data.message || 'Failed to fetch submissions');
      }
    } catch (err) {
      console.error('Error fetching module submissions:', err);
      setError(err instanceof Error ? err.message : 'Failed to fetch submissions');
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <div>Loading...</div>;
  if (error) return <div className="error">{error}</div>;

  return (
    <div className="module-submissions">
      <h2>Module Submissions</h2>
      
      {assignments.length === 0 ? (
        <p>No assignments found for this module.</p>
      ) : (
        <div className="assignments-list">
          {assignments.map((assignment) => (
            <div key={assignment.assignment_id} className="assignment-card">
              <h3>{assignment.assignment_title}</h3>
              <p>Max Points: {assignment.max_points}</p>
              <p>Due Date: {new Date(assignment.due_date).toLocaleDateString()}</p>
              
              <div className="stats">
                <span>Total: {assignment.total_submissions}</span>
                <span>Graded: {assignment.graded_submissions}</span>
                <span>Pending: {assignment.pending_submissions}</span>
                {assignment.average_marks !== null && (
                  <span>Avg: {assignment.average_marks.toFixed(2)}</span>
                )}
              </div>
              
              <button 
                onClick={() => {
                  // Navigate to assignment submissions detail page
                  window.location.href = `/instructor/assignments/${assignment.assignment_id}/submissions`;
                }}
              >
                View Submissions
              </button>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
```

---

## 🧪 Testing

### 1. Check Your Token
```typescript
console.log('Token:', localStorage.getItem('api_token'));
```

### 2. Test the Endpoint Directly
Open browser console and run:
```javascript
fetch('http://localhost/learning-lms/public/api/instructor/modules/5/submissions', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN_HERE',
    'Accept': 'application/json'
  }
})
.then(r => r.json())
.then(console.log)
.catch(console.error);
```

### 3. Expected Response
```json
{
  "success": true,
  "module_id": 5,
  "assignments": [
    {
      "assignment_id": 1,
      "assignment_title": "Laravel Basics Assignment",
      "max_points": 100,
      "due_date": "2024-01-15 23:59:59",
      "total_submissions": 12,
      "graded_submissions": 7,
      "pending_submissions": 5,
      "average_marks": 78.5
    }
  ]
}
```

---

## ✅ Verification Checklist

After making the changes:

- [ ] No more 405 errors in browser console
- [ ] Network tab shows request to correct URL (`http://localhost/learning-lms/public/api/instructor/modules/X/submissions`)
- [ ] Request includes `Authorization: Bearer ...` header
- [ ] Response status is 200
- [ ] Assignments data displays correctly in UI

---

## 🔍 Backend Endpoints Reference

All these endpoints are **already implemented and working** on the backend:

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/instructor/modules/{moduleId}/submissions` | Get all submissions for a module |
| GET | `/api/instructor/assignments/{assignmentId}/submissions` | Get submissions for one assignment |
| GET | `/api/instructor/submissions/{submissionId}` | Get single submission details |
| PUT | `/api/instructor/submissions/{submissionId}/grade` | Grade a submission |
| GET | `/api/instructor/submissions/{submissionId}/download` | Download submission file |

**Base URL:** `http://localhost/learning-lms/public`  
**Auth:** Bearer token in `Authorization` header

---

## 💡 Common Mistakes to Avoid

1. ❌ Don't forget `/submissions` suffix: `/modules/5` → `/modules/5/submissions`
2. ❌ Don't forget the Authorization header
3. ❌ Don't use Vite dev server URL (`localhost:5173`) for API calls
4. ❌ Don't use `localhost:8000` - use `localhost/learning-lms/public`

---

## 🆘 Still Having Issues?

### Error: 401 Unauthorized
- Check your token is valid: `console.log(localStorage.getItem('api_token'))`
- Token might be expired - try logging in again

### Error: 404 Not Found
- Module might not exist in database
- Module might have no assignments
- Check `moduleId` value is correct

### Error: CORS
- If you see CORS errors, use the Vite proxy configuration (Option 2)

### Error: Network Failed
- Laravel backend might not be running
- Check WAMP/XAMPP is started
- Verify URL: `http://localhost/learning-lms/public/api/instructor/modules/5/submissions`

---

## 📞 Questions?

The backend is **fully implemented and tested**. The only issue is the frontend URL configuration. Follow the steps above and it will work immediately.

**Backend Status:** ✅ Working  
**Frontend Status:** ⚠️ Needs URL fix  
**Estimated Fix Time:** 5 minutes
