# Frontend Fix: Instructor Registration - FormData Implementation

## 🚨 Root Cause Identified

The frontend shows "success" but data isn't saved because:

1. **Wrong Endpoint:** Frontend → `/api/register/instructor` | Backend → `/api/register-instructor` ❌
2. **Wrong Format:** Frontend sends JSON | Backend expects FormData with file ❌  
3. **Missing Field:** Backend requires CV file (PDF) but frontend doesn't send it ❌

## Backend Requirements

**Endpoint:** `POST http://localhost:8000/api/register-instructor`

**Content-Type:** `multipart/form-data` (NOT `application/json`)

**Required Fields:**
- `first_name` (string)
- `last_name` (string)
- `username` (string, unique)
- `email` (string, unique)
- `password` (string, min 6 chars)
- `date_of_birth` (YYYY-MM-DD)
- `address` (string)
- `mobile_number` (string)
- `highest_qualification` (none|certificate|diploma|degree)
- `subject_area` (string)
- `cv` (PDF file, max 2MB) **← REQUIRED FILE UPLOAD**

---

## Complete Fix for InstructorRegistration.tsx

### Step 1: Update State

```tsx
const [formData, setFormData] = useState({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  password: '',
  date_of_birth: '',
  address: '',
  mobile_number: '',
  highest_qualification: '',
  subject_area: '',
});

const [cvFile, setCvFile] = useState<File | null>(null);
const [isSubmitting, setIsSubmitting] = useState(false);
const [error, setError] = useState('');
const [success, setSuccess] = useState('');
```

### Step 2: Add File Handler

```tsx
const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
  const file = e.target.files?.[0];
  
  if (file) {
    if (file.type !== 'application/pdf') {
      setError('CV must be a PDF file');
      e.target.value = '';
      return;
    }
    
    if (file.size > 2097152) { // 2MB
      setError('CV file must be less than 2MB');
      e.target.value = '';
      return;
    }
    
    setCvFile(file);
    setError('');
  }
};
```

### Step 3: Update Submit Handler

**REPLACE your entire handleSubmit function with:**

```tsx
const handleSubmit = async (e: React.FormEvent) => {
  e.preventDefault();
  setIsSubmitting(true);
  setError('');
  setSuccess('');

  if (!cvFile) {
    setError('Please select a CV file');
    setIsSubmitting(false);
    return;
  }

  try {
    // Create FormData (NOT JSON)
    const formDataToSend = new FormData();
    formDataToSend.append('first_name', formData.first_name);
    formDataToSend.append('last_name', formData.last_name);
    formDataToSend.append('username', formData.username);
    formDataToSend.append('email', formData.email);
    formDataToSend.append('password', formData.password);
    formDataToSend.append('date_of_birth', formData.date_of_birth);
    formDataToSend.append('address', formData.address);
    formDataToSend.append('mobile_number', formData.mobile_number);
    formDataToSend.append('highest_qualification', formData.highest_qualification);
    formDataToSend.append('subject_area', formData.subject_area);
    formDataToSend.append('cv', cvFile);

    const response = await fetch('http://localhost:8000/api/register-instructor', {
      method: 'POST',
      body: formDataToSend,
      // DO NOT set Content-Type - browser sets it automatically with boundary
    });

    const contentType = response.headers.get('content-type');
    if (!contentType?.includes('application/json')) {
      throw new Error('Server returned non-JSON response');
    }

    const data = await response.json();

    if (response.ok) {
      setSuccess(data.message || 'Registration successful!');
      
      // Clear form
      setFormData({
        first_name: '', last_name: '', username: '', email: '',
        password: '', date_of_birth: '', address: '', mobile_number: '',
        highest_qualification: '', subject_area: '',
      });
      setCvFile(null);
      
      (document.getElementById('cv') as HTMLInputElement).value = '';

      setTimeout(() => window.location.href = '/login', 2000);
    } else {
      setError(data.message || Object.values(data.errors || {}).flat().join(', '));
    }
  } catch (error: any) {
    setError(error.message || 'Registration failed');
  } finally {
    setIsSubmitting(false);
  }
};
```

### Step 4: Add File Input to Form

```tsx
<div className="form-group">
  <label htmlFor="cv">CV (PDF only, max 2MB) *</label>
  <input
    type="file"
    id="cv"
    accept=".pdf"
    onChange={handleFileChange}
    required
  />
  {cvFile && (
    <small className="text-muted">
      {cvFile.name} ({(cvFile.size / 1024).toFixed(2)} KB)
    </small>
  )}
</div>
```

### Step 5: Ensure All Form Fields Match Backend

Your form must have these fields:
- `first_name` and `last_name` (NOT just `name`)
- `username` (separate from name)
- `email`
- `password`
- `date_of_birth` (date input)
- `address` (textarea)
- `mobile_number`
- `highest_qualification` (select: none/certificate/diploma/degree)
- `subject_area` (text input)
- `cv` (file input)

---

## Testing

### 1. Test Backend Directly
Open `test_instructor_registration.html` in your browser and test:
```
http://localhost:8000/test_instructor_registration.html
```

Fill the form and submit. You should see JSON response and data in database.

### 2. Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

Should show:
```
Instructor Registration Request
{
  "has_cv_file": true,
  "content_type": "multipart/form-data; boundary=..."
}
```

### 3. Verify Database
```bash
php artisan tinker
>>> \App\Models\User::where('role', 'instructor')->latest()->first()
>>> \App\Models\Instructor::latest()->first()
```

### 4. Check React Console
Should see:
```
API URL: http://localhost:8000/api/register-instructor
FormData contents: first_name: John, last_name: Doe, cv: resume.pdf (125648 bytes)
Response status: 201
```

---

## Key Differences: Before vs After

| Before (BROKEN) | After (FIXED) |
|-----------------|---------------|
| `/api/register/instructor` | `/api/register-instructor` |
| `JSON.stringify()` | `new FormData()` |
| `Content-Type: application/json` | No Content-Type header |
| No file upload | Required CV file upload |
| Fields: name, email, password, qualifications, bio, expertise | Fields: first_name, last_name, username, email, password, date_of_birth, address, mobile_number, highest_qualification, subject_area, cv |

---

## Why It Appeared to Work

The frontend showed "success" because:
1. It was hitting Vite dev server (localhost:5173) which returned 200 with HTML
2. Frontend didn't validate the response was JSON
3. Any 200 response triggered "success" message

The fix ensures:
✅ Correct backend endpoint (with :8000)
✅ Validates JSON response
✅ Sends FormData with file
✅ Matches backend validation rules

---

## Priority: CRITICAL
Registration is completely broken. This fix is required for any instructor signups.

**Time: 30-45 minutes**
