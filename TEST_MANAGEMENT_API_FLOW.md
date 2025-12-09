# Test Management API Flow Documentation

## Overview
Test Management API teachers ko tests create, manage, aur marks entry karne ki facility deta hai. Ye API existing pattern follow karta hai jo `TeacherHomeworkDiaryController`, `TeacherNoticeboardController`, etc. mein use hua hai.

## API Base URL
```
/api/teacher/test-management
```

## Authentication
- Sabhi endpoints `auth:sanctum` middleware require karte hain
- Sirf teachers access kar sakte hain (designation check)

---

## API Endpoints Flow

### 1. Filter Options API
**Endpoint:** `GET /api/teacher/test-management/filter-options`

**Purpose:** Test create/edit ke liye required filter options fetch karta hai

**Response:**
```json
{
  "success": true,
  "message": "Filter options retrieved successfully",
  "data": {
    "campuses": ["Campus A", "Campus B"],
    "classes": ["1st", "2nd", "3rd"],
    "subjects": ["Mathematics", "English"],
    "test_types": ["Quiz", "Mid Term", "Final Term"],
    "sessions": ["2024-2025", "2025-2026"]
  }
}
```

**Logic:**
- Campuses: Teacher ke assigned subjects se
- Classes: Teacher ke assigned subjects se
- Subjects: Teacher ke assigned subjects se
- Test Types: Existing tests se ya default values
- Sessions: Existing tests se ya default values

---

### 2. Get Sections by Class
**Endpoint:** `GET /api/teacher/test-management/sections?class={class_name}`

**Purpose:** Class select karne ke baad sections fetch karta hai

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

**Logic:**
- Teacher ke assigned subjects se sections filter karta hai
- Class parameter ke basis par

---

### 3. Get Subjects by Class/Section
**Endpoint:** `GET /api/teacher/test-management/subjects?class={class}&section={section}`

**Purpose:** Class aur section select karne ke baad subjects fetch karta hai

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

**Logic:**
- Teacher ke assigned subjects se filter karta hai
- Class aur section ke basis par

---

### 4. Create Test
**Endpoint:** `POST /api/teacher/test-management/create`

**Purpose:** Naya test create karta hai

**Request Body:**
```json
{
  "campus": "Campus A",
  "test_name": "Math Quiz 1",
  "for_class": "1st",
  "section": "A",
  "subject": "Mathematics",
  "test_type": "Quiz",
  "description": "First quiz of the semester",
  "date": "2025-01-15",
  "session": "2024-2025"
}
```

**Validation:**
- campus: required, string
- test_name: required, string, max:255
- for_class: required, string
- section: required, string
- subject: required, string
- test_type: required, string
- description: nullable, string
- date: required, date
- session: required, string

**Response:**
```json
{
  "success": true,
  "message": "Test created successfully",
  "data": {
    "test": {
      "id": 1,
      "campus": "Campus A",
      "test_name": "Math Quiz 1",
      "for_class": "1st",
      "section": "A",
      "subject": "Mathematics",
      "test_type": "Quiz",
      "description": "First quiz of the semester",
      "date": "2025-01-15",
      "date_formatted": "15 Jan 2025",
      "session": "2024-2025",
      "result_status": false,
      "created_at": "2025-01-10 10:00:00",
      "updated_at": "2025-01-10 10:00:00"
    }
  }
}
```

---

### 5. List Tests
**Endpoint:** `GET /api/teacher/test-management/list` or `POST /api/teacher/test-management/list`

**Purpose:** Tests ki list fetch karta hai with filters

**Query Parameters (GET) / Request Body (POST):**
- campus (optional)
- class (optional)
- section (optional)
- subject (optional)
- test_type (optional)
- session (optional)
- search (optional) - test_name, subject, etc. mein search
- start_date (optional)
- end_date (optional)
- per_page (optional, default: 30) - 10, 25, 30, 50, 100

**Response:**
```json
{
  "success": true,
  "message": "Tests list retrieved successfully",
  "data": {
    "tests": [
      {
        "id": 1,
        "campus": "Campus A",
        "test_name": "Math Quiz 1",
        "for_class": "1st",
        "section": "A",
        "subject": "Mathematics",
        "test_type": "Quiz",
        "description": "First quiz",
        "date": "2025-01-15",
        "date_formatted": "15 Jan 2025",
        "session": "2024-2025",
        "result_status": false,
        "created_at": "2025-01-10 10:00:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "last_page": 5,
      "per_page": 30,
      "total": 150,
      "from": 1,
      "to": 30
    }
  }
}
```

**Logic:**
- Sirf teacher ke assigned classes/sections ke tests show hote hain
- Multiple filters apply ho sakte hain
- Search functionality test_name, subject, class, section mein
- Date range filter available

---

### 6. Get Single Test
**Endpoint:** `GET /api/teacher/test-management/{id}`

**Purpose:** Single test ka detail fetch karta hai

**Response:**
```json
{
  "success": true,
  "message": "Test retrieved successfully",
  "data": {
    "test": {
      "id": 1,
      "campus": "Campus A",
      "test_name": "Math Quiz 1",
      "for_class": "1st",
      "section": "A",
      "subject": "Mathematics",
      "test_type": "Quiz",
      "description": "First quiz",
      "date": "2025-01-15",
      "date_formatted": "15 Jan 2025",
      "session": "2024-2025",
      "result_status": false,
      "created_at": "2025-01-10 10:00:00",
      "updated_at": "2025-01-10 10:00:00"
    }
  }
}
```

---

### 7. Update Test
**Endpoint:** `PUT /api/teacher/test-management/{id}`

**Purpose:** Existing test ko update karta hai

**Request Body:** (Same as create)

**Response:**
```json
{
  "success": true,
  "message": "Test updated successfully",
  "data": {
    "test": { /* updated test object */ }
  }
}
```

**Validation:**
- Teacher sirf apne assigned classes/sections ke tests update kar sakta hai

---

### 8. Delete Test
**Endpoint:** `DELETE /api/teacher/test-management/{id}`

**Purpose:** Test ko delete karta hai

**Response:**
```json
{
  "success": true,
  "message": "Test deleted successfully"
}
```

**Validation:**
- Teacher sirf apne assigned classes/sections ke tests delete kar sakta hai

---

### 9. Toggle Result Status
**Endpoint:** `POST /api/teacher/test-management/{id}/toggle-result-status`

**Purpose:** Test ka result status toggle karta hai (declare/undeclare)

**Response:**
```json
{
  "success": true,
  "message": "Result declared successfully!",
  "data": {
    "test": {
      "id": 1,
      "result_status": true
    }
  }
}
```

---

### 10. Get Students for Test (Marks Entry)
**Endpoint:** `GET /api/teacher/test-management/{id}/students`

**Purpose:** Test ke liye students list fetch karta hai (marks entry ke liye)

**Response:**
```json
{
  "success": true,
  "message": "Students retrieved successfully",
  "data": {
    "test": {
      "id": 1,
      "test_name": "Math Quiz 1",
      "for_class": "1st",
      "section": "A",
      "subject": "Mathematics"
    },
    "students": [
      {
        "id": 1,
        "student_name": "John Doe",
        "student_code": "ST001",
        "gr_number": "GR001",
        "marks": null  // if marks already entered
      }
    ]
  }
}
```

**Logic:**
- Test ke class, section, campus ke basis par students fetch
- Agar marks already entered hain to wo bhi include

---

## Error Handling

### Authentication Error (403)
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access test management.",
  "token": null
}
```

### Validation Error (422)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "test_name": ["The test name field is required."],
    "date": ["The date field is required."]
  }
}
```

### Not Found Error (404)
```json
{
  "success": false,
  "message": "Test not found"
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "An error occurred while processing your request: {error_message}"
}
```

---

## Security & Authorization

1. **Authentication:** Sabhi endpoints `auth:sanctum` middleware use karte hain
2. **Authorization:** Sirf teachers access kar sakte hain (designation check)
3. **Data Isolation:** Teacher sirf apne assigned classes/sections ke tests dekh sakta hai
4. **Validation:** Server-side validation sabhi inputs ke liye

---

## Database Models Used

1. **Test Model:** `App\Models\Test`
   - Fields: campus, test_name, for_class, section, subject, test_type, description, date, session, result_status

2. **Subject Model:** `App\Models\Subject`
   - Teacher ke assigned subjects fetch karne ke liye

3. **Student Model:** `App\Models\Student`
   - Students list fetch karne ke liye

4. **StudentMark Model:** `App\Models\StudentMark`
   - Marks entry ke liye (future implementation)

---

## Implementation Notes

1. **Case-Insensitive Matching:** Campus, class, section, subject comparisons case-insensitive hain
2. **Pagination:** Default 30 items per page, options: 10, 25, 30, 50, 100
3. **Date Formatting:** Dates `Y-m-d` format mein store, `d M Y` format mein display
4. **Filter Options:** Teacher ke assigned subjects se dynamically generate hote hain
5. **Search:** Multiple fields mein search support (test_name, subject, class, section, campus)

---

## Future Enhancements

1. **Marks Entry API:** Bulk marks entry endpoint
2. **Test Results API:** Test results view karne ke liye
3. **Test Reports API:** Various test reports generate karne ke liye
4. **Test Schedule API:** Test schedule view/edit karne ke liye

---

# Add & Manage Diaries API Documentation

## Overview
Diary Management API teachers ko homework diary create aur manage karne ki facility deta hai. Ye APIs teachers ko unke assigned subjects aur classes ke subjects list karne mein help karte hain.

## API Base URL
```
/api/teacher/homework-diary
```

## Authentication
- Sabhi endpoints `auth:sanctum` middleware require karte hain
- Sirf teachers access kar sakte hain (designation check)

---

## API Endpoints

### 1. Get Teacher's Assigned Subjects (Simple List)
**Endpoint:** `GET /api/teacher/homework-diary/teacher-subjects`

**Purpose:** Logged-in teacher ke assigned subjects ki list fetch karta hai. Ye API homework dene ke liye teacher ko unke assigned subjects dikhata hai.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `class` (optional) - Class name filter. Agar class select kiya to sirf us class ke subjects return honge.
- `section` (optional) - Section name filter. Agar section select kiya to sirf us section ke subjects return honge.
- Dono filters ek saath use kar sakte hain - agar class aur section dono select kiye to sirf us specific class aur section ke subjects return honge.

**Examples:**

**1. All Subjects (No Filter):**
```
GET /api/teacher/homework-diary/teacher-subjects
```

**2. Filter by Class Only:**
```
GET /api/teacher/homework-diary/teacher-subjects?class=five
```

**3. Filter by Class and Section:**
```
GET /api/teacher/homework-diary/teacher-subjects?class=five&section=A
```

**4. Filter by Section Only:**
```
GET /api/teacher/homework-diary/teacher-subjects?section=A
```

**Response (Without Class Filter):**
```json
{
  "success": true,
  "message": "Teacher subjects retrieved successfully",
  "data": {
    "teacher": {
      "name": "talha",
      "emp_id": "EMP001"
    },
    "class_filter": null,
    "subjects": [
      {
        "id": 1,
        "subject_name": "English",
        "campus": "icms",
        "class": "five",
        "section": "A"
      },
      {
        "id": 4,
        "subject_name": "urdu",
        "campus": "icms",
        "class": "five",
        "section": "B"
      },
      {
        "id": 3,
        "subject_name": "Urdu",
        "campus": "icms",
        "class": "Ninth",
        "section": "A"
      }
    ],
    "subjects_by_class": {
      "five": [...],
      "ninth": [...]
    },
    "total_subjects": 3,
    "classes": ["five", "Ninth"]
  }
}
```

**Response (With Class Filter - class=five):**
```json
{
  "success": true,
  "message": "Teacher subjects retrieved successfully for class: five",
  "data": {
    "teacher": {
      "name": "talha",
      "emp_id": "EMP001"
    },
    "class_filter": "five",
    "section_filter": null,
    "subjects": [
      {
        "id": 1,
        "subject_name": "English",
        "campus": "icms",
        "class": "five",
        "section": "A"
      },
      {
        "id": 4,
        "subject_name": "urdu",
        "campus": "icms",
        "class": "five",
        "section": "B"
      }
    ],
    "subjects_by_class": {
      "five": [...]
    },
    "total_subjects": 2,
    "classes": ["five"]
  }
}
```

**Response (With Class and Section Filter - class=five&section=A):**
```json
{
  "success": true,
  "message": "Teacher subjects retrieved successfully for class: five, section: A",
  "data": {
    "teacher": {
      "name": "talha",
      "emp_id": "EMP001"
    },
    "class_filter": "five",
    "section_filter": "A",
    "subjects": [
      {
        "id": 1,
        "subject_name": "English",
        "campus": "icms",
        "class": "five",
        "section": "A"
      }
    ],
    "subjects_by_class": {
      "five": [...]
    },
    "total_subjects": 1,
    "classes": ["five"]
  }
}
```

**Logic:**
- Teacher ke name se match karke assigned subjects fetch karta hai
- **Class Filter:** Agar `class` parameter diya gaya hai, to sirf us class ke subjects return hote hain
- **Section Filter:** Agar `section` parameter diya gaya hai, to sirf us section ke subjects return hote hain
- **Combined Filter:** Agar class aur section dono diye gaye hain, to sirf us specific class aur section ke subjects return hote hain
- **No Mixing:** Agar class "five" aur section "A" select kiye to sirf "five" class ke "A" section ke subjects aayenge, doosre classes ya sections ke subjects mix nahi honge
- Subject name ke basis par sorted list return karta hai
- Har subject ka id, name, campus, class, aur section include hota hai

**Full URLs:**
- All Subjects: `https://school.ritpk.com/api/teacher/homework-diary/teacher-subjects`
- Filter by Class: `https://school.ritpk.com/api/teacher/homework-diary/teacher-subjects?class=five`
- Filter by Class and Section: `https://school.ritpk.com/api/teacher/homework-diary/teacher-subjects?class=five&section=A`
- Filter by Section Only: `https://school.ritpk.com/api/teacher/homework-diary/teacher-subjects?section=A`

---

### 2. Get All Subjects by Class
**Endpoint:** `GET /api/teacher/homework-diary/subjects-by-class?class={class_name}`

**Purpose:** Kisi specific class ke liye saare subjects ki list fetch karta hai (chahe wo kisi bhi teacher ko assigned ho). Example: Class 3 ke liye saare subjects list ho jayenge.

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters:**
- `class` (required) - Class name (e.g., "3rd", "5th", "10th")

**Example:**
```
GET /api/teacher/homework-diary/subjects-by-class?class=3rd
```

**Response:**
```json
{
  "success": true,
  "message": "Subjects retrieved successfully",
  "data": {
    "class": "3rd",
    "subjects": [
      {
        "id": 1,
        "subject_name": "Mathematics",
        "campus": "Main Campus",
        "class": "3rd",
        "section": "A",
        "teacher": "John Doe"
      },
      {
        "id": 2,
        "subject_name": "English",
        "campus": "Main Campus",
        "class": "3rd",
        "section": "A",
        "teacher": "Jane Smith"
      },
      {
        "id": 3,
        "subject_name": "Science",
        "campus": "Main Campus",
        "class": "3rd",
        "section": "A",
        "teacher": "Bob Johnson"
      },
      {
        "id": 4,
        "subject_name": "Urdu",
        "campus": "Main Campus",
        "class": "3rd",
        "section": "B",
        "teacher": "Ali Khan"
      }
    ],
    "total_subjects": 4
  }
}
```

**Logic:**
- **Strict Filtering:** Sirf us specific class ke assigned subjects fetch karta hai
- **No Mixing:** Doosre classes ke subjects mix nahi hote, har class ka apna subject hai
- Class name ke basis par case-insensitive matching se subjects filter karta hai
- Har subject ka id, name, campus, class, section, aur assigned teacher ka name include hota hai
- Subject name aur section ke basis par sorted list return karta hai
- Double verification: Database query ke baad bhi verify karta hai ke sab subjects requested class ke hain

**Important Notes:**
- Har class ka apna subject hai - jo subject us class ko assign kiya gaya hai, wo hi list hota hai
- Agar class 3rd pass kiya to sirf class 3rd ke subjects aayenge, doosre classes ke subjects mix nahi honge
- Same subject agar multiple sections mein hai to wo bhi alag-alag entries mein dikhega

**Validation:**
- `class` parameter required hai
- Agar class parameter missing hai to 422 validation error return hota hai

**Full URL:** `https://school.ritpk.com/api/teacher/homework-diary/subjects-by-class?class=3rd`

---

## Error Handling

### Authentication Error (403)
```json
{
  "success": false,
  "message": "Access denied. Only teachers can access this endpoint."
}
```

### Validation Error (422) - For getSubjectsByClass
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "class": ["The class field is required."]
  }
}
```

### Server Error (500)
```json
{
  "success": false,
  "message": "An error occurred while retrieving teacher subjects: {error_message}"
}
```

---

## Use Cases

### Use Case 1: Teacher ko apne assigned subjects dikhana
- Teacher login karta hai
- `/api/teacher/homework-diary/teacher-subjects` endpoint call karta hai
- Teacher ko apne saare assigned subjects ki list milti hai
- Teacher in subjects ke liye homework de sakta hai

### Use Case 2: Class ke saare subjects dikhana
- Teacher login karta hai
- Class select karta hai (e.g., "3rd")
- `/api/teacher/homework-diary/subjects-by-class?class=3rd` endpoint call karta hai
- **Sirf Class 3rd ke assigned subjects ki list milti hai** (chahe wo kisi bhi teacher ko assigned ho)
- **No Mixing:** Doosre classes (jaise 4th, 5th) ke subjects mix nahi hote
- Teacher dekh sakta hai ke is specific class mein kaun se subjects assign kiye gaye hain
- Har class ka apna subject hai, jo assign kiya gaya hai wo hi list hota hai

**Example:**
- Class 3rd ke liye: Mathematics, English, Science, Urdu
- Class 5th ke liye: Mathematics, English, Science, Social Studies
- Agar `class=3rd` pass kiya to sirf 3rd class ke subjects aayenge, 5th class ke subjects mix nahi honge

---

## Notes

1. **Case-Insensitive Matching:** Class name comparison case-insensitive hai
2. **Teacher Filtering:** `getTeacherSubjects` API sirf logged-in teacher ke assigned subjects return karta hai
3. **Strict Class Filtering:** `getSubjectsByClass` API sirf us specific class ke assigned subjects return karta hai
   - **No Mixing:** Doosre classes ke subjects mix nahi hote
   - **Har class ka apna subject:** Jo subject us class ko assign kiya gaya hai, wo hi list hota hai
   - Double verification ensure karta hai ke sab subjects requested class ke hain
4. **Subject Sorting:** Dono APIs subject name ke basis par ascending order mein sorted results return karte hain
5. **Multiple Sections:** Agar same subject multiple sections mein hai to wo alag-alag entries mein dikhega

