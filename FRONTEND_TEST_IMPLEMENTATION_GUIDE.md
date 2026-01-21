# Frontend Implementation Guide: Student Test Taking Feature

## Overview
This guide provides complete implementation details for integrating the test-taking feature into the React TypeScript Vite frontend application.

---

## 1. Vite Proxy Configuration

Update your frontend's `vite.config.ts` to proxy API requests to the Laravel backend:

```typescript
// vite.config.ts
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost/learning-lms/public',
        changeOrigin: true,
        secure: false,
      }
    }
  }
});
```

---

## 2. TypeScript Interfaces

Create/update `src/types/test.ts`:

```typescript
// src/types/test.ts

export interface TestQuestion {
  id: number;
  test_id: number;
  question_text: string;
  question_type: 'multiple_choice' | 'true_false' | 'short_answer' | 'essay' | 'file_upload';
  options?: string[] | null;  // JSON array for multiple choice
  correct_answer?: string;    // Hidden from students during test
  marks: number;
  order_index: number;
  created_at: string;
  updated_at: string;
}

export interface Test {
  id: number;
  module_id: number;
  course_id: number;
  test_title: string;
  test_description?: string;
  instructions?: string;
  total_marks: number;
  passing_marks: number;
  time_limit: number;  // in minutes
  max_attempts: number;
  start_date: string;
  end_date: string;
  is_published: boolean;
  shuffle_questions: boolean;
  show_results_immediately: boolean;
  allow_review: boolean;
  created_at: string;
  updated_at: string;
  questions?: TestQuestion[];
}

export interface TestAnswer {
  id: number;
  submission_id: number;
  question_id: number;
  answer_text?: string;
  selected_option?: string;
  file_path?: string;
  marks_obtained?: number;
  feedback?: string;
  is_correct?: boolean;
  created_at: string;
  updated_at: string;
}

export interface TestSubmission {
  id: number;
  test_id: number;
  student_id: number;
  attempt_number: number;
  started_at: string;
  submitted_at?: string;
  time_taken?: number;  // in seconds
  total_score?: number;
  percentage?: number;
  passed?: boolean;
  submission_status: 'in_progress' | 'submitted' | 'late' | 'abandoned';
  grading_status: 'pending' | 'auto_graded' | 'manually_graded' | 'published';
  graded_by?: number;
  graded_at?: string;
  created_at: string;
  updated_at: string;
  answers?: TestAnswer[];
}

export interface TestViewResponse {
  success: boolean;
  data: {
    test: Test;
    can_attempt: boolean;
    reason?: string;
    remaining_attempts: number;
    current_submission?: TestSubmission;
  };
}

export interface StartTestResponse {
  success: boolean;
  data: TestSubmission;
  message?: string;
}

export interface SubmitTestResponse {
  success: boolean;
  data: {
    submission: TestSubmission;
    auto_graded_score?: number;
    total_marks: number;
    percentage?: number;
    passed?: boolean;
  };
  message?: string;
}

export interface TestResultsResponse {
  success: boolean;
  data: {
    submission: TestSubmission;
    test: Test;
    answers: (TestAnswer & { question: TestQuestion })[];
    score: number;
    total_marks: number;
    percentage: number;
    passed: boolean;
    can_review: boolean;
  };
}

// For course details - tests in modules
export interface ModuleTest {
  id: number;
  test_title: string;
  test_description?: string;
  total_marks: number;
  passing_marks: number;
  time_limit: number;
  max_attempts: number;
  start_date: string;
  end_date: string;
  status: 'scheduled' | 'active' | 'closed';
  is_published: boolean;
  submission_status: 'not_started' | 'in_progress' | 'submitted' | 'graded';
  grading_status?: string;
  submitted_at?: string;
  attempt_number?: number;
  total_score?: number;
  attempts_used: number;
  attempts_remaining: number;
}
```

---

## 3. API Service Functions

Create/update `src/services/testApi.ts`:

```typescript
// src/services/testApi.ts
import { fetchWithAuth } from './auth';
import type {
  TestViewResponse,
  StartTestResponse,
  SubmitTestResponse,
  TestResultsResponse,
} from '../types/test';

const API_BASE = '/api/student';

/**
 * Get test details for student view
 * Shows test info, whether student can attempt, remaining attempts, current submission if any
 */
export const getStudentTestView = async (testId: number): Promise<TestViewResponse> => {
  const response = await fetchWithAuth(`${API_BASE}/tests/${testId}`);
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to load test');
  }
  return response.json();
};

/**
 * Start a new test attempt
 * Creates a new submission record and returns it
 */
export const startTest = async (testId: number): Promise<StartTestResponse> => {
  const response = await fetchWithAuth(`${API_BASE}/tests/${testId}/start`, {
    method: 'POST',
  });
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to start test');
  }
  return response.json();
};

/**
 * Submit completed test
 * Sends all answers and finalizes the submission
 */
export const submitTest = async (
  submissionId: number,
  answers: Array<{
    question_id: number;
    answer_text?: string;
    selected_option?: string;
  }>
): Promise<SubmitTestResponse> => {
  const response = await fetchWithAuth(`${API_BASE}/test-submissions/${submissionId}/submit`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ answers }),
  });
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to submit test');
  }
  return response.json();
};

/**
 * Upload file for file_upload question type
 */
export const uploadTestFile = async (
  submissionId: number,
  questionId: number,
  file: File
): Promise<{ success: boolean; file_path: string }> => {
  const formData = new FormData();
  formData.append('question_id', questionId.toString());
  formData.append('file', file);

  const response = await fetchWithAuth(`${API_BASE}/test-submissions/${submissionId}/upload`, {
    method: 'POST',
    body: formData,
  });
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to upload file');
  }
  return response.json();
};

/**
 * Auto-save answers periodically
 * Call this every 30-60 seconds to prevent data loss
 */
export const autosaveAnswers = async (
  submissionId: number,
  answers: Array<{
    question_id: number;
    answer_text?: string;
    selected_option?: string;
  }>
): Promise<{ success: boolean }> => {
  const response = await fetchWithAuth(`${API_BASE}/test-submissions/${submissionId}/autosave`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ answers }),
  });
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to autosave');
  }
  return response.json();
};

/**
 * Get test results after submission
 * Only available if show_results_immediately is true or results are published
 */
export const getTestResults = async (testId: number): Promise<TestResultsResponse> => {
  const response = await fetchWithAuth(`${API_BASE}/tests/${testId}/results`);
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to load results');
  }
  return response.json();
};
```

---

## 4. React Components

### 4.1 Test View Component (Before Starting)

```typescript
// src/components/tests/StudentTestView.tsx
import React, { useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { getStudentTestView, startTest } from '../../services/testApi';
import type { Test, TestSubmission } from '../../types/test';

interface TestViewData {
  test: Test;
  can_attempt: boolean;
  reason?: string;
  remaining_attempts: number;
  current_submission?: TestSubmission;
}

const StudentTestView: React.FC = () => {
  const { testId } = useParams<{ testId: string }>();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [data, setData] = useState<TestViewData | null>(null);
  const [starting, setStarting] = useState(false);

  useEffect(() => {
    loadTestView();
  }, [testId]);

  const loadTestView = async () => {
    try {
      setLoading(true);
      setError(null);
      const response = await getStudentTestView(Number(testId));
      if (response.success) {
        setData(response.data);
        
        // If there's an in-progress submission, redirect to test taking
        if (response.data.current_submission) {
          navigate(`/tests/${testId}/take`, { 
            state: { submission: response.data.current_submission, test: response.data.test }
          });
        }
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load test');
    } finally {
      setLoading(false);
    }
  };

  const handleStartTest = async () => {
    try {
      setStarting(true);
      setError(null);
      const response = await startTest(Number(testId));
      if (response.success) {
        navigate(`/tests/${testId}/take`, { 
          state: { submission: response.data, test: data?.test }
        });
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to start test');
    } finally {
      setStarting(false);
    }
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleString();
  };

  const getStatusBadge = () => {
    if (!data?.test) return null;
    const now = new Date();
    const start = new Date(data.test.start_date);
    const end = new Date(data.test.end_date);

    if (now < start) {
      return <span className="badge bg-yellow-500 text-white px-2 py-1 rounded">Scheduled</span>;
    } else if (now > end) {
      return <span className="badge bg-red-500 text-white px-2 py-1 rounded">Closed</span>;
    } else {
      return <span className="badge bg-green-500 text-white px-2 py-1 rounded">Active</span>;
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-2xl mx-auto p-6">
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {error}
        </div>
        <button 
          onClick={loadTestView}
          className="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Retry
        </button>
      </div>
    );
  }

  if (!data) return null;

  const { test, can_attempt, reason, remaining_attempts } = data;

  return (
    <div className="max-w-3xl mx-auto p-6">
      <div className="bg-white rounded-lg shadow-lg p-6">
        {/* Header */}
        <div className="flex justify-between items-start mb-6">
          <div>
            <h1 className="text-2xl font-bold text-gray-800">{test.test_title}</h1>
            {test.test_description && (
              <p className="text-gray-600 mt-2">{test.test_description}</p>
            )}
          </div>
          {getStatusBadge()}
        </div>

        {/* Test Details */}
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
          <div className="bg-gray-50 p-3 rounded">
            <p className="text-sm text-gray-500">Total Marks</p>
            <p className="text-lg font-semibold">{test.total_marks}</p>
          </div>
          <div className="bg-gray-50 p-3 rounded">
            <p className="text-sm text-gray-500">Passing Marks</p>
            <p className="text-lg font-semibold">{test.passing_marks}</p>
          </div>
          <div className="bg-gray-50 p-3 rounded">
            <p className="text-sm text-gray-500">Time Limit</p>
            <p className="text-lg font-semibold">{test.time_limit} min</p>
          </div>
          <div className="bg-gray-50 p-3 rounded">
            <p className="text-sm text-gray-500">Attempts Left</p>
            <p className="text-lg font-semibold">{remaining_attempts} / {test.max_attempts}</p>
          </div>
        </div>

        {/* Schedule */}
        <div className="mb-6 p-4 bg-blue-50 rounded">
          <h3 className="font-semibold text-blue-800 mb-2">Schedule</h3>
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span className="text-gray-600">Start:</span>{' '}
              <span className="font-medium">{formatDate(test.start_date)}</span>
            </div>
            <div>
              <span className="text-gray-600">End:</span>{' '}
              <span className="font-medium">{formatDate(test.end_date)}</span>
            </div>
          </div>
        </div>

        {/* Instructions */}
        {test.instructions && (
          <div className="mb-6">
            <h3 className="font-semibold text-gray-800 mb-2">Instructions</h3>
            <div className="bg-yellow-50 p-4 rounded border border-yellow-200">
              <p className="text-gray-700 whitespace-pre-line">{test.instructions}</p>
            </div>
          </div>
        )}

        {/* Test Settings Info */}
        <div className="mb-6 text-sm text-gray-600">
          <ul className="list-disc list-inside space-y-1">
            {test.shuffle_questions && <li>Questions will be shuffled</li>}
            {test.show_results_immediately && <li>Results will be shown after submission</li>}
            {test.allow_review && <li>You can review your answers after grading</li>}
          </ul>
        </div>

        {/* Action Button */}
        <div className="flex justify-center">
          {can_attempt ? (
            <button
              onClick={handleStartTest}
              disabled={starting}
              className="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
              {starting ? 'Starting...' : 'Start Test'}
            </button>
          ) : (
            <div className="text-center">
              <p className="text-red-600 font-medium">{reason || 'Cannot attempt this test'}</p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default StudentTestView;
```

### 4.2 Test Taking Component

```typescript
// src/components/tests/TestTaking.tsx
import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useLocation, useNavigate } from 'react-router-dom';
import { submitTest, autosaveAnswers, uploadTestFile } from '../../services/testApi';
import type { Test, TestSubmission, TestQuestion } from '../../types/test';

interface Answer {
  question_id: number;
  answer_text?: string;
  selected_option?: string;
  file_path?: string;
}

const TestTaking: React.FC = () => {
  const { testId } = useParams<{ testId: string }>();
  const location = useLocation();
  const navigate = useNavigate();
  
  const [test, setTest] = useState<Test | null>(location.state?.test || null);
  const [submission, setSubmission] = useState<TestSubmission | null>(location.state?.submission || null);
  const [answers, setAnswers] = useState<Map<number, Answer>>(new Map());
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [timeRemaining, setTimeRemaining] = useState<number>(0);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [lastSaved, setLastSaved] = useState<Date | null>(null);

  // Initialize timer
  useEffect(() => {
    if (test && submission) {
      const startTime = new Date(submission.started_at).getTime();
      const timeLimit = test.time_limit * 60 * 1000; // Convert to ms
      const endTime = startTime + timeLimit;
      const remaining = Math.max(0, Math.floor((endTime - Date.now()) / 1000));
      setTimeRemaining(remaining);
    }
  }, [test, submission]);

  // Countdown timer
  useEffect(() => {
    if (timeRemaining <= 0) return;

    const timer = setInterval(() => {
      setTimeRemaining((prev) => {
        if (prev <= 1) {
          clearInterval(timer);
          handleAutoSubmit();
          return 0;
        }
        return prev - 1;
      });
    }, 1000);

    return () => clearInterval(timer);
  }, [timeRemaining]);

  // Autosave every 30 seconds
  useEffect(() => {
    if (!submission) return;

    const autosaveInterval = setInterval(async () => {
      try {
        const answersArray = Array.from(answers.values());
        if (answersArray.length > 0) {
          await autosaveAnswers(submission.id, answersArray);
          setLastSaved(new Date());
        }
      } catch (err) {
        console.error('Autosave failed:', err);
      }
    }, 30000);

    return () => clearInterval(autosaveInterval);
  }, [submission, answers]);

  // Prevent accidental navigation
  useEffect(() => {
    const handleBeforeUnload = (e: BeforeUnloadEvent) => {
      e.preventDefault();
      e.returnValue = '';
    };
    window.addEventListener('beforeunload', handleBeforeUnload);
    return () => window.removeEventListener('beforeunload', handleBeforeUnload);
  }, []);

  const handleAutoSubmit = async () => {
    if (submission) {
      await handleSubmit();
    }
  };

  const updateAnswer = (questionId: number, value: string, type: 'text' | 'option') => {
    setAnswers((prev) => {
      const newAnswers = new Map(prev);
      const existing = newAnswers.get(questionId) || { question_id: questionId };
      
      if (type === 'text') {
        existing.answer_text = value;
      } else {
        existing.selected_option = value;
      }
      
      newAnswers.set(questionId, existing);
      return newAnswers;
    });
  };

  const handleFileUpload = async (questionId: number, file: File) => {
    if (!submission) return;
    
    try {
      const result = await uploadTestFile(submission.id, questionId, file);
      setAnswers((prev) => {
        const newAnswers = new Map(prev);
        newAnswers.set(questionId, {
          question_id: questionId,
          file_path: result.file_path,
        });
        return newAnswers;
      });
    } catch (err) {
      setError('Failed to upload file');
    }
  };

  const handleSubmit = async () => {
    if (!submission || submitting) return;

    const confirmSubmit = window.confirm(
      'Are you sure you want to submit? You cannot change your answers after submission.'
    );
    if (!confirmSubmit) return;

    try {
      setSubmitting(true);
      setError(null);
      
      const answersArray = Array.from(answers.values());
      const response = await submitTest(submission.id, answersArray);
      
      if (response.success) {
        navigate(`/tests/${testId}/results`, { state: { result: response.data } });
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to submit test');
    } finally {
      setSubmitting(false);
    }
  };

  const formatTime = (seconds: number): string => {
    const hrs = Math.floor(seconds / 3600);
    const mins = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    
    if (hrs > 0) {
      return `${hrs}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
    return `${mins}:${secs.toString().padStart(2, '0')}`;
  };

  const renderQuestion = (question: TestQuestion) => {
    const answer = answers.get(question.id);

    switch (question.question_type) {
      case 'multiple_choice':
        const options = typeof question.options === 'string' 
          ? JSON.parse(question.options) 
          : question.options || [];
        
        return (
          <div className="space-y-2">
            {options.map((option: string, index: number) => (
              <label key={index} className="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input
                  type="radio"
                  name={`question_${question.id}`}
                  value={option}
                  checked={answer?.selected_option === option}
                  onChange={(e) => updateAnswer(question.id, e.target.value, 'option')}
                  className="mr-3"
                />
                <span>{option}</span>
              </label>
            ))}
          </div>
        );

      case 'true_false':
        return (
          <div className="space-y-2">
            {['True', 'False'].map((option) => (
              <label key={option} className="flex items-center p-3 border rounded hover:bg-gray-50 cursor-pointer">
                <input
                  type="radio"
                  name={`question_${question.id}`}
                  value={option}
                  checked={answer?.selected_option === option}
                  onChange={(e) => updateAnswer(question.id, e.target.value, 'option')}
                  className="mr-3"
                />
                <span>{option}</span>
              </label>
            ))}
          </div>
        );

      case 'short_answer':
        return (
          <input
            type="text"
            value={answer?.answer_text || ''}
            onChange={(e) => updateAnswer(question.id, e.target.value, 'text')}
            className="w-full p-3 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Enter your answer"
          />
        );

      case 'essay':
        return (
          <textarea
            value={answer?.answer_text || ''}
            onChange={(e) => updateAnswer(question.id, e.target.value, 'text')}
            rows={6}
            className="w-full p-3 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            placeholder="Write your essay here..."
          />
        );

      case 'file_upload':
        return (
          <div>
            <input
              type="file"
              onChange={(e) => {
                const file = e.target.files?.[0];
                if (file) handleFileUpload(question.id, file);
              }}
              className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            {answer?.file_path && (
              <p className="mt-2 text-sm text-green-600">✓ File uploaded</p>
            )}
          </div>
        );

      default:
        return <p>Unknown question type</p>;
    }
  };

  if (!test || !submission) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <p className="text-red-600 mb-4">Test data not found. Please start the test again.</p>
          <button
            onClick={() => navigate(`/tests/${testId}`)}
            className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            Go Back
          </button>
        </div>
      </div>
    );
  }

  const questions = test.questions || [];
  const currentQuestion = questions[currentQuestionIndex];

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header with Timer */}
      <div className="sticky top-0 bg-white shadow-md z-10">
        <div className="max-w-4xl mx-auto px-4 py-3 flex justify-between items-center">
          <h1 className="text-lg font-semibold truncate">{test.test_title}</h1>
          <div className="flex items-center gap-4">
            {lastSaved && (
              <span className="text-xs text-gray-500">
                Saved {lastSaved.toLocaleTimeString()}
              </span>
            )}
            <div className={`px-4 py-2 rounded font-mono font-bold ${
              timeRemaining < 300 ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'
            }`}>
              ⏱ {formatTime(timeRemaining)}
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-4xl mx-auto p-4">
        {error && (
          <div className="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {error}
          </div>
        )}

        {/* Question Navigation */}
        <div className="mb-4 flex flex-wrap gap-2">
          {questions.map((q, index) => (
            <button
              key={q.id}
              onClick={() => setCurrentQuestionIndex(index)}
              className={`w-10 h-10 rounded ${
                index === currentQuestionIndex
                  ? 'bg-blue-600 text-white'
                  : answers.has(q.id)
                  ? 'bg-green-100 text-green-800 border border-green-300'
                  : 'bg-gray-100 text-gray-700 border'
              }`}
            >
              {index + 1}
            </button>
          ))}
        </div>

        {/* Current Question */}
        {currentQuestion && (
          <div className="bg-white rounded-lg shadow p-6">
            <div className="flex justify-between items-start mb-4">
              <span className="text-sm text-gray-500">
                Question {currentQuestionIndex + 1} of {questions.length}
              </span>
              <span className="text-sm font-medium text-blue-600">
                {currentQuestion.marks} marks
              </span>
            </div>

            <h2 className="text-lg font-medium mb-4">{currentQuestion.question_text}</h2>

            <div className="mb-6">
              {renderQuestion(currentQuestion)}
            </div>

            {/* Navigation Buttons */}
            <div className="flex justify-between">
              <button
                onClick={() => setCurrentQuestionIndex((prev) => Math.max(0, prev - 1))}
                disabled={currentQuestionIndex === 0}
                className="px-4 py-2 border rounded hover:bg-gray-50 disabled:opacity-50"
              >
                ← Previous
              </button>
              
              {currentQuestionIndex === questions.length - 1 ? (
                <button
                  onClick={handleSubmit}
                  disabled={submitting}
                  className="px-6 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                >
                  {submitting ? 'Submitting...' : 'Submit Test'}
                </button>
              ) : (
                <button
                  onClick={() => setCurrentQuestionIndex((prev) => Math.min(questions.length - 1, prev + 1))}
                  className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                >
                  Next →
                </button>
              )}
            </div>
          </div>
        )}

        {/* Submit Button (Always Visible) */}
        <div className="mt-6 text-center">
          <button
            onClick={handleSubmit}
            disabled={submitting}
            className="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            {submitting ? 'Submitting...' : 'Submit Test'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default TestTaking;
```

### 4.3 Test Results Component

```typescript
// src/components/tests/TestResults.tsx
import React, { useEffect, useState } from 'react';
import { useParams, useLocation, useNavigate } from 'react-router-dom';
import { getTestResults } from '../../services/testApi';
import type { TestResultsResponse } from '../../types/test';

const TestResults: React.FC = () => {
  const { testId } = useParams<{ testId: string }>();
  const location = useLocation();
  const navigate = useNavigate();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [results, setResults] = useState<TestResultsResponse['data'] | null>(
    location.state?.result || null
  );

  useEffect(() => {
    if (!results) {
      loadResults();
    } else {
      setLoading(false);
    }
  }, [testId]);

  const loadResults = async () => {
    try {
      setLoading(true);
      const response = await getTestResults(Number(testId));
      if (response.success) {
        setResults(response.data);
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load results');
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error || !results) {
    return (
      <div className="max-w-2xl mx-auto p-6 text-center">
        <div className="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
          {error || 'Results are not available yet. Please check back later.'}
        </div>
        <button
          onClick={() => navigate(-1)}
          className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          Go Back
        </button>
      </div>
    );
  }

  return (
    <div className="max-w-3xl mx-auto p-6">
      <div className="bg-white rounded-lg shadow-lg p-6">
        {/* Header */}
        <div className="text-center mb-8">
          <h1 className="text-2xl font-bold text-gray-800 mb-2">Test Results</h1>
          <h2 className="text-lg text-gray-600">{results.test.test_title}</h2>
        </div>

        {/* Score Card */}
        <div className={`p-6 rounded-lg mb-6 text-center ${
          results.passed ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'
        }`}>
          <div className="text-5xl font-bold mb-2">
            {results.percentage.toFixed(1)}%
          </div>
          <div className="text-lg mb-2">
            {results.score} / {results.total_marks} marks
          </div>
          <div className={`inline-block px-4 py-2 rounded-full font-semibold ${
            results.passed 
              ? 'bg-green-500 text-white' 
              : 'bg-red-500 text-white'
          }`}>
            {results.passed ? '✓ PASSED' : '✗ FAILED'}
          </div>
          <p className="mt-2 text-sm text-gray-600">
            Passing marks: {results.test.passing_marks}
          </p>
        </div>

        {/* Submission Details */}
        <div className="mb-6 p-4 bg-gray-50 rounded">
          <h3 className="font-semibold mb-2">Submission Details</h3>
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <span className="text-gray-500">Attempt:</span>{' '}
              <span className="font-medium">#{results.submission.attempt_number}</span>
            </div>
            <div>
              <span className="text-gray-500">Submitted:</span>{' '}
              <span className="font-medium">
                {results.submission.submitted_at 
                  ? new Date(results.submission.submitted_at).toLocaleString()
                  : 'N/A'}
              </span>
            </div>
            <div>
              <span className="text-gray-500">Time Taken:</span>{' '}
              <span className="font-medium">
                {results.submission.time_taken 
                  ? `${Math.floor(results.submission.time_taken / 60)} min ${results.submission.time_taken % 60} sec`
                  : 'N/A'}
              </span>
            </div>
            <div>
              <span className="text-gray-500">Status:</span>{' '}
              <span className="font-medium capitalize">{results.submission.grading_status}</span>
            </div>
          </div>
        </div>

        {/* Answer Review (if allowed) */}
        {results.can_review && results.answers && (
          <div>
            <h3 className="font-semibold mb-4">Answer Review</h3>
            <div className="space-y-4">
              {results.answers.map((answer, index) => (
                <div key={answer.id} className={`p-4 rounded border ${
                  answer.is_correct 
                    ? 'bg-green-50 border-green-200' 
                    : 'bg-red-50 border-red-200'
                }`}>
                  <div className="flex justify-between items-start mb-2">
                    <span className="font-medium">Q{index + 1}. {answer.question.question_text}</span>
                    <span className="text-sm">
                      {answer.marks_obtained ?? 0} / {answer.question.marks}
                    </span>
                  </div>
                  <div className="text-sm">
                    <p><strong>Your answer:</strong> {answer.answer_text || answer.selected_option || 'No answer'}</p>
                    {answer.question.correct_answer && (
                      <p className="text-green-700"><strong>Correct answer:</strong> {answer.question.correct_answer}</p>
                    )}
                    {answer.feedback && (
                      <p className="mt-2 text-gray-600"><strong>Feedback:</strong> {answer.feedback}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Actions */}
        <div className="mt-8 flex justify-center gap-4">
          <button
            onClick={() => navigate('/courses')}
            className="px-6 py-2 border rounded hover:bg-gray-50"
          >
            Back to Courses
          </button>
          <button
            onClick={() => navigate(`/tests/${testId}`)}
            className="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
          >
            View Test Details
          </button>
        </div>
      </div>
    </div>
  );
};

export default TestResults;
```

---

## 5. Router Configuration

Add routes in your `App.tsx` or router configuration:

```typescript
// In your router configuration
import StudentTestView from './components/tests/StudentTestView';
import TestTaking from './components/tests/TestTaking';
import TestResults from './components/tests/TestResults';

// Add these routes (inside authenticated routes)
<Route path="/tests/:testId" element={<StudentTestView />} />
<Route path="/tests/:testId/take" element={<TestTaking />} />
<Route path="/tests/:testId/results" element={<TestResults />} />
```

---

## 6. Course Details Integration

In your `CourseDetails.tsx`, display tests from each module:

```typescript
// In CourseDetails.tsx - render tests section in each module
{module.tests && module.tests.length > 0 && (
  <div className="mt-4">
    <h4 className="font-medium text-gray-700 mb-2">Tests</h4>
    <div className="space-y-2">
      {module.tests.map((test) => (
        <div
          key={test.id}
          className="flex items-center justify-between p-3 bg-purple-50 rounded-lg border border-purple-100"
        >
          <div className="flex items-center gap-3">
            <span className="text-purple-600">📝</span>
            <div>
              <p className="font-medium">{test.test_title}</p>
              <p className="text-sm text-gray-500">
                {test.total_marks} marks • {test.time_limit} min • 
                {test.attempts_remaining} attempts left
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <span className={`px-2 py-1 text-xs rounded ${
              test.status === 'active' 
                ? 'bg-green-100 text-green-700'
                : test.status === 'scheduled'
                ? 'bg-yellow-100 text-yellow-700'
                : 'bg-gray-100 text-gray-700'
            }`}>
              {test.status}
            </span>
            {test.status === 'active' && test.attempts_remaining > 0 && (
              <button
                onClick={() => navigate(`/tests/${test.id}`)}
                className="px-3 py-1 bg-purple-600 text-white text-sm rounded hover:bg-purple-700"
              >
                {test.submission_status === 'in_progress' ? 'Continue' : 'Start'}
              </button>
            )}
            {test.submission_status === 'graded' && (
              <button
                onClick={() => navigate(`/tests/${test.id}/results`)}
                className="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700"
              >
                View Results
              </button>
            )}
          </div>
        </div>
      ))}
    </div>
  </div>
)}
```

---

## 7. API Endpoints Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/student/tests/{testId}` | Get test details with attempt info |
| POST | `/api/student/tests/{testId}/start` | Start a new test attempt |
| POST | `/api/student/test-submissions/{submissionId}/submit` | Submit completed test |
| POST | `/api/student/test-submissions/{submissionId}/upload` | Upload file for file_upload questions |
| POST | `/api/student/test-submissions/{submissionId}/autosave` | Auto-save answers |
| GET | `/api/student/tests/{testId}/results` | Get test results |

---

## 8. Important Notes

1. **Authentication**: All endpoints require authentication. Ensure the `Authorization: Bearer <token>` header is sent with every request.

2. **Time Management**: The timer is client-side based on `started_at` from the submission. The server also validates time limits on submission.

3. **Auto-save**: Implement auto-save to prevent data loss. The provided code saves every 30 seconds.

4. **File Uploads**: For `file_upload` question types, use `FormData` and don't set `Content-Type` header (let the browser set it with boundary).

5. **Prevent Navigation**: Add `beforeunload` event listener to warn users before leaving during a test.

6. **Error Handling**: Always handle errors gracefully and show user-friendly messages.

7. **Responsive Design**: The provided components use Tailwind CSS. Adjust classes as needed for your design system.

---

## 9. Testing Checklist

- [ ] Test view loads correctly with test details
- [ ] "Start Test" button creates submission and redirects
- [ ] Timer counts down correctly
- [ ] All question types render properly (multiple_choice, true_false, short_answer, essay, file_upload)
- [ ] Answers are saved and persist across question navigation
- [ ] Auto-save works every 30 seconds
- [ ] File upload works for file_upload questions
- [ ] Submit test sends all answers and shows results
- [ ] Results page displays score, pass/fail status
- [ ] Answer review shows correct/incorrect answers (if allowed)
- [ ] Navigation warning appears when trying to leave during test
- [ ] Test cannot be started if max attempts reached
- [ ] Test cannot be started before start_date or after end_date
