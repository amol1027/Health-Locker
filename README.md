# Health Locker 🏥

![Health Locker Banner](https://via.placeholder.com/1200x400/0ea5e9/ffffff?text=Health+Locker)  
*A secure digital vault for your family's medical records powered by AI*

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-777BB4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1.svg)](https://www.mysql.com/)
[![Admin Dashboard](https://img.shields.io/badge/Admin-Dashboard-success.svg)](admin/)

## 📋 Table of Contents
- [Key Features](#-key-features)
- [Admin Dashboard](#-admin-dashboard-new)
- [Tech Stack](#-tech-stack)
- [Installation](#-installation)
- [Usage](#-usage)
- [Admin Panel Access](#-admin-panel-access)
- [Project Structure](#-project-structure)
- [Security](#-security)
- [Contributing](#-contributing)

## ✨ Key Features

### 🔐 Secure Health Management
- **Military-Grade Encryption**: All records encrypted with AES-256 at rest and in transit
- **HIPAA-Compliant Storage**: Secure cloud storage with automatic backups
- **Zero-Knowledge Architecture**: Even we can't access your unencrypted data

### 👨‍👩‍👧‍👦 Family Health Hub
- **Unlimited Family Profiles**: Add parents, children, and dependents
- **Granular Permissions**: Control who sees what (e.g., hide sensitive records from kids)
- **Emergency Access**: Designate trusted contacts for emergency situations

### 🤖 AI-Powered Insights
- **Report Simplification**: Transforms complex medical jargon into plain English (English, Hindi, Marathi)
- **Trend Analysis**: Visualizes health metrics over time (blood pressure, cholesterol, etc.)
- **Smart Alerts**: Flags abnormal results and suggests next steps

### 🚀 Productivity Boosters
- **Auto-Expiring Shares**: Time-limited access links for doctors
- **OCR Processing**: Extracts text from scanned documents and handwritten notes
- **Medication Tracker**: With dosage reminders and interaction warnings

## 🎛️ Admin Dashboard **NEW**

### Comprehensive Administrative Panel
Full-featured admin dashboard for system management with:

#### 📊 Dashboard Overview
- Real-time statistics (users, family members, records, reminders)
- Recent activity monitoring
- Quick access navigation

#### 👥 User Management
- View all registered users
- Search by name or email
- User statistics (family members, records count)
- Delete users with cascading data removal

#### 👨‍👩‍👧 Family Member Management
- Grid view of all family profiles
- Search and filter capabilities
- View health information (blood type, allergies, DOB)
- Medical records count per member

#### 📄 Medical Records Management
- View all uploaded documents
- Filter by record type (Prescription, Lab Report, Scan, etc.)
- Multi-field search (patient, doctor, hospital)
- File management and deletion

#### 🔔 Reminders Management
- View all scheduled reminders
- Filter by status (Pending, Sent, All)
- Monitor overdue reminders
- Bulk management capabilities

#### 🔐 Admin Features
- Secure authentication system
- Session-based access control
- Password hashing with bcrypt
- Activity tracking (last login)

**Access:** `http://localhost/healt-checker/admin/login.php`  
**Documentation:** See [ADMIN_SETUP_GUIDE.md](ADMIN_SETUP_GUIDE.md)

## 🛠 Tech Stack

### Frontend
| Technology       | Purpose                          |
|------------------|----------------------------------|
| Tailwind CSS 3.3 | Modern utility-first CSS framework |
| Alpine.js        | Lightweight reactivity           |
| FilePond         | Smooth file uploads with preview |
| Chart.js         | Health metric visualizations     |

### Backend
| Technology       | Purpose                          |
|------------------|----------------------------------|
| PHP 8.1+         | Core application logic           |
| Laravel Sanctum  | API authentication               |
| Intervention Image| Image processing library        |
| TCPDF            | PDF generation and processing    |

### AI Services
| Service          | Usage                            |
|------------------|----------------------------------|
| OpenAI API       | Medical report simplification    |
| Google Cloud Vision | OCR and document analysis     |

### Infrastructure
| Component        | Specification                    |
|------------------|----------------------------------|
| Database         | MySQL 8.0 (InnoDB cluster)       |
| Storage          | S3-compatible encrypted storage  |
| Server           | Ubuntu 22.04 LTS (4vCPU/8GB RAM) |

## 🚀 Installation

### Prerequisites
- PHP 8.1 or higher
- MySQL 8.0
- Composer
- XAMPP/WAMP/LAMP (or similar local server)

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/healt-checker.git
cd healt-checker
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Database Setup
1. Create database `health_sys` in phpMyAdmin
2. Import the SQL file:
   ```sql
   -- In phpMyAdmin, import: database/health_sys.sql
   ```

### Step 4: Configure Database
Edit `config/config.php`:
```php
$host = 'localhost:3307';
$dbname = 'health_sys';
$username = 'root';
$password = '';
```

### Step 5: Setup Admin Account (Optional)
Run in phpMyAdmin:
```sql
INSERT INTO `admins` (`username`, `email`, `password`, `full_name`) 
VALUES ('admin', 'admin@healthlocker.com', 
'$2y$10$Kd7p5YvJVU7SfzqNYW5EYemN7K3rQ.9rLp8YkZU/5fhLPxL9H4GiW', 
'System Administrator');
```
**Default Admin Login:** Username: `admin`, Password: `Admin@123`

### Step 6: Configure API Keys
Update in `config/config.php`:
```php
define('GEMINI_API_KEY', 'your_gemini_api_key');
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
```

### Step 7: Access the Application
- **User Portal:** `http://localhost/healt-checker/`
- **Admin Panel:** `http://localhost/healt-checker/admin/login.php`

## 📖 Usage

### For Users
1. **Register:** Create an account at `/user/register.php`
2. **Login:** Access your dashboard at `/user/login.php`
3. **Add Family Members:** Create profiles for family members
4. **Upload Records:** Upload medical documents (PDF, JPG, PNG)
5. **Set Reminders:** Schedule medication/appointment reminders
6. **Simplify Reports:** Use AI to understand medical reports in your language

### For Administrators
1. **Login:** Access admin panel at `/admin/login.php`
2. **Monitor:** View system statistics and recent activity
3. **Manage:** Control users, records, and reminders
4. **Moderate:** Delete inappropriate or outdated content

## 🔧 Admin Panel Access

### Quick Start
1. Navigate to `http://localhost/healt-checker/admin/login.php`
2. Use default credentials (change immediately after first login):
   - Username: `admin`
   - Password: `Admin@123`
3. Explore the dashboard features

### Creating New Admin Accounts
```bash
# Generate password hash
php -r "echo password_hash('YourPassword', PASSWORD_DEFAULT);"
```

Then insert into database:
```sql
INSERT INTO admins (username, email, password, full_name) 
VALUES ('newadmin', 'admin@example.com', 'PASTE_HASH_HERE', 'Admin Name');
```

For detailed admin documentation, see [admin/README.md](admin/README.md)

## 📂 Project Structure

```
healt-checker/
├── admin/                      # Admin dashboard (NEW)
│   ├── login.php               # Admin authentication
│   ├── dashboard.php           # Main admin dashboard
│   ├── users.php               # User management
│   ├── family_members.php      # Family profiles management
│   ├── medical_records.php     # Records management
│   ├── reminders.php           # Reminders management
│   ├── logout.php              # Logout handler
│   └── README.md               # Admin documentation
├── config/
│   └── config.php              # Database & API configuration
├── database/
│   └── health_sys.sql          # Database schema
├── frontend/
│   ├── dashboard.php           # User dashboard
│   ├── add_member.php          # Add family member
│   ├── view_records.php        # View medical records
│   ├── upload_record.php       # Upload documents
│   ├── simplify_report.php     # AI report simplification
│   └── delete_*.php            # Deletion handlers
├── remainders/
│   ├── add_reminder.php        # Create reminders
│   ├── check_reminders.php     # Reminder checker service
│   └── email_template.php      # Email templates
├── user/
│   ├── login.php               # User login
│   ├── register.php            # User registration
│   └── logout.php              # User logout
├── uploads/
│   └── health_records/         # Uploaded medical files
├── index.php                   # Landing page
├── composer.json               # PHP dependencies
├── README.md                   # This file
└── ADMIN_SETUP_GUIDE.md        # Admin setup instructions
```

## 🔒 Security

### Implemented Security Measures
- **Password Hashing:** Using PHP's `password_hash()` with bcrypt algorithm
- **SQL Injection Prevention:** PDO prepared statements throughout
- **XSS Protection:** Output sanitization with `htmlspecialchars()`
- **Session Management:** Secure session handling for both users and admins
- **File Upload Validation:** Type and size restrictions on uploads
- **CSRF Protection:** Form validation and session verification
- **Access Control:** Route protection for authenticated pages

### Database Security
- Foreign key constraints for data integrity
- Cascading deletes to prevent orphaned records
- Indexed columns for optimized queries
- Separate admin authentication table

### Best Practices
- Change default admin credentials immediately
- Use strong passwords (minimum 8 characters)
- Keep API keys secure and out of version control
- Regular database backups
- Monitor admin access logs

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test all features before submitting PR
- Update documentation for new features

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Authors

- **Amol Basavraj Solase** - *Initial work*

## 🙏 Acknowledgments

- OpenAI/Google Gemini for AI-powered report simplification
- Tailwind CSS for the beautiful UI framework
- Font Awesome for icons
- All contributors and testers

## 📞 Support

For support, email support@healthlocker.com or open an issue on GitHub.

## 🗺️ Roadmap

- [x] User authentication and registration
- [x] Family member management
- [x] Medical records upload and storage
- [x] AI-powered report simplification
- [x] Reminder system with email notifications
- [x] Admin dashboard with full management capabilities
- [ ] Mobile application (iOS/Android)
- [ ] Two-factor authentication
- [ ] Advanced analytics and reporting
- [ ] Multi-language support expansion
- [ ] Integration with hospital systems
- [ ] Telemedicine features

---

**Made with ❤️ for better health management**

