# Frontend Task: Display Complete Module Content (Lessons, Quizzes, Assignments)

## 🎯 Objective
Update the course view to display all module content: **Lessons (Notes)**, **Quizzes**, and **Assignments**. Each module should be expandable and show all three content types in an organized, user-friendly layout.

## 🔄 What Changed in the API

### ✅ NEW - API Now Includes:
- **`modules[].notes`** - Lessons with content and downloadable files
- **`modules[].quizzes`** - Interactive assessments (NEW!)
- **`modules[].assignments`** - Homework submissions (NEW!)

### ❌ REMOVED:
- **`modules[].lessons`** - Blank field removed (use `notes` instead)

---

## 📡 API Endpoint

**Endpoint:** `GET /api/learner/courses/{courseId}`  
**Headers:** `Authorization: Bearer {token}`

**Complete Response Structure:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "title": "Web Development Fundamentals",
    "description": "Learn web development from scratch",
    "level": "Beginner",
    "category": "Technology",
    "totalLessons": 2,
    "completedLessons": 0,
    "modules": [
      {
        "id": 1,
        "title": "Introduction to HTML",
        "description": "Learn HTML basics",
        "order": 1,
        "notes": [
          {
            "id": 1,
            "title": "CSS Introduction",
            "body": "CSS styles HTML elements...\n\nKey concepts:\n- Selectors\n- Properties",
            "attachment_url": "http://localhost:8000/storage/course-attachments/css-guide.pdf",
            "attachment_name": "css-guide.pdf",
            "created_at": "2025-12-14T10:30:00.000000Z"
          }
        ],
        "quizzes": [
          {
            "id": 2,
            "title": "HTML Basics Quiz",
            "description": "Test your HTML knowledge",
            "duration": 5,
            "total_marks": 100,
            "passing_percentage": 70,
            "created_at": "2025-12-09T06:59:56.000000Z"
          }
        ],
        "assignments": [
          {
            "id": 1,
            "title": "Build Your First Web Page",
            "description": "Create a simple HTML page",
            "deadline": "2025-12-20T23:59:59.000000Z",
            "max_marks": 100,
            "created_at": "2025-12-09T07:23:33.000000Z"
          }
        ]
      }
    ]
  }
}
```

---

## 💻 Complete Implementation

### React/TypeScript Component

```tsx
import React, { useState, useEffect } from 'react';
import './CourseView.css';

// ===== TYPE DEFINITIONS =====

interface Note {
  id: number;
  title: string;
  body: string;
  attachment_url: string | null;
  attachment_name: string | null;
  created_at: string;
}

interface Quiz {
  id: number;
  title: string;
  description: string;
  duration: number;
  total_marks: number;
  passing_percentage: number;
  created_at: string;
}

interface Assignment {
  id: number;
  title: string;
  description: string;
  deadline: string | null;
  max_marks: number;
  created_at: string;
}

interface Module {
  id: number;
  title: string;
  description: string;
  order: number;
  notes: Note[];
  quizzes: Quiz[];
  assignments: Assignment[];
}

interface CourseData {
  id: number;
  title: string;
  description: string;
  level: string;
  category: string;
  modules: Module[];
  totalLessons: number;
  completedLessons: number;
}

// ===== MAIN COURSE COMPONENT =====

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

  if (loading) return <div className="loading">Loading course...</div>;
  if (error || !courseData) return <div className="error">{error || 'Course not found'}</div>;

  return (
    <div className="course-container">
      {/* Course Header */}
      <div className="course-header">
        <h1>{courseData.title}</h1>
        <p>{courseData.description}</p>
        <div className="course-meta">
          <span className="badge">{courseData.level}</span>
          <span className="badge">{courseData.category}</span>
        </div>
        <div className="progress-section">
          <span>Progress: {courseData.completedLessons} / {courseData.totalLessons}</span>
          <div className="progress-bar">
            <div 
              className="progress-fill" 
              style={{ width: `${(courseData.completedLessons / courseData.totalLessons) * 100}%` }}
            />
          </div>
        </div>
      </div>

      {/* Modules */}
      <div className="modules-section">
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

// ===== MODULE CARD =====

function ModuleCard({ module, isExpanded, onToggle }: {
  module: Module;
  isExpanded: boolean;
  onToggle: () => void;
}) {
  return (
    <div className="module-card">
      <div className="module-header" onClick={onToggle}>
        <div className="module-info">
          <h3>
            <span className="module-number">Module {module.order}</span>
            {module.title}
          </h3>
          <p>{module.description}</p>
        </div>
        <div className="module-stats">
          <span className="stat">📝 {module.notes.length} Lessons</span>
          <span className="stat">📊 {module.quizzes.length} Quizzes</span>
          <span className="stat">📋 {module.assignments.length} Assignments</span>
          <button className="expand-btn">{isExpanded ? '▼' : '▶'}</button>
        </div>
      </div>

      {isExpanded && (
        <div className="module-content">
          <LessonsSection notes={module.notes} />
          <QuizzesSection quizzes={module.quizzes} />
          <AssignmentsSection assignments={module.assignments} />
        </div>
      )}
    </div>
  );
}

// ===== LESSONS SECTION =====

function LessonsSection({ notes }: { notes: Note[] }) {
  const [expandedLesson, setExpandedLesson] = useState<number | null>(null);

  if (notes.length === 0) {
    return (
      <div className="content-section">
        <h4>📝 Lessons</h4>
        <p className="empty">No lessons available yet</p>
      </div>
    );
  }

  return (
    <div className="content-section">
      <h4>📝 Lessons ({notes.length})</h4>
      {notes.map((note) => (
        <div key={note.id} className="item-card lesson-card">
          <div 
            className="item-header"
            onClick={() => setExpandedLesson(expandedLesson === note.id ? null : note.id)}
          >
            <h5>{note.title}</h5>
            <span className="toggle">{expandedLesson === note.id ? '−' : '+'}</span>
          </div>
          
          {expandedLesson === note.id && (
            <div className="item-body">
              <p style={{ whiteSpace: 'pre-wrap' }}>{note.body}</p>
              
              {note.attachment_url && (
                <div className="file-section">
                  <h6>📎 Download Materials</h6>
                  <a 
                    href={note.attachment_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="download-btn"
                  >
                    <span>📄</span>
                    <span>{note.attachment_name}</span>
                    <span>⬇</span>
                  </a>
                </div>
              )}
              
              <small>Added: {new Date(note.created_at).toLocaleDateString()}</small>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}

// ===== QUIZZES SECTION =====

function QuizzesSection({ quizzes }: { quizzes: Quiz[] }) {
  if (quizzes.length === 0) {
    return (
      <div className="content-section">
        <h4>📊 Quizzes</h4>
        <p className="empty">No quizzes available yet</p>
      </div>
    );
  }

  return (
    <div className="content-section">
      <h4>📊 Quizzes ({quizzes.length})</h4>
      {quizzes.map((quiz) => (
        <div key={quiz.id} className="item-card quiz-card">
          <div className="item-header">
            <h5>{quiz.title}</h5>
          </div>
          <div className="item-body">
            <p>{quiz.description}</p>
            <div className="quiz-meta">
              <span>⏱️ {quiz.duration} minutes</span>
              <span>📝 {quiz.total_marks} marks</span>
              <span>✅ Pass: {quiz.passing_percentage}%</span>
            </div>
            <button className="action-btn primary">Start Quiz</button>
          </div>
        </div>
      ))}
    </div>
  );
}

// ===== ASSIGNMENTS SECTION =====

function AssignmentsSection({ assignments }: { assignments: Assignment[] }) {
  if (assignments.length === 0) {
    return (
      <div className="content-section">
        <h4>📋 Assignments</h4>
        <p className="empty">No assignments available yet</p>
      </div>
    );
  }

  return (
    <div className="content-section">
      <h4>📋 Assignments ({assignments.length})</h4>
      {assignments.map((assignment) => (
        <div key={assignment.id} className="item-card assignment-card">
          <div className="item-header">
            <h5>{assignment.title}</h5>
            {assignment.deadline && (
              <span className="deadline">
                Due: {new Date(assignment.deadline).toLocaleDateString()}
              </span>
            )}
          </div>
          <div className="item-body">
            <p>{assignment.description}</p>
            <div className="assignment-meta">
              <span>📊 Max Marks: {assignment.max_marks}</span>
            </div>
            <button className="action-btn secondary">View Assignment</button>
          </div>
        </div>
      ))}
    </div>
  );
}

export default CourseView;
```

---

## 🎨 Complete CSS (CourseView.css)

```css
/* Container */
.course-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

.loading, .error {
  text-align: center;
  padding: 60px 20px;
  font-size: 18px;
  color: #666;
}

.error {
  color: #d32f2f;
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

.course-meta {
  display: flex;
  gap: 10px;
  margin: 15px 0;
}

.badge {
  background: rgba(255,255,255,0.2);
  padding: 6px 14px;
  border-radius: 16px;
  font-size: 14px;
}

.progress-section {
  margin-top: 20px;
}

.progress-bar {
  height: 8px;
  background: rgba(255,255,255,0.3);
  border-radius: 4px;
  overflow: hidden;
  margin-top: 8px;
}

.progress-fill {
  height: 100%;
  background: #4CAF50;
  transition: width 0.3s ease;
}

/* Modules Section */
.modules-section h2 {
  color: #333;
  margin-bottom: 20px;
}

/* Module Card */
.module-card {
  background: white;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  margin-bottom: 20px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.module-header {
  display: flex;
  justify-content: space-between;
  padding: 24px;
  cursor: pointer;
  background: #fafafa;
  border-bottom: 1px solid #e0e0e0;
}

.module-header:hover {
  background: #f5f5f5;
}

.module-info {
  flex: 1;
}

.module-info h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.module-number {
  background: #1976D2;
  color: white;
  padding: 6px 14px;
  border-radius: 6px;
  font-size: 14px;
}

.module-stats {
  display: flex;
  gap: 10px;
  align-items: center;
}

.stat {
  background: #E3F2FD;
  color: #1976D2;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
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
}

.content-section {
  margin-bottom: 30px;
  padding-bottom: 30px;
  border-bottom: 1px solid #e0e0e0;
}

.content-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.content-section h4 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 18px;
}

.empty {
  text-align: center;
  color: #999;
  padding: 30px;
  font-style: italic;
  background: #fafafa;
  border-radius: 8px;
  border: 2px dashed #e0e0e0;
}

/* Item Cards (Lessons, Quizzes, Assignments) */
.item-card {
  background: #f9f9f9;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  margin-bottom: 12px;
  overflow: hidden;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  background: white;
}

.item-header h5 {
  margin: 0;
  font-size: 16px;
  color: #333;
}

.toggle {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #E3F2FD;
  border-radius: 50%;
  color: #1976D2;
  font-size: 18px;
  font-weight: bold;
}

.item-body {
  padding: 20px;
  background: white;
  border-top: 1px solid #e0e0e0;
}

.item-body p {
  margin: 0 0 15px 0;
  color: #666;
  line-height: 1.6;
}

/* Lesson Specific */
.lesson-card .item-header h5 {
  color: #2196F3;
}

.file-section {
  background: #f0f7ff;
  padding: 16px;
  border-radius: 8px;
  border-left: 4px solid #2196F3;
  margin: 15px 0;
}

.file-section h6 {
  margin: 0 0 10px 0;
  color: #1976D2;
  font-size: 14px;
}

.download-btn {
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
}

.download-btn:hover {
  background: #2196F3;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
}

/* Quiz Specific */
.quiz-card {
  border-left: 4px solid #FF9800;
}

.quiz-meta {
  display: flex;
  gap: 15px;
  margin: 15px 0;
  flex-wrap: wrap;
}

.quiz-meta span {
  background: #FFF3E0;
  color: #E65100;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
}

/* Assignment Specific */
.assignment-card {
  border-left: 4px solid #9C27B0;
}

.deadline {
  background: #FCE4EC;
  color: #C2185B;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
}

.assignment-meta {
  margin: 15px 0;
}

.assignment-meta span {
  background: #F3E5F5;
  color: #7B1FA2;
  padding: 6px 12px;
  border-radius: 16px;
  font-size: 13px;
}

/* Action Buttons */
.action-btn {
  padding: 10px 24px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 10px;
  transition: all 0.3s ease;
}

.action-btn.primary {
  background: #FF9800;
  color: white;
}

.action-btn.primary:hover {
  background: #F57C00;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.action-btn.secondary {
  background: #9C27B0;
  color: white;
}

.action-btn.secondary:hover {
  background: #7B1FA2;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(156, 39, 176, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
  .course-header {
    padding: 24px 20px;
  }
  
  .course-header h1 {
    font-size: 24px;
  }
  
  .module-header {
    flex-direction: column;
    gap: 15px;
  }
  
  .module-stats {
    width: 100%;
    flex-wrap: wrap;
  }
  
  .stat {
    font-size: 12px;
    padding: 5px 10px;
  }
}
```

---

## ✅ Implementation Checklist

**Module Display:**
- [ ] Modules display in order (Module 1, Module 2, etc.)
- [ ] Click module header to expand/collapse
- [ ] First module expanded by default
- [ ] Stats show correct counts (lessons, quizzes, assignments)

**Lessons:**
- [ ] All lessons display with title
- [ ] Click lesson to expand content
- [ ] Body text preserves line breaks
- [ ] Download button shows when attachment exists
- [ ] No errors when attachment is null

**Quizzes:**
- [ ] All quizzes display with title and description
- [ ] Duration, marks, and passing percentage visible
- [ ] "Start Quiz" button present
- [ ] Empty state shows when no quizzes

**Assignments:**
- [ ] All assignments display with title and description
- [ ] Deadline displays in readable format
- [ ] Max marks visible
- [ ] "View Assignment" button present
- [ ] Empty state shows when no assignments

**General:**
- [ ] Responsive design (mobile + desktop)
- [ ] Loading state while fetching
- [ ] Error handling for API failures
- [ ] Bearer token in request headers

---

## 🧪 Testing

1. **Test with Course ID 2** (has mixed content)
2. **Open browser console** - verify no errors
3. **Test module expansion** - click to open/close
4. **Test lesson expansion** - click individual lessons
5. **Verify all three sections** appear in each module
6. **Test empty states** - modules without content show empty message
7. **Test responsive** - resize browser, check mobile view
8. **Test download** - if files exist, click download button

---

## 📞 API Reference Quick Guide

| Field | Type | Description |
|-------|------|-------------|
| `modules[].notes` | Array | Lessons with content and files |
| `modules[].quizzes` | Array | Interactive assessments |
| `modules[].assignments` | Array | Homework submissions |
| `notes[].attachment_url` | String\|null | Full download URL or null |
| `quizzes[].duration` | Number | Quiz time limit in minutes |
| `assignments[].deadline` | String\|null | ISO date string or null |

---

## 🚀 Time Estimate

**Total: 3-4 hours**
- Component structure: 1 hour
- Styling: 1 hour
- Testing & fixes: 1-2 hours

---

## 🎯 Priority: HIGH

This is the main learning interface where students access all course materials. Complete implementation required before deployment.

**Questions?** Use [test_course_modules_complete.html](test_course_modules_complete.html) to verify API responses.
