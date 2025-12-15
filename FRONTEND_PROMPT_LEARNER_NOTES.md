# Frontend Task: Module-Based Course View with Notes for Learners

## 🎯 Objective
Create a comprehensive module-based course view where each module displays its complete content: lessons (notes), quizzes, and assignments. Learners should see all learning materials organized by module with downloadable files and interactive content.

## 📋 Key Concept: Course Structure
```
Course
  └── Module 1
      ├── Lessons (Notes) - with text, files, attachments
      ├── Quizzes - belonging to this module
      └── Assignments - belonging to this module
  └── Module 2
      ├── Lessons (Notes)
      ├── Quizzes
      └── Assignments
  └── Module 3...
```

**Important:** Lessons = Notes. Each note is a lesson with a title, description/body text, and optional downloadable files.

## 📍 Where to Implement
**Primary Page:** Course Detail/Overview Page - Display all modules with expandable sections showing lessons (notes), quizzes, and assignments for each module

## 🔌 API Endpoints (Already Working)

### 1. Get Single Lesson with Notes
```
GET /api/learner/lessons/{moduleId}
Headers: Authorization: Bearer {token}
```

**Response Structure:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "Introduction to CSS",
    "description": "...",
    "notes": [
      {
        "id": 1,
        "title": "Introduction to CSS (Cascading Style Sheets)",
        "body": "Lesson Objectives\n\nBy the end of this lesson...",
        "attachment_url": "http://localhost:8000/storage/course-attachments/file.pdf",
        "attachment_name": "file.pdf",
        "created_at": "2025-12-14T10:30:00.000000Z"
      }
    ],
    "progress": { ... },
    "navigation": { ... }
  }
}
```

### 2. Get Course with All Modules & Notes
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
    "modules": [
      {
        "id": 2,
        "title": "Introduction",
        "notes": [
          {
            "id": 1,
            "title": "CSS Introduction",
            "body": "Content...",
            "attachment_url": "http://localhost:8000/storage/course-attachments/file.pdf",
            "attachment_name": "file.pdf",
            "created_at": "2025-12-14T10:30:00.000000Z"
          }
        ],
        "lessons": [ ... ]
      }
    ]
  }
}
```

## ✅ Implementation Steps

### Main Implementation: Module-Based Course View

**Location:** Your course detail/overview component (e.g., `CourseDetail.tsx`, `CourseOverview.tsx`)

**What to build:**
A comprehensive module view where each module can be expanded to show:
1. **Lessons (Notes)** - Full lesson content with text and downloadable files
2. **Quizzes** - List of quizzes for this module
3. **Assignments** - List of assignments for this module

**Example Code:**

```tsx
import React, { useState, useEffect } from 'react';

// Type Definitions
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
  lessons: any[]; // Your existing lesson structure
}

interface CourseData {
  id: number;
  title: string;
  description: string;
  modules: Module[];
  totalLessons: number;
  completedLessons: number;
}

// Main Course Component
function CourseView({ courseId }: { courseId: number }) {
  const [courseData, setCourseData] = useState<CourseData | null>(null);
  const [expandedModules, setExpandedModules] = useState<Set<number>>(new Set([1])); // First module expanded by default
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchCourseData();
  }, [courseId]);

  const fetchCourseData = async () => {
    try {
      const response = await fetch(`/api/learner/courses/${courseId}`, {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        }
      });
      const data = await response.json();
      if (data.success) {
        setCourseData(data.data);
      }
    } catch (error) {
      console.error('Failed to fetch course:', error);
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

  if (loading) return <div>Loading course...</div>;
  if (!courseData) return <div>Course not found</div>;

  return (
    <div className="course-container">
      {/* Course Header */}
      <div className="course-header">
        <h1>{courseData.title}</h1>
        <p>{courseData.description}</p>
        <div className="course-progress">
          <span>Progress: {courseData.completedLessons} / {courseData.totalLessons} lessons</span>
          <div className="progress-bar">
            <div 
              className="progress-fill" 
              style={{ width: `${(courseData.completedLessons / courseData.totalLessons) * 100}%` }}
            />
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

// Module Card Component
function ModuleCard({ module, isExpanded, onToggle }: {
  module: Module;
  isExpanded: boolean;
  onToggle: () => void;
}) {
  const notesCount = module.notes?.length || 0;

  return (
    <div className="module-card">
      {/* Module Header */}
      <div className="module-header" onClick={onToggle}>
        <div className="module-title-section">
          <h3>
            <span className="module-number">Module {module.order}</span>
            {module.title}
          </h3>
          <p className="module-description">{module.description}</p>
        </div>
        
        <div className="module-stats">
          <span className="stat-badge">📝 {notesCount} Lessons</span>
          <span className="stat-badge">📊 Quizzes</span>
          <span className="stat-badge">📋 Assignments</span>
          <button className="expand-btn">
            {isExpanded ? '▼' : '▶'}
          </button>
        </div>
      </div>

      {/* Module Content (Expanded) */}
      {isExpanded && (
        <div className="module-content">
          {/* Lessons/Notes Section */}
          <LessonsSection notes={module.notes} />
          
          {/* Quizzes Section - TODO: Add when quiz data available */}
          <QuizzesSection moduleId={module.id} />
          
          {/* Assignments Section - TODO: Add when assignment data available */}
          <AssignmentsSection moduleId={module.id} />
        </div>
      )}
    </div>
  );
}

// Lessons (Notes) Section
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

// Individual Lesson Card
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

          {/* Lesson Date */}
          <div className="lesson-meta">
            <small>Added: {new Date(lesson.created_at).toLocaleDateString()}</small>
          </div>
        </div>
      )}
    </div>
  );
}

// Quizzes Section (Placeholder - extend based on your quiz API)
function QuizzesSection({ moduleId }: { moduleId: number }) {
  // TODO: Fetch quizzes for this module
  return (
    <div className="content-section quizzes-section">
      <h4>📊 Quizzes</h4>
      <p className="empty-state">Quiz functionality coming soon</p>
      {/* Add quiz list here when available */}
    </div>
  );
}

// Assignments Section (Placeholder - extend based on your assignment API)
function AssignmentsSection({ moduleId }: { moduleId: number }) {
  // TODO: Fetch assignments for this module
  return (
    <div className="content-section assignments-section">
      <h4>📋 Assignments</h4>
      <p className="empty-state">Assignment functionality coming soon</p>
      {/* Add assignment list here when available */}
    </div>
  );
}

export default CourseView;
```

## 🎨 Styling Suggestions

```css
/* Course Container */
.course-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

/* Course Header */
.course-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 40px;
  border-radius: 12px;
  margin-bottom: 30px;
}

.course-header h1 {
  margin: 0 0 10px 0;
  font-size: 32px;
}

.course-progress {
  margin-top: 20px;
}

.progress-bar {
  height: 8px;
  background: rgba(255,255,255,0.3);
  border-radius: 4px;
  overflow: hidden;
  margin-top: 10px;
}

.progress-fill {
  height: 100%;
  background: #4CAF50;
  transition: width 0.3s ease;
}

/* Modules Container */
.modules-container h2 {
  color: #333;
  margin-bottom: 20px;
  font-size: 24px;
}

/* Module Card */
.module-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  margin-bottom: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  transition: box-shadow 0.3s ease;
}

.module-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}

/* Module Header */
.module-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 24px;
  cursor: pointer;
  background: #fafafa;
  border-bottom: 1px solid #e0e0e0;
}

.module-header:hover {
  background: #f5f5f5;
}

.module-title-section {
  flex: 1;
}

.module-tiModule Expansion:**
   - Click on module header to expand/collapse
   - Verify smooth animation
   - First module should be expanded by default
   - Other modules should be collapsed initially

2. **Test with Lessons (Notes) Present:**
   - Course ID 2 has Module ID 2 with 2 lessons/notes
   - Verify lessons section shows "📝 Lessons (2)"
   - Click on individual lesson cards to expand
   - Check lesson title, body text, and formatting
   - Currently notes have no attachments - verify empty file state

3. **Test with Attachments (after instructor uploads):**
   - When attachment exists, verify download button appears
   - File icon and name should display correctly
   - Click download button - file should open/download
   - Hover effect should highlight the button

4. **Test Empty States:**
   - Navigate to module without lessons - verify "No lessons available yet"
   - Quizzes section shows placeholder
   - Assignments section shows placeholder

5. **Test Module Organization:**
   - Each module displays: Lessons → Quizzes → Assignments (in order)
   - Module numbers display correctly (Module 1, Module 2, etc.)
   - Stats badges show correct counts

6. **Test Responsive Design:**
   - Test on mobile view (< 768px)
   - Module cards should stack properly
   - Stats badges should wrap correctly
   - Download buttons should remain usable
  color: #666;
  font-size: 14px;
}

.module-stats {
  display: flex;
  gap: 10px;
  align-items: center;
}

.stat-badge {
  background: #E3F2FD;
  color: #1976D2;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
  font-weight: 500;
}

.expand-btn {
  background: none;
  border: none;
  font-size: 20px;
  cursor: pointer;
  padding: 8px;
  color: #666;
}

/* Module Content */
.module-content {
  padding: 24px;
  background: white;
}

/* Content Sections */
.content-section {
  margin-bottom: 30px;
  padding-bottom: 30px;
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
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Lessons Grid */
.lessons-grid {
  display: grid;
  gap: 16px;
}

/* Lesson Card */
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
}

.lesson-header h5 {
  margin: 0;
  color: #2196F3;
  font-size: 16px;
  flex: 1;
}

.expand-icon {
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E3F2FD;
  border-radius: 50%;
  color: #1976D2;
  font-weight: bold;
  font-size: 18px;
}

/* Lesson Body */
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
}

/* Lesson Files */
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
}

.file-download-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: white;
  border: 1px solid #2196F3;
  border-radius: 6px;
  text-decoration: none;
  color: #1976D2;
  transition: all 0.3s ease;
}

.file-download-btn:hover {
  background: #2196F3;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
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

/* Lesson Meta */
.lesson-meta {
  text-align: right;
}

.lesson-meta small {
  color: #999;
  font-size: 12px;
}

/* Empty State */
.empty-state {
  text-align: center;
  color: #999;
  padding: 40px 20px;
  font-style: italic;
  background: #fafafa;
  border-radius: 8px;
  border: 2px dashed #e0e0e0;
}

/* Responsive Design */
@media (max-width: 768px) {
  .course-header {
    padding: 24px;
  }
  
  .course-header h1 {
    font-size: 24px;
  }
  
  .module-header {
    flex-direction: column;
    gap: 16px;
  }
  
  .module-stats {
    width: 100%;
    flex-wrap: wrap;
  }
  
  .stat-badge {
    font-size: 12px;
    padding: 4px 10px;
  }
}
```

## 🧪 Testing Instructions

1. **Test with notes present:**
   - Navigate to Module ID 2 (has 2 notes)
   - Verify notes section appears below lesson content
   - Check note titles and body text display correctly
   - Verify "No attachment" message shows (current notes have no files)

2. **Test with attachments (after instructor uploads):**
   - Verify download button appears when attachment exists
   - Click download button - file should download/open
   - Check file name displays correctly

3. **Test empty state:**
   - Navigate to module without notes
   - Verify no errors occur
   - Notes section should hide or show "No notes available"

4. **Test in course overview:**
**Module Display:**
- [ ] Course displays all modules in order (Module 1, Module 2, etc.)
- [ ] Modules can be expanded/collapsed by clicking header
- [ ] First module is expanded by default
- [ ] Module stats show lesson count correctly

**Lessons (Notes) Display:**
- [ ] Each module shows its lessons (notes) in a grid/list
- [ ] Lessons can be expanded to view full content
- [ ] Lesson title displays prominently
- [ ] Lesson body text renders with proper line breaks (`white-space: pre-wrap`)
- [ ] Created date displays in user-friendly format

**File Downloads:**
- [ ] Download button appears when attachment exists
- [ ] Download button is hidden when no attachment (graceful handling of null)
- [ ] File name displays correctly
- [ ] Click opens/downloads file in new tab
- [ ] Visual feedback on hover

**Module Content Organization:**
- [ ] Each module shows three sections: Lessons, Quizzes, Assignments
- [ ] Sections appear in correct order
- [ ] Empty states handled gracefully for each section
- [ ] Quizzes section ready for integration (placeholder present)
- [ ] Assignments section ready for integration (placeholder present)

**User Experience:**the main course learning interface for students.

**Estimated Time:** 4-6 hours for complete implementation including:
- Module expansion/collapse functionality (1 hour)
- Lessons (notes) display with expandable cards (2 hours)
- File download integration (1 hour)
- Styling and responsive design (1-2 hours)
- Testing across devices (30 mins)

## 📌 Future Enhancements (Phase 2)

Once basic implementation is complete, these features can be added:

1. **Quiz Integration:**
   - Fetch quizzes by module_id
   - Display quiz cards with "Start Quiz" button
   - Show quiz status (not started, in progress, completed)
   - Display quiz score if completed

2. **Assignment Integration:**
   - Fetch assignments by module_id
   - Display assignment cards with deadline
   - Show submission status
   - Add "Submit Assignment" button

3. **Progress Tracking:**
   - Mark lessons as complete
   - Update progress percentage in real-time
   - Show completion checkmarks on finished content
   - Display time spent on each lesson

4. **Search & Filter:**
   - Search across all module content
   - Filter by content type (lessons, quizzes, assignments)
   - Jump to specific module

5. **Enhanced File Handling:**
   - Preview PDFs inline
   - Play videos directly in the interface
   - Multiple file attachments per lesson
   - File type icons (PDF, video, doc, etc.)
- [ ] Loading state shows while fetching data
- [ ] Error handling for failed API calls
- [ ] Progress bar shows course completion percentage

**Technical:**
- [ ] Bearer token included in API requests
- [ ] Enrollment check respected (403 error handled)
- [ ] TypeScript types defined correctly
- [ ] No console errors
- [ ] Performance optimized (no unnecessary re-renders)nrolled in course to see notes
- **Line Breaks:** Note body may contain `\n` - use `whiteSpace: 'pre-wrap'` in CSS
- **No Attachments:** Current test data has notes without attachments - handle null gracefully
- **File Types:** Future attachments may be PDFs, videos, docs, etc.

## ✅ Acceptance Criteria

- [ ] Notes display in lesson detail page
- [ ] Notes count/badge shows in course overview
- [ ] Download button works for attachments
- [ ] Empty state handled (no notes)
- [ ] No errors when attachment_url is null
- [ ] Responsive design (mobile-friendly)
- [ ] Line breaks in note body render correctly
- [ ] Date formatting is user-friendly

## 🔍 Test URLs

Use the provided `test_learner_notes.html` file to verify API responses before implementing:
1. Open `test_learner_notes.html` in browser
2. Enter your API token
3. Test Module ID 2 (has notes)
4. Review JSON response structure

## 📞 Backend Contact

If API issues occur:
- Notes field missing → Backend issue, contact backend team
- 401 Unauthorized → Check token and authentication
- 403 Forbidden → User not enrolled in course
- Empty notes array → Module genuinely has no notes (normal)

## 🚀 Priority

**High Priority** - This is instructor-provided learning material that students need to access.

Estimated time: 2-4 hours for basic implementation + styling
