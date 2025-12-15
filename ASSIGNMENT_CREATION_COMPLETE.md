# Assignment Creation - Complete API Documentation

## 🎯 Overview
The assignment creation API has been enhanced with comprehensive submission settings, grading options, and availability controls.

---

## 📋 API Endpoint

**POST** `/api/instructor/modules/{moduleId}/assignments`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
Accept: application/json
```

---

## 📝 Request Fields

### Basic Information (Required)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `assignment_title` | string | ✅ Yes | Assignment name (max 255 chars) |
| `instructions` | text | ❌ No | Detailed instructions for students |
| `max_points` | integer | ❌ No | Maximum points (default: 100) |
| `due_date` | datetime | ❌ No | Deadline (ISO 8601 format) |

### Submission Type

| Field | Type | Options | Default | Description |
|-------|------|---------|---------|-------------|
| `submission_type` | enum | `file`, `text`, `both` | `file` | Type of submission allowed |

### File Upload Settings

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `attachment` | file | ❌ No | - | Assignment instructions file (max 10MB) |
| `allowed_file_types` | string | ❌ No | null | Comma-separated extensions: `pdf,doc,docx,txt` |
| `max_file_size_mb` | integer | ❌ No | 10 | Maximum file size in MB (1-100) |
| `max_files` | integer | ❌ No | 1 | Maximum number of files (1-10) |

### Late Submission Policy

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `allow_late_submission` | boolean | ❌ No | false | Allow submissions after due date |
| `late_submission_deadline` | datetime | ❌ No | null | Final deadline for late submissions |
| `late_penalty_percent` | decimal | ❌ No | 0 | Percentage deduction (0-100) |

### Grading Settings

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `require_rubric` | boolean | ❌ No | false | Whether to use grading rubric |
| `grading_criteria` | text | ❌ No | null | Specific grading criteria details |
| `peer_review_enabled` | boolean | ❌ No | false | Enable peer review |
| `peer_reviews_required` | integer | ❌ No | null | Number of peer reviews per student |

### Availability Settings

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `available_from` | datetime | ❌ No | null | When assignment becomes visible |
| `show_after_due_date` | boolean | ❌ No | true | Show submissions/grades after due date |

### Text Submission Settings

| Field | Type | Required | Default | Description |
|-------|------|----------|---------|-------------|
| `min_words` | integer | ❌ No | null | Minimum word count |
| `max_words` | integer | ❌ No | null | Maximum word count |

---

## 💻 Frontend Implementation Examples

### React/TypeScript Example

```typescript
interface AssignmentFormData {
  // Basic
  assignment_title: string;
  instructions?: string;
  max_points?: number;
  due_date?: string;
  
  // Submission type
  submission_type?: 'file' | 'text' | 'both';
  
  // File settings
  attachment?: File;
  allowed_file_types?: string; // "pdf,doc,docx"
  max_file_size_mb?: number;
  max_files?: number;
  
  // Late submission
  allow_late_submission?: boolean;
  late_submission_deadline?: string;
  late_penalty_percent?: number;
  
  // Grading
  require_rubric?: boolean;
  grading_criteria?: string;
  peer_review_enabled?: boolean;
  peer_reviews_required?: number;
  
  // Availability
  available_from?: string;
  show_after_due_date?: boolean;
  
  // Text settings
  min_words?: number;
  max_words?: number;
}

const createAssignment = async (
  moduleId: number, 
  data: AssignmentFormData
): Promise<Assignment> => {
  const formData = new FormData();
  
  // Basic fields
  formData.append('assignment_title', data.assignment_title);
  if (data.instructions) formData.append('instructions', data.instructions);
  if (data.max_points) formData.append('max_points', data.max_points.toString());
  if (data.due_date) formData.append('due_date', data.due_date);
  
  // Submission type
  if (data.submission_type) {
    formData.append('submission_type', data.submission_type);
  }
  
  // File settings
  if (data.attachment) formData.append('attachment', data.attachment);
  if (data.allowed_file_types) {
    formData.append('allowed_file_types', data.allowed_file_types);
  }
  if (data.max_file_size_mb) {
    formData.append('max_file_size_mb', data.max_file_size_mb.toString());
  }
  if (data.max_files) {
    formData.append('max_files', data.max_files.toString());
  }
  
  // Late submission
  if (data.allow_late_submission !== undefined) {
    formData.append('allow_late_submission', data.allow_late_submission ? '1' : '0');
  }
  if (data.late_submission_deadline) {
    formData.append('late_submission_deadline', data.late_submission_deadline);
  }
  if (data.late_penalty_percent) {
    formData.append('late_penalty_percent', data.late_penalty_percent.toString());
  }
  
  // Grading
  if (data.require_rubric !== undefined) {
    formData.append('require_rubric', data.require_rubric ? '1' : '0');
  }
  if (data.grading_criteria) {
    formData.append('grading_criteria', data.grading_criteria);
  }
  if (data.peer_review_enabled !== undefined) {
    formData.append('peer_review_enabled', data.peer_review_enabled ? '1' : '0');
  }
  if (data.peer_reviews_required) {
    formData.append('peer_reviews_required', data.peer_reviews_required.toString());
  }
  
  // Availability
  if (data.available_from) {
    formData.append('available_from', data.available_from);
  }
  if (data.show_after_due_date !== undefined) {
    formData.append('show_after_due_date', data.show_after_due_date ? '1' : '0');
  }
  
  // Text settings
  if (data.min_words) formData.append('min_words', data.min_words.toString());
  if (data.max_words) formData.append('max_words', data.max_words.toString());
  
  const response = await fetch(
    `http://127.0.0.1:8000/api/instructor/modules/${moduleId}/assignments`,
    {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${getToken()}`,
        'Accept': 'application/json',
        // Don't set Content-Type - browser will set it with boundary
      },
      body: formData,
    }
  );
  
  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || 'Failed to create assignment');
  }
  
  const result = await response.json();
  return result.assignment;
};
```

### Form UI Component Example

```typescript
function AssignmentForm() {
  const [formData, setFormData] = useState<AssignmentFormData>({
    assignment_title: '',
    submission_type: 'file',
    max_points: 100,
    max_file_size_mb: 10,
    max_files: 1,
    allow_late_submission: false,
    late_penalty_percent: 0,
    show_after_due_date: true,
  });
  
  const [showAdvanced, setShowAdvanced] = useState(false);

  return (
    <form onSubmit={handleSubmit}>
      {/* Basic Information */}
      <section>
        <h3>Basic Information</h3>
        
        <Input
          label="Assignment Title *"
          value={formData.assignment_title}
          onChange={(e) => setFormData({...formData, assignment_title: e.target.value})}
          required
        />
        
        <Textarea
          label="Instructions"
          value={formData.instructions}
          onChange={(e) => setFormData({...formData, instructions: e.target.value})}
          rows={5}
        />
        
        <div className="grid grid-cols-2 gap-4">
          <Input
            type="number"
            label="Max Points"
            value={formData.max_points}
            onChange={(e) => setFormData({...formData, max_points: parseInt(e.target.value)})}
          />
          
          <Input
            type="datetime-local"
            label="Due Date"
            value={formData.due_date}
            onChange={(e) => setFormData({...formData, due_date: e.target.value})}
          />
        </div>
        
        <FileUpload
          label="Attachment (Instructions File)"
          accept=".pdf,.doc,.docx"
          onChange={(file) => setFormData({...formData, attachment: file})}
        />
      </section>

      {/* Submission Type */}
      <section>
        <h3>Submission Type</h3>
        
        <RadioGroup
          value={formData.submission_type}
          onChange={(value) => setFormData({...formData, submission_type: value})}
        >
          <Radio value="file">File Upload Only</Radio>
          <Radio value="text">Text Entry Only</Radio>
          <Radio value="both">Both File and Text</Radio>
        </RadioGroup>
      </section>

      {/* File Upload Settings */}
      {(formData.submission_type === 'file' || formData.submission_type === 'both') && (
        <section>
          <h3>File Upload Settings</h3>
          
          <Input
            label="Allowed File Types"
            placeholder="pdf,doc,docx,txt"
            value={formData.allowed_file_types}
            onChange={(e) => setFormData({...formData, allowed_file_types: e.target.value})}
            helperText="Comma-separated file extensions"
          />
          
          <div className="grid grid-cols-2 gap-4">
            <Input
              type="number"
              label="Max File Size (MB)"
              min={1}
              max={100}
              value={formData.max_file_size_mb}
              onChange={(e) => setFormData({...formData, max_file_size_mb: parseInt(e.target.value)})}
            />
            
            <Input
              type="number"
              label="Max Files"
              min={1}
              max={10}
              value={formData.max_files}
              onChange={(e) => setFormData({...formData, max_files: parseInt(e.target.value)})}
            />
          </div>
        </section>
      )}

      {/* Text Submission Settings */}
      {(formData.submission_type === 'text' || formData.submission_type === 'both') && (
        <section>
          <h3>Text Submission Settings</h3>
          
          <div className="grid grid-cols-2 gap-4">
            <Input
              type="number"
              label="Minimum Words"
              min={0}
              value={formData.min_words}
              onChange={(e) => setFormData({...formData, min_words: parseInt(e.target.value)})}
            />
            
            <Input
              type="number"
              label="Maximum Words"
              min={1}
              value={formData.max_words}
              onChange={(e) => setFormData({...formData, max_words: parseInt(e.target.value)})}
            />
          </div>
        </section>
      )}

      {/* Late Submission Policy */}
      <section>
        <h3>Late Submission Policy</h3>
        
        <Checkbox
          label="Allow Late Submissions"
          checked={formData.allow_late_submission}
          onChange={(checked) => setFormData({...formData, allow_late_submission: checked})}
        />
        
        {formData.allow_late_submission && (
          <>
            <Input
              type="datetime-local"
              label="Late Submission Deadline"
              value={formData.late_submission_deadline}
              onChange={(e) => setFormData({...formData, late_submission_deadline: e.target.value})}
            />
            
            <Input
              type="number"
              label="Late Penalty (%)"
              min={0}
              max={100}
              value={formData.late_penalty_percent}
              onChange={(e) => setFormData({...formData, late_penalty_percent: parseFloat(e.target.value)})}
              helperText="Percentage deducted from final score"
            />
          </>
        )}
      </section>

      {/* Advanced Settings Toggle */}
      <button
        type="button"
        onClick={() => setShowAdvanced(!showAdvanced)}
        className="text-blue-600 underline"
      >
        {showAdvanced ? 'Hide' : 'Show'} Advanced Settings
      </button>

      {/* Advanced Settings */}
      {showAdvanced && (
        <>
          <section>
            <h3>Grading Settings</h3>
            
            <Checkbox
              label="Require Grading Rubric"
              checked={formData.require_rubric}
              onChange={(checked) => setFormData({...formData, require_rubric: checked})}
            />
            
            <Textarea
              label="Grading Criteria"
              value={formData.grading_criteria}
              onChange={(e) => setFormData({...formData, grading_criteria: e.target.value})}
              placeholder="Describe specific grading criteria..."
              rows={4}
            />
            
            <Checkbox
              label="Enable Peer Review"
              checked={formData.peer_review_enabled}
              onChange={(checked) => setFormData({...formData, peer_review_enabled: checked})}
            />
            
            {formData.peer_review_enabled && (
              <Input
                type="number"
                label="Peer Reviews Required"
                min={1}
                value={formData.peer_reviews_required}
                onChange={(e) => setFormData({...formData, peer_reviews_required: parseInt(e.target.value)})}
                helperText="Number of peers each student must review"
              />
            )}
          </section>

          <section>
            <h3>Availability Settings</h3>
            
            <Input
              type="datetime-local"
              label="Available From"
              value={formData.available_from}
              onChange={(e) => setFormData({...formData, available_from: e.target.value})}
              helperText="When students can first see this assignment"
            />
            
            <Checkbox
              label="Show Submissions/Grades After Due Date"
              checked={formData.show_after_due_date}
              onChange={(checked) => setFormData({...formData, show_after_due_date: checked})}
            />
          </section>
        </>
      )}

      <button type="submit" className="btn-primary">
        Create Assignment
      </button>
    </form>
  );
}
```

---

## 📤 Success Response (201)

```json
{
  "message": "Assignment added successfully",
  "assignment": {
    "id": 15,
    "module_id": 8,
    "assignment_title": "Final Project Report",
    "instructions": "Submit your final project report...",
    "submission_type": "both",
    "attachment_url": "http://127.0.0.1:8000/storage/course-attachments/xyz.pdf",
    "max_points": 100,
    "due_date": "2025-12-30T23:59:00.000000Z",
    
    "allowed_file_types": "pdf,doc,docx",
    "max_file_size_mb": 10,
    "max_files": 3,
    
    "allow_late_submission": true,
    "late_submission_deadline": "2026-01-05T23:59:00.000000Z",
    "late_penalty_percent": 10.00,
    
    "require_rubric": true,
    "grading_criteria": "Clarity (30%), Completeness (40%), Formatting (30%)",
    "peer_review_enabled": false,
    "peer_reviews_required": null,
    
    "available_from": "2025-12-01T00:00:00.000000Z",
    "show_after_due_date": true,
    
    "min_words": 1000,
    "max_words": 5000,
    
    "created_at": "2025-12-13T18:00:00.000000Z",
    "updated_at": "2025-12-13T18:00:00.000000Z"
  }
}
```

---

## ❌ Error Responses

### 422 Validation Error
```json
{
  "message": "Validation failed",
  "errors": {
    "assignment_title": ["The assignment title field is required."],
    "late_submission_deadline": ["The late submission deadline must be after due date."],
    "max_files": ["The max files must be between 1 and 10."]
  }
}
```

### 403 Forbidden
```json
{
  "message": "You can only add assignments to your own courses"
}
```

---

## 🎨 UI/UX Recommendations

### Progressive Disclosure
1. **Basic Tab**: Title, instructions, due date, points, submission type
2. **File Settings Tab**: Show only when submission_type includes 'file'
3. **Text Settings Tab**: Show only when submission_type includes 'text'
4. **Advanced Tab**: Late submission, grading, availability (collapsed by default)

### Smart Defaults
- `submission_type`: file
- `max_points`: 100
- `max_file_size_mb`: 10
- `max_files`: 1
- `allow_late_submission`: false
- `show_after_due_date`: true

### Conditional Fields
- Show `late_submission_deadline` and `late_penalty_percent` only when `allow_late_submission` is true
- Show `peer_reviews_required` only when `peer_review_enabled` is true
- Show file settings only for file/both submission types
- Show text settings only for text/both submission types

### Validation Messages
```typescript
const validateAssignment = (data: AssignmentFormData): string[] => {
  const errors: string[] = [];
  
  if (!data.assignment_title?.trim()) {
    errors.push('Assignment title is required');
  }
  
  if (data.late_submission_deadline && data.due_date) {
    if (new Date(data.late_submission_deadline) <= new Date(data.due_date)) {
      errors.push('Late submission deadline must be after due date');
    }
  }
  
  if (data.max_words && data.min_words && data.max_words < data.min_words) {
    errors.push('Maximum words must be greater than minimum words');
  }
  
  if (data.peer_review_enabled && !data.peer_reviews_required) {
    errors.push('Specify number of peer reviews required');
  }
  
  return errors;
};
```

---

## 🔄 Update Assignment

**PUT** `/api/instructor/assignments/{id}`

Uses the same fields as creation. All fields are optional for updates.

---

## ✅ Testing Checklist

- [ ] Create assignment with file upload only
- [ ] Create assignment with text entry only
- [ ] Create assignment with both submission types
- [ ] Test allowed file types validation
- [ ] Test max file size limits
- [ ] Test late submission with penalty
- [ ] Test availability from date (future date)
- [ ] Test peer review settings
- [ ] Test word count limits for text
- [ ] Update assignment with new settings
- [ ] Test validation errors
- [ ] Test file attachment upload
- [ ] Verify all fields save correctly
- [ ] Test instructor ownership validation

---

## 🚀 Ready to Implement!

Backend is 100% ready with:
- ✅ 24 database fields for comprehensive assignment configuration
- ✅ Full validation rules
- ✅ Create and update endpoints
- ✅ File upload support
- ✅ Smart defaults

Frontend needs to implement:
1. Multi-step or tabbed form UI
2. Conditional field visibility
3. File upload component
4. Date/time pickers
5. Validation and error display
6. FormData submission logic
