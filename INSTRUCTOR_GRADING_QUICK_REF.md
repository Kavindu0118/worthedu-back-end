# 🎓 Instructor Grading System - Quick Reference

## 📋 API Endpoints

### Base URL
```
http://localhost/learning-lms/public/api/instructor
```

### Authentication
```
Authorization: Bearer <your_api_token>
```

---

## 🚀 Endpoints Overview

| # | Method | Endpoint | Purpose |
|---|--------|----------|---------|
| 1 | GET | `/assignments/{id}/submissions` | List all submissions for assignment |
| 2 | GET | `/submissions/{id}` | Get single submission details |
| 3 | PUT | `/submissions/{id}/grade` | Grade a submission |
| 4 | GET | `/submissions/{id}/download` | Download submission file |
| 5 | GET | `/modules/{id}/submissions` | Get module overview |

---

## 💡 Quick Examples

### 1. Get Submissions
```bash
curl -X GET 'http://localhost/learning-lms/public/api/instructor/assignments/1/submissions' \
  -H 'Authorization: Bearer YOUR_TOKEN'
```

### 2. Grade Submission
```bash
curl -X PUT 'http://localhost/learning-lms/public/api/instructor/submissions/1/grade' \
  -H 'Authorization: Bearer YOUR_TOKEN' \
  -H 'Content-Type: application/json' \
  -d '{
    "marks_obtained": 85,
    "feedback": "Excellent work!",
    "grade": "B"
  }'
```

### 3. JavaScript Example
```javascript
const response = await fetch(
  'http://localhost/learning-lms/public/api/instructor/submissions/1/grade',
  {
    method: 'PUT',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      marks_obtained: 85,
      feedback: 'Great work!',
      grade: 'B'
    }),
  }
);
const data = await response.json();
```

---

## 🎯 Grading Parameters

### Required:
- `marks_obtained` (number, 0 to max_points)

### Optional:
- `feedback` (string, max 2000 chars)
- `grade` (string: A, A-, B+, B, B-, C+, C, C-, D+, D, F)

---

## 📊 Grade Scale

| Grade | Percentage |
|-------|-----------|
| A     | 93-100%   |
| A-    | 90-92%    |
| B+    | 87-89%    |
| B     | 83-86%    |
| B-    | 80-82%    |
| C+    | 77-79%    |
| C     | 73-76%    |
| C-    | 70-72%    |
| D+    | 67-69%    |
| D     | 60-66%    |
| F     | < 60%     |

**Note:** Auto-calculated if not provided

---

## 📁 Files Created

1. **Controller:** `app/Http/Controllers/InstructorSubmissionController.php`
2. **Documentation:** `INSTRUCTOR_GRADING_API_GUIDE.md`
3. **PHP Test:** `test_grading_api.php`
4. **HTML Test:** `test_grading.html`
5. **Status:** `INSTRUCTOR_GRADING_COMPLETE.md`
6. **This File:** `INSTRUCTOR_GRADING_QUICK_REF.md`

---

## 🧪 Testing

### Method 1: HTML Interface (Recommended)
```
Open: test_grading.html
```
- Beautiful UI
- Easy to use
- Save token
- Test all endpoints

### Method 2: PHP Script
```bash
php test_grading_api.php
```

---

## 🔍 Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 401 | Unauthorized (bad token) |
| 404 | Not found |
| 422 | Validation error |
| 500 | Server error |

---

## ⚡ Quick Start (3 Steps)

1. **Get API Token**
   ```bash
   # Login first
   curl -X POST http://localhost/learning-lms/public/api/login \
     -H 'Content-Type: application/json' \
     -d '{"username": "instructor@example.com", "password": "password"}'
   ```

2. **Open Test Interface**
   ```
   Open test_grading.html in browser
   Enter your token and click "Save Token"
   ```

3. **Test Grading**
   ```
   Use the form to grade a submission
   View JSON response
   ```

---

## 📚 Full Documentation

For complete details, see:
- **`INSTRUCTOR_GRADING_API_GUIDE.md`** - Full API documentation with React examples
- **`INSTRUCTOR_GRADING_COMPLETE.md`** - Implementation status and summary

---

## 🎓 Example Workflow

```
1. Instructor logs in → Get API token
2. View assignment submissions → GET /assignments/1/submissions
3. Click on a submission → GET /submissions/1
4. Download student file → GET /submissions/1/download
5. Grade the submission → PUT /submissions/1/grade
6. Status automatically updates to "graded"
7. Student sees feedback in their dashboard
```

---

## 💻 Frontend Integration Checklist

- [ ] Copy TypeScript types from guide
- [ ] Create API service layer
- [ ] Build submission list component
- [ ] Build grading form component
- [ ] Add file download handler
- [ ] Test with real data
- [ ] Add error handling
- [ ] Add loading states

---

## ✅ Status

**ALL ENDPOINTS: Ready for Production**
- ✅ Backend complete
- ✅ Routes registered
- ✅ Database ready
- ✅ Documentation complete
- ✅ Tests available
- ✅ Frontend integration guide ready

---

## 🆘 Need Help?

1. Check `INSTRUCTOR_GRADING_API_GUIDE.md`
2. Test with `test_grading.html`
3. Review error messages
4. Check Laravel logs: `storage/logs/laravel.log`

---

**Last Updated:** 2024
**Version:** 1.0.0
**Status:** ✅ Production Ready
