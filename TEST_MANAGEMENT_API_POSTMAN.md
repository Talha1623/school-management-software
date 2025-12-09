# Test Management API - Postman Guide

**Base URL:** `https://school.ritpk.com/api/teacher`

**Authentication:** All endpoints require Bearer token in header:
```
Authorization: Bearer {your_token}
```

---

## 📋 Available Endpoints

### 1. Get Filter Options for Marks Entry

**GET** `/api/teacher/test-management/marks-entry/filter-options`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response:**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully",
  "data": {
    "campuses": ["Main Campus", "Branch Campus"],
    "classes": ["five", "sixth", "Ninth"]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/marks-entry/filter-options`

---

### 2. Get Sections by Class

**GET** `/api/teacher/test-management/marks-entry/sections?class={class_name}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `class` (required) - Class name (e.g., "five", "Ninth")

**Example:**
```
GET /api/teacher/test-management/marks-entry/sections?class=five
```

**Response:**
```json
{
  "success": true,
  "message": "Sections retrieved successfully",
  "data": {
    "sections": ["A", "B", "C"]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/marks-entry/sections?class=five`

---

### 3. Get Subjects for Marks Entry

**GET** `/api/teacher/test-management/marks-entry/subjects?class={class}&section={section}&campus={campus}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `class` (required) - Class name
- `section` (optional) - Section name
- `campus` (optional) - Campus name

**Example:**
```
GET /api/teacher/test-management/marks-entry/subjects?class=five&section=A&campus=icms
```

**Response:**
```json
{
  "success": true,
  "message": "Subjects retrieved successfully",
  "data": {
    "subjects": ["Mathematics", "English", "Science"]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/marks-entry/subjects?class=five&section=A`

---

### 4. Get Tests (Only Declared Results)

**GET** `/api/teacher/test-management/marks-entry/tests?campus={campus}&class={class}&section={section}&subject={subject}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (All Optional):**
- `campus` - Campus name
- `class` - Class name
- `section` - Section name
- `subject` - Subject name

**Example:**
```
GET /api/teacher/test-management/marks-entry/tests?campus=icms&class=five&section=A&subject=Mathematics
```

**Response:**
```json
{
  "success": true,
  "message": "Tests retrieved successfully",
  "data": {
    "tests": ["Math Quiz 1", "Math Mid Term", "Math Final"]
  }
}
```

**Note:** Only tests with `result_status = 1` (declared) are returned.

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/marks-entry/tests?campus=icms&class=five&section=A`

---

### 5. Get Students for Marks Entry

**GET** `/api/teacher/test-management/marks-entry/students?campus={campus}&class={class}&section={section}&test_name={test_name}&subject={subject}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters (Required):**
- `campus` (required) - Campus name
- `class` (required) - Class name
- `test_name` (required) - Test name

**Query Parameters (Optional):**
- `section` - Section name
- `subject` - Subject name

**Example:**
```
GET /api/teacher/test-management/marks-entry/students?campus=icms&class=five&section=A&test_name=Math%20Quiz%201&subject=Mathematics
```

**Response:**
```json
{
  "success": true,
  "message": "Students retrieved successfully",
  "data": {
    "test": {
      "id": 1,
      "test_name": "Math Quiz 1",
      "for_class": "five",
      "section": "A",
      "subject": "Mathematics"
    },
    "students": [
      {
        "id": 1,
        "student_name": "John Doe",
        "student_code": "ST001",
        "gr_number": "GR001",
        "father_name": "Father Name",
        "marks": {
          "obtained": 85,
          "total": 100,
          "passing": 50
        }
      },
      {
        "id": 2,
        "student_name": "Jane Smith",
        "student_code": "ST002",
        "gr_number": "GR002",
        "father_name": "Father Name 2",
        "marks": null
      }
    ]
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/marks-entry/students?campus=icms&class=five&section=A&test_name=Math%20Quiz%201`

---

### 6. Save Marks Entry

**POST** `/api/teacher/test-management/marks-entry/save`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "test_name": "Math Quiz 1",
  "campus": "icms",
  "class": "five",
  "section": "A",
  "subject": "Mathematics",
  "marks": {
    "1": {
      "obtained": 85,
      "total": 100,
      "passing": 50
    },
    "2": {
      "obtained": 92,
      "total": 100,
      "passing": 50
    },
    "3": {
      "obtained": 45,
      "total": 100,
      "passing": 50
    }
  }
}
```

**Request Body Fields:**
- `test_name` (required, string) - Test name
- `campus` (required, string) - Campus name
- `class` (required, string) - Class name
- `section` (optional, string) - Section name
- `subject` (optional, string) - Subject name
- `marks` (required, object) - Object with student_id as key
  - `marks.{student_id}.obtained` (optional, numeric, min:0) - Marks obtained
  - `marks.{student_id}.total` (optional, numeric, min:0) - Total marks
  - `marks.{student_id}.passing` (optional, numeric, min:0) - Passing marks

**Response:**
```json
{
  "success": true,
  "message": "Marks saved successfully",
  "data": {
    "saved_count": 3
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/marks-entry/save`

---

### 7. Save Remarks Entry

**POST** `/api/teacher/test-management/remarks-entry/save`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "test_name": "Math Quiz 1",
  "campus": "icms",
  "class": "five",
  "section": "A",
  "subject": "Mathematics",
  "remarks": {
    "1": "Excellent performance",
    "2": "Good work, keep it up",
    "3": "Needs improvement"
  }
}
```

**Request Body Fields:**
- `test_name` (required, string) - Test name
- `campus` (required, string) - Campus name
- `class` (required, string) - Class name
- `section` (optional, string) - Section name
- `subject` (optional, string) - Subject name
- `remarks` (required, object) - Object with student_id as key
  - `remarks.{student_id}` (optional, string) - Teacher remarks for student

**Response:**
```json
{
  "success": true,
  "message": "Remarks saved successfully",
  "data": {
    "saved_count": 3
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/remarks-entry/save`

---

## 📝 Postman Collection Setup

### Step 1: Create Environment Variables

In Postman, create an environment with:
- `base_url`: `https://school.ritpk.com/api/teacher`
- `token`: `{your_bearer_token}`

### Step 2: Set Authorization

For each request, set Authorization header:
- Type: Bearer Token
- Token: `{{token}}`

Or manually set header:
```
Authorization: Bearer {{token}}
```

### Step 3: Example Request Flow

1. **Get Filter Options:**
   ```
   GET {{base_url}}/test-management/marks-entry/filter-options
   ```

2. **Get Sections:**
   ```
   GET {{base_url}}/test-management/marks-entry/sections?class=five
   ```

3. **Get Subjects:**
   ```
   GET {{base_url}}/test-management/marks-entry/subjects?class=five&section=A
   ```

4. **Get Tests:**
   ```
   GET {{base_url}}/test-management/marks-entry/tests?campus=icms&class=five&section=A&subject=Mathematics
   ```

5. **Get Students:**
   ```
   GET {{base_url}}/test-management/marks-entry/students?campus=icms&class=five&section=A&test_name=Math%20Quiz%201
   ```

6. **Save Marks:**
   ```
   POST {{base_url}}/test-management/marks-entry/save
   Body: (JSON as shown above)
   ```

---

## 🔄 Complete Workflow Example

### Step 1: Login to Get Token
```
POST https://school.ritpk.com/api/teacher/login
Body: {
  "email": "teacher@example.com",
  "password": "password"
}
```

### Step 2: Get Filter Options
```
GET https://school.ritpk.com/api/teacher/test-management/marks-entry/filter-options
Headers: Authorization: Bearer {token}
```

### Step 3: Get Sections for Selected Class
```
GET https://school.ritpk.com/api/teacher/test-management/marks-entry/sections?class=five
Headers: Authorization: Bearer {token}
```

### Step 4: Get Subjects for Class/Section
```
GET https://school.ritpk.com/api/teacher/test-management/marks-entry/subjects?class=five&section=A
Headers: Authorization: Bearer {token}
```

### Step 5: Get Tests (Declared Results Only)
```
GET https://school.ritpk.com/api/teacher/test-management/marks-entry/tests?campus=icms&class=five&section=A&subject=Mathematics
Headers: Authorization: Bearer {token}
```

### Step 6: Get Students for Selected Test
```
GET https://school.ritpk.com/api/teacher/test-management/marks-entry/students?campus=icms&class=five&section=A&test_name=Math%20Quiz%201&subject=Mathematics
Headers: Authorization: Bearer {token}
```

### Step 7: Save Marks
```
POST https://school.ritpk.com/api/teacher/test-management/marks-entry/save
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json
Body: {
  "test_name": "Math Quiz 1",
  "campus": "icms",
  "class": "five",
  "section": "A",
  "subject": "Mathematics",
  "marks": {
    "1": {"obtained": 85, "total": 100, "passing": 50},
    "2": {"obtained": 92, "total": 100, "passing": 50}
  }
}
```

### Step 8: Save Remarks (Optional)
```
POST https://school.ritpk.com/api/teacher/test-management/remarks-entry/save
Headers: 
  Authorization: Bearer {token}
  Content-Type: application/json
Body: {
  "test_name": "Math Quiz 1",
  "campus": "icms",
  "class": "five",
  "section": "A",
  "subject": "Mathematics",
  "remarks": {
    "1": "Excellent performance",
    "2": "Good work"
  }
}
```

---

## ⚠️ Important Notes

1. **Authentication:** All endpoints require valid Bearer token
2. **Teacher Only:** Only teachers can access these endpoints
3. **Assigned Classes:** Teacher can only access their assigned classes/sections
4. **Test Status:** Only tests with `result_status = 1` (declared) are shown in tests list
5. **Marks Format:** Marks object uses student_id as key
6. **URL Encoding:** Test names with spaces should be URL encoded (e.g., "Math Quiz 1" → "Math%20Quiz%201")

---

## 🐛 Error Responses

### 403 - Access Denied
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access marks entry."
}
```

### 422 - Validation Error
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "campus": ["The campus field is required."],
    "class": ["The class field is required."]
  }
}
```

### 404 - Test Not Found
```json
{
  "success": false,
  "message": "Test not found or result not declared."
}
```

---

---

## 📱 My Test Flow (Mobile App)

### 1. Get My Test - Teacher's Assigned Subjects

**GET** `/api/teacher/test-management/my-test/subjects`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Description:** 
Login teacher ke assigned subjects return karta hai. "My Test" par click karne par teacher ka name aur uske assigned subjects list hote hain.

**Response:**
```json
{
  "success": true,
  "message": "My test subjects retrieved successfully",
  "data": {
    "teacher": {
      "id": 1,
      "name": "John Doe",
      "email": "teacher@example.com"
    },
    "subjects": [
      {
        "subject_name": "Mathematics",
        "campus": "Main Campus",
        "total_classes": 3,
        "classes": ["five", "sixth", "seventh"],
        "sections": ["A", "B"]
      },
      {
        "subject_name": "English",
        "campus": "Main Campus",
        "total_classes": 2,
        "classes": ["five", "sixth"],
        "sections": ["A"]
      }
    ],
    "total_subjects": 2
  }
}
```

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/my-test/subjects`

---

### 2. Get Students for My Test - By Subject

**GET** `/api/teacher/test-management/my-test/students?subject={subject}&class={class}&section={section}&test_name={test_name}`

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
- `subject` (required) - Subject name (exact match required, e.g., "Maths", "English", "Science")
- `class` (required) - Class name (case-insensitive, e.g., "five", "Ninth", "ninth")
- `section` (required) - Section name (case-insensitive, e.g., "A", "a")
- `test_name` (optional) - Test name to get existing marks

**Description:**
Subject par click karne par students list return hota hai with obtained marks, minimum marks (passing), aur maximum marks (total).

**Important Notes:**
- Subject name must match exactly as returned in `/my-test/subjects` endpoint
- Class and section are case-insensitive (both "ninth" and "Ninth" work)
- Use the exact subject name from the subjects list response

**Example:**
```
GET /api/teacher/test-management/my-test/students?subject=Maths&class=ninth&section=A&test_name=Mid%20Term
```

**Note:** Agar subject name "Maths" hai to URL mein bhi "Maths" use karein, "Mathematics" nahi.

**Response:**
```json
{
  "success": true,
  "message": "Students retrieved successfully for my test",
  "data": {
    "subject": {
      "subject_name": "Mathematics",
      "class": "five",
      "section": "A",
      "campus": "Main Campus"
    },
    "test_name": "Mid Term",
    "students": [
      {
        "student_id": 1,
        "student_name": "Ali Ahmed",
        "student_code": "ST001",
        "gr_number": "GR001",
        "father_name": "Ahmed Khan",
        "obtained_marks": 85,
        "minimum_marks": 33,
        "maximum_marks": 100,
        "teacher_remarks": "Good performance"
      },
      {
        "student_id": 2,
        "student_name": "Fatima Ali",
        "student_code": "ST002",
        "gr_number": "GR002",
        "father_name": "Ali Khan",
        "obtained_marks": null,
        "minimum_marks": null,
        "maximum_marks": null,
        "teacher_remarks": null
      }
    ],
    "total_students": 2
  }
}
```

**Response Fields:**
- `obtained_marks` - Student ne kitne marks obtain kiye (null if not entered)
- `minimum_marks` - Passing marks (minimum required)
- `maximum_marks` - Total marks (maximum possible)
- `teacher_remarks` - Teacher ki remarks (if any)

**Full URL:** `https://school.ritpk.com/api/teacher/test-management/my-test/students?subject=Mathematics&class=five&section=A`

---

## 📱 My Test Flow Steps:

1. **Test Management** → Click "Marks Entry"
2. **Marks Entry** → Click "My Test"
3. **My Test** → Teacher ka name aur assigned subjects list
4. **Subject Click** → Students list with marks:
   - Obtained Marks
   - Minimum Marks (Passing)
   - Maximum Marks (Total)

---

## 📱 Quick Reference URLs

- Filter Options: `https://school.ritpk.com/api/teacher/test-management/marks-entry/filter-options`
- Sections: `https://school.ritpk.com/api/teacher/test-management/marks-entry/sections?class=five`
- Subjects: `https://school.ritpk.com/api/teacher/test-management/marks-entry/subjects?class=five&section=A`
- Tests: `https://school.ritpk.com/api/teacher/test-management/marks-entry/tests?campus=icms&class=five&section=A`
- Students: `https://school.ritpk.com/api/teacher/test-management/marks-entry/students?campus=icms&class=five&section=A&test_name=Math%20Quiz%201`
- Save Marks: `https://school.ritpk.com/api/teacher/test-management/marks-entry/save`
- Save Remarks: `https://school.ritpk.com/api/teacher/test-management/remarks-entry/save`
- **My Test Subjects:** `https://school.ritpk.com/api/teacher/test-management/my-test/subjects`
- **My Test Students:** `https://school.ritpk.com/api/teacher/test-management/my-test/students?subject=Mathematics&class=five&section=A`

