# Entity-Relationship Diagram (ERD)

This document outlines the database structure for the Student Evaluation System v2.

```mermaid
erDiagram
    USERS ||--o{ SUBJECTS : teaches
    COURSES ||--o{ SUBJECTS : defines
    SEMESTERS ||--o{ SUBJECTS : contains
    
    SUBJECTS ||--|| SUBJECT_SCHEDULES : has
    SUBJECTS ||--o{ STUDENTS : enrolls
    SUBJECTS ||--o{ CRITERIA : uses
    SUBJECTS ||--o{ ACTIVITIES : manages
    SUBJECTS ||--o{ ATTENDANCE : tracks
    
    STUDENTS ||--o{ ACTIVITY_SCORES : receives
    ACTIVITIES ||--o{ ACTIVITY_SCORES : records
    
    STUDENTS ||--o{ ATTENDANCE_RECORDS : logs
    ATTENDANCE ||--o{ ATTENDANCE_RECORDS : contains

    USERS {
        int id PK
        string username
        string password
        string full_name
        enum role "admin, instructor"
        boolean status
    }

    COURSES {
        int id PK
        string course_name
        string description
        boolean status
    }

    SEMESTERS {
        int id PK
        string name
        boolean status "1=Active, 0=Archived"
    }

    SUBJECTS {
        int id PK
        int semester_id FK
        int course_id FK
        int instructor_id FK
        string course_no
        string descriptive_title
        boolean with_lab
        boolean status
    }

    SUBJECT_SCHEDULES {
        int id PK
        int subject_id FK
        string day
        time time_start
        time time_end
        string room
        int total_students
    }

    STUDENTS {
        int id PK
        int subject_id FK
        string student_id
        string first_name
        string last_name
        enum gender "Male, Female"
        boolean status
    }

    CRITERIA {
        int id PK
        int subject_id FK
        string category
        decimal weight
        enum type "Lecture, Lab"
    }

    ACTIVITIES {
        int id PK
        int subject_id FK
        string category
        string title
        decimal total_points
        enum type "Lecture, Lab"
        date activity_date
    }

    ACTIVITY_SCORES {
        int id PK
        int activity_id FK
        int student_id FK
        decimal score
    }

    ATTENDANCE {
        int id PK
        int subject_id FK
        date session_date
        string title
    }

    ATTENDANCE_RECORDS {
        int id PK
        int attendance_id FK
        int student_id FK
        int status "1=Present, 0=Absent, 2=Late"
    }
```

## Database Relationships Summary

- **Users**: Admins manage the system; Instructors are assigned to Subjects.
- **Semesters**: Provide academic cycles. A Subject is tied to a specific Semester.
- **Subjects**: The core entity. Connects a Course, an Instructor, and a Semester.
- **Students**: Enrolled per Subject. Student ID is unique within a subject but may exist across multiple subjects.
- **Criteria**: Defines how grades are weighted (e.g., Quizzes=20%, Exam=40%).
- **Activities**: Individual graded items (Quizzes, Recitation, Exams).
- **Scores & Attendance**: Link student records to specific activities and attendance sessions.
