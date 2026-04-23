# Student Evaluation System — Project Walkthrough

The **Student Evaluation System** is a complete, offline-ready web application built with pure PHP and MySQL. It features a modern, Tailwind-inspired UI and provides comprehensive tools for managing academic performance.

## 🚀 Setup Instructions

1.  **Database Import**:
    *   Open **Laragon** and start Apache and MySQL.
    *   Open **phpMyAdmin** (or any MySQL client).
    *   Create a database named `grading_system_v2`.
    *   Import the schema file located at: `c:\laragon\www\grading_systemv2\database\schema.sql`.
2.  **Access the System**:
    *   Open your browser and go to: `http://localhost/grading_systemv2/`.
3.  **Default Credentials**:
    *   **Admin**: `admin` / `password`
    *   **Instructor**: `instructor` / `password`

## 🛠️ Key Features

### Admin Module
- **Dashboard**: Visual statistics of student enrollment using Chart.js.
- **Courses & Subjects**: Centralized management of the academic catalog.
- **Instructor Assignment**: Assign faculty to subjects directly within the subject management form.
- **System Settings**: Customize the application title and upload a local logo.

### Instructor Module
- **Class Setup**: Manage class logistics (Day, Time, Room) and student population counts.
- **Student Enrollment**: Add and manage student lists per subject.
- **Grading Criteria**: Define weighted categories (e.g., Quiz 30%, Exam 50%, Attendance 20%).
- **Activity & Attendance**: Record quiz scores and mark attendance (Present, Late, Absent).
- **Evaluation Engine**: Automated grade computation and risk-level prediction based on academic performance.

### Student Portal
- **ID Lookup**: Students can securely view their results using their Student ID.
- **Performance Report**: Includes final grades, equivalents, strengths, weaknesses, and targeted improvement suggestions.

## 📁 Project Structure

- `/api/`: PHP action handlers for all CRUD operations.
- `/assets/`: Local CSS, JS (Chart.js), and uploaded media.
- `/config/`: Database connection settings.
- `/database/`: SQL schema and seed data.
- `/includes/`: Shared logic, layout components (header/footer), and helper functions.
- `/pages/`: Role-based application pages.

## 🧪 Verification Steps

1.  Log in as **Admin** and create a new Course (e.g., BSIT).
2.  Create a Subject and assign it to the default **Instructor**.
3.  Log in as **Instructor**, select the subject, and add at least 2 students.
4.  Set the **Criteria** (ensure they sum to 100%).
5.  Add a **Quiz** and an **Attendance** session, then record scores for the students.
6.  Go to the **Evaluate** page to see the automated predictions and risk assessments.
7.  Access the **Student Portal** and search for a Student ID to view the final report.
