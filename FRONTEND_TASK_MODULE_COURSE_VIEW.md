# Frontend Task: Module-Based Course View for Learners

## 🎯 Objective
Build a comprehensive module-based course view where learners can see all course content organized by modules. Each module displays lessons (notes), quizzes, and assignments with expandable sections and downloadable materials.

## 📋 Course Structure Overview
```
Course
  └── Module 1
      ├── Lessons (Notes) - Text content + downloadable files
      ├── Quizzes - Interactive assessments
      └── Assignments - Homework submissions
  └── Module 2
      ├── Lessons (Notes)
      ├── Quizzes
      └── Assignments
  └── Module 3...
```

**Key Concept:** Lessons = Notes. Each note represents a lesson with title, body text, and optional file attachments (PDFs, videos, documents).

---

## 🔌 API Endpoint

**Primary Endpoint:**
```
GET /api/learner/courses/{courseId}
Headers: Authorization: Bearer {token}
```

**Response Structure:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "Web Development Fundamentals",
    "description": "Learn the basics of web development...",
    "thumbnail": "http://localhost:8000/storage/thumbnails/course.jpg",
    "category": "Technology",
    "level": "Beginner",
    "modules": [
      {
        "id": 2,
        "title": "Introduction to CSS",
        "description": "Learn CSS basics and styling",
        "order": 1,
        "notes": [
          {
            "id": 1,
            "title": "CSS Introduction",
            "body": "CSS (Cascading Style Sheets) is used for styling...\n\nKey points:\n- Colors\n- Fonts\n- Layout",
            "attachment_url": "http://localhost:8000/storage/course-attachments/css-guide.pdf",
            "attachment_name": "css-guide.pdf",
            "created_at": "2025-12-14T10:30:00.000000Z"
          },
          {
            "id": 2,
            "title": "CSS Selectors",
            "body": "Learn how to select HTML elements...",
            "attachment_url": null,
            "attachment_name": null,
            "created_at": "2025-12-14T11:00:00.000000Z"
          }
        ],
        "lessons": []
      }
    ],
    "totalLessons": 12,
    "completedLessons": 3,
    "enrollment": {
      "id": 5,
      "status": "active",
      "progress": 25.5,
      "enrolled_at": "2025-12-10T08:00:00.000000Z"
    }
  }
}
```

---

## ✅ Implementation Requirements

### 1. Page Layout
**Location:** `src/pages/CourseView.tsx` or `src/components/CourseDetail.tsx`

**Components to Create:**
- `CourseView` - Main container with course header
- `ModuleCard` - Expandable module with stats
- `LessonsSection` - Display all lessons in a module
- `LessonCard` - Individual expandable lesson
- `QuizzesSection` - Placeholder for quizzes (Phase 2)
- `AssignmentsSection` - Placeholder for assignments (Phase 2)

### 2. Features to Implement

**Module Display:**
- Display all modules in sequential order
- Each module shows: Module number, title, description
- Display stats badges: Lesson count, Quiz count, Assignment count
- Expandable/collapsible on click
- First module expanded by default, others collapsed

**Lessons (Notes) Display:**
- Show all lessons within each module
- Each lesson has: Title, body text, optional downloadable file
- Click lesson to expand and view full content
- Body text should preserve line breaks (`\n` → new lines)
- If attachment exists, show download button with file name
- Display date when lesson was added

**File Downloads:**
- Show download button only if `attachment_url` is not null
- Button displays file name from `attachment_name`
- Opens in new tab when clicked
- Visual feedback on hover

**Empty States:**
- "No lessons available yet" when notes array is empty
- Placeholder messages for quizzes and assignments sections

---

## 💻 Complete Implementation Code

```tsx
import React, { useState, useEffect } from 'react';
import './CourseView.css';

// ============================================
// TYPE DEFINITIONS
// ============================================

interface Note {
  id: number;
  title: string;
  body: string;
  attachment_url: string | null;
  attachment_name: string | null;
  created_at: string;
}

interface Module {
  id: number;
  title: string;
  description: string;
  order: number;
  notes: Note[];
}

interface CourseData {
  id: number;
  title: string;
  description: string;
  thumbnail: string | null;
  category: string;
  level: string;
  modules: Module[];
  totalLessons: number;
  completedLessons: number;
  enrollment: {
    progress: number;
    enrolled_at: string;
  };
}

// ============================================
// MAIN COURSE VIEW COMPONENT
// ============================================

function CourseView({ courseId }: { courseId: number }) {
  const [courseData, setCourseData] = useState<CourseData | null>(null);
  const [expandedModules, setExpandedModules] = useState<Set<number>>(new Set());
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    fetchCourseData();
  }, [courseId]);

  const fetchCourseData = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`http://localhost:8000/api/learner/courses/${courseId}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json'
        }
      });

      const data = await response.json();
      
      if (response.ok && data.success) {
        setCourseData(data.data);
        // Expand first module by default
        if (data.data.modules.length > 0) {
          setExpandedModules(new Set([data.data.modules[0].id]));
        }
      } else {
        setError(data.message || 'Failed to load course');
      }
    } catch (err) {
      setError('Failed to connect to server');
      console.error('Error fetching course:', err);
    } finally {
      setLoading(false);
    }
  };

  const toggleModule = (moduleId: number) => {
    const newExpanded = new Set(expandedModules);
    if (newExpanded.has(moduleId)) {
      newExpanded.delete(moduleId);
    } else {
      newExpanded.add(moduleId);
    }
    setExpandedModules(newExpanded);
  };

  // Loading State
  if (loading) {
    return (
      <div className="course-container">
        <div className="loading-state">Loading course...</div>
      </div>
    );
  }

  // Error State
  if (error || !courseData) {
    return (
      <div className="course-container">
        <div className="error-state">{error || 'Course not found'}</div>
      </div>
    );
  }

  // Calculate progress percentage
  const progressPercentage = courseData.totalLessons > 0 
    ? (courseData.completedLessons / courseData.totalLessons) * 100 
    : 0;

  return (
    <div className="course-container">
      {/* Course Header */}
      <div className="course-header">
        <div className="course-header-content">
          <h1>{courseData.title}</h1>
          <p className="course-description">{courseData.description}</p>
          
          <div className="course-meta">
            <span className="meta-badge">{courseData.level}</span>
            <span className="meta-badge">{courseData.category}</span>
          </div>

          <div className="course-progress">
            <div className="progress-label">
              <span>Your Progress</span>
              <span>{courseData.completedLessons} / {courseData.totalLessons} lessons</span>
            </div>
            <div className="progress-bar">
              <div 
                className="progress-fill" 
                style={{ width: `${progressPercentage}%` }}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Modules List */}
      <div className="modules-container">
        <h2>📚 Course Modules</h2>
        {courseData.modules.map((module) => (
          <ModuleCard
            key={module.id}
            module={module}
            isExpanded={expandedModules.has(module.id)}
            onToggle={() => toggleModule(module.id)}
          />
        ))}
      </div>
    </div>
  );
}

// ============================================
// MODULE CARD COMPONENT
// ============================================

function ModuleCard({ module, isExpanded, onToggle }: {
  module: Module;
  isExpanded: boolean;
  onToggle: () => void;
}) {
  const lessonsCount = module.notes?.length || 0;

  return (
    <div className="module-card">
      {/* Module Header */}
      <div className="module-header" onClick={onToggle}>
        <div className="module-title-section">
          <h3>
            <span className="module-number">Module {module.order}</span>
            <span className="module-title">{module.title}</span>
          </h3>
          <p className="module-description">{module.description}</p>
        </div>
        
        <div className="module-stats">
          <span className="stat-badge">📝 {lessonsCount} Lessons</span>
          <span className="stat-badge">📊 Quizzes</span>
          <span className="stat-badge">📋 Assignments</span>
          <button className="expand-btn" aria-label="Toggle module">
            {isExpanded ? '▼' : '▶'}
          </button>
        </div>
      </div>

      {/* Module Content (Expanded) */}
      {isExpanded && (
        <div className="module-content">
          <LessonsSection notes={module.notes} />
          <QuizzesSection moduleId={module.id} />
          <AssignmentsSection moduleId={module.id} />
        </div>
      )}
    </div>
  );
}

// ============================================
// LESSONS SECTION
// ============================================

function LessonsSection({ notes }: { notes: Note[] }) {
  if (!notes || notes.length === 0) {
    return (
      <div className="content-section">
        <h4>📝 Lessons</h4>
        <p className="empty-state">No lessons available yet</p>
      </div>
    );
  }

  return (
    <div className="content-section lessons-section">
      <h4>📝 Lessons ({notes.length})</h4>
      <div className="lessons-grid">
        {notes.map((note) => (
          <LessonCard key={note.id} lesson={note} />
        ))}
      </div>
    </div>
  );
}

// ============================================
// LESSON CARD
// ============================================

function LessonCard({ lesson }: { lesson: Note }) {
  const [isExpanded, setIsExpanded] = useState(false);

  return (
    <div className="lesson-card">
      <div className="lesson-header" onClick={() => setIsExpanded(!isExpanded)}>
        <h5>{lesson.title}</h5>
        <span className="expand-icon">{isExpanded ? '−' : '+'}</span>
      </div>
      
      {isExpanded && (
        <div className="lesson-body">
          {/* Lesson Text Content */}
          <div className="lesson-text">
            <p style={{ whiteSpace: 'pre-wrap' }}>{lesson.body}</p>
          </div>

          {/* Downloadable File */}
          {lesson.attachment_url && (
            <div className="lesson-files">
              <h6>📎 Downloadable Materials</h6>
              <a 
                href={lesson.attachment_url}
                target="_blank"
                rel="noopener noreferrer"
                className="file-download-btn"
              >
                <span className="file-icon">📄</span>
                <span className="file-name">{lesson.attachment_name}</span>
                <span className="download-icon">⬇</span>
              </a>
            </div>
          )}

          {/* Lesson Meta */}
          <div className="lesson-meta">
            <small>Added: {new Date(lesson.created_at).toLocaleDateString()}</small>
          </div>
        </div>
      )}
    </div>
  );
}

// ============================================
// PLACEHOLDER SECTIONS
// ============================================

function QuizzesSection({ moduleId }: { moduleId: number }) {
  return (
    <div className="content-section quizzes-section">
      <h4>📊 Quizzes</h4>
      <p className="empty-state">Quiz functionality will be added in Phase 2</p>
    </div>
  );
}

function AssignmentsSection({ moduleId }: { moduleId: number }) {
  return (
    <div className="content-section assignments-section">
      <h4>📋 Assignments</h4>
      <p className="empty-state">Assignment functionality will be added in Phase 2</p>
    </div>
  );
}

export default CourseView;
```

---

## 🎨 Complete CSS Styling

Create `CourseView.css`:

```css
/* ============================================
   COURSE CONTAINER
   ============================================ */

.course-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.loading-state,
.error-state {
  text-align: center;
  padding: 60px 20px;
  font-size: 18px;
  color: #666;
}

.error-state {
  color: #d32f2f;
}

/* ============================================
   COURSE HEADER
   ============================================ */

.course-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 40px;
  border-radius: 12px;
  margin-bottom: 30px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.course-header-content h1 {
  margin: 0 0 12px 0;
  font-size: 32px;
  font-weight: 700;
}

.course-description {
  margin: 0 0 20px 0;
  font-size: 16px;
  line-height: 1.6;
  opacity: 0.95;
}

.course-meta {
  display: flex;
  gap: 12px;
  margin-bottom: 24px;
}

.meta-badge {
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 14px;
  font-weight: 500;
}

.course-progress {
  margin-top: 20px;
}

.progress-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 500;
}

.progress-bar {
  height: 10px;
  background: rgba(255, 255, 255, 0.25);
  border-radius: 5px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: #4CAF50;
  transition: width 0.5s ease;
  border-radius: 5px;
}

/* ============================================
   MODULES CONTAINER
   ============================================ */

.modules-container h2 {
  color: #333;
  margin-bottom: 24px;
  font-size: 24px;
  font-weight: 600;
}

/* ============================================
   MODULE CARD
   ============================================ */

.module-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  margin-bottom: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.module-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

.module-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px;
  cursor: pointer;
  background: #fafafa;
  border-bottom: 1px solid #e0e0e0;
  transition: background 0.2s ease;
}

.module-header:hover {
  background: #f5f5f5;
}

.module-title-section {
  flex: 1;
  padding-right: 20px;
}

.module-title-section h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.module-number {
  background: #1976D2;
  color: white;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
}

.module-title {
  color: #333;
  font-weight: 600;
}

.module-description {
  margin: 0;
  color: #666;
  font-size: 14px;
  line-height: 1.5;
}

.module-stats {
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}

.stat-badge {
  background: #E3F2FD;
  color: #1976D2;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 500;
  white-space: nowrap;
}

.expand-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 8px 12px;
  color: #666;
  transition: color 0.2s ease;
}

.expand-btn:hover {
  color: #1976D2;
}

/* ============================================
   MODULE CONTENT
   ============================================ */

.module-content {
  padding: 24px;
  background: white;
}

.content-section {
  margin-bottom: 32px;
  padding-bottom: 32px;
  border-bottom: 1px solid #e0e0e0;
}

.content-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.content-section h4 {
  color: #333;
  margin: 0 0 20px 0;
  font-size: 18px;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ============================================
   LESSONS SECTION
   ============================================ */

.lessons-grid {
  display: grid;
  gap: 16px;
}

.lesson-card {
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  overflow: hidden;
  transition: all 0.3s ease;
}

.lesson-card:hover {
  border-color: #4CAF50;
  box-shadow: 0 2px 8px rgba(76, 175, 80, 0.15);
}

.lesson-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  background: white;
  transition: background 0.2s ease;
}

.lesson-header:hover {
  background: #f5f5f5;
}

.lesson-header h5 {
  margin: 0;
  color: #2196F3;
  font-size: 16px;
  font-weight: 600;
  flex: 1;
  padding-right: 12px;
}

.expand-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E3F2FD;
  border-radius: 50%;
  color: #1976D2;
  font-weight: bold;
  font-size: 18px;
  flex-shrink: 0;
}

.lesson-body {
  padding: 20px;
  background: white;
  border-top: 1px solid #e0e0e0;
}

.lesson-text {
  margin-bottom: 20px;
}

.lesson-text p {
  color: #666;
  line-height: 1.8;
  margin: 0;
  font-size: 15px;
}

/* ============================================
   LESSON FILES
   ============================================ */

.lesson-files {
  background: #f0f7ff;
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #2196F3;
  margin-bottom: 16px;
}

.lesson-files h6 {
  margin: 0 0 12px 0;
  color: #1976D2;
  font-size: 14px;
  font-weight: 600;
}

.file-download-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: white;
  border: 2px solid #2196F3;
  border-radius: 6px;
  text-decoration: none;
  color: #1976D2;
  transition: all 0.3s ease;
  font-weight: 500;
}

.file-download-btn:hover {
  background: #2196F3;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

.file-icon {
  font-size: 24px;
}

.file-name {
  flex: 1;
  font-weight: 500;
}

.download-icon {
  font-size: 18px;
}

.lesson-meta {
  text-align: right;
  padding-top: 12px;
  border-top: 1px solid #f0f0f0;
}

.lesson-meta small {
  color: #999;
  font-size: 12px;
}

/* ============================================
   EMPTY STATE
   ============================================ */

.empty-state {
  text-align: center;
  color: #999;
  padding: 40px 20px;
  font-style: italic;
  background: #fafafa;
  border-radius: 8px;
  border: 2px dashed #e0e0e0;
  margin: 0;
}

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */

@media (max-width: 768px) {
  .course-header {
    padding: 24px 20px;
  }
  
  .course-header-content h1 {
    font-size: 24px;
  }
  
  .course-description {
    font-size: 14px;
  }
  
  .module-header {
    flex-direction: column;
    gap: 16px;
    padding: 20px;
  }
  
  .module-title-section {
    padding-right: 0;
  }
  
  .module-stats {
    width: 100%;
    justify-content: flex-start;
  }
  
  .stat-badge {
    font-size: 12px;
    padding: 5px 10px;
  }
  
  .module-content {
    padding: 20px;
  }
  
  .lesson-header {
    padding: 14px 16px;
  }
  
  .lesson-header h5 {
    font-size: 15px;
  }
  
  .lesson-body {
    padding: 16px;
  }
}
```

---

## 🧪 Testing Checklist

### Module Functionality
- [ ] All modules display in correct order (Module 1, Module 2, etc.)
- [ ] Click module header to expand/collapse
- [ ] First module expands automatically on page load
- [ ] Other modules remain collapsed initially
- [ ] Smooth animation when expanding/collapsing
- [ ] Module stats show correct lesson count

### Lessons Display
- [ ] Lessons section displays "📝 Lessons (X)" with correct count
- [ ] All lessons within module are visible
- [ ] Click lesson to expand content
- [ ] Lesson title is clear and prominent
- [ ] Lesson body text preserves line breaks (multi-line content displays correctly)
- [ ] Date displays in readable format

### File Downloads
- [ ] Download button appears when `attachment_url` is not null
- [ ] Download button hidden when no attachment
- [ ] File name displays correctly
- [ ] Click opens file in new tab
- [ ] Hover effect works (color change, shadow)

### Empty States
- [ ] "No lessons available yet" shows when notes array is empty
- [ ] Quizzes section shows placeholder message
- [ ] Assignments section shows placeholder message
- [ ] No JavaScript errors in console

### Responsive Design
- [ ] Desktop view (>768px) - modules and lessons display properly
- [ ] Mobile view (<768px) - stats badges wrap, layout stacks
- [ ] Touch-friendly on mobile (buttons easy to tap)

### API Integration
- [ ] Bearer token included in request headers
- [ ] Loading state displays while fetching
- [ ] Error message shows if API fails
- [ ] Handles 403 Forbidden (not enrolled) gracefully

---

## 📊 Test Data

**Test with Course ID: 2**
- Has Module ID 2 with 2 lessons (notes)
- Lessons have titles and body text
- Currently no attachments (test null handling)

**API Test Page:**
Open `test_learner_notes.html` in browser to verify API responses before coding.

---

## ✅ Acceptance Criteria

**Must Have (Phase 1):**
- ✅ Display all course modules in expandable cards
- ✅ Show lessons (notes) with title, body text, and optional download
- ✅ First module expanded by default
- ✅ Click to expand/collapse modules and lessons
- ✅ Proper line break rendering in lesson body
- ✅ Download button only for lessons with attachments
- ✅ Empty states for missing content
- ✅ Progress bar showing course completion
- ✅ Responsive design (mobile + desktop)
- ✅ Loading and error states

**Nice to Have (Future):**
- Quiz integration with module_id filtering
- Assignment integration with module_id filtering
- Mark lessons as complete
- Video/PDF preview inline
- Search functionality

---

## ⏱️ Time Estimate

**Total: 4-6 hours**

- Module layout & expand/collapse: 1 hour
- Lessons display with expand: 2 hours
- File download integration: 1 hour
- CSS styling & responsive: 1-2 hours
- Testing & fixes: 30 mins

---

## 📞 Support & Troubleshooting

**Common Issues:**

| Issue | Solution |
|-------|----------|
| Notes array empty | Normal if instructor hasn't added lessons yet |
| 401 Unauthorized | Check token in localStorage, re-login if needed |
| 403 Forbidden | User not enrolled in this course |
| CORS error | Backend CORS configured, check API URL is correct |
| Line breaks not showing | Use `whiteSpace: 'pre-wrap'` in CSS |

**Backend Contact:**
- Notes not appearing in API → Contact backend team
- Attachment URLs broken → Check storage symlink on server

---

## 🚀 Getting Started

1. **Create component file:** `src/pages/CourseView.tsx`
2. **Create CSS file:** `src/pages/CourseView.css`
3. **Copy implementation code** from this document
4. **Test with Course ID 2** using your API token
5. **Verify on mobile and desktop**
6. **Report any issues** to backend team

---

## 📌 Phase 2 Features (Future)

After Phase 1 is complete and tested:

1. **Quiz Integration**
   - Fetch quizzes by `module_id`
   - Display quiz cards with "Start" button
   - Show score after completion

2. **Assignment Integration**
   - Fetch assignments by `module_id`
   - Show deadline and status
   - "Submit" button functionality

3. **Progress Tracking**
   - Mark lessons as complete with checkbox
   - Auto-update progress percentage
   - Save to backend

4. **Enhanced Files**
   - PDF preview inline
   - Video player integration
   - Multiple attachments per lesson

---

**Questions?** Contact the project lead or backend team.
