# Quiz Feature - Frontend Implementation Guide

## 🎯 Overview
The quiz backend is fully functional. This guide will help you implement the complete quiz-taking experience in the learner dashboard.

---

## 📋 Complete Quiz Flow

```
1. User clicks "Start Quiz" button
   ↓
2. Call POST /api/learner/quizzes/{id}/start
   ↓
3. Receive questions and start timer
   ↓
4. Display questions one by one
   ↓
5. As user selects answer, call PUT /api/learner/quiz-attempts/{attemptId}/answer
   ↓
6. When all answered or time expires, call POST /api/learner/quiz-attempts/{attemptId}/submit
   ↓
7. Show results with score and pass/fail status
```

---

## 🔌 API Endpoints Reference

### 1. Start Quiz
**POST** `/api/learner/quizzes/{quizId}/start`

**Headers:**
```javascript
{
  'Authorization': 'Bearer YOUR_TOKEN',
  'Content-Type': 'application/json',
  'Accept': 'application/json'
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Quiz attempt started",
  "data": {
    "attempt_id": 15,
    "quiz_id": 2,
    "quiz_title": "HTML Basics",
    "attempt_number": 1,
    "started_at": "2025-12-12T12:30:00.000000Z",
    "expires_at": "2025-12-12T12:35:00.000000Z",
    "time_limit_minutes": 5,
    "total_points": 10,
    "passing_percentage": "70.00",
    "questions": [
      {
        "id": 1,
        "question_text": "What's the tag for insert an image?",
        "question_type": "multiple_choice",
        "points": 10,
        "options": [
          {"id": 1, "option_text": "video"},
          {"id": 2, "option_text": "source"},
          {"id": 3, "option_text": "img"},
          {"id": 4, "option_text": "file"}
        ]
      }
    ]
  }
}
```

**Error Responses:**
- `403` - Not enrolled in course
- `400` - Max attempts reached or quiz not available
- `401` - Not authenticated

---

### 2. Submit Answer (Auto-save)
**PUT** `/api/learner/quiz-attempts/{attemptId}/answer`

**Request Body:**
```json
{
  "question_id": 1,
  "answer": "img"
}
```

**⚠️ CRITICAL - Answer Format:**
- `question_id` must be an **integer** (not string)
- `answer` must be a **string** (the exact option text, not option ID)
- Backend stores answers with string keys for JSON compatibility
- Do NOT send `option_id` - send the actual `option_text`

**Example - Correct:**
```javascript
{
  question_id: 1,           // ✅ Integer
  answer: "img"             // ✅ String (the option text)
}
```

**Example - Wrong:**
```javascript
{
  question_id: "1",         // ❌ String - will fail validation
  answer: 3                 // ❌ Integer - will fail validation
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Answer recorded successfully",
  "data": {
    "question_id": 1,
    "answer": "img"
  }
}
```

**Response (422) - Validation Error:**
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "question_id": ["The question id field is required."],
    "answer": ["The answer field must be a string."]
  },
  "received_data": { /* what you sent */ }
}
```

**Important:** Call this immediately when user selects an answer (auto-save)

---

### 3. Submit Quiz (Final)
**POST** `/api/learner/quiz-attempts/{attemptId}/submit`

**Response (200):**
```json
{
  "success": true,
  "message": "Congratulations! You passed the quiz.",
  "data": {
    "attempt_id": 15,
    "score": 100.00,
    "points_earned": 10,
    "total_points": 10,
    "time_taken_minutes": 3,
    "passed": true,
    "passing_percentage": "70.00",
    "feedback": "Great job! You scored 100%",
    "answers": [
      {
        "question_id": 1,
        "question_text": "What's the tag for insert an image?",
        "user_answer": "img",
        "correct_answer": "img",
        "is_correct": true,
        "points_earned": 10,
        "points_possible": 10
      }
    ]
  }
}
```

---

## 💻 Implementation Steps

### Step 1: Quiz Start Button Handler

```javascript
async function handleStartQuiz(quizId) {
  try {
    // Show loading state
    setLoading(true);
    setError(null);
    
    const response = await fetch(
      `${API_BASE_URL}/learner/quizzes/${quizId}/start`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      }
    );
    
    if (!response.ok) {
      const error = await response.json();
      
      if (response.status === 403) {
        // Not enrolled - redirect to enrollment
        showMessage('Please enroll in this course first');
        redirectToEnrollment(quizId);
        return;
      }
      
      if (response.status === 400) {
        // Max attempts or not available
        showMessage(error.message);
        return;
      }
      
      throw new Error(error.message || 'Failed to start quiz');
    }
    
    const data = await response.json();
    
    // Store quiz session data
    setQuizSession({
      attemptId: data.data.attempt_id,
      quizId: data.data.quiz_id,
      quizTitle: data.data.quiz_title,
      questions: data.data.questions,
      expiresAt: new Date(data.data.expires_at),
      timeLimitMinutes: data.data.time_limit_minutes,
      totalPoints: data.data.total_points,
      passingPercentage: data.data.passing_percentage,
      startedAt: new Date(data.data.started_at)
    });
    
    // Initialize answers array
    setUserAnswers({});
    
    // Navigate to quiz taking page
    navigateToQuizTaking();
    
    // Start timer
    startQuizTimer(data.data.expires_at);
    
  } catch (error) {
    setError(error.message);
    showErrorMessage('Failed to start quiz: ' + error.message);
  } finally {
    setLoading(false);
  }
}
```

---

### Step 2: Quiz Taking Interface

```javascript
function QuizTakingPage() {
  const [currentQuestionIndex, setCurrentQuestionIndex] = useState(0);
  const [userAnswers, setUserAnswers] = useState({});
  const [timeRemaining, setTimeRemaining] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  
  const quizSession = getQuizSession(); // From state/context
  const currentQuestion = quizSession.questions[currentQuestionIndex];
  const totalQuestions = quizSession.questions.length;
  
  // Timer countdown
  useEffect(() => {
    if (!quizSession.expiresAt) return;
    
    const interval = setInterval(() => {
      const now = new Date();
      const remaining = quizSession.expiresAt - now;
      
      if (remaining <= 0) {
        // Time's up! Auto-submit
        clearInterval(interval);
        handleAutoSubmit();
      } else {
        setTimeRemaining(remaining);
      }
    }, 1000);
    
    return () => clearInterval(interval);
  }, [quizSession.expiresAt]);
  
  // Format time remaining
  const formatTime = (milliseconds) => {
    const totalSeconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}:${seconds.toString().padStart(2, '0')}`;
  };
  
  return (
    <div className="quiz-taking-container">
      {/* Header with timer */}
      <div className="quiz-header">
        <h2>{quizSession.quizTitle}</h2>
        
        {timeRemaining && (
          <div className={`timer ${timeRemaining < 60000 ? 'warning' : ''}`}>
            ⏱️ Time Remaining: {formatTime(timeRemaining)}
          </div>
        )}
        
        <div className="progress-bar">
          Question {currentQuestionIndex + 1} of {totalQuestions}
        </div>
      </div>
      
      {/* Question */}
      <div className="question-card">
        <h3>Question {currentQuestionIndex + 1}</h3>
        <p className="question-text">{currentQuestion.question_text}</p>
        <p className="points-badge">{currentQuestion.points} points</p>
        
        {/* Options */}
        <div className="options-list">
          {currentQuestion.options.map((option) => (
            <button
              key={option.id}
              className={`option-button ${
                userAnswers[currentQuestion.id] === option.option_text
                  ? 'selected'
                  : ''
              }`}
              onClick={() => handleSelectAnswer(currentQuestion.id, option.option_text)}
            >
              {option.option_text}
            </button>
          ))}
        </div>
      </div>
      
      {/* Navigation */}
      <div className="quiz-navigation">
        <button
          onClick={() => setCurrentQuestionIndex(prev => prev - 1)}
          disabled={currentQuestionIndex === 0}
        >
          ← Previous
        </button>
        
        {/* Show progress dots */}
        <div className="question-dots">
          {quizSession.questions.map((q, idx) => (
            <span 
              key={idx}
              className={`dot ${
                userAnswers[q.id] ? 'answered' : ''
              } ${
                idx === currentQuestionIndex ? 'current' : ''
              }`}
              onClick={() => setCurrentQuestionIndex(idx)}
            />
          ))}
        </div>
        
        {currentQuestionIndex < totalQuestions - 1 ? (
          <button
            onClick={() => setCurrentQuestionIndex(prev => prev + 1)}
          >
            Next →
          </button>
        ) : (
          <button
            onClick={handleSubmitQuiz}
            disabled={isSubmitting}
            className="submit-button"
          >
            {isSubmitting ? 'Submitting...' : 'Submit Quiz'}
          </button>
        )}
      </div>
    </div>
  );
}
```

---

### Step 3: Answer Selection Handler (Auto-save)

```javascript
async function handleSelectAnswer(questionId, answer) {
  // Update local state immediately for UI feedback
  setUserAnswers(prev => ({
    ...prev,
    [questionId]: answer
  }));
  
  // Save to backend (auto-save)
  try {
    // ⚠️ CRITICAL: Send question_id as INTEGER, answer as STRING
    const payload = {
      question_id: parseInt(questionId),  // Must be integer
      answer: String(answer)              // Must be string (option_text, not option_id)
    };
    
    console.log('Submitting answer:', payload); // Debug logging
    
    const response = await fetch(
      `${API_BASE_URL}/learner/quiz-attempts/${quizSession.attemptId}/answer`,
      {
        method: 'PUT',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      }
    );
    
    if (!response.ok) {
      const error = await response.json();
      
      // Handle validation errors (422)
      if (response.status === 422) {
        console.error('Validation error:', error.errors);
        console.error('Sent data:', error.received_data);
        throw new Error('Invalid answer format. Check console for details.');
      }
      
      if (response.status === 400 && error.message.includes('Time limit')) {
        // Time expired during answer submission
        showMessage('Time limit exceeded. Quiz will be submitted.');
        handleAutoSubmit();
        return;
      }
      
      throw new Error(error.message || 'Failed to save answer');
    }
    
    // Show brief "Saved" indicator (optional)
    showBriefNotification('Answer saved ✓');
    
  } catch (error) {
    console.error('Failed to save answer:', error);
    showErrorMessage('Failed to save answer. Please try again.');
  }
}
```

**Common Pitfalls:**

❌ **WRONG - Sending option_id instead of option_text:**
```javascript
// DON'T DO THIS:
{
  question_id: 1,
  answer: 3  // ❌ This is the option ID, not the answer text
}
```

✅ **CORRECT - Send the actual option text:**
```javascript
// DO THIS:
{
  question_id: 1,
  answer: "img"  // ✅ This is the option text
}

// When user clicks an option:
const selectedOption = question.options.find(opt => opt.id === selectedId);
handleSelectAnswer(question.id, selectedOption.option_text); // ✅ Use option_text
```

---

### Step 4: Submit Quiz Handler

```javascript
async function handleSubmitQuiz() {
  // Confirm submission
  const confirmed = await showConfirmDialog(
    'Submit Quiz?',
    'Are you sure you want to submit? You cannot change your answers after submission.'
  );
  
  if (!confirmed) return;
  
  setIsSubmitting(true);
  
  try {
    const response = await fetch(
      `${API_BASE_URL}/learner/quiz-attempts/${quizSession.attemptId}/submit`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${authToken}`,
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      }
    );
    
    if (!response.ok) {
      const error = await response.json();
      throw new Error(error.message || 'Failed to submit quiz');
    }
    
    const data = await response.json();
    
    // Store results
    setQuizResults(data.data);
    
    // Navigate to results page
    navigateToQuizResults();
    
  } catch (error) {
    showErrorMessage('Failed to submit quiz: ' + error.message);
  } finally {
    setIsSubmitting(false);
  }
}

// Auto-submit when time expires
async function handleAutoSubmit() {
  showMessage('Time is up! Submitting quiz automatically...');
  await handleSubmitQuiz();
}
```

---

### Step 5: Results Page

```javascript
function QuizResultsPage() {
  const results = getQuizResults(); // From state/context
  
  return (
    <div className="quiz-results-container">
      {/* Score Banner */}
      <div className={`score-banner ${results.passed ? 'passed' : 'failed'}`}>
        <div className="score-circle">
          <span className="score-value">{results.score}%</span>
          <span className="score-label">Score</span>
        </div>
        
        <div className="result-status">
          {results.passed ? (
            <>
              <h2>🎉 Congratulations!</h2>
              <p>You passed the quiz!</p>
            </>
          ) : (
            <>
              <h2>Keep Trying!</h2>
              <p>You need {results.passing_percentage}% to pass</p>
            </>
          )}
        </div>
      </div>
      
      {/* Stats */}
      <div className="quiz-stats">
        <div className="stat-item">
          <span className="stat-label">Points Earned</span>
          <span className="stat-value">
            {results.points_earned} / {results.total_points}
          </span>
        </div>
        
        <div className="stat-item">
          <span className="stat-label">Time Taken</span>
          <span className="stat-value">{results.time_taken_minutes} min</span>
        </div>
        
        <div className="stat-item">
          <span className="stat-label">Status</span>
          <span className={`stat-value ${results.passed ? 'passed' : 'failed'}`}>
            {results.passed ? 'Passed ✓' : 'Failed ✗'}
          </span>
        </div>
      </div>
      
      {/* Feedback */}
      <div className="feedback-message">
        <p>{results.feedback}</p>
      </div>
      
      {/* Answer Review */}
      <div className="answers-review">
        <h3>Review Your Answers</h3>
        {results.answers.map((answer, index) => (
          <div
            key={index}
            className={`answer-card ${answer.is_correct ? 'correct' : 'incorrect'}`}
          >
            <div className="question-header">
              <span className="question-number">Question {index + 1}</span>
              <span className="points-earned">
                {answer.points_earned} / {answer.points_possible} pts
              </span>
            </div>
            
            <p className="question-text">{answer.question_text}</p>
            
            <div className="answer-comparison">
              <div className={`user-answer ${answer.is_correct ? 'correct' : 'incorrect'}`}>
                <strong>Your Answer:</strong> {answer.user_answer}
                {answer.is_correct ? ' ✓' : ' ✗'}
              </div>
              
              {!answer.is_correct && answer.correct_answer && (
                <div className="correct-answer">
                  <strong>Correct Answer:</strong> {answer.correct_answer}
                </div>
              )}
            </div>
          </div>
        ))}
      </div>
      
      {/* Actions */}
      <div className="result-actions">
        {canRetake && (
          <button onClick={() => navigateToQuizDetails()} className="retake-button">
            🔄 Retake Quiz
          </button>
        )}
        
        <button onClick={() => navigateToQuizList()} className="back-button">
          ← Back to Quizzes
        </button>
      </div>
    </div>
  );
}
```

---

## 🎨 CSS Styling Recommendations

```css
/* Timer Warning */
.timer.warning {
  color: #f44336;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

/* Selected Option */
.option-button {
  padding: 15px;
  margin: 10px 0;
  border: 2px solid #ddd;
  background: white;
  cursor: pointer;
  border-radius: 8px;
  transition: all 0.3s;
}

.option-button:hover {
  border-color: #007bff;
  background: #f0f8ff;
}

.option-button.selected {
  background: #4CAF50;
  color: white;
  border: 2px solid #388E3C;
  font-weight: bold;
}

/* Progress Dots */
.question-dots {
  display: flex;
  gap: 8px;
  align-items: center;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ddd;
  cursor: pointer;
  transition: all 0.3s;
}

.dot.answered {
  background: #4CAF50;
}

.dot.current {
  width: 14px;
  height: 14px;
  border: 2px solid #007bff;
}

/* Score Banner */
.score-banner {
  padding: 40px;
  border-radius: 10px;
  text-align: center;
  color: white;
  margin-bottom: 30px;
}

.score-banner.passed {
  background: linear-gradient(135deg, #4CAF50, #81C784);
}

.score-banner.failed {
  background: linear-gradient(135deg, #f44336, #e57373);
}

.score-circle {
  display: inline-block;
  width: 150px;
  height: 150px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.3);
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  margin-bottom: 20px;
}

.score-value {
  font-size: 48px;
  font-weight: bold;
}

/* Answer Cards */
.answer-card {
  padding: 20px;
  margin: 15px 0;
  border-radius: 8px;
  border-left: 4px solid #ddd;
}

.answer-card.correct {
  border-left: 4px solid #4CAF50;
  background: #E8F5E9;
}

.answer-card.incorrect {
  border-left: 4px solid #f44336;
  background: #FFEBEE;
}

.user-answer.correct {
  color: #4CAF50;
  font-weight: bold;
}

.user-answer.incorrect {
  color: #f44336;
  font-weight: bold;
  text-decoration: line-through;
}

.correct-answer {
  color: #4CAF50;
  margin-top: 10px;
  padding: 10px;
  background: #E8F5E9;
  border-radius: 5px;
}
```

---

## ⚠️ Important Requirements

### 1. Timer Implementation
- Parse `expires_at` from start response
- Calculate remaining time every second
- Show warning when < 1 minute remaining
- **Auto-submit when timer reaches 0** (don't let user continue)

### 2. Auto-Save Answers
- Save answer immediately when user clicks an option
- Show brief "Saved ✓" indicator
- Handle network errors gracefully
- Don't block UI while saving

### 3. Navigation
- Allow moving between questions freely
- Show which questions are answered (progress dots)
- Don't submit until user clicks "Submit Quiz" or timer expires

### 4. Validation
- Check if user answered all questions before final submit (optional)
- Show confirmation dialog before submitting
- Disable submit button while submitting to prevent double-click

### 5. Error Handling
```javascript
// Time limit exceeded during quiz
if (error.message.includes('Time limit')) {
  handleAutoSubmit();
}

// Max attempts reached
if (error.message.includes('Maximum attempts')) {
  showMessage('No more attempts available');
  navigateToQuizList();
}

// Not enrolled
if (response.status === 403) {
  redirectToEnrollment(courseId);
}
```

---

## 🧪 Testing Checklist

- [ ] Start quiz button creates attempt and loads questions
- [ ] Timer starts and counts down correctly
- [ ] Timer shows warning when < 1 minute
- [ ] Timer expires and auto-submits quiz
- [ ] Selecting answer highlights the option
- [ ] Selecting answer saves to backend immediately (check network tab)
- [ ] Answer saves return 200 OK (not 422 validation error)
- [ ] Can navigate between questions
- [ ] Previous answers are preserved when returning to question
- [ ] Submit button shows on last question
- [ ] Confirmation dialog appears before submit
- [ ] Results page shows correct score (not 0 when answered correctly)
- [ ] Results page shows pass/fail status
- [ ] Answer review shows correct/incorrect indicators
- [ ] Correct answers displayed (if enabled in quiz settings)
- [ ] Can retake quiz if attempts remaining
- [ ] Max attempts enforced (button disabled)
- [ ] 403 error redirects to enrollment
- [ ] Network errors handled gracefully

---

## 🐛 Troubleshooting Common Issues

### Issue 1: Score Always Shows 0% (Answered Correctly But Marked Wrong)

**Symptoms:**
- User submits correct answers
- Results show score: 0%
- All answers marked as incorrect

**Cause:**
Frontend is sending `option_id` (number) instead of `option_text` (string) as the answer.

**Fix:**
```javascript
// ❌ WRONG
const handleClick = (optionId) => {
  handleSelectAnswer(questionId, optionId);  // Sending ID (3)
};

// ✅ CORRECT
const handleClick = (optionId) => {
  const option = question.options.find(o => o.id === optionId);
  handleSelectAnswer(questionId, option.option_text);  // Sending text ("img")
};
```

---

### Issue 2: 422 Validation Error - "answer must be a string"

**Symptoms:**
- PUT /api/learner/quiz-attempts/{id}/answer returns 422
- Error: "The answer field must be a string"

**Cause:**
Sending answer as number or boolean instead of string.

**Fix:**
```javascript
// Ensure answer is always a string
{
  question_id: parseInt(questionId),  // Integer
  answer: String(answerValue)         // Convert to string
}
```

---

### Issue 3: Answers Not Saving - "Validation error"

**Symptoms:**
- 422 error with validation details
- `received_data` shows incorrect format

**Debug Steps:**
1. Check browser console for error details
2. Check `received_data` in error response
3. Verify payload format:
```javascript
console.log('Sending:', {
  question_id: typeof questionId,  // Should log 'number'
  answer: typeof answer            // Should log 'string'
});
```

**Solution:**
```javascript
const payload = {
  question_id: parseInt(questionId),  // Force integer
  answer: String(answer).trim()       // Force string, trim whitespace
};
```

---

### Issue 4: "Maximum attempts reached" Even Though Not Completed

**Symptoms:**
- Can't start quiz
- Error: "Maximum attempts reached"
- But no completed attempts exist

**Cause:**
Old abandoned/in-progress attempts were being counted (fixed in backend).

**Solution:**
Backend now only counts `completed` attempts. If still seeing this:
1. Clear browser cache
2. Contact backend to run cleanup script
3. Backend runs: `php check_quiz_attempts.php`

---

### Issue 5: Timer Not Auto-Submitting

**Symptoms:**
- Timer reaches 0:00
- Quiz doesn't submit automatically
- User can still answer

**Fix:**
```javascript
useEffect(() => {
  if (!quizSession.expiresAt) return;
  
  const interval = setInterval(() => {
    const now = new Date();
    const remaining = quizSession.expiresAt - now;
    
    if (remaining <= 0) {
      clearInterval(interval);
      setTimeRemaining(0);
      handleAutoSubmit();  // ✅ Must call this
      return;             // ✅ Stop checking
    }
    
    setTimeRemaining(remaining);
  }, 1000);
  
  return () => clearInterval(interval);
}, [quizSession.expiresAt]);
```

---

## 🔧 State Management Structure

```javascript
// Quiz Session (during quiz taking)
{
  attemptId: 15,
  quizId: 2,
  quizTitle: "HTML Basics",
  questions: [...],
  expiresAt: Date,
  timeLimitMinutes: 5,
  totalPoints: 10,
  passingPercentage: 70,
  startedAt: Date
}

// User Answers (during quiz taking)
{
  1: "img",      // question_id: answer
  2: ".style1",
  3: "video"
}

// Quiz Results (after submission)
{
  attempt_id: 15,
  score: 100.00,
  points_earned: 10,
  total_points: 10,
  time_taken_minutes: 3,
  passed: true,
  passing_percentage: 70,
  feedback: "Great job!",
  answers: [...]
}
```

---

## 🎯 Key Points

1. **Backend is 100% ready** - All endpoints tested and working
2. **Use the plain token** for authentication (not hashed)
3. **Auto-save is critical** - Save answers as user selects them
4. **Timer must auto-submit** - Don't let quiz continue after time expires
5. **Show immediate feedback** - Selected option should highlight instantly
6. **Handle all error cases** - 401, 403, 400, network errors
7. **Test with real user flow** - Start → Answer → Submit → Results

---

## 📞 API Configuration

```javascript
const API_BASE_URL = 'http://127.0.0.1:8000/api';

// Get token from your auth system
const authToken = getUserAuthToken();

// All requests need these headers
const headers = {
  'Authorization': `Bearer ${authToken}`,
  'Content-Type': 'application/json',
  'Accept': 'application/json'
};
```

---

## ✅ Ready to Implement!

The backend is fully functional with:
- ✅ Quiz start with questions
- ✅ Answer auto-save
- ✅ Score calculation
- ✅ Pass/fail determination
- ✅ Attempt counting
- ✅ Timer support
- ✅ Detailed results

Follow this guide step-by-step and you'll have a complete quiz system! 🚀

---

## 📝 Quick Reference - Key Implementation Points

### ✅ DO's:

1. **Answer Submission:**
   ```javascript
   {
     question_id: parseInt(questionId),      // Integer
     answer: option.option_text              // String (text, not ID)
   }
   ```

2. **Option Click Handler:**
   ```javascript
   onClick={() => handleSelectAnswer(
     question.id, 
     option.option_text  // ✅ Send text
   )}
   ```

3. **Auto-save on Selection:**
   - Save immediately when user clicks option
   - Don't wait for "Next" button
   - Show brief "Saved ✓" notification

4. **Timer Implementation:**
   - Parse `expires_at` from start response
   - Auto-submit when timer reaches 0
   - Show warning at < 1 minute

5. **Answer Storage in State:**
   ```javascript
   setUserAnswers(prev => ({
     ...prev,
     [questionId]: optionText  // Store by question ID
   }))
   ```

### ❌ DON'Ts:

1. **Don't send option_id as answer:**
   ```javascript
   ❌ answer: 3                    // Wrong - this is option ID
   ✅ answer: "img"                // Right - this is option text
   ```

2. **Don't send question_id as string:**
   ```javascript
   ❌ question_id: "1"             // Wrong - validation fails
   ✅ question_id: 1               // Right - must be integer
   ```

3. **Don't batch answer saves:**
   ```javascript
   ❌ Save all answers when clicking "Next"
   ✅ Save each answer immediately when selected
   ```

4. **Don't let quiz continue after timer expires:**
   ```javascript
   ❌ Allow answering when timer = 0
   ✅ Auto-submit and disable questions
   ```

5. **Don't count abandoned attempts:**
   - Backend already fixed this
   - Only completed attempts count toward max_attempts

### 🔍 Debugging Checklist:

When quiz isn't working correctly:

1. **Check Network Tab:**
   - Is PUT /answer returning 200 or 422?
   - What payload is being sent?
   - What error message in response?

2. **Check Console Logs:**
   - Any validation errors logged?
   - What data types are being sent?

3. **Verify Answer Format:**
   ```javascript
   console.log({
     question_id: typeof questionId,  // Should be 'number'
     answer: typeof answer,           // Should be 'string'
     answer_value: answer             // Should be text like "img"
   });
   ```

4. **Check Results Page:**
   - Score = 0%? → Sending wrong answer format
   - 422 errors? → Check payload types
   - Answers not saved? → Check auto-save logic

### 🎯 Testing Your Implementation:

```javascript
// Test answer submission manually:
const testAnswer = async () => {
  const response = await fetch(
    'http://127.0.0.1:8000/api/learner/quiz-attempts/12/answer',
    {
      method: 'PUT',
      headers: {
        'Authorization': 'Bearer YOUR_TOKEN',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        question_id: 1,              // ✅ Integer
        answer: "background-color"   // ✅ String
      })
    }
  );
  
  const data = await response.json();
  console.log('Status:', response.status);
  console.log('Response:', data);
};
```

Expected result: Status 200, success message

---

## 🎉 Summary

The quiz system is **100% functional** on the backend. Follow these rules for frontend:

1. ✅ Send `question_id` as **integer**
2. ✅ Send `answer` as **string** (option text, not ID)
3. ✅ Auto-save answers immediately
4. ✅ Handle timer auto-submit
5. ✅ Show proper error messages for 422, 403, 400

**Backend handles:**
- ✅ Answer storage (JSON with string keys)
- ✅ Auto-grading (case-insensitive comparison)
- ✅ Attempt counting (only completed attempts)
- ✅ Time limit enforcement
- ✅ Auto-abandoning old attempts

You're all set! 🚀
