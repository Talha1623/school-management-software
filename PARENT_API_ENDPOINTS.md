# Parent API Endpoints - Live Server

## Base URL
```
https://school.ritpk.com/api/parent
```

---

## 1. Login API 🔐

**Endpoint:** `POST /api/parent/login`

**URL:** `https://school.ritpk.com/api/parent/login`

**Request:**
```json
{
    "email": "parent@example.com",
    "password": "password123"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Login successful",
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "Invalid credentials",
    "token": null
}
```

---

## 2. Logout API 🚪

**Endpoint:** `POST /api/parent/logout`

**URL:** `https://school.ritpk.com/api/parent/logout`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Logout successful",
    "token": null
}
```

---

## 3. Get Profile API 👤

**Endpoint:** `GET /api/parent/profile`

**URL:** `https://school.ritpk.com/api/parent/profile`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "parent": {
            "id": 1,
            "name": "John Doe",
            "email": "parent@example.com",
            "phone": "1234567890",
            "whatsapp": "1234567890",
            "id_card_number": "12345-1234567-1",
            "address": "123 Main Street",
            "profession": "Engineer"
        },
        "students": [
            {
                "id": 1,
                "student_name": "Student Name",
                "student_code": "ST0001-1",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus",
                "gender": "male",
                "date_of_birth": "2010-01-01"
            }
        ],
        "total_students": 1,
        "has_login_access": true
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 4. Get Personal Details API 📋

**Endpoint:** `GET /api/parent/personal-details`

**URL:** `https://school.ritpk.com/api/parent/personal-details`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Personal details retrieved successfully",
    "data": {
        "name": "John Doe",
        "email": "parent@example.com",
        "phone": "1234567890",
        "whatsapp": "1234567890",
        "id_card_number": "12345-1234567-1",
        "address": "123 Main Street",
        "profession": "Engineer"
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 5. Get Students List API 👨‍👩‍👧‍👦

**Endpoint:** `GET /api/parent/students`

**URL:** `https://school.ritpk.com/api/parent/students`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Students list retrieved successfully",
    "data": {
        "parent_id": 1,
        "parent_name": "John Doe",
        "total_students": 2,
        "students": [
            {
                "id": 1,
                "student_name": "Student Name",
                "surname_caste": "Surname",
                "full_name": "Student Name Surname",
                "student_code": "ST0001-1",
                "gr_number": "GR12345",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus",
                "gender": "male",
                "date_of_birth": "2010-01-01",
                "date_of_birth_formatted": "01 Jan 2010",
                "age": 14,
                "admission_date": "2020-01-15",
                "admission_date_formatted": "15 Jan 2020",
                "photo": "https://school.ritpk.com/storage/students/photos/photo.jpg",
                "monthly_fee": 5000.00,
                "discounted_student": false,
                "transport_route": "Route 1",
                "b_form_number": "12345-1234567-1",
                "religion": "Islam",
                "place_of_birth": "Lahore",
                "home_address": "123 Main Street",
                "previous_school": "ABC School",
                "father_name": "John Doe",
                "father_email": "father@example.com",
                "father_phone": "1234567890",
                "mother_phone": "0987654321",
                "whatsapp_number": "1234567890"
            }
        ]
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 6. Get School Notices/Notifications API 📢

**Endpoint:** `GET /api/parent/notices`

**URL:** `https://school.ritpk.com/api/parent/notices`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `search` - Search in title, notice, or campus
- `campus` - Filter by campus
- `start_date` - Filter from date (YYYY-MM-DD)
- `end_date` - Filter to date (YYYY-MM-DD)
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "School notices retrieved successfully",
    "data": {
        "parent_id": 1,
        "parent_campuses": ["Main Campus", "Branch Campus"],
        "noticeboards": [
            {
                "id": 1,
                "campus": "Main Campus",
                "title": "Holiday Notice",
                "notice": "School will be closed on 25th December for Christmas",
                "date": "2025-12-20",
                "date_formatted": "20 Dec 2025",
                "date_formatted_full": "Friday, 20 December 2025",
                "image": "https://school.ritpk.com/storage/noticeboards/image.jpg",
                "show_on": "Yes",
                "created_at": "2025-12-13 10:30:00",
                "created_at_formatted": "13 Dec 2025, 10:30 AM",
                "updated_at": "2025-12-13 10:30:00"
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
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 7. Get Single Notice API 📄

**Endpoint:** `GET /api/parent/notices/{id}`

**URL:** `https://school.ritpk.com/api/parent/notices/1`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Notice retrieved successfully",
    "data": {
        "noticeboard": {
            "id": 1,
            "campus": "Main Campus",
            "title": "Holiday Notice",
            "notice": "School will be closed on 25th December for Christmas",
            "date": "2025-12-20",
            "date_formatted": "20 Dec 2025",
            "date_formatted_full": "Friday, 20 December 2025",
            "image": "https://school.ritpk.com/storage/noticeboards/image.jpg",
            "show_on": "Yes",
            "created_at": "2025-12-13 10:30:00",
            "created_at_formatted": "13 Dec 2025, 10:30 AM",
            "updated_at": "2025-12-13 10:30:00"
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 8. Get Notice Filter Options API 🔍

**Endpoint:** `GET /api/parent/notices/filter-options`

**URL:** `https://school.ritpk.com/api/parent/notices/filter-options`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Filter options retrieved successfully",
    "data": {
        "campuses": [
            "Main Campus",
            "Branch Campus"
        ],
        "all_campuses": [
            "Main Campus",
            "Branch Campus",
            "ICMS"
        ]
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 9. Get Homework List API 📚

**Endpoint:** `GET /api/parent/homework`

**URL:** `https://school.ritpk.com/api/parent/homework`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `student_id` - Filter by specific student ID
- `date` - Filter by specific date (YYYY-MM-DD)
- `start_date` - Filter from date (YYYY-MM-DD)
- `end_date` - Filter to date (YYYY-MM-DD)
- `subject_id` - Filter by subject ID
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "Homework list retrieved successfully",
    "data": {
        "parent_id": 1,
        "total_students": 2,
        "students": [
            {
                "id": 1,
                "student_name": "Ahmed",
                "student_code": "ST0001-1",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus"
            }
        ],
        "homework": [
            {
                "id": 1,
                "subject_id": 5,
                "subject_name": "Mathematics",
                "campus": "Main Campus",
                "class": "1st",
                "section": "A",
                "date": "2025-12-13",
                "date_formatted": "13 Dec 2025",
                "date_formatted_full": "Friday, 13 December 2025",
                "homework_content": "Complete exercises 1 to 10 from page 45",
                "applicable_students": [
                    {
                        "id": 1,
                        "student_name": "Ahmed",
                        "student_code": "ST0001-1",
                        "class": "1st",
                        "section": "A"
                    }
                ],
                "applicable_students_count": 1,
                "created_at": "2025-12-13 10:30:00",
                "created_at_formatted": "13 Dec 2025, 10:30 AM"
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
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 10. Get Homework by Student ID API 👨‍🎓

**Endpoint:** `GET /api/parent/homework/student/{studentId}`

**URL:** `https://school.ritpk.com/api/parent/homework/student/1`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `date` - Filter by specific date (YYYY-MM-DD)
- `start_date` - Filter from date (YYYY-MM-DD)
- `end_date` - Filter to date (YYYY-MM-DD)
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "Homework retrieved successfully",
    "data": {
        "student": {
            "id": 1,
            "student_name": "Ahmed",
            "student_code": "ST0001-1",
            "class": "1st",
            "section": "A",
            "campus": "Main Campus"
        },
        "homework": [
            {
                "id": 1,
                "subject_id": 5,
                "subject_name": "Mathematics",
                "campus": "Main Campus",
                "class": "1st",
                "section": "A",
                "date": "2025-12-13",
                "date_formatted": "13 Dec 2025",
                "date_formatted_full": "Friday, 13 December 2025",
                "homework_content": "Complete exercises 1 to 10 from page 45",
                "created_at": "2025-12-13 10:30:00",
                "created_at_formatted": "13 Dec 2025, 10:30 AM"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 30,
            "total": 75,
            "from": 1,
            "to": 30
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 11. Get Subjects List API 📖

**Endpoint:** `GET /api/parent/homework/subjects`

**URL:** `https://school.ritpk.com/api/parent/homework/subjects`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Subjects retrieved successfully",
    "data": {
        "subjects": [
            {
                "id": 5,
                "subject_name": "Mathematics",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus"
            },
            {
                "id": 6,
                "subject_name": "English",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus"
            }
        ],
        "total_subjects": 2
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 12. Get Academic Calendar Events API 📅

**Endpoint:** `GET /api/parent/academic-calendar`

**URL:** `https://school.ritpk.com/api/parent/academic-calendar`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `year` - Filter by year (e.g., 2025)
- `month` - Filter by month (1-12)
- `event_type` - Filter by event type
- `start_date` - Filter from date (YYYY-MM-DD)
- `end_date` - Filter to date (YYYY-MM-DD)
- `search` - Search in title or details
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "Academic calendar events retrieved successfully",
    "data": {
        "parent_id": 1,
        "events": [
            {
                "id": 1,
                "event_title": "Eid Holiday",
                "event_details": "School will be closed for Eid ul Fitr",
                "event_type": "holiday",
                "event_date": "2025-04-10",
                "event_date_formatted": "10 Apr 2025",
                "event_date_formatted_full": "Thursday, 10 April 2025",
                "day_name": "Thursday",
                "day_short": "Thu",
                "month": 4,
                "month_name": "April",
                "year": 2025,
                "created_at": "2025-12-13 10:30:00",
                "created_at_formatted": "13 Dec 2025, 10:30 AM",
                "updated_at": "2025-12-13 10:30:00"
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
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 13. Get Events by Month and Year API 📆

**Endpoint:** `GET /api/parent/academic-calendar/{month}/{year}`

**URL:** `https://school.ritpk.com/api/parent/academic-calendar/4/2025`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Events retrieved successfully",
    "data": {
        "month": 4,
        "year": 2025,
        "month_name": "April",
        "month_name_short": "Apr",
        "total_events": 5,
        "events": [
            {
                "id": 1,
                "event_title": "Eid Holiday",
                "event_details": "School will be closed",
                "event_type": "holiday",
                "event_date": "2025-04-10",
                "event_date_formatted": "10 Apr 2025",
                "event_date_formatted_full": "Thursday, 10 April 2025",
                "day_name": "Thursday",
                "day_short": "Thu",
                "day_number": 10,
                "created_at": "2025-12-13 10:30:00",
                "updated_at": "2025-12-13 10:30:00"
            }
        ]
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 14. Get Single Event API 📄

**Endpoint:** `GET /api/parent/academic-calendar/event/{id}`

**URL:** `https://school.ritpk.com/api/parent/academic-calendar/event/1`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Event retrieved successfully",
    "data": {
        "event": {
            "id": 1,
            "event_title": "Eid Holiday",
            "event_details": "School will be closed for Eid ul Fitr",
            "event_type": "holiday",
            "event_date": "2025-04-10",
            "event_date_formatted": "10 Apr 2025",
            "event_date_formatted_full": "Thursday, 10 April 2025",
            "day_name": "Thursday",
            "day_short": "Thu",
            "month": 4,
            "month_name": "April",
            "year": 2025,
            "created_at": "2025-12-13 10:30:00",
            "created_at_formatted": "13 Dec 2025, 10:30 AM",
            "updated_at": "2025-12-13 10:30:00"
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 15. Get Calendar View API 🗓️

**Endpoint:** `GET /api/parent/academic-calendar/calendar-view`

**URL:** `https://school.ritpk.com/api/parent/academic-calendar/calendar-view`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `year` - Year for calendar (default: current year)

**Response:**
```json
{
    "success": true,
    "message": "Academic calendar view retrieved successfully",
    "data": {
        "year": 2025,
        "total_events": 12,
        "events_by_month": {
            "1": [
                {
                    "id": 1,
                    "event_title": "New Year Holiday",
                    "event_details": "School closed",
                    "event_type": "holiday",
                    "event_date": "2025-01-01",
                    "event_date_formatted": "01 Jan 2025",
                    "event_date_formatted_full": "Wednesday, 01 January 2025",
                    "day_name": "Wednesday",
                    "day_number": 1
                }
            ],
            "4": [
                {
                    "id": 2,
                    "event_title": "Eid Holiday",
                    "event_details": "Eid ul Fitr",
                    "event_type": "holiday",
                    "event_date": "2025-04-10",
                    "event_date_formatted": "10 Apr 2025",
                    "event_date_formatted_full": "Thursday, 10 April 2025",
                    "day_name": "Thursday",
                    "day_number": 10
                }
            ]
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 16. Get Study Materials List API 📚

**Endpoint:** `GET /api/parent/study-material`

**URL:** `https://school.ritpk.com/api/parent/study-material`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `student_id` - Filter by specific student ID
- `class` - Filter by class
- `section` - Filter by section
- `subject` - Filter by subject name
- `file_type` - Filter by file type (picture, video, documents)
- `search` - Search in title, description, or subject
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "Study materials retrieved successfully",
    "data": {
        "parent_id": 1,
        "total_students": 2,
        "students": [
            {
                "id": 1,
                "student_name": "Ahmed",
                "student_code": "ST0001-1",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus"
            }
        ],
        "study_materials": [
            {
                "id": 1,
                "title": "Mathematics Chapter 5 Notes",
                "description": "Complete notes for Chapter 5",
                "campus": "Main Campus",
                "class": "1st",
                "section": "A",
                "subject": "Mathematics",
                "file_type": "documents",
                "file_url": "https://school.ritpk.com/storage/study-materials/file.pdf",
                "youtube_url": null,
                "file_path": "study-materials/file.pdf",
                "applicable_students": [
                    {
                        "id": 1,
                        "student_name": "Ahmed",
                        "student_code": "ST0001-1",
                        "class": "1st",
                        "section": "A"
                    }
                ],
                "applicable_students_count": 1,
                "created_at": "2025-12-13 10:30:00",
                "created_at_formatted": "13 Dec 2025, 10:30 AM",
                "updated_at": "2025-12-13 10:30:00"
            },
            {
                "id": 2,
                "title": "Science Video Lecture",
                "description": "Video explanation of photosynthesis",
                "campus": "Main Campus",
                "class": "1st",
                "section": "A",
                "subject": "Science",
                "file_type": "video",
                "file_url": "https://www.youtube.com/watch?v=xxxxx",
                "youtube_url": "https://www.youtube.com/watch?v=xxxxx",
                "file_path": null,
                "applicable_students": [
                    {
                        "id": 1,
                        "student_name": "Ahmed",
                        "student_code": "ST0001-1",
                        "class": "1st",
                        "section": "A"
                    }
                ],
                "applicable_students_count": 1,
                "created_at": "2025-12-13 11:00:00",
                "created_at_formatted": "13 Dec 2025, 11:00 AM",
                "updated_at": "2025-12-13 11:00:00"
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
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 17. Get Study Materials by Student ID API 👨‍🎓

**Endpoint:** `GET /api/parent/study-material/student/{studentId}`

**URL:** `https://school.ritpk.com/api/parent/study-material/student/1`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `subject` - Filter by subject name
- `file_type` - Filter by file type (picture, video, documents)
- `search` - Search in title, description, or subject
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "Study materials retrieved successfully",
    "data": {
        "student": {
            "id": 1,
            "student_name": "Ahmed",
            "student_code": "ST0001-1",
            "class": "1st",
            "section": "A",
            "campus": "Main Campus"
        },
        "study_materials": [
            {
                "id": 1,
                "title": "Mathematics Chapter 5 Notes",
                "description": "Complete notes for Chapter 5",
                "campus": "Main Campus",
                "class": "1st",
                "section": "A",
                "subject": "Mathematics",
                "file_type": "documents",
                "file_url": "https://school.ritpk.com/storage/study-materials/file.pdf",
                "youtube_url": null,
                "file_path": "study-materials/file.pdf",
                "created_at": "2025-12-13 10:30:00",
                "created_at_formatted": "13 Dec 2025, 10:30 AM",
                "updated_at": "2025-12-13 10:30:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 3,
            "per_page": 30,
            "total": 75,
            "from": 1,
            "to": 30
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 18. Get Subjects List API 📖

**Endpoint:** `GET /api/parent/study-material/subjects`

**URL:** `https://school.ritpk.com/api/parent/study-material/subjects`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Subjects retrieved successfully",
    "data": {
        "subjects": [
            "Mathematics",
            "English",
            "Science",
            "Urdu"
        ],
        "total_subjects": 4
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 19. Get Students for Leave Request API 👨‍🎓

**Endpoint:** `GET /api/parent/leave/students`

**URL:** `https://school.ritpk.com/api/parent/leave/students`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Students retrieved successfully",
    "data": {
        "students": [
            {
                "id": 1,
                "student_name": "Ahmed",
                "student_code": "ST0001-1",
                "class": "1st",
                "section": "A",
                "campus": "Main Campus"
            }
        ]
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 20. Create Leave Request API 📝

**Endpoint:** `POST /api/parent/leave/create`

**URL:** `https://school.ritpk.com/api/parent/leave/create`

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
    "student_id": 1,
    "leave_reason": "Medical emergency",
    "from_date": "2025-12-15",
    "to_date": "2025-12-17"
}
```

**Request Body Fields:**
- `student_id` (required, integer): Student ID (must belong to parent)
- `leave_reason` (required, string, max 255 characters): Reason for leave
- `from_date` (required, date, format: YYYY-MM-DD): Start date of leave
- `to_date` (required, date, format: YYYY-MM-DD, must be >= from_date): End date of leave

**Response (Success - 201):**
```json
{
    "success": true,
    "message": "Leave request submitted successfully. It will be reviewed by the admin.",
    "data": {
        "leave": {
            "id": 1,
            "student_id": 1,
            "student_name": "Ahmed",
            "student_code": "ST0001-1",
            "leave_reason": "Medical emergency",
            "from_date": "2025-12-15",
            "to_date": "2025-12-17",
            "status": "Pending",
            "created_at": "2025-12-13 14:30:00"
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Validation Error - 422):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "student_id": ["The student id field is required."],
        "leave_reason": ["The leave reason field is required."],
        "to_date": ["The to date must be a date after or equal to from date."]
    },
    "token": null
}
```

**Response (Student Not Found - 404):**
```json
{
    "success": false,
    "message": "Student not found or does not belong to this parent",
    "token": null
}
```

---

## 21. Get Leave Requests List API 📋

**Endpoint:** `GET /api/parent/leave/list`

**URL:** `https://school.ritpk.com/api/parent/leave/list`

**Headers:**
```
Authorization: Bearer {token}
```

**Query Parameters (Optional):**
- `student_id` - Filter by specific student ID
- `status` - Filter by status (Pending, Approved, Rejected)
- `from_date` - Filter by from date (YYYY-MM-DD)
- `to_date` - Filter by to date (YYYY-MM-DD)
- `per_page` - Items per page (10, 25, 30, 50, 100) - Default: 30

**Response:**
```json
{
    "success": true,
    "message": "Leave requests retrieved successfully",
    "data": {
        "leaves": [
            {
                "id": 1,
                "student_id": 1,
                "student_name": "Ahmed",
                "student_code": "ST0001-1",
                "class": "1st",
                "section": "A",
                "leave_reason": "Medical emergency",
                "from_date": "2025-12-15",
                "from_date_formatted": "15 Dec 2025",
                "to_date": "2025-12-17",
                "to_date_formatted": "17 Dec 2025",
                "status": "Pending",
                "remarks": null,
                "created_at": "2025-12-13 14:30:00",
                "created_at_formatted": "13 Dec 2025, 02:30 PM",
                "updated_at": "2025-12-13 14:30:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 30,
            "total": 1,
            "from": 1,
            "to": 1
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

---

## 22. Get Leave Request by ID API 🔍

**Endpoint:** `GET /api/parent/leave/{id}`

**URL:** `https://school.ritpk.com/api/parent/leave/1`

**Headers:**
```
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "message": "Leave request retrieved successfully",
    "data": {
        "leave": {
            "id": 1,
            "student_id": 1,
            "student_name": "Ahmed",
            "student_code": "ST0001-1",
            "class": "1st",
            "section": "A",
            "leave_reason": "Medical emergency",
            "from_date": "2025-12-15",
            "from_date_formatted": "15 Dec 2025",
            "to_date": "2025-12-17",
            "to_date_formatted": "17 Dec 2025",
            "status": "Pending",
            "remarks": null,
            "created_at": "2025-12-13 14:30:00",
            "created_at_formatted": "13 Dec 2025, 02:30 PM",
            "updated_at": "2025-12-13 14:30:00"
        }
    },
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Not Found - 404):**
```json
{
    "success": false,
    "message": "Leave request not found or does not belong to your students",
    "token": null
}
```

---

## 23. Change Password API 🔑

**Endpoint:** `POST /api/parent/change-password`

**URL:** `https://school.ritpk.com/api/parent/change-password`

**Headers:**
```
Authorization: Bearer {token}
```

**Request:**
```json
{
    "current_password": "oldpassword123",
    "new_password": "newpassword123",
    "confirm_password": "newpassword123"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Password changed successfully.",
    "data": [],
    "token": "1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
}
```

**Response (Error):**
```json
{
    "success": false,
    "message": "Current password is incorrect.",
    "token": null
}
```

---

## Complete API Endpoints List 📝

| Method | Endpoint | Auth Required | Description |
|--------|----------|---------------|-------------|
| POST | `/api/parent/login` | ❌ No | Parent login |
| POST | `/api/parent/logout` | ✅ Yes | Parent logout |
| GET | `/api/parent/profile` | ✅ Yes | Get parent profile with students |
| GET | `/api/parent/personal-details` | ✅ Yes | Get personal details only |
| GET | `/api/parent/students` | ✅ Yes | Get all students list (parent's children) |
| GET | `/api/parent/notices` | ✅ Yes | Get school notices/notifications list |
| GET | `/api/parent/notices/{id}` | ✅ Yes | Get single notice details |
| GET | `/api/parent/notices/filter-options` | ✅ Yes | Get filter options (campuses) |
| GET | `/api/parent/homework` | ✅ Yes | Get homework list for all students |
| GET | `/api/parent/homework/student/{id}` | ✅ Yes | Get homework for specific student |
| GET | `/api/parent/homework/subjects` | ✅ Yes | Get subjects list |
| GET | `/api/parent/academic-calendar` | ✅ Yes | Get academic calendar events list |
| GET | `/api/parent/academic-calendar/{month}/{year}` | ✅ Yes | Get events by month and year |
| GET | `/api/parent/academic-calendar/event/{id}` | ✅ Yes | Get single event details |
| GET | `/api/parent/academic-calendar/calendar-view` | ✅ Yes | Get calendar view (events by month) |
| GET | `/api/parent/study-material` | ✅ Yes | Get study materials list for all students |
| GET | `/api/parent/study-material/student/{id}` | ✅ Yes | Get study materials for specific student |
| GET | `/api/parent/study-material/subjects` | ✅ Yes | Get subjects list |
| GET | `/api/parent/leave/students` | ✅ Yes | Get students list for leave request |
| POST | `/api/parent/leave/create` | ✅ Yes | Create leave request for student |
| GET | `/api/parent/leave/list` | ✅ Yes | Get leave requests list |
| GET | `/api/parent/leave/{id}` | ✅ Yes | Get leave request by ID |
| POST | `/api/parent/change-password` | ✅ Yes | Change password |

---

## Example Usage (cURL) 💻

### Login:
```bash
curl -X POST https://school.ritpk.com/api/parent/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "parent@example.com",
    "password": "password123"
  }'
```

### Get Profile:
```bash
curl -X GET https://school.ritpk.com/api/parent/profile \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Students List:
```bash
curl -X GET https://school.ritpk.com/api/parent/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get School Notices:
```bash
curl -X GET https://school.ritpk.com/api/parent/notices \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Single Notice:
```bash
curl -X GET https://school.ritpk.com/api/parent/notices/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Notices with Filters:
```bash
curl -X GET "https://school.ritpk.com/api/parent/notices?campus=Main%20Campus&per_page=10" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Homework List:
```bash
curl -X GET https://school.ritpk.com/api/parent/homework \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Homework for Specific Student:
```bash
curl -X GET https://school.ritpk.com/api/parent/homework/student/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Homework by Date:
```bash
curl -X GET "https://school.ritpk.com/api/parent/homework?date=2025-12-13" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Homework by Date Range:
```bash
curl -X GET "https://school.ritpk.com/api/parent/homework?start_date=2025-12-01&end_date=2025-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Subjects List:
```bash
curl -X GET https://school.ritpk.com/api/parent/homework/subjects \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Academic Calendar Events:
```bash
curl -X GET https://school.ritpk.com/api/parent/academic-calendar \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Events by Month and Year:
```bash
curl -X GET https://school.ritpk.com/api/parent/academic-calendar/4/2025 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Single Event:
```bash
curl -X GET https://school.ritpk.com/api/parent/academic-calendar/event/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Calendar View:
```bash
curl -X GET "https://school.ritpk.com/api/parent/academic-calendar/calendar-view?year=2025" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Students for Leave Request:

curl -X GET https://school.ritpk.com/api/parent/leave/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Create Leave Request:

curl -X POST https://school.ritpk.com/api/parent/leave/create \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "leave_reason": "Medical emergency",
    "from_date": "2025-12-15",
    "to_date": "2025-12-17"
  }'

### Get Leave Requests List:

curl -X GET https://school.ritpk.com/api/parent/leave/list \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Get Leave Requests by Status:

curl -X GET "https://school.ritpk.com/api/parent/leave/list?status=Pending" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Get Leave Request by ID:

curl -X GET https://school.ritpk.com/api/parent/leave/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Get Study Materials:
```bash
curl -X GET https://school.ritpk.com/api/parent/study-material \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Study Materials for Specific Student:
```bash
curl -X GET https://school.ritpk.com/api/parent/study-material/student/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Study Materials by Subject:
```bash
curl -X GET "https://school.ritpk.com/api/parent/study-material?subject=Mathematics" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Study Materials by File Type:
```bash
curl -X GET "https://school.ritpk.com/api/parent/study-material?file_type=video" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Get Subjects List:
```bash
curl -X GET https://school.ritpk.com/api/parent/study-material/subjects \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Logout:
```bash
curl -X POST https://school.ritpk.com/api/parent/logout \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

---

## Example Usage (JavaScript/Fetch) 🌐

### Login:
```javascript
fetch('https://school.ritpk.com/api/parent/login', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    email: 'parent@example.com',
    password: 'password123'
  })
})
.then(response => response.json())
.then(data => {
  console.log('Token:', data.token);
  // Store token for future requests
  localStorage.setItem('parent_token', data.token);
});
```

### Get Profile:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/profile', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Profile:', data.data);
});
```

### Get Students List:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/students', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Total Students:', data.data.total_students);
  console.log('Students:', data.data.students);
  // Loop through students
  data.data.students.forEach(student => {
    console.log(`${student.student_name} - Class ${student.class}-${student.section}`);
  });
});
```

### Get School Notices:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/notices', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Total Notices:', data.data.pagination.total);
  console.log('Notices:', data.data.noticeboards);
  // Loop through notices
  data.data.noticeboards.forEach(notice => {
    console.log(`${notice.title} - ${notice.date_formatted}`);
  });
});
```

### Get Single Notice:
```javascript
const token = localStorage.getItem('parent_token');
const noticeId = 1;

fetch(`https://school.ritpk.com/api/parent/notices/${noticeId}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Notice:', data.data.noticeboard);
});
```

### Get Homework List:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/homework', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Total Homework:', data.data.pagination.total);
  console.log('Homework:', data.data.homework);
  // Loop through homework
  data.data.homework.forEach(hw => {
    console.log(`${hw.subject_name} - ${hw.date_formatted}: ${hw.homework_content}`);
    console.log(`Applicable to ${hw.applicable_students_count} student(s)`);
  });
});
```

### Get Homework for Specific Student:
```javascript
const token = localStorage.getItem('parent_token');
const studentId = 1;

fetch(`https://school.ritpk.com/api/parent/homework/student/${studentId}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Student:', data.data.student.student_name);
  console.log('Homework:', data.data.homework);
});
```

### Get Homework by Date:
```javascript
const token = localStorage.getItem('parent_token');
const date = '2025-12-13';

fetch(`https://school.ritpk.com/api/parent/homework?date=${date}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log(`Homework for ${date}:`, data.data.homework);
});
```

### Get Academic Calendar Events:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/academic-calendar', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Total Events:', data.data.pagination.total);
  console.log('Events:', data.data.events);
  // Loop through events
  data.data.events.forEach(event => {
    console.log(`${event.event_title} - ${event.event_date_formatted}`);
  });
});
```

### Get Events by Month:
```javascript
const token = localStorage.getItem('parent_token');
const month = 4; // April
const year = 2025;

fetch(`https://school.ritpk.com/api/parent/academic-calendar/${month}/${year}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log(`Events for ${data.data.month_name} ${year}:`, data.data.events);
});
```

### Get Calendar View:
```javascript
const token = localStorage.getItem('parent_token');
const year = 2025;

fetch(`https://school.ritpk.com/api/parent/academic-calendar/calendar-view?year=${year}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log(`Calendar for ${year}:`, data.data.events_by_month);
  // Access events by month
  Object.keys(data.data.events_by_month).forEach(month => {
    console.log(`Month ${month}:`, data.data.events_by_month[month]);
  });
});
```

### Get Students for Leave Request:

curl -X GET https://school.ritpk.com/api/parent/leave/students \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Create Leave Request:

curl -X POST https://school.ritpk.com/api/parent/leave/create \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{
    "student_id": 1,
    "leave_reason": "Medical emergency",
    "from_date": "2025-12-15",
    "to_date": "2025-12-17"
  }'

### Get Leave Requests List:

curl -X GET https://school.ritpk.com/api/parent/leave/list \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Get Leave Requests by Status:

curl -X GET "https://school.ritpk.com/api/parent/leave/list?status=Pending" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Get Leave Request by ID:

curl -X GET https://school.ritpk.com/api/parent/leave/1 \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

### Get Students for Leave Request:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/leave/students', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Students:', data.data.students);
  // Populate student dropdown
  data.data.students.forEach(student => {
    console.log(`${student.student_name} (${student.student_code}) - ${student.class} ${student.section}`);
  });
});
```

### Create Leave Request:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/leave/create', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    student_id: 1,
    leave_reason: 'Medical emergency',
    from_date: '2025-12-15',
    to_date: '2025-12-17'
  })
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Leave request created:', data.data.leave);
    alert('Leave request submitted successfully!');
  } else {
    console.error('Error:', data.message);
  }
});
```

### Get Leave Requests List:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/leave/list', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Total Leave Requests:', data.data.pagination.total);
  console.log('Leave Requests:', data.data.leaves);
  // Loop through leave requests
  data.data.leaves.forEach(leave => {
    console.log(`${leave.student_name} - ${leave.leave_reason} (${leave.status})`);
  });
});
```

### Get Leave Requests by Status:
```javascript
const token = localStorage.getItem('parent_token');
const status = 'Pending'; // Pending, Approved, Rejected

fetch(`https://school.ritpk.com/api/parent/leave/list?status=${status}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log(`${status} Leave Requests:`, data.data.leaves);
});
```

### Get Leave Request by ID:
```javascript
const token = localStorage.getItem('parent_token');
const leaveId = 1;

fetch(`https://school.ritpk.com/api/parent/leave/${leaveId}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Leave Request Details:', data.data.leave);
});
```

### Get Study Materials:
```javascript
const token = localStorage.getItem('parent_token');

fetch('https://school.ritpk.com/api/parent/study-material', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Total Study Materials:', data.data.pagination.total);
  console.log('Study Materials:', data.data.study_materials);
  // Loop through study materials
  data.data.study_materials.forEach(material => {
    console.log(`${material.title} - ${material.file_type}`);
    if (material.file_type === 'video') {
      console.log('YouTube URL:', material.youtube_url);
    } else {
      console.log('File URL:', material.file_url);
    }
  });
});
```

### Get Study Materials for Specific Student:
```javascript
const token = localStorage.getItem('parent_token');
const studentId = 1;

fetch(`https://school.ritpk.com/api/parent/study-material/student/${studentId}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log('Student:', data.data.student.student_name);
  console.log('Study Materials:', data.data.study_materials);
});
```

### Get Study Materials by File Type:
```javascript
const token = localStorage.getItem('parent_token');
const fileType = 'video'; // or 'picture', 'documents'

fetch(`https://school.ritpk.com/api/parent/study-material?file_type=${fileType}`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
  }
})
.then(response => response.json())
.then(data => {
  console.log(`${fileType} materials:`, data.data.study_materials);
});
```

---

## Example Usage (Postman) 📮

1. **Login Request:**
   - Method: `POST`
   - URL: `https://school.ritpk.com/api/parent/login`
   - Body (raw JSON):
     ```json
     {
       "email": "parent@example.com",
       "password": "password123"
     }
     ```

2. **Profile Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/profile`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

3. **Students List Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/students`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

4. **School Notices Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/notices`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

5. **Single Notice Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/notices/1`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

6. **Homework List Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/homework`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

7. **Homework by Student Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/homework/student/1`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

8. **Homework by Date Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/homework?date=2025-12-13`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

9. **Academic Calendar Events Request:**
   - Method: `GET`
   - URL: `https://school.ritpk.com/api/parent/academic-calendar`
   - Headers:
     - `Authorization`: `Bearer {token_from_login}`

10. **Events by Month/Year Request:**
    - Method: `GET`
    - URL: `https://school.ritpk.com/api/parent/academic-calendar/4/2025`
    - Headers:
      - `Authorization`: `Bearer {token_from_login}`

11. **Calendar View Request:**
    - Method: `GET`
    - URL: `https://school.ritpk.com/api/parent/academic-calendar/calendar-view?year=2025`
    - Headers:
      - `Authorization`: `Bearer {token_from_login}`

---

## Error Responses ⚠️

All APIs return consistent error format:

```json
{
    "success": false,
    "message": "Error message here",
    "token": null
}
```

**Common Error Messages:**
- `"Invalid credentials"` - Wrong email or password
- `"Password not set. Please contact administrator."` - Password not set
- `"You do not have login access. Please contact administrator."` - No login access
- `"Unauthenticated"` - Token missing or invalid
- `"Validation failed"` - Request validation failed

---

## Status Codes 📊

- `200` - Success (even for errors, Laravel returns 200 with success: false)
- `404` - Not found
- `422` - Validation error
- `500` - Server error

---

## Notes 📌

1. **Token Storage:** Token ko securely store karein (localStorage, secure cookie, etc.)
2. **Token Expiry:** Tokens never expire (null expiration)
3. **Token Reuse:** Same token multiple times use kar sakte hain
4. **Logout:** Logout karne se token revoke ho jata hai
5. **HTTPS:** Live server par HTTPS use ho raha hai (secure)

---

## Testing Checklist ✅

- [ ] Login API working
- [ ] Token received
- [ ] Profile API working with token
- [ ] Personal details API working
- [ ] Change password API working
- [ ] Logout API working
- [ ] Error handling tested

---

**Base URL:** `https://school.ritpk.com/api/parent`  
**Last Updated:** December 13, 2025

