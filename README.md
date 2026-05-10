# SECURED-RFID-VERIFACTION-SYSTEM

A complete RFID Student Registration, Login, and Admin Management System built with PHP, MySQL, and Bootstrap 5.

## Features

- **Admin Authentication**: Secure login with password hashing and session management
- **Dashboard**: Overview of total students and RFID cards with recent registrations
- **Student Management**: Complete CRUD operations for student records
- **RFID Integration**: Simulated RFID scanner input via keyboard
- **File Upload**: Student photo upload with validation
- **Search & Pagination**: Search students and paginated results
- **Activity Logging**: Track admin activities
- **Responsive UI**: Modern Bootstrap 5 interface

## Tech Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, HTML5, CSS3, JavaScript
- **Server**: XAMPP (Apache, MySQL, PHP)

## Installation

1. **Clone or Download** the project to your XAMPP `htdocs` directory:
   ```
   cd /path/to/xampp/htdocs
   git clone https://github.com/your-repo/rfid-system.git
   ```

2. **Database Setup**:
   - Start XAMPP and ensure MySQL is running
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `rfid_system`
   - Import the `schema.sql` file from the project root

3. **Configuration**:
   - Update database credentials in `config/database.php` if needed
   - Default credentials: host=localhost, username=root, password=(empty)

4. **Permissions**:
   - Ensure the `uploads/` directory is writable by the web server
   - On Linux/Mac: `chmod 755 uploads/`
   - On Windows: Make sure the web server has write permissions

5. **Access the Application**:
   - Open your browser and go to: `http://localhost/rfid-system/`
   - Login with default admin credentials:
     - Username: `admin`
     - Password: `admin123`

## Project Structure

```
rfid-system/
├── assets/              # Static assets (images, etc.)
├── config/
│   └── database.php     # Database configuration
├── css/
│   └── style.css        # Custom styles
├── includes/
│   ├── header.php       # Common header with navigation
│   └── footer.php       # Common footer
├── js/
│   └── script.js        # Custom JavaScript
├── pages/               # Main application pages
│   ├── dashboard.php
│   ├── students.php
│   ├── register_student.php
│   ├── edit_student.php
│   ├── delete_student.php
│   └── profile.php
├── uploads/             # Student photo uploads
├── index.php           # Entry point
├── login.php           # Admin login
├── logout.php          # Admin logout
├── schema.sql          # Database schema
└── README.md
```

## Database Schema

### Tables

- **admins**: Admin user accounts
- **students**: Student information and RFID data
- **activity_logs**: Admin activity tracking

### Default Admin Account

- Username: `admin`
- Password: `admin123`
- Password is hashed using `password_hash()`

## Usage

### Admin Login
- Access the login page at the root URL
- Enter admin credentials
- Use "Remember me" for persistent sessions

### Dashboard
- View total students and RFID cards
- See recently registered students
- Navigate to different sections

### Student Management
- **Register**: Add new students with RFID scanning
- **View**: List all students with search and pagination
- **Edit**: Update student information
- **Delete**: Remove students with confirmation

### RFID Integration
- Focus on the RFID UID field
- Type the RFID code using keyboard (simulates scanner)
- The system automatically captures and fills the input

### File Upload
- Upload student photos (JPG, PNG, GIF)
- Maximum file size: 5MB
- Files are stored in the `uploads/` directory

## Security Features

- **Password Hashing**: Uses `password_hash()` and `password_verify()`
- **Prepared Statements**: All database queries use PDO prepared statements
- **Session Management**: Secure session handling with timeout
- **Input Validation**: Server-side validation for all inputs
- **File Upload Security**: Type and size validation for uploads
- **CSRF Protection**: Form tokens (can be added for enhancement)

## API Endpoints

The system uses traditional PHP pages rather than REST APIs. Main pages:

- `login.php` - Admin authentication
- `pages/dashboard.php` - Main dashboard
- `pages/students.php` - Student listing
- `pages/register_student.php` - Student registration
- `pages/edit_student.php` - Student editing
- `pages/delete_student.php` - Student deletion
- `pages/profile.php` - Admin profile management

## Development

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server
- XAMPP (recommended for development)

### Code Standards
- Uses PDO for database operations
- Prepared statements for all queries
- Input sanitization and validation
- Clean, readable code with comments
- Bootstrap 5 for responsive UI

### Adding New Features
1. Create new PHP files in the `pages/` directory
2. Include `header.php` and `footer.php` for consistent UI
3. Add navigation links in `includes/header.php`
4. Follow the existing code patterns and security practices

## Troubleshooting

### Common Issues

1. **Database Connection Error**:
   - Check MySQL is running in XAMPP
   - Verify credentials in `config/database.php`
   - Ensure database `rfid_system` exists

2. **File Upload Issues**:
   - Check `uploads/` directory permissions
   - Verify file size limits in PHP configuration
   - Check allowed file types

3. **Session Issues**:
   - Clear browser cookies
   - Check PHP session configuration
   - Ensure session save path is writable

4. **RFID Input Not Working**:
   - Ensure JavaScript is enabled
   - Check browser console for errors
   - Try refreshing the page

### Logs
- Check Apache error logs in XAMPP
- PHP errors are displayed if `display_errors` is enabled
- Database errors are shown on connection failure

## License

This project is open source. Feel free to modify and distribute.

## Support

For issues or questions, please check the code comments or create an issue in the repository.