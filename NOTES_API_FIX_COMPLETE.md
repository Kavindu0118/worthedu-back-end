# Notes API - Complete Fix Documentation

## Backend Changes Completed ✅

### 1. Fixed Instructor Relationship Bug
**Issue:** Controller was accessing `$instructor->instructor_id` without null check, causing redirect when instructor profile was missing.

**Fixed in:** `app/Http/Controllers/NoteController.php`
```php
// Now checks if instructor exists before accessing properties
if (!$instructor || $course->instructor_id !== $instructor->instructor_id) {
    return response()->json(['message' => 'You can only add notes to your own courses'], 403);
}
```

### 2. Increased File Upload Limits
- **Max file size:** 100MB (was 10MB)
- **Supported formats:** PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, MP4, MOV, AVI, MKV, WEBM, ZIP, RAR
- **Execution time:** 300 seconds

### 3. Better File Handling
- Unique filenames (timestamp + uniqid)
- MIME type validation
- Proper old file deletion on update

### 4. Storage Directory Created
- Directory: `storage/app/public/course-attachments`
- Symbolic link verified

## API Endpoint

**POST** `/api/instructor/modules/{moduleId}/notes`

**Headers:**
```
Authorization: Bearer <YOUR_TOKEN>
Content-Type: multipart/form-data (auto-set by browser)
```

**Body (FormData):**
- `note_title` (required, string, max 255)
- `note_body` (required, string)
- `attachment` (optional, file, max 100MB)

**Response 201:**
```json
{
  "message": "Note added successfully",
  "note": {
    "id": 1,
    "module_id": 2,
    "note_title": "Week 1 Lecture",
    "note_body": "Introduction to...",
    "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/1734206730_abc123.pdf",
    "created_at": "2025-12-14T22:05:30.000000Z"
  }
}
```

## Frontend Implementation Guide

### React Example with File Upload Progress

```typescript
import { useState } from 'react';
import axios from 'axios';

const apiClient = axios.create({
  baseURL: 'http://127.0.0.1:8000',
  timeout: 300000, // 5 minutes for large uploads
});

// Auto-add auth to all requests
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem('authToken');
  if (token) config.headers.Authorization = `Bearer ${token}`;
  return config;
});

export async function addNote(moduleId: number, formData: FormData) {
  const { data } = await apiClient.post(
    `/api/instructor/modules/${moduleId}/notes`,
    formData
  );
  return data;
}

function AddNoteForm({ moduleId }: { moduleId: number }) {
  const [title, setTitle] = useState('');
  const [body, setBody] = useState('');
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [progress, setProgress] = useState(0);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setUploading(true);

    const formData = new FormData();
    formData.append('note_title', title);
    formData.append('note_body', body);
    if (file) formData.append('attachment', file);

    try {
      await axios.post(
        `http://127.0.0.1:8000/api/instructor/modules/${moduleId}/notes`,
        formData,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('authToken')}`,
          },
          onUploadProgress: (e) => {
            setProgress(Math.round((e.loaded * 100) / e.total!));
          },
        }
      );
      alert('Note added successfully!');
      setTitle('');
      setBody('');
      setFile(null);
    } catch (error) {
      alert('Failed to add note: ' + (error as any).response?.data?.message);
    } finally {
      setUploading(false);
      setProgress(0);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <input
        placeholder="Note Title"
        value={title}
        onChange={(e) => setTitle(e.target.value)}
        required
      />
      <textarea
        placeholder="Note Content"
        value={body}
        onChange={(e) => setBody(e.target.value)}
        required
      />
      <input
        type="file"
        accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.mov,.avi,.mkv,.webm,.zip"
        onChange={(e) => setFile(e.target.files?.[0] || null)}
      />
      {uploading && <progress value={progress} max={100}>{progress}%</progress>}
      <button type="submit" disabled={uploading}>
        {uploading ? 'Uploading...' : 'Add Note'}
      </button>
    </form>
  );
}
```

### Vanilla JavaScript / Fetch

```javascript
async function addNote(moduleId, title, body, file) {
  const token = localStorage.getItem('authToken');
  const formData = new FormData();
  formData.append('note_title', title);
  formData.append('note_body', body);
  if (file) formData.append('attachment', file);

  const response = await fetch(
    `http://127.0.0.1:8000/api/instructor/modules/${moduleId}/notes`,
    {
      method: 'POST',
      headers: { Authorization: `Bearer ${token}` },
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

## WAMP Configuration (Critical)

The `.htaccess` file won't override WAMP's PHP settings. Update `php.ini`:

**Location:** WAMP tray icon → PHP → php.ini

**Add/Update:**
```ini
upload_max_filesize = 100M
post_max_size = 100M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

**Restart:** WAMP → Restart All Services

**Verify:**
```bash
php -r "echo ini_get('upload_max_filesize');"
# Should output: 100M
```

## Testing

### curl Test:
```bash
curl -X POST http://127.0.0.1:8000/api/instructor/modules/2/notes \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "note_title=Test Video" \
  -F "note_body=Test description" \
  -F "attachment=@video.mp4"
```

### Browser Console Test:
```javascript
const token = localStorage.getItem('authToken');
const form = new FormData();
form.append('note_title', 'Test');
form.append('note_body', 'Body');

fetch('http://127.0.0.1:8000/api/instructor/modules/2/notes', {
  method: 'POST',
  headers: { Authorization: `Bearer ${token}` },
  body: form
}).then(r => r.json()).then(console.log);
```

## Checklist

- [x] Backend: Fixed instructor null check
- [x] Backend: Increased file size to 100MB
- [x] Backend: Added MIME validation
- [x] Backend: Created storage directory
- [ ] WAMP: Update php.ini with 100MB limits
- [ ] WAMP: Restart services
- [ ] Frontend: Add Authorization header to notes API
- [ ] Frontend: Implement file upload with progress
- [ ] Frontend: Add file type/size validation
- [ ] Test: Upload PDF (< 100MB)
- [ ] Test: Upload video (< 100MB)
- [ ] Test: Verify file accessible via URL

## Troubleshooting

**401 Unauthorized:**
- Verify `Authorization: Bearer <token>` header is present
- Check token is valid: `curl http://127.0.0.1:8000/api/me -H "Authorization: Bearer TOKEN"`

**413 Payload Too Large:**
- Update php.ini as shown above
- Restart WAMP

**422 Validation Error:**
- Check file extension is in allowed list
- Verify file size < 100MB

**Redirect to localhost:5173:**
- This was the original bug - now fixed
- Ensure using latest NoteController.php code
