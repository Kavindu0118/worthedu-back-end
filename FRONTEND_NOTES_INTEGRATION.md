# Frontend: Notes API Integration Guide

## Problem Solved ✅
The CORS error when adding notes has been fixed. The backend was redirecting due to a null check bug, which is now resolved.

## API Endpoint

**POST** `/api/instructor/modules/{moduleId}/notes`

Add learning materials (PDFs, videos, documents) to a course module.

## Request

**Method:** POST  
**Content-Type:** multipart/form-data  
**Authentication:** Required

### Headers
```
Authorization: Bearer YOUR_AUTH_TOKEN
```

### Body (FormData)
| Field | Type | Required | Max Size | Description |
|-------|------|----------|----------|-------------|
| `note_title` | string | Yes | 255 chars | Title of the learning material |
| `note_body` | string | Yes | - | Description/instructions |
| `attachment` | file | No | 100MB | PDF, video, document, or archive |

### Supported File Types
- **Documents:** PDF, DOC, DOCX
- **Presentations:** PPT, PPTX  
- **Spreadsheets:** XLS, XLSX
- **Videos:** MP4, MOV, AVI, MKV, WEBM
- **Archives:** ZIP, RAR

## Response

### Success (201)
```json
{
  "message": "Note added successfully",
  "note": {
    "id": 1,
    "module_id": 2,
    "note_title": "Week 1: Introduction Video",
    "note_body": "Watch this video to understand the basics...",
    "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/1734206730_abc123.mp4",
    "created_at": "2025-12-14T22:05:30.000000Z",
    "updated_at": "2025-12-14T22:05:30.000000Z"
  }
}
```

### Errors
| Code | Meaning | Action |
|------|---------|--------|
| 401 | No auth token or invalid | Redirect to login |
| 403 | Not an instructor or wrong course | Show "Access denied" |
| 404 | Module not found | Check module ID |
| 422 | Invalid file type/size | Show validation errors |

## Implementation

### Option 1: Axios (Recommended)

```typescript
// src/api/instructorApi.ts
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  timeout: 300000, // 5 minutes for large uploads
});

// Auto-attach auth token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export async function addNoteToModule(
  moduleId: number,
  title: string,
  body: string,
  file?: File
) {
  const formData = new FormData();
  formData.append('note_title', title);
  formData.append('note_body', body);
  if (file) formData.append('attachment', file);

  const { data } = await api.post(
    `/api/instructor/modules/${moduleId}/notes`,
    formData
  );
  return data;
}
```

### Option 2: Fetch

```typescript
export async function addNoteToModule(
  moduleId: number,
  title: string,
  body: string,
  file?: File
) {
  const token = localStorage.getItem('authToken');
  
  const formData = new FormData();
  formData.append('note_title', title);
  formData.append('note_body', body);
  if (file) formData.append('attachment', file);

  const response = await fetch(
    `http://127.0.0.1:8000/api/instructor/modules/${moduleId}/notes`,
    {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${token}`,
        // DO NOT set Content-Type - browser handles it for FormData
      },
      body: formData,
    }
  );

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to add note');
  }

  return response.json();
}
```

## React Component Example

```tsx
import { useState } from 'react';
import { addNoteToModule } from '@/api/instructorApi';

export function AddNoteForm({ moduleId }: { moduleId: number }) {
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [error, setError] = useState('');

  const validateFile = (file: File): string | null => {
    const maxSize = 100 * 1024 * 1024; // 100MB
    if (file.size > maxSize) {
      return 'File must be less than 100MB';
    }

    const allowedExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 
                         'mp4', 'mov', 'avi', 'mkv', 'webm', 'zip', 'rar'];
    const ext = file.name.split('.').pop()?.toLowerCase();
    if (!ext || !allowedExts.includes(ext)) {
      return 'Invalid file type. Allowed: PDF, DOC, PPT, XLS, videos, archives';
    }

    return null;
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const selectedFile = e.target.files?.[0];
    if (!selectedFile) {
      setFile(null);
      setError('');
      return;
    }

    const validationError = validateFile(selectedFile);
    if (validationError) {
      setError(validationError);
      setFile(null);
      e.target.value = ''; // Clear input
    } else {
      setFile(selectedFile);
      setError('');
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!title.trim() || !body.trim()) {
      setError('Title and description are required');
      return;
    }

    try {
      setUploading(true);
      setError('');

      await addNoteToModule(moduleId, title, body, file || undefined);

      // Success - reset form
      setTitle('');
      setBody('');
      setFile(null);
      alert('Learning material added successfully!');
      
      // Optionally refresh the notes list here
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to add note');
    } finally {
      setUploading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-4">
      <div>
        <label className="block font-medium mb-1">
          Title <span className="text-red-500">*</span>
        </label>
        <input
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          placeholder="e.g., Week 1 Lecture Video"
          maxLength={255}
          required
          disabled={uploading}
          className="w-full border rounded px-3 py-2"
        />
      </div>

      <div>
        <label className="block font-medium mb-1">
          Description <span className="text-red-500">*</span>
        </label>
        <textarea
          value={body}
          onChange={(e) => setBody(e.target.value)}
          placeholder="Provide details about this learning material..."
          rows={5}
          required
          disabled={uploading}
          className="w-full border rounded px-3 py-2"
        />
      </div>

      <div>
        <label className="block font-medium mb-1">
          Attachment <span className="text-gray-500">(Optional, max 100MB)</span>
        </label>
        <input
          type="file"
          onChange={handleFileChange}
          accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.mp4,.mov,.avi,.mkv,.webm,.zip,.rar"
          disabled={uploading}
          className="w-full"
        />
        {file && (
          <p className="text-sm text-gray-600 mt-1">
            Selected: {file.name} ({(file.size / (1024 * 1024)).toFixed(2)} MB)
          </p>
        )}
        <p className="text-xs text-gray-500 mt-1">
          Supported: PDF, DOC, PPT, XLS, Videos (MP4, MOV, etc.), ZIP, RAR
        </p>
      </div>

      {error && (
        <div className="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
      )}

      <button
        type="submit"
        disabled={uploading || !title.trim() || !body.trim()}
        className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
      >
        {uploading ? 'Uploading...' : 'Add Learning Material'}
      </button>
    </form>
  );
}
```

## With Upload Progress

For large video uploads, show progress to users:

```typescript
import axios from 'axios';

export async function addNoteWithProgress(
  moduleId: number,
  title: string,
  body: string,
  file: File | undefined,
  onProgress: (percent: number) => void
) {
  const token = localStorage.getItem('authToken');
  const formData = new FormData();
  formData.append('note_title', title);
  formData.append('note_body', body);
  if (file) formData.append('attachment', file);

  const { data } = await axios.post(
    `http://127.0.0.1:8000/api/instructor/modules/${moduleId}/notes`,
    formData,
    {
      headers: { Authorization: `Bearer ${token}` },
      onUploadProgress: (progressEvent) => {
        const percent = Math.round(
          (progressEvent.loaded * 100) / (progressEvent.total || 100)
        );
        onProgress(percent);
      },
    }
  );

  return data;
}
```

Then in your component:
```tsx
const [uploadProgress, setUploadProgress] = useState(0);

// In handleSubmit:
await addNoteWithProgress(moduleId, title, body, file, setUploadProgress);

// In JSX:
{uploading && uploadProgress > 0 && (
  <div className="w-full bg-gray-200 rounded-full h-2.5">
    <div
      className="bg-blue-600 h-2.5 rounded-full transition-all"
      style={{ width: `${uploadProgress}%` }}
    />
  </div>
)}
```

## Update & Delete Endpoints

### Update Note
**PUT** `/api/instructor/notes/{noteId}`

Same FormData fields as POST. Use PATCH or PUT with `_method` field if needed:
```typescript
formData.append('_method', 'PUT');
```

### Delete Note  
**DELETE** `/api/instructor/notes/{noteId}`

```typescript
await api.delete(`/api/instructor/notes/${noteId}`, {
  headers: { Authorization: `Bearer ${token}` }
});
```

## Testing Checklist

- [ ] Can add note with title + body (no file)
- [ ] Can add note with PDF attachment (< 10MB)
- [ ] Can add note with large video (< 100MB)
- [ ] File type validation works (try .exe - should fail)
- [ ] File size validation works (try > 100MB - should fail)
- [ ] Upload progress shows for large files
- [ ] Error messages display correctly
- [ ] Success message shows after upload
- [ ] Attachment URL is accessible (can download/view)
- [ ] Can update existing note
- [ ] Can delete note
- [ ] Works on mobile devices

## Troubleshooting

### "401 Unauthorized"
**Cause:** Missing or invalid auth token  
**Fix:** Verify `Authorization: Bearer ${token}` header is present

### "413 Payload Too Large"  
**Cause:** File > 100MB or server limit  
**Fix:** Check file size before upload, contact backend team if needed

### "422 Validation Error"
**Cause:** Invalid file type or missing required fields  
**Fix:** Check file extension and ensure title/body are filled

### Upload timeout
**Cause:** Large file + slow connection  
**Fix:** Increase axios timeout to 300000ms (5 min) as shown above

### File not accessible after upload
**Cause:** Storage link not created or wrong URL  
**Fix:** Backend issue - contact backend team

## Notes for Backend Team

If you see this error, the backend issue is already fixed. Just ensure:
1. `php.ini` updated: `upload_max_filesize = 100M`, `post_max_size = 100M`
2. WAMP restarted after php.ini changes
3. Storage link exists: `php artisan storage:link`

## Quick Test

Copy-paste in browser console (after login):

```javascript
const token = localStorage.getItem('authToken');
const formData = new FormData();
formData.append('note_title', 'Test Note');
formData.append('note_body', 'This is a test');

fetch('http://127.0.0.1:8000/api/instructor/modules/2/notes', {
  method: 'POST',
  headers: { Authorization: `Bearer ${token}` },
  body: formData
})
  .then(r => r.json())
  .then(data => console.log('✅ Success:', data))
  .catch(err => console.error('❌ Error:', err));
```

Replace `2` with your actual module ID. If you see `✅ Success`, the API is working!
