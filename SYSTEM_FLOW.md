# SECURED RFID VERIFICATION SYSTEM - System Flow

## 1. Project Overview
This system is a PHP/MySQL student registration and attendance management application with admin access control and RFID scan support.

It covers:
- Admin authentication and session-based access
- Student registration, edit, delete, and photo upload
- RFID attendance session creation, start/stop, and scanner logging
- Department-based scoping for admin users
- Activity logging and attendance reporting

---

## 2. Main Components

### 2.1 Entry Points
- index.php → redirects to login.php
- login.php → shows login form and handles admin authentication
- logout.php → logs out the admin and destroys the session

### 2.2 Core Backend
- config/database.php → PDO database connection, schema bootstrap, default admin and department creation
- ackend/auth.php → authentication, session enforcement, role checks, department scoping, activity logging
- ackend/admin/manage_admin_actions.php → admin CRUD, department CRUD, activity log retrieval
- ackend/admin/student_actions.php → student query helpers for views

### 2.3 Views and Pages
- iews/layout.php → page layout, protected sidebar, header/footer, includes auth enforcement
- iews/dashboard.php → admin home page with stat cards and recent entries
- iews/students.php → student list with search and pagination
- iews/register_student.php → student registration form
- iews/edit_student.php → student editing form
- iews/delete_student.php → student delete confirmation
- iews/profile.php → admin profile management
- iews/manage_admins.php → admin user management
- iews/manage_departments.php → department management
- iews/activity_logs.php → activity log viewer

### 2.4 Attendance Module
- iews/attendance/attendance_sessions.php → attendance session list and start/stop controls
- iews/attendance/create_session.php → create a new attendance session
- iews/attendance/start_session.php → start an inactive session
- iews/attendance/stop_session.php → stop an active session
- iews/attendance/attendance_logs.php → attendance history page
- iews/attendance/attendance_reports.php → attendance reports page
- iews/attendance/attendance_logs_ajax.php → JSON endpoint for filtered attendance logs
- scanner/scan.php → attendance scanner UI
- scanner/process_scan.php → scan validation and attendance log writer

---

## 3. User Flow

### 3.1 System Initialization
1. index.php redirects to login.php.
2. config/database.php initializes the database and required tables.
3. Default dmin and General department rows are created if missing.

### 3.2 Admin Login Flow
1. Admin opens login.php.
2. Credentials are submitted via POST.
3. ackend/auth.php validates the admin account and saves session values.
4. On success, the admin is redirected to iews/dashboard.php.
5. On failure, login errors are shown.

### 3.3 Page Access and Layout
1. Protected pages include iews/layout.php.
2. iews/layout.php calls ensure_user_session() from ackend/auth.php.
3. If the user is not authenticated or inactive, they are redirected to login.php.
4. Non-super-admins are scoped to their department using get_department_filter().

### 3.4 Student Management
1. iews/students.php displays student records with filters and pagination.
2. iews/register_student.php adds a student record with RFID and photo upload.
3. iews/edit_student.php updates student details.
4. iews/delete_student.php deletes a student.

### 3.5 Attendance Session Management
1. iews/attendance/attendance_sessions.php lists sessions and shows status.
2. iews/attendance/create_session.php creates sessions with type IN or OUT.
3. iews/attendance/start_session.php activates a session.
4. iews/attendance/stop_session.php deactivates a session.

### 3.6 Scanner and Attendance Logging
1. scanner/scan.php provides the scan UI and active session status.
2. Scans are sent to scanner/process_scan.php as JSON.
3. The processor searches students by 
fid_uid.
4. Unknown scans are written to unknown_scans.
5. Valid scans are checked against an active session and duplicate logs.
6. Attendance events are recorded in ttendance_logs.

### 3.7 Reports and Logs
1. iews/attendance/attendance_logs.php displays attendance history.
2. iews/attendance/attendance_reports.php displays summary reports.
3. iews/attendance/attendance_logs_ajax.php returns filtered JSON for dynamic tables.
4. iews/activity_logs.php shows admin activity history.

---

## 4. Data Access Rules
- Super admins can view and manage all records.
- Department admins are limited to their own department.
- Attendance sessions can be department-specific or general.
- ctivity_logs capture admin activity across the system.

---

## 5. Database Structure

### Key Tables
- dmins
  - id, username, password, ullname, 
ole, department_id, status, created_at
- departments
  - id, department_name, department_code, created_at
- students
  - id, student_id, irst_name, last_name, middle_name, course, year_level,
    section, contact_number, email, ddress, 
fid_uid, photo, status, department_id, created_at
- ctivity_logs
  - id, dmin_id, ctivity, created_at
- ttendance_sessions
  - id, department_id, session_name, ttendance_type, start_time, end_time, status, created_at
- ttendance_logs
  - id, student_id, 
fid_uid, ttendance_type, session_id, scan_method, scan_time, created_at
- unknown_scans
  - id, scanned_value, scan_method, created_at

---

## 6. System Flow Diagram

`mermaid
flowchart TD
    A[index.php] --> B[login.php]
    B --> C{Valid login?}
    C -- Yes --> D[views/dashboard.php]
    C -- No --> B
    D --> E[views/students.php]
    D --> F[views/attendance/attendance_sessions.php]
    D --> G[views/activity_logs.php]
    D --> H[views/manage_admins.php]
    D --> I[views/manage_departments.php]
    E --> J[register/edit/delete student pages]
    F --> K[scanner/scan.php]
    K --> L[scanner/process_scan.php]
    L --> M[attendance_logs table]
    L --> N[unknown_scans table]
    M --> O[attendance_reports/attendance_logs pages]
`

---

## 7. Technical Notes
- config/database.php bootstraps the database and tables.
- ackend/auth.php enforces login and department-level access.
- scanner/process_scan.php handles real-time scan validation and duplicate prevention.
- iews/attendance/attendance_logs_ajax.php provides JSON data for live attendance tables.

---

## 8. Useful File References
- config/database.php
- ackend/auth.php
- ackend/admin/manage_admin_actions.php
- ackend/admin/student_actions.php
- iews/layout.php
- scanner/scan.php
- scanner/process_scan.php
- iews/attendance/attendance_sessions.php
- iews/attendance/attendance_logs_ajax.php
