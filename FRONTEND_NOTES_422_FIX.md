# URGENT FIX: Notes API 422 Error - "The attachment failed to upload"

## Problem
Getting 422 validation error when adding notes/lessons:
```
Error: The attachment failed to upload.
```

## Root Causes & Solutions

### 1. Frontend is Catching Wrong Error (Most Likely)

**Problem:** Your frontend `courseApi.ts` (line 400) is throwing a generic error instead of showing the actual backend validation error.

**Fix courseApi.ts:**

```typescript
// WRONG - Generic error message
export async function addNote(moduleId: number, data: FormData) {
  const response = await api.post(
    `/api/instructor/modules/${moduleId}/notes`,
    data
  );
  
  if (!response.ok) {
    throw new Error('The attachment failed to upload.');  // ❌ Too generic!
  }
  
  return response.json();
}

// CORRECT - Show actual backend error
export async function addNote(moduleId: number, data: FormData) {
  try {
    const response = await api.post(
      `/api/instructor/modules/${moduleId}/notes`,
      data
    );
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error) && error.response) {
      // Show the actual backend validation errors
      const backendError = error.response.data;
      
      if (backendError.errors) {
        // Format validation errors nicely
        const errorMessages = Object.entries(backendError.errors)
          .map(([field, messages]) => `${field}: ${(messages as string[]).join(', ')}`)
          .join('\n');
        throw new Error(errorMessages);
      }
      
      throw new Error(backendError.message || 'Failed to add note');
    }
    throw error;
  }
}
```

### 2. Common Validation Issues

#### A. Missing Required Fields
**Backend expects:**
- `note_title` (string, required, max 255 chars)
- `note_body` (string, required)
- `attachment` (file, optional, max 100MB)

**Fix:** Ensure all fields are present in FormData:
```typescript
const formData = new FormData();
formData.append('note_title', title.trim());  // Don't send empty strings!
formData.append('note_body', body.trim());    // Don't send empty strings!
if (file) {
  formData.append('attachment', file);
}
```

#### B. File Size Too Large
**Backend limit:** 100MB (102400 KB)

**Fix:** Add client-side validation:
```typescript
const MAX_SIZE = 100 * 1024 * 1024; // 100MB in bytes

if (file && file.size > MAX_SIZE) {
  throw new Error(`File is too large (${(file.size / (1024*1024)).toFixed(2)}MB). Maximum is 100MB.`);
}
```

#### C. Invalid File Type
**Allowed types:** pdf, doc, docx, ppt, pptx, xls, xlsx, mp4, mov, avi, mkv, webm, zip, rar

**Fix:** Validate file extension:
```typescript
const allowedExtensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 
                          'mp4', 'mov', 'avi', 'mkv', 'webm', 'zip', 'rar'];

if (file) {
  const extension = file.name.split('.').pop()?.toLowerCase();
  if (!extension || !allowedExtensions.includes(extension)) {
    throw new Error(`Invalid file type. Allowed: ${allowedExtensions.join(', ')}`);
  }
}
```

#### D. Missing Authorization Header
**Fix:** Ensure token is included:
```typescript
const token = localStorage.getItem('authToken');
if (!token) {
  throw new Error('Not authenticated');
}

// With axios:
const response = await axios.post(url, formData, {
  headers: { Authorization: `Bearer ${token}` }
});

// With fetch:
const response = await fetch(url, {
  method: 'POST',
  headers: { Authorization: `Bearer ${token}` },  // Don't set Content-Type!
  body: formData
});
```

### 3. PHP Upload Limits (Server-Side)

If backend logs show file upload errors, check php.ini:

**Required settings:**
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
memory_limit = 256M
```

**How to fix:**
1. WAMP icon → PHP → php.ini
2. Update the values above
3. WAMP icon → Restart All Services

**Verify:**
```bash
php -r "echo ini_get('upload_max_filesize');"
# Should output: 100M
```

## Complete Working Example

### courseApi.ts (Fixed)

```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  timeout: 300000, // 5 minutes
});

// Auto-add auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

interface AddNoteParams {
  moduleId: number;
  noteTitle: string;
  noteBody: string;
  attachment?: File;
}

export async function addNote({ moduleId, noteTitle, noteBody, attachment }: AddNoteParams) {
  // Validate before sending
  if (!noteTitle.trim()) {
    throw new Error('Note title is required');
  }
  
  if (!noteBody.trim()) {
    throw new Error('Note body is required');
  }

  if (attachment) {
    // Check file size
    const maxSize = 100 * 1024 * 1024; // 100MB
    if (attachment.size > maxSize) {
      throw new Error(
        `File is too large (${(attachment.size / (1024 * 1024)).toFixed(2)}MB). Maximum is 100MB.`
      );
    }

    // Check file type
    const allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 
                        'mp4', 'mov', 'avi', 'mkv', 'webm', 'zip', 'rar'];
    const ext = attachment.name.split('.').pop()?.toLowerCase();
    if (!ext || !allowedExts.includes(ext)) {
      throw new Error(
        `Invalid file type (.${ext}). Allowed: ${allowedExts.join(', ')}`
      );
    }
  }

  // Build FormData
  const formData = new FormData();
  formData.append('note_title', noteTitle.trim());
  formData.append('note_body', noteBody.trim());
  if (attachment) {
    formData.append('attachment', attachment);
  }

  try {
    const response = await api.post(
      `/api/instructor/modules/${moduleId}/notes`,
      formData
    );
    return response.data;
  } catch (error) {
    if (axios.isAxiosError(error)) {
      // Handle validation errors from backend
      if (error.response?.status === 422 && error.response.data.errors) {
        const errors = error.response.data.errors;
        const errorMessages = Object.entries(errors)
          .map(([field, messages]) => {
            const messageArray = messages as string[];
            return `${field}: ${messageArray.join(', ')}`;
          })
          .join('\n');
        throw new Error(errorMessages);
      }
      
      // Handle other errors
      throw new Error(
        error.response?.data?.message || 
        error.message || 
        'Failed to add note'
      );
    }
    throw error;
  }
}
```

### React Component (Fixed)

```tsx
import { useState } from 'react';
import { addNote } from '@/api/courseApi';

export function AddNoteForm({ moduleId }: { moduleId: number }) {
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    // Basic validation
    if (!title.trim()) {
      setError('Please enter a title');
      return;
    }

    if (!body.trim()) {
      setError('Please enter a description');
      return;
    }

    try {
      setUploading(true);
      
      await addNote({
        moduleId,
        noteTitle: title,
        noteBody: body,
        attachment: file || undefined,
      });

      // Success
      alert('Note added successfully!');
      setTitle('');
      setBody('');
      setFile(null);
      
      // Refresh notes list here...
    } catch (err) {
      console.error('❌ Add note error:', err);
      setError(err instanceof Error ? err.message : 'Failed to add note');
    } finally {
      setUploading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div>
        <label>Title *</label>
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          maxLength={255}
          required
          disabled={uploading}
          placeholder="e.g., Week 1 Lecture"
        />
      </div>

      <div>
        <label>Description *</label>
        <textarea
          value={body}
          onChange={(e) => setBody(e.target.value)}
          required
          disabled={uploading}
          rows={5}
          placeholder="Provide details..."
        />
      </div>

      <div>
        <label>Attachment (Optional, max 100MB)</label>
        <input
          type="file"
          onChange={(e) => setFile(e.target.files?.[0] || null)}
          accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.mp4,.mov,.avi,.mkv,.webm,.zip,.rar"
          disabled={uploading}
        />
        {file && (
          <p className="text-sm text-gray-600">
            {file.name} ({(file.size / (1024 * 1024)).toFixed(2)} MB)
          </p>
        )}
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 p-3 rounded">
          <strong>Error:</strong>
          <pre className="whitespace-pre-wrap text-sm mt-1">{error}</pre>
        </div>
      )}

      <button
        type="submit"
        disabled={uploading || !title.trim() || !body.trim()}
        className="btn-primary"
      >
        {uploading ? 'Uploading...' : 'Add Note'}
      </button>
    </form>
  );
}
```

## Debugging Steps

### 1. Check Network Tab
Open DevTools → Network → Try adding a note

**Look for:**
- Request URL: `http://127.0.0.1:8000/api/instructor/modules/2/notes`
- Method: POST
- Status: 422

**Click the request and check:**
- **Request Headers:** Must have `Authorization: Bearer ...`
- **Request Payload:** Check FormData contains `note_title`, `note_body`, and optional `attachment`
- **Response:** Shows the actual backend error

### 2. Check Backend Logs
Backend now has detailed logging. Check:
```bash
Get-Content storage\logs\laravel.log -Tail 50
```

Look for:
```
[INFO] === Note Store Request ===
```

This will show:
- Module ID
- Note title
- Whether attachment exists
- File size and MIME type
- Validation errors (if any)

### 3. Test API Directly

**Without file:**
```bash
curl -X POST http://127.0.0.1:8000/api/instructor/modules/2/notes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "note_title=Test Note" \
  -F "note_body=This is a test"
```

**With file:**
```bash
curl -X POST http://127.0.0.1:8000/api/instructor/modules/2/notes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "note_title=Test with File" \
  -F "note_body=Testing file upload" \
  -F "attachment=@C:\path\to\file.pdf"
```

## Quick Checklist

- [ ] Frontend: Updated `courseApi.ts` to show actual backend errors (not generic message)
- [ ] Frontend: Validating file size < 100MB before upload
- [ ] Frontend: Validating file extension is in allowed list
- [ ] Frontend: Sending `note_title` and `note_body` as non-empty strings
- [ ] Frontend: Including `Authorization: Bearer <token>` header
- [ ] Frontend: NOT setting `Content-Type` header (let browser handle FormData)
- [ ] Server: php.ini has `upload_max_filesize = 100M`
- [ ] Server: php.ini has `post_max_size = 100M`
- [ ] Server: WAMP services restarted after php.ini changes
- [ ] Test: Can add note without file
- [ ] Test: Can add note with PDF file (< 10MB)
- [ ] Test: Error shows clearly when file too large
- [ ] Test: Error shows clearly when wrong file type

## Expected Backend Response

**Success (201):**
```json
{
  "message": "Note added successfully",
  "note": {
    "id": 1,
    "module_id": 2,
    "note_title": "Week 1 Lecture",
    "note_body": "Introduction to...",
    "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/1734207890_abc.pdf",
    "created_at": "2025-12-14T22:31:30.000000Z"
  }
}
```

**Validation Error (422):**
```json
{
  "message": "Validation error",
  "errors": {
    "note_title": ["The note title field is required."],
    "attachment": ["The file must not be larger than 100MB."]
  }
}
```

## Summary

**Most likely issue:** Frontend is showing a generic error instead of the actual backend validation error.

**Fix:** Update `courseApi.ts` line ~400 to parse and display `error.response.data.errors` from the backend.

**After fix:** You'll see the real error message (e.g., "file too large", "invalid type", "title required") which will make debugging much easier.
