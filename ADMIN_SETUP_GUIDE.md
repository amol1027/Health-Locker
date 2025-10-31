# 🚀 Admin Dashboard Quick Setup Guide

## Step-by-Step Installation

### Step 1: Update Database
1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Select your `health_sys` database
3. Click **Import** tab
4. Choose file: `database/health_sys.sql`
5. Click **Go** to execute

This will create the `admins` table.

### Step 2: Create Default Admin Account
Run this SQL query in phpMyAdmin:

```sql
INSERT INTO `admins` (`username`, `email`, `password`, `full_name`) 
VALUES ('admin', 'admin@healthlocker.com', '$2y$10$Kd7p5YvJVU7SfzqNYW5EYemN7K3rQ.9rLp8YkZU/5fhLPxL9H4GiW', 'System Administrator');
```

**Default Credentials:**
- **Username:** `admin`
- **Password:** `Admin@123`

### Step 3: Access Admin Panel
Open your browser and navigate to:
```
http://localhost/healt-checker/admin/login.php
```

### Step 4: Login
Use the default credentials to login:
- Username: `admin`
- Password: `Admin@123`

---

## 📋 Admin Panel Features

### ✅ What You Can Do:

1. **Dashboard**
   - View total users, family members, records, and reminders
   - See recent activity
   - Quick statistics overview

2. **Users Management**
   - View all registered users
   - Search users by name/email
   - Delete users (removes all their data)

3. **Family Members**
   - View all family profiles
   - See blood type, allergies, DOB
   - Delete members

4. **Medical Records**
   - View all uploaded documents
   - Filter by type (Prescription, Lab Report, etc.)
   - Search by patient, doctor, hospital
   - Delete records

5. **Reminders**
   - View all scheduled reminders
   - Filter by status (Pending/Sent)
   - Delete reminders

---

## 🔐 Security Notice

⚠️ **IMPORTANT:** Change the default password immediately after first login!

To create a new admin or change password:
```sql
-- Generate password hash in PHP:
-- php -r "echo password_hash('YourNewPassword', PASSWORD_DEFAULT);"

-- Then insert/update in database:
INSERT INTO admins (username, email, password, full_name) 
VALUES ('yourusername', 'your@email.com', 'PASTE_HASH_HERE', 'Your Name');
```

---

## 📂 File Structure

```
admin/
├── login.php              ← Admin login page
├── dashboard.php          ← Main dashboard
├── users.php              ← User management
├── family_members.php     ← Family profiles
├── medical_records.php    ← Medical records
├── reminders.php          ← Reminders
├── logout.php             ← Logout handler
├── create_admin.sql       ← SQL helper file
└── README.md              ← Detailed documentation
```

---

## ❓ Troubleshooting

**Can't login?**
- Verify admin account exists in `admins` table
- Check username and password spelling
- Make sure database is connected (check `config/config.php`)

**Statistics showing 0?**
- Register some users through the main site
- Add family members and upload records
- Data will appear automatically

**Delete not working?**
- Check file permissions on `uploads` folder
- Verify foreign key constraints in database

---

## 🎯 Next Steps

1. ✅ Login to admin panel
2. ✅ Change default password
3. ✅ Explore the dashboard
4. ✅ Test user management features
5. ✅ Familiarize yourself with all sections

---

For detailed documentation, see: `admin/README.md`

**Happy Managing! 🎉**
