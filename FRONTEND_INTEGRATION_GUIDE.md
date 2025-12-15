# 🎨 Frontend Integration Guide - Learner Dashboard API

## 📋 Quick Start

Your backend API is **100% ready** with 36 endpoints for a complete Learning Management System. This guide will help you integrate everything with your React/Vue/Angular frontend.

---

## 🔗 API Configuration

### Base Configuration

```javascript
// config/api.js
const API_CONFIG = {
  baseURL: 'http://127.0.0.1:8000/api',
  learnerPrefix: '/learner',
  timeout: 30000,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
};

// Get token from localStorage or your auth system
const getAuthToken = () => localStorage.getItem('api_token');

// Create axios instance or fetch wrapper
const apiClient = axios.create({
  baseURL: API_CONFIG.baseURL,
  timeout: API_CONFIG.timeout,
  headers: API_CONFIG.headers,
});

// Add auth interceptor
apiClient.interceptors.request.use(config => {
  const token = getAuthToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle errors globally
apiClient.interceptors.response.use(
  response => response.data,
  error => {
    if (error.response?.status === 401) {
      // Redirect to login
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default apiClient;
```

---

## 📊 Pages to Build

### 1. **Dashboard Page** (`/dashboard`)

**What to display:**
- Welcome message with user info
- Learning statistics (courses, hours, certificates)
- Current learning streak with visual badge
- Progress chart (last 7 days)
- "Continue Learning" section (3 courses)
- Upcoming assignments (next 5)
- Recent notifications

**API Calls:**
```javascript
// services/dashboardService.js
import apiClient from '@/config/api';

export const dashboardService = {
  // Get complete dashboard data
  getDashboard: async () => {
    return await apiClient.get('/learner/dashboard');
  },
  
  // Get detailed stats
  getStats: async () => {
    return await apiClient.get('/learner/stats');
  },
  
  // Get activity for chart
  getActivity: async (days = 7) => {
    return await apiClient.get(`/learner/activity?days=${days}`);
  },
  
  // Get learning streak
  getStreak: async () => {
    return await apiClient.get('/learner/streak');
  },
};
```

**Component Structure:**
```jsx
// pages/Dashboard.jsx
import React, { useState, useEffect } from 'react';
import { dashboardService } from '@/services/dashboardService';

const Dashboard = () => {
  const [dashboardData, setDashboardData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadDashboard();
  }, []);

  const loadDashboard = async () => {
    try {
      const { data } = await dashboardService.getDashboard();
      setDashboardData(data);
    } catch (error) {
      console.error('Failed to load dashboard:', error);
    } finally {
      setLoading(false);
    }
  };

  if (loading) return <LoadingSpinner />;

  return (
    <div className="dashboard">
      {/* Stats Cards */}
      <StatsCards stats={dashboardData.stats} />
      
      {/* Learning Streak Badge */}
      <StreakBadge 
        current={dashboardData.streak.current}
        longest={dashboardData.streak.longest}
        isActive={dashboardData.streak.isActiveToday}
      />
      
      {/* Progress Chart */}
      <ProgressChart data={dashboardData.progressData} />
      
      {/* Continue Learning */}
      <ContinueLearning courses={dashboardData.continueLearning} />
      
      {/* Upcoming Assignments */}
      <UpcomingAssignments assignments={dashboardData.upcomingAssignments} />
      
      {/* Notifications */}
      <NotificationsList notifications={dashboardData.recentNotifications} />
    </div>
  );
};
```

---

### 2. **My Courses Page** (`/courses`)

**Tabs:**
- Active Courses
- Completed Courses
- Available Courses (browse & enroll)

**API Calls:**
```javascript
// services/courseService.js
export const courseService = {
  // Get enrolled courses
  getMyCourses: async (status = 'active') => {
    return await apiClient.get(`/learner/courses?status=${status}`);
  },
  
  // Get available courses to enroll
  getAvailableCourses: async (filters = {}) => {
    const params = new URLSearchParams(filters);
    return await apiClient.get(`/learner/courses/available?${params}`);
  },
  
  // Get course details
  getCourseDetails: async (courseId) => {
    return await apiClient.get(`/learner/courses/${courseId}`);
  },
  
  // Enroll in course
  enrollCourse: async (courseId) => {
    return await apiClient.post(`/learner/courses/${courseId}/enroll`);
  },
  
  // Get course progress
  getCourseProgress: async (courseId) => {
    return await apiClient.get(`/learner/courses/${courseId}/progress`);
  },
};
```

**Example Component:**
```jsx
const MyCourses = () => {
  const [activeTab, setActiveTab] = useState('active');
  const [courses, setCourses] = useState([]);

  useEffect(() => {
    loadCourses(activeTab);
  }, [activeTab]);

  const loadCourses = async (status) => {
    const { data } = await courseService.getMyCourses(status);
    setCourses(data);
  };

  return (
    <div>
      <Tabs value={activeTab} onChange={setActiveTab}>
        <Tab value="active">Active ({courses.length})</Tab>
        <Tab value="completed">Completed</Tab>
        <Tab value="available">Browse Courses</Tab>
      </Tabs>
      
      <CourseGrid>
        {courses.map(course => (
          <CourseCard 
            key={course.id}
            course={course}
            progress={course.enrollment?.progress}
            onContinue={() => navigate(`/course/${course.id}`)}
          />
        ))}
      </CourseGrid>
    </div>
  );
};
```

---

### 3. **Course Details Page** (`/course/:id`)

**What to display:**
- Course info (title, description, instructor)
- Module list with progress indicators
- Lesson navigation
- Enrollment status

**API & Navigation:**
```javascript
const CourseDetails = ({ courseId }) => {
  const [course, setCourse] = useState(null);
  const [progress, setProgress] = useState(null);

  useEffect(() => {
    loadCourse();
    loadProgress();
  }, [courseId]);

  const loadCourse = async () => {
    const { data } = await courseService.getCourseDetails(courseId);
    setCourse(data);
  };

  const loadProgress = async () => {
    const { data } = await courseService.getCourseProgress(courseId);
    setProgress(data);
  };

  const startLesson = (lessonId) => {
    navigate(`/lesson/${lessonId}`);
  };

  return (
    <div>
      <CourseHeader course={course} />
      <ProgressBar value={course.enrollment?.progress || 0} />
      
      <ModuleList>
        {course.modules.map(module => (
          <Module key={module.id}>
            <h3>{module.title}</h3>
            <LessonList>
              {module.lessons.map(lesson => (
                <LessonItem 
                  lesson={lesson}
                  completed={lesson.progress?.status === 'completed'}
                  onClick={() => startLesson(lesson.id)}
                />
              ))}
            </LessonList>
          </Module>
        ))}
      </ModuleList>
    </div>
  );
};
```

---

### 4. **Lesson Viewer Page** (`/lesson/:id`)

**Features:**
- Video player or content viewer
- Progress tracking
- Navigation (previous/next)
- Mark as complete button

**API Calls:**
```javascript
// services/lessonService.js
export const lessonService = {
  getLesson: async (lessonId) => {
    return await apiClient.get(`/learner/lessons/${lessonId}`);
  },
  
  startLesson: async (lessonId) => {
    return await apiClient.post(`/learner/lessons/${lessonId}/start`);
  },
  
  updateProgress: async (lessonId, data) => {
    return await apiClient.put(`/learner/lessons/${lessonId}/progress`, data);
  },
  
  completeLesson: async (lessonId) => {
    return await apiClient.post(`/learner/lessons/${lessonId}/complete`);
  },
};
```

**Implementation:**
```jsx
const LessonViewer = ({ lessonId }) => {
  const [lesson, setLesson] = useState(null);
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    loadLesson();
    markAsStarted();
  }, [lessonId]);

  const loadLesson = async () => {
    const { data } = await lessonService.getLesson(lessonId);
    setLesson(data);
  };

  const markAsStarted = async () => {
    await lessonService.startLesson(lessonId);
  };

  const handleProgressUpdate = async (currentTime, duration) => {
    const percentage = (currentTime / duration) * 100;
    setProgress(percentage);
    
    // Update every 30 seconds
    if (currentTime % 30 === 0) {
      await lessonService.updateProgress(lessonId, {
        time_spent_minutes: currentTime / 60,
        last_position_seconds: currentTime,
      });
    }
  };

  const completeLesson = async () => {
    await lessonService.completeLesson(lessonId);
    navigate(lesson.nextLesson ? `/lesson/${lesson.nextLesson.id}` : `/course/${lesson.courseId}`);
  };

  return (
    <div className="lesson-viewer">
      <VideoPlayer
        url={lesson.contentUrl}
        onTimeUpdate={handleProgressUpdate}
        startAt={lesson.progress?.last_position_seconds}
      />
      
      <LessonContent>
        <h1>{lesson.title}</h1>
        <div dangerouslySetInnerHTML={{ __html: lesson.content }} />
      </LessonContent>
      
      <Navigation>
        {lesson.previousLesson && (
          <Button onClick={() => navigate(`/lesson/${lesson.previousLesson.id}`)}>
            Previous
          </Button>
        )}
        <Button primary onClick={completeLesson}>
          Mark Complete & Continue
        </Button>
      </Navigation>
    </div>
  );
};
```

---

### 5. **Assignments Page** (`/assignments`)

**Filters:**
- All, Pending, Submitted, Graded, Overdue

**API Calls:**
```javascript
// services/assignmentService.js
export const assignmentService = {
  getAssignments: async (filters = {}) => {
    const params = new URLSearchParams(filters);
    return await apiClient.get(`/learner/assignments?${params}`);
  },
  
  getAssignmentDetails: async (assignmentId) => {
    return await apiClient.get(`/learner/assignments/${assignmentId}`);
  },
  
  submitAssignment: async (assignmentId, formData) => {
    return await apiClient.post(
      `/learner/assignments/${assignmentId}/submit`,
      formData,
      { headers: { 'Content-Type': 'multipart/form-data' } }
    );
  },
  
  getSubmission: async (assignmentId) => {
    return await apiClient.get(`/learner/assignments/${assignmentId}/submission`);
  },
};
```

**Submission Component:**
```jsx
const AssignmentSubmission = ({ assignmentId }) => {
  const [assignment, setAssignment] = useState(null);
  const [submissionText, setSubmissionText] = useState('');
  const [file, setFile] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    const formData = new FormData();
    formData.append('submission_text', submissionText);
    if (file) {
      formData.append('file', file);
    }

    try {
      await assignmentService.submitAssignment(assignmentId, formData);
      toast.success('Assignment submitted successfully!');
      navigate('/assignments');
    } catch (error) {
      toast.error(error.response?.data?.message || 'Submission failed');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <h2>{assignment?.title}</h2>
      <p>Due: {assignment?.dueDate}</p>
      
      <TextArea
        label="Your Answer"
        value={submissionText}
        onChange={(e) => setSubmissionText(e.target.value)}
        rows={10}
      />
      
      <FileUpload
        label="Upload File (Optional)"
        accept={assignment?.allowedFileTypes}
        maxSize={assignment?.maxFileSizeMb * 1024 * 1024}
        onChange={setFile}
      />
      
      <Button type="submit" disabled={submitting}>
        {submitting ? 'Submitting...' : 'Submit Assignment'}
      </Button>
    </form>
  );
};
```

---

### 6. **Quizzes Page** (`/quizzes`)

**Features:**
- List quizzes with attempt history
- Take quiz functionality
- View results

**API Calls:**
```javascript
// services/quizService.js
export const quizService = {
  getQuizzes: async (filters = {}) => {
    const params = new URLSearchParams(filters);
    return await apiClient.get(`/learner/quizzes?${params}`);
  },
  
  getQuizDetails: async (quizId) => {
    return await apiClient.get(`/learner/quizzes/${quizId}`);
  },
  
  startQuiz: async (quizId) => {
    return await apiClient.post(`/learner/quizzes/${quizId}/start`);
  },
  
  submitAnswer: async (attemptId, data) => {
    return await apiClient.put(`/learner/quiz-attempts/${attemptId}/answer`, data);
  },
  
  submitQuiz: async (attemptId) => {
    return await apiClient.post(`/learner/quiz-attempts/${attemptId}/submit`);
  },
  
  getAttemptResults: async (attemptId) => {
    return await apiClient.get(`/learner/quiz-attempts/${attemptId}`);
  },
};
```

**Quiz Taking Component:**
```jsx
const QuizTaker = ({ quizId }) => {
  const [quiz, setQuiz] = useState(null);
  const [attemptId, setAttemptId] = useState(null);
  const [currentQuestion, setCurrentQuestion] = useState(0);
  const [answers, setAnswers] = useState({});
  const [timeLeft, setTimeLeft] = useState(null);

  useEffect(() => {
    loadQuiz();
  }, [quizId]);

  const loadQuiz = async () => {
    const { data } = await quizService.getQuizDetails(quizId);
    setQuiz(data);
  };

  const startQuiz = async () => {
    const { data } = await quizService.startQuiz(quizId);
    setAttemptId(data.attempt_id);
    if (quiz.timeLimitMinutes) {
      setTimeLeft(quiz.timeLimitMinutes * 60);
    }
  };

  const saveAnswer = async (questionId, selectedOptions) => {
    setAnswers({ ...answers, [questionId]: selectedOptions });
    
    await quizService.submitAnswer(attemptId, {
      question_id: questionId,
      selected_option_ids: selectedOptions,
    });
  };

  const submitQuiz = async () => {
    const { data } = await quizService.submitQuiz(attemptId);
    navigate(`/quiz/${quizId}/results/${attemptId}`);
  };

  return (
    <div className="quiz-taker">
      {!attemptId ? (
        <QuizIntro quiz={quiz} onStart={startQuiz} />
      ) : (
        <>
          {timeLeft && <Timer seconds={timeLeft} />}
          
          <QuestionProgress 
            current={currentQuestion + 1}
            total={quiz.questions.length}
          />
          
          <Question
            question={quiz.questions[currentQuestion]}
            selectedAnswer={answers[quiz.questions[currentQuestion].id]}
            onAnswer={saveAnswer}
          />
          
          <Navigation>
            {currentQuestion > 0 && (
              <Button onClick={() => setCurrentQuestion(currentQuestion - 1)}>
                Previous
              </Button>
            )}
            
            {currentQuestion < quiz.questions.length - 1 ? (
              <Button onClick={() => setCurrentQuestion(currentQuestion + 1)}>
                Next
              </Button>
            ) : (
              <Button primary onClick={submitQuiz}>
                Submit Quiz
              </Button>
            )}
          </Navigation>
        </>
      )}
    </div>
  );
};
```

---

### 7. **Performance Page** (`/performance`)

**What to display:**
- Overall progress metrics
- Assignment performance chart
- Quiz performance chart
- Time management stats
- AI-generated insights

**API Call:**
```javascript
// services/analyticsService.js
export const analyticsService = {
  getPerformance: async () => {
    return await apiClient.get('/learner/performance');
  },
  
  getRecommendations: async () => {
    return await apiClient.get('/learner/recommendations');
  },
};
```

**Component:**
```jsx
const Performance = () => {
  const [performance, setPerformance] = useState(null);

  useEffect(() => {
    loadPerformance();
  }, []);

  const loadPerformance = async () => {
    const { data } = await analyticsService.getPerformance();
    setPerformance(data);
  };

  return (
    <div className="performance">
      <OverallProgressCard progress={performance.overallProgress} />
      
      <CompletionRateChart data={performance.completionRate} />
      
      <Row>
        <AssignmentStats stats={performance.assignments} />
        <QuizStats stats={performance.quizzes} />
      </Row>
      
      <TimeManagement data={performance.timeManagement} />
      
      <InsightsSection insights={performance.insights} />
    </div>
  );
};
```

---

### 8. **Certificates Page** (`/certificates`)

**Features:**
- List all earned certificates
- Download/view certificates

**API Call:**
```javascript
const Certificates = () => {
  const [certificates, setCertificates] = useState([]);

  useEffect(() => {
    loadCertificates();
  }, []);

  const loadCertificates = async () => {
    const { data } = await apiClient.get('/learner/certificates');
    setCertificates(data);
  };

  return (
    <div className="certificates">
      <h1>My Certificates</h1>
      
      <CertificateGrid>
        {certificates.map(cert => (
          <CertificateCard
            key={cert.id}
            certificate={cert}
            onDownload={() => downloadCertificate(cert.id)}
          />
        ))}
      </CertificateGrid>
    </div>
  );
};
```

---

### 9. **Profile Page** (`/profile`)

**Sections:**
- Personal info (edit)
- Avatar upload
- Password change
- Learning statistics

**API Calls:**
```javascript
// services/profileService.js
export const profileService = {
  getProfile: async () => {
    return await apiClient.get('/learner/profile');
  },
  
  updateProfile: async (data) => {
    return await apiClient.put('/learner/profile', data);
  },
  
  uploadAvatar: async (file) => {
    const formData = new FormData();
    formData.append('avatar', file);
    return await apiClient.post('/learner/profile/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
  },
  
  deleteAvatar: async () => {
    return await apiClient.delete('/learner/profile/avatar');
  },
  
  changePassword: async (data) => {
    return await apiClient.put('/learner/profile/password', data);
  },
};
```

---

## 🎨 Reusable Components to Build

### Stats Card Component
```jsx
const StatsCard = ({ icon, title, value, subtitle, trend }) => (
  <div className="stats-card">
    <div className="icon">{icon}</div>
    <div className="content">
      <h3>{title}</h3>
      <div className="value">{value}</div>
      {subtitle && <p className="subtitle">{subtitle}</p>}
      {trend && <span className={`trend ${trend > 0 ? 'up' : 'down'}`}>
        {trend > 0 ? '↑' : '↓'} {Math.abs(trend)}%
      </span>}
    </div>
  </div>
);
```

### Progress Bar Component
```jsx
const ProgressBar = ({ value, max = 100, showLabel = true }) => (
  <div className="progress-bar">
    <div className="progress-track">
      <div 
        className="progress-fill" 
        style={{ width: `${(value / max) * 100}%` }}
      />
    </div>
    {showLabel && <span className="progress-label">{value}%</span>}
  </div>
);
```

### Streak Badge Component
```jsx
const StreakBadge = ({ current, longest, isActive }) => (
  <div className={`streak-badge ${isActive ? 'active' : ''}`}>
    <div className="fire-icon">🔥</div>
    <div className="streak-info">
      <div className="current-streak">
        <span className="number">{current}</span>
        <span className="label">Day Streak</span>
      </div>
      <div className="longest-streak">
        Best: {longest} days
      </div>
    </div>
    {isActive && <div className="active-badge">Active Today!</div>}
  </div>
);
```

### Course Card Component
```jsx
const CourseCard = ({ course, progress, onContinue }) => (
  <div className="course-card">
    <img src={course.thumbnail} alt={course.title} />
    <div className="course-info">
      <h3>{course.title}</h3>
      <p>{course.instructor?.name}</p>
      <ProgressBar value={progress} />
      <div className="course-meta">
        <span>{course.level}</span>
        <span>{course.duration}</span>
      </div>
      <Button onClick={onContinue}>
        {progress > 0 ? 'Continue' : 'Start'} Learning
      </Button>
    </div>
  </div>
);
```

---

## 🔔 Notifications Implementation

```javascript
// services/notificationService.js
export const notificationService = {
  getNotifications: async () => {
    return await apiClient.get('/learner/notifications');
  },
  
  getUnreadCount: async () => {
    return await apiClient.get('/learner/notifications/unread-count');
  },
  
  markAsRead: async (notificationId) => {
    return await apiClient.post(`/learner/notifications/${notificationId}/read`);
  },
  
  markAllAsRead: async () => {
    return await apiClient.post('/learner/notifications/read-all');
  },
  
  deleteNotification: async (notificationId) => {
    return await apiClient.delete(`/learner/notifications/${notificationId}`);
  },
};

// Use in header component
const NotificationBell = () => {
  const [unreadCount, setUnreadCount] = useState(0);

  useEffect(() => {
    loadUnreadCount();
    // Poll every 30 seconds
    const interval = setInterval(loadUnreadCount, 30000);
    return () => clearInterval(interval);
  }, []);

  const loadUnreadCount = async () => {
    const { data } = await notificationService.getUnreadCount();
    setUnreadCount(data.unread_count);
  };

  return (
    <div className="notification-bell" onClick={() => setShowPanel(true)}>
      <BellIcon />
      {unreadCount > 0 && <span className="badge">{unreadCount}</span>}
    </div>
  );
};
```

---

## 🎯 State Management Suggestions

### Using Redux Toolkit
```javascript
// store/slices/dashboardSlice.js
import { createSlice, createAsyncThunk } from '@reduxjs/toolkit';
import { dashboardService } from '@/services/dashboardService';

export const fetchDashboard = createAsyncThunk(
  'dashboard/fetch',
  async () => {
    const response = await dashboardService.getDashboard();
    return response.data;
  }
);

const dashboardSlice = createSlice({
  name: 'dashboard',
  initialState: {
    data: null,
    loading: false,
    error: null,
  },
  reducers: {},
  extraReducers: (builder) => {
    builder
      .addCase(fetchDashboard.pending, (state) => {
        state.loading = true;
      })
      .addCase(fetchDashboard.fulfilled, (state, action) => {
        state.loading = false;
        state.data = action.payload;
      })
      .addCase(fetchDashboard.rejected, (state, action) => {
        state.loading = false;
        state.error = action.error.message;
      });
  },
});

export default dashboardSlice.reducer;
```

### Using React Query
```javascript
// hooks/useDashboard.js
import { useQuery } from '@tanstack/react-query';
import { dashboardService } from '@/services/dashboardService';

export const useDashboard = () => {
  return useQuery({
    queryKey: ['dashboard'],
    queryFn: dashboardService.getDashboard,
    staleTime: 5 * 60 * 1000, // 5 minutes
  });
};

// Usage in component
const Dashboard = () => {
  const { data, isLoading, error, refetch } = useDashboard();
  
  if (isLoading) return <LoadingSpinner />;
  if (error) return <ErrorMessage error={error} />;
  
  return <DashboardContent data={data.data} />;
};
```

---

## 📱 Responsive Design Tips

### Mobile-First Breakpoints
```css
/* Mobile: 0-640px */
.dashboard {
  padding: 1rem;
}

/* Tablet: 641px-1024px */
@media (min-width: 641px) {
  .dashboard {
    padding: 2rem;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }
}

/* Desktop: 1025px+ */
@media (min-width: 1025px) {
  .dashboard {
    padding: 3rem;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
  }
}
```

---

## 🚀 Performance Optimization

### 1. Lazy Load Routes
```javascript
import { lazy, Suspense } from 'react';

const Dashboard = lazy(() => import('./pages/Dashboard'));
const Courses = lazy(() => import('./pages/Courses'));
const Assignments = lazy(() => import('./pages/Assignments'));

const App = () => (
  <Suspense fallback={<LoadingSpinner />}>
    <Routes>
      <Route path="/dashboard" element={<Dashboard />} />
      <Route path="/courses" element={<Courses />} />
      <Route path="/assignments" element={<Assignments />} />
    </Routes>
  </Suspense>
);
```

### 2. Cache API Responses
```javascript
// Use React Query for automatic caching
const { data } = useQuery({
  queryKey: ['courses', status],
  queryFn: () => courseService.getMyCourses(status),
  staleTime: 5 * 60 * 1000, // Cache for 5 minutes
});
```

### 3. Virtualize Long Lists
```javascript
import { FixedSizeList } from 'react-window';

const CourseList = ({ courses }) => (
  <FixedSizeList
    height={600}
    itemCount={courses.length}
    itemSize={120}
  >
    {({ index, style }) => (
      <div style={style}>
        <CourseCard course={courses[index]} />
      </div>
    )}
  </FixedSizeList>
);
```

---

## 🎨 UI Library Recommendations

### Option 1: Material-UI (MUI)
```bash
npm install @mui/material @emotion/react @emotion/styled
```

### Option 2: Ant Design
```bash
npm install antd
```

### Option 3: Chakra UI
```bash
npm install @chakra-ui/react @emotion/react @emotion/styled framer-motion
```

### Option 4: Tailwind CSS
```bash
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p
```

---

## 📊 Chart Libraries

### For Progress Charts
```bash
npm install recharts
# or
npm install chart.js react-chartjs-2
# or
npm install apexcharts react-apexcharts
```

**Example with Recharts:**
```jsx
import { LineChart, Line, XAxis, YAxis, CartesianGrid, Tooltip } from 'recharts';

const ProgressChart = ({ data }) => (
  <LineChart width={600} height={300} data={data}>
    <CartesianGrid strokeDasharray="3 3" />
    <XAxis dataKey="date" />
    <YAxis />
    <Tooltip />
    <Line type="monotone" dataKey="hours" stroke="#8884d8" />
  </LineChart>
);
```

---

## 🔐 Authentication Flow

### Login & Token Storage
```javascript
// services/authService.js
export const authService = {
  login: async (email, password) => {
    const response = await apiClient.post('/auth/login', { email, password });
    const { token, user } = response.data;
    
    // Store token
    localStorage.setItem('api_token', token);
    localStorage.setItem('user', JSON.stringify(user));
    
    return { token, user };
  },
  
  logout: () => {
    localStorage.removeItem('api_token');
    localStorage.removeItem('user');
    window.location.href = '/login';
  },
  
  getCurrentUser: () => {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  },
};

// Protected Route Component
const ProtectedRoute = ({ children }) => {
  const token = localStorage.getItem('api_token');
  
  if (!token) {
    return <Navigate to="/login" />;
  }
  
  return children;
};

// Usage
<Route 
  path="/dashboard" 
  element={
    <ProtectedRoute>
      <Dashboard />
    </ProtectedRoute>
  } 
/>
```

---

## 🧪 Testing Recommendations

### Test API Integration
```javascript
// __tests__/services/courseService.test.js
import { courseService } from '@/services/courseService';
import { apiClient } from '@/config/api';

jest.mock('@/config/api');

describe('Course Service', () => {
  it('should fetch enrolled courses', async () => {
    const mockData = { data: [{ id: 1, title: 'Test Course' }] };
    apiClient.get.mockResolvedValue(mockData);
    
    const result = await courseService.getMyCourses('active');
    
    expect(apiClient.get).toHaveBeenCalledWith('/learner/courses?status=active');
    expect(result).toEqual(mockData);
  });
});
```

---

## 📦 Recommended Package List

```json
{
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.20.0",
    "axios": "^1.6.0",
    "@tanstack/react-query": "^5.0.0",
    "recharts": "^2.10.0",
    "@mui/material": "^5.14.0",
    "react-hook-form": "^7.48.0",
    "react-toastify": "^9.1.0",
    "date-fns": "^2.30.0",
    "react-player": "^2.13.0"
  },
  "devDependencies": {
    "@types/react": "^18.2.0",
    "vite": "^5.0.0",
    "eslint": "^8.54.0",
    "prettier": "^3.1.0"
  }
}
```

---

## 🎬 Quick Start Checklist

- [ ] Set up API configuration with base URL and auth headers
- [ ] Create all service files for API calls
- [ ] Build authentication flow (login/logout)
- [ ] Create protected route wrapper
- [ ] Build Dashboard page with stats and charts
- [ ] Build My Courses page with tabs
- [ ] Build Course Details page with modules
- [ ] Build Lesson Viewer with video player
- [ ] Build Assignments page with submission
- [ ] Build Quizzes page with quiz taker
- [ ] Build Performance/Analytics page
- [ ] Build Certificates page
- [ ] Build Profile page with edit functionality
- [ ] Implement notifications system
- [ ] Add loading states for all pages
- [ ] Add error handling and toasts
- [ ] Make responsive for mobile/tablet
- [ ] Test all API integrations
- [ ] Add loading skeletons
- [ ] Optimize performance

---

## 🆘 Common Issues & Solutions

### Issue: CORS Errors
**Solution:** Backend already has CORS configured. Make sure you're using the correct base URL.

### Issue: 401 Unauthorized
**Solution:** Check if token is being sent in Authorization header. Verify token is valid.

### Issue: File Upload Not Working
**Solution:** Ensure `Content-Type: multipart/form-data` header is set for file uploads.

### Issue: Progress Not Updating
**Solution:** Make sure to call the progress update endpoints regularly (e.g., every 30 seconds during lesson viewing).

---

## 📞 API Support

**Backend Documentation:** See `LEARNER_API_DOCUMENTATION.md` for detailed endpoint specs.

**Total Endpoints:** 36 fully functional endpoints

**Base URL:** `http://127.0.0.1:8000/api`

**Authentication:** Bearer Token in Authorization header

**All endpoints return consistent JSON format:**
```json
{
  "success": true/false,
  "data": { ... },
  "message": "Optional message"
}
```

---

## 🎉 You're Ready!

Your backend is **100% complete** with all features including:
- ✅ Complete course management
- ✅ Progress tracking
- ✅ Assignments & quizzes
- ✅ Learning streaks
- ✅ Performance analytics
- ✅ Personalized recommendations
- ✅ Certificates
- ✅ Notifications

Start building your frontend with confidence! 🚀

---

**Need Help?** Check the detailed API documentation in `LEARNER_API_DOCUMENTATION.md` or review example responses in `LEARNER_API_COMPLETE.md`.

**Happy Coding!** 💻✨
