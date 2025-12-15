# Frontend: Learner Notes Access - Implementation Guide

## Overview
Learners can now access all notes (learning materials) added by instructors for each module/lesson. Notes are automatically included in the API responses for both lesson details and course overview.

## API Endpoints

### 1. Get Lesson/Module Details (with Notes)
**GET** `/api/learner/lessons/{lessonId}`

Returns complete lesson information including all associated notes.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "course_id": 5,
    "course_title": "CSS Basics",
    "title": "Introduction to CSS",
    "description": "Learn the fundamentals of CSS...",
    "type": "reading",
    "content_url": "https://example.com/video.mp4",
    "content_text": "CSS stands for...",
    "duration": "45 minutes",
    "duration_minutes": 45,
    "order_index": 1,
    "is_mandatory": true,
    "notes": [
      {
        "id": 1,
        "title": "Week 1 Lecture Slides",
        "body": "These slides cover the basics of CSS selectors...",
        "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/1734207890_abc123.pdf",
        "attachment_name": "1734207890_abc123.pdf",
        "created_at": "2025-12-14T17:30:00.000000Z"
      },
      {
        "id": 2,
        "title": "Video Tutorial",
        "body": "Watch this video to see CSS in action",
        "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/1734208000_xyz789.mp4",
        "attachment_name": "1734208000_xyz789.mp4",
        "created_at": "2025-12-14T18:00:00.000000Z"
      }
    ],
    "progress": {
      "status": "in_progress",
      "started_at": "2025-12-14T10:00:00.000000Z",
      "completed_at": null,
      "time_spent_minutes": 15,
      "last_position": null
    },
    "navigation": {
      "next_module": {
        "id": 2,
        "title": "CSS Selectors"
      },
      "previous_module": null
    }
  }
}
```

### 2. Get Course Details (with Notes in Modules)
**GET** `/api/learner/courses/{courseId}`

Returns full course structure with modules, lessons, and notes.

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 5,
    "title": "CSS Basics",
    "description": "Learn CSS from scratch",
    "thumbnail": "http://127.0.0.1:8000/storage/thumbnails/course.jpg",
    "category": "Web Development",
    "level": "beginner",
    "duration": "10 hours",
    "modules": [
      {
        "id": 1,
        "title": "Introduction to CSS",
        "description": "Get started with CSS",
        "order": 1,
        "duration": "2 hours",
        "notes": [
          {
            "id": 1,
            "title": "Introduction Materials",
            "body": "Download these materials before starting...",
            "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/file.pdf",
            "attachment_name": "file.pdf",
            "created_at": "2025-12-14T17:30:00.000000Z"
          }
        ],
        "lessons": [
          {
            "id": 1,
            "title": "What is CSS?",
            "contentType": "video",
            "contentUrl": "https://example.com/video.mp4",
            "duration": "15 minutes",
            "order": 1,
            "progress": {
              "status": "completed",
              "progress_percentage": 100,
              "completed_at": "2025-12-13T16:00:00.000000Z"
            }
          }
        ]
      }
    ],
    "totalLessons": 8,
    "completedLessons": 3,
    "enrollment": {
      "id": 42,
      "status": "active",
      "progress": 37.5,
      "enrolled_at": "2025-12-01T10:00:00.000000Z"
    }
  }
}
```

## UI Implementation

### Lesson Detail Page - Display Notes Section

```tsx
import { useState, useEffect } from 'react';
import { Download, FileText, Video, Archive } from 'lucide-react';

interface Note {
  id: number;
  title: string;
  body: string;
  attachment_url: string | null;
  attachment_name: string | null;
  created_at: string;
}

interface LessonData {
  id: number;
  title: string;
  description: string;
  notes: Note[];
  // ... other fields
}

export function LessonDetailPage({ lessonId }: { lessonId: number }) {
  const [lesson, setLesson] = useState<LessonData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchLesson();
  }, [lessonId]);

  const fetchLesson = async () => {
    const token = localStorage.getItem('authToken');
    const response = await fetch(
      `http://127.0.0.1:8000/api/learner/lessons/${lessonId}`,
      {
        headers: { Authorization: `Bearer ${token}` }
      }
    );
    const result = await response.json();
    setLesson(result.data);
    setLoading(false);
  };

  const getFileIcon = (filename: string | null) => {
    if (!filename) return <FileText className="w-5 h-5" />;
    
    const ext = filename.split('.').pop()?.toLowerCase();
    if (['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(ext || '')) {
      return <Video className="w-5 h-5" />;
    }
    if (['zip', 'rar'].includes(ext || '')) {
      return <Archive className="w-5 h-5" />;
    }
    return <FileText className="w-5 h-5" />;
  };

  const getFileSize = async (url: string) => {
    try {
      const response = await fetch(url, { method: 'HEAD' });
      const size = response.headers.get('content-length');
      return size ? formatFileSize(parseInt(size)) : null;
    } catch {
      return null;
    }
  };

  const formatFileSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
  };

  if (loading) return <div>Loading...</div>;
  if (!lesson) return <div>Lesson not found</div>;

  return (
    <div className="lesson-detail">
      <h1>{lesson.title}</h1>
      <p>{lesson.description}</p>

      {/* Main lesson content here */}
      {/* ... video player, text content, etc. ... */}

      {/* Learning Materials / Notes Section */}
      {lesson.notes.length > 0 && (
        <div className="learning-materials mt-8">
          <h2 className="text-2xl font-bold mb-4">
            📚 Learning Materials
          </h2>
          <p className="text-gray-600 mb-4">
            Additional resources provided by your instructor
          </p>

          <div className="space-y-4">
            {lesson.notes.map((note) => (
              <div
                key={note.id}
                className="border rounded-lg p-4 bg-white shadow-sm hover:shadow-md transition"
              >
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold mb-2">
                      {note.title}
                    </h3>
                    <p className="text-gray-700 mb-3 whitespace-pre-wrap">
                      {note.body}
                    </p>
                    
                    {note.attachment_url && (
                      <a
                        href={note.attachment_url}
                        download
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                      >
                        {getFileIcon(note.attachment_name)}
                        <span>Download {note.attachment_name}</span>
                        <Download className="w-4 h-4" />
                      </a>
                    )}
                  </div>
                  
                  <div className="text-sm text-gray-500 ml-4">
                    {new Date(note.created_at).toLocaleDateString()}
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
```

### Course Overview - Notes in Module Cards

```tsx
export function CourseModuleCard({ module }: { module: Module }) {
  const [expanded, setExpanded] = useState(false);

  return (
    <div className="module-card border rounded-lg p-4 mb-4">
      <div 
        className="flex items-center justify-between cursor-pointer"
        onClick={() => setExpanded(!expanded)}
      >
        <div>
          <h3 className="text-xl font-bold">{module.title}</h3>
          <p className="text-gray-600">{module.description}</p>
        </div>
        <div className="text-sm text-gray-500">
          {module.notes.length > 0 && (
            <span className="inline-flex items-center gap-1 mr-4">
              <FileText className="w-4 h-4" />
              {module.notes.length} material{module.notes.length !== 1 ? 's' : ''}
            </span>
          )}
          {module.lessons.length} lessons
        </div>
      </div>

      {expanded && (
        <div className="mt-4 space-y-4">
          {/* Learning Materials */}
          {module.notes.length > 0 && (
            <div className="bg-blue-50 rounded p-3">
              <h4 className="font-semibold mb-2 flex items-center gap-2">
                <FileText className="w-5 h-5" />
                Learning Materials
              </h4>
              <div className="space-y-2">
                {module.notes.map((note) => (
                  <div key={note.id} className="flex items-center justify-between">
                    <div className="flex-1">
                      <div className="font-medium">{note.title}</div>
                      <div className="text-sm text-gray-600">{note.body}</div>
                    </div>
                    {note.attachment_url && (
                      <a
                        href={note.attachment_url}
                        download
                        className="text-blue-600 hover:text-blue-800 flex items-center gap-1"
                      >
                        <Download className="w-4 h-4" />
                        Download
                      </a>
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {/* Lessons List */}
          <div className="space-y-2">
            {module.lessons.map((lesson) => (
              <LessonListItem key={lesson.id} lesson={lesson} />
            ))}
          </div>
        </div>
      )}
    </div>
  );
}
```

### Standalone Notes Viewer Component

```tsx
interface NotesViewerProps {
  notes: Note[];
  title?: string;
  emptyMessage?: string;
}

export function NotesViewer({ 
  notes, 
  title = "Learning Materials",
  emptyMessage = "No materials available yet"
}: NotesViewerProps) {
  if (notes.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        {emptyMessage}
      </div>
    );
  }

  return (
    <div className="notes-viewer">
      <h2 className="text-2xl font-bold mb-4">{title}</h2>
      
      <div className="grid gap-4 md:grid-cols-2">
        {notes.map((note) => (
          <NoteCard key={note.id} note={note} />
        ))}
      </div>
    </div>
  );
}

function NoteCard({ note }: { note: Note }) {
  const hasAttachment = !!note.attachment_url;
  
  return (
    <div className="border rounded-lg p-4 hover:shadow-lg transition">
      <div className="flex items-start gap-3">
        {hasAttachment && (
          <div className="text-blue-600">
            {getFileIcon(note.attachment_name)}
          </div>
        )}
        
        <div className="flex-1">
          <h3 className="font-semibold mb-1">{note.title}</h3>
          <p className="text-sm text-gray-600 mb-3">{note.body}</p>
          
          {hasAttachment && (
            <a
              href={note.attachment_url}
              download
              target="_blank"
              rel="noopener noreferrer"
              className="inline-flex items-center gap-2 text-sm text-blue-600 hover:text-blue-800"
            >
              <Download className="w-4 h-4" />
              Download Attachment
            </a>
          )}
          
          <div className="text-xs text-gray-400 mt-2">
            Added {new Date(note.created_at).toLocaleDateString()}
          </div>
        </div>
      </div>
    </div>
  );
}
```

## Vanilla JavaScript Example

```javascript
// Fetch and display notes
async function loadLessonNotes(lessonId) {
  const token = localStorage.getItem('authToken');
  
  const response = await fetch(
    `http://127.0.0.1:8000/api/learner/lessons/${lessonId}`,
    { headers: { Authorization: `Bearer ${token}` } }
  );
  
  const result = await response.json();
  const notes = result.data.notes;
  
  displayNotes(notes);
}

function displayNotes(notes) {
  const container = document.getElementById('notes-container');
  
  if (notes.length === 0) {
    container.innerHTML = '<p class="empty">No learning materials available</p>';
    return;
  }
  
  container.innerHTML = `
    <h2>📚 Learning Materials</h2>
    <div class="notes-list">
      ${notes.map(note => `
        <div class="note-card">
          <h3>${escapeHtml(note.title)}</h3>
          <p>${escapeHtml(note.body)}</p>
          ${note.attachment_url ? `
            <a href="${note.attachment_url}" 
               download 
               class="download-btn">
              📥 Download ${escapeHtml(note.attachment_name)}
            </a>
          ` : ''}
          <small>Added ${new Date(note.created_at).toLocaleDateString()}</small>
        </div>
      `).join('')}
    </div>
  `;
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}
```

## File Type Detection & Preview

```typescript
export function getFileType(filename: string | null): string {
  if (!filename) return 'unknown';
  
  const ext = filename.split('.').pop()?.toLowerCase() || '';
  
  if (['pdf'].includes(ext)) return 'pdf';
  if (['doc', 'docx'].includes(ext)) return 'document';
  if (['ppt', 'pptx'].includes(ext)) return 'presentation';
  if (['xls', 'xlsx'].includes(ext)) return 'spreadsheet';
  if (['mp4', 'mov', 'avi', 'mkv', 'webm'].includes(ext)) return 'video';
  if (['zip', 'rar'].includes(ext)) return 'archive';
  
  return 'file';
}

export function canPreview(fileType: string): boolean {
  return ['pdf', 'video'].includes(fileType);
}

// Optional: Add preview modal
function PreviewModal({ note }: { note: Note }) {
  const fileType = getFileType(note.attachment_name);
  
  if (fileType === 'pdf') {
    return (
      <iframe
        src={note.attachment_url}
        className="w-full h-screen"
        title={note.title}
      />
    );
  }
  
  if (fileType === 'video') {
    return (
      <video controls className="w-full">
        <source src={note.attachment_url} />
        Your browser does not support video playback.
      </video>
    );
  }
  
  return <div>Preview not available</div>;
}
```

## Mobile-Friendly Notes List

```tsx
export function MobileNotesList({ notes }: { notes: Note[] }) {
  return (
    <div className="mobile-notes">
      {notes.map((note) => (
        <div key={note.id} className="border-b py-3">
          <div className="font-semibold mb-1">{note.title}</div>
          <div className="text-sm text-gray-600 mb-2">{note.body}</div>
          
          {note.attachment_url && (
            <button
              onClick={() => window.open(note.attachment_url, '_blank')}
              className="w-full bg-blue-500 text-white py-2 px-4 rounded flex items-center justify-center gap-2"
            >
              <Download className="w-4 h-4" />
              Download Attachment
            </button>
          )}
        </div>
      ))}
    </div>
  );
}
```

## Styling Examples

```css
/* Notes Section Styles */
.learning-materials {
  background: #f9fafb;
  border-radius: 8px;
  padding: 24px;
  margin-top: 32px;
}

.note-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  transition: box-shadow 0.2s;
}

.note-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.download-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border-radius: 6px;
  text-decoration: none;
  font-weight: 500;
  transition: background 0.2s;
}

.download-btn:hover {
  background: #2563eb;
}

.empty-state {
  text-align: center;
  padding: 48px;
  color: #9ca3af;
}
```

## Key Features to Implement

### 1. Download Tracking (Optional)
Track when learners download materials:
```typescript
const trackDownload = async (noteId: number) => {
  await fetch(`/api/learner/notes/${noteId}/track-download`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` }
  });
};
```

### 2. Offline Access (Progressive)
Cache notes for offline viewing:
```typescript
if ('serviceWorker' in navigator) {
  // Cache note attachments
  caches.open('notes-cache').then(cache => {
    cache.addAll(notes.map(n => n.attachment_url));
  });
}
```

### 3. Search/Filter Notes
```typescript
const [searchTerm, setSearchTerm] = useState('');

const filteredNotes = notes.filter(note =>
  note.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
  note.body.toLowerCase().includes(searchTerm.toLowerCase())
);
```

## Testing Checklist

- [ ] Notes display in lesson detail page
- [ ] Notes display in course overview (module cards)
- [ ] Download links work for all file types
- [ ] PDF files open correctly
- [ ] Video files can be played/downloaded
- [ ] Empty state shows when no notes
- [ ] Mobile layout is responsive
- [ ] Notes are sorted by creation date
- [ ] File icons display correctly
- [ ] Download attribute triggers download (not navigate)
- [ ] Works for enrolled learners only (403 if not enrolled)
- [ ] Loading states display during fetch

## Error Handling

```typescript
const fetchNotes = async (lessonId: number) => {
  try {
    const response = await fetch(`/api/learner/lessons/${lessonId}`, {
      headers: { Authorization: `Bearer ${token}` }
    });
    
    if (response.status === 403) {
      alert('You must be enrolled to view this content');
      navigate('/courses');
      return;
    }
    
    if (!response.ok) {
      throw new Error('Failed to load notes');
    }
    
    const data = await response.json();
    setNotes(data.data.notes);
  } catch (error) {
    console.error('Error loading notes:', error);
    setError('Failed to load learning materials');
  }
};
```

## Summary

**Backend Ready:** ✅ Notes automatically included in learner API responses  
**Frontend Tasks:**
1. Display notes in lesson detail page
2. Show notes in course module cards
3. Implement download buttons with proper file icons
4. Add mobile-responsive layout
5. Handle empty states gracefully
6. Test with various file types (PDF, video, documents)

**No additional API calls needed** - notes come with existing lesson/course endpoints!
