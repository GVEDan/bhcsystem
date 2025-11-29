# CLINICare - Barangay Health Center Management System

A comprehensive web-based health management system designed for barangay health centers to manage patient records, appointments, consultations, and medical services.

---

## 📋 Table of Contents

- [System Overview](#system-overview)
- [Features](#features)
- [Installation & Setup](#installation--setup)
- [User Roles & Access](#user-roles--access)
- [How to Use](#how-to-use)
  - [Patient Guide](#patient-guide)
  - [Admin/Doctor Guide](#admindoctor-guide)
- [File Structure](#file-structure)
- [Database Information](#database-information)
- [Demo Accounts](#demo-accounts)
- [Contact & Support](#contact--support)

---

## 🏥 System Overview

CLINICare is a modern barangay health center management system built with PHP, MySQL, and Bootstrap. It streamlines healthcare delivery by providing:

- **Patient Management** - Register, login, and manage health records
- **Appointment Scheduling** - Book and manage doctor appointments
- **Medical Records** - Create, store, and retrieve patient medical records
- **Medicine Requests** - Patients can request medicines
- **Consultation Requests** - Patients can request consultations with doctors
- **Doctor Availability** - Manage doctor schedules and availability
- **Email Notifications** - Automated emails for appointments and records

---

## ✨ Features

### For Patients

✅ **User Registration & Authentication**
- Create account with username, email, and password
- Secure login system with role-based access
- Password reset functionality

✅ **Medical Records**
- View all medical records created by doctors
- Access detailed record information
- Track medical history
- Receive email notifications for new records

✅ **Appointment Management**
- Request appointments with doctors
- View appointment schedules
- Check doctor availability by date
- Schedule follow-up appointments

✅ **Service Requests**
- Request medicines from the clinic
- Request consultations with specific doctors
- Track request status (pending, approved, completed)

✅ **Profile Management**
- Update personal information
- Change password
- View account details

### For Admin/Doctors

✅ **Dashboard**
- View system statistics
- Access administrative tools
- Manage requests and records

✅ **Patient Management**
- View all registered patients
- Access patient medical histories
- Manage patient records

✅ **Medical Records**
- Create medical records for patients
- Include diagnosis, symptoms, treatment, prescriptions
- Schedule follow-up appointments
- Send email notifications to patients

✅ **Appointment Management**
- View and manage patient appointments
- Update appointment status
- Track consultation requests

✅ **Doctor Management**
- Manage doctor profiles and specializations
- Set doctor availability schedules
- View doctor performance

✅ **Request Management**
- Review medicine requests
- Review consultation requests
- Approve or reject requests
- Add admin notes

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP or similar local server environment
- PHP 7.4+ 
- MySQL 5.7+
- Modern web browser

### Step 1: Install XAMPP
1. Download XAMPP from https://www.apachefriends.org/
2. Install and run XAMPP
3. Start Apache and MySQL services

### Step 2: Set Up the Project
1. Navigate to `C:\xampp\htdocs\`
2. Create a folder named `bhcsystem` (or clone the project)
3. Place all project files in this folder

### Step 3: Access the Application
1. Open your browser
2. Navigate to: `http://localhost/bhcsystem/`
3. The system will automatically create the database and tables on first load

### Step 4: Configure Email (Optional)
To enable email notifications:
1. Edit `includes/email_config.php`
2. Add your email credentials:
   ```php
   define('EMAIL_CONFIG', [
       'smtp_host' => 'smtp.gmail.com',
       'smtp_port' => 587,
       'username' => 'your-email@gmail.com',
       'password' => 'your-app-password',
       'sender_email' => 'your-email@gmail.com',
       'sender_name' => 'CLINICare',
       'admin_email' => 'admin@clincare.com'
   ]);
   ```

---

## 👥 User Roles & Access

### Patient Role
- Access patient dashboard
- View and manage personal medical records
- Request appointments and consultations
- Request medicines
- Update personal profile
- **Cannot**: Create records, manage other patients, access admin panel

### Admin/Doctor Role
- Access admin dashboard
- Create and manage medical records
- Manage patient accounts
- Set availability schedules
- Process service requests (medicine, consultation)
- View reports and analytics
- **Cannot**: Access as patient (different login)

---

## 📖 How to Use

### Patient Guide

#### 1. Registration
1. Click "Sign Up" on the homepage
2. Fill in your information:
   - Full Name
   - Username
   - Email
   - Contact Number
   - Password
   - Date of Birth (optional)
   - Gender (optional)
   - Address (optional)
3. Accept terms and conditions
4. Click "Create Account"
5. You'll receive a confirmation email with your login credentials

#### 2. Login
1. Click "Login" on the homepage
2. Select "Patient" tab
3. Enter your username/email and password
4. Click "Login to CLINICare"
5. You'll be directed to the homepage where you can:
   - View upcoming appointments
   - Request new appointments
   - Request medicines
   - Request consultations
   - View medical records

#### 3. Viewing Medical Records
1. Click "Medical Records" in the navigation menu
2. See all your medical records with:
   - Record date and time
   - Doctor name and specialization
   - Diagnosis information
   - Preview of symptoms
3. Click "View Full Record" to see:
   - Complete diagnosis
   - Symptoms description
   - Treatment prescribed
   - Prescriptions
   - Doctor notes
   - Follow-up appointment info (if applicable)

#### 4. Requesting an Appointment
1. On the homepage, scroll to "Request Consultation"
2. Fill in the form:
   - Consultation type
   - Description of your concern
   - Preferred date
   - Preferred doctor (or "Any Available")
3. Click "Submit Consultation Request"
4. Wait for doctor to confirm your appointment
5. You'll receive an email notification

#### 5. Requesting a Medicine
1. On the homepage, scroll to "Request Medicine"
2. Fill in the form:
   - Medicine name
   - Quantity
   - Reason for request
3. Click "Submit Medicine Request"
4. Admin will review and approve/reject
5. You'll receive an email notification

#### 6. Managing Your Profile
1. Click "My Account" in the navigation
2. Update your personal information
3. Change your password
4. Click "Save Changes"

---

### Admin/Doctor Guide

#### 1. Login
1. Click "Login" on the homepage
2. Select "Staff" tab
3. Enter your email/username and password
4. Click "Login"
5. You'll access the admin dashboard

#### 2. Creating Medical Records
1. Go to "Admin" → "Records" → "Create Record"
2. Select the patient from the dropdown
3. Fill in medical information:
   - Diagnosis
   - Symptoms observed
   - Treatment provided
   - Prescriptions
   - Notes
4. (Optional) Schedule follow-up:
   - Check "Follow-up Checkup Required"
   - Set date and time
5. Click "Create Record"
6. Patient receives email notification automatically

#### 3. Viewing Patient Records
1. Go to "Admin" → "Patients"
2. Click on a patient name
3. View all their medical records in chronological order
4. Click record to view full details

#### 4. Managing Appointments
1. Go to "Admin" → "Dashboard"
2. View all pending appointments
3. Click to view appointment details
4. Confirm or cancel appointments
5. Patients receive email notifications

#### 5. Processing Service Requests
1. Go to "Admin" → "Requests" → "Medicine Requests" or "Consultation Requests"
2. Review pending requests
3. Click "Approve" or "Reject"
4. Add admin notes if needed
5. System notifies patient automatically

#### 6. Setting Doctor Availability
1. Go to "Admin" → "Settings" → "Manage Doctor Availability"
2. Select a doctor
3. Set availability for each day (Monday-Friday)
4. Set start and end times
5. Save changes
6. Patients can see available time slots when booking

#### 7. Viewing Reports
1. Go to "Admin" → "Settings" → "Reports"
2. View various health statistics
3. Export data as needed

---

## 📁 File Structure

```
bhcsystem/
├── index.php                          # Homepage
├── login.php                          # Login page
├── register.php                       # Registration page
├── about_us.php                       # About Us page
├── contact.php                        # Contact page
├── services.php                       # Services page
├── privacy_policy.php                 # Privacy policy
├── forgot_password.php                # Password reset
├── process_contact.php                # Contact form handler
│
├── admin/                             # Admin section
│   ├── dashboard.php                  # Admin dashboard
│   ├── debug.php                      # Debug tools
│   ├── messages.php                   # Message management
│   ├── logout.php                     # Admin logout
│   ├── get_doctor_schedule.php        # Get doctor schedule (API)
│   ├── records/
│   │   ├── create_record.php          # Create medical record
│   │   └── view_record.php            # View medical record
│   ├── requests/
│   │   ├── manage_medicine_requests.php
│   │   └── manage_consultation_requests.php
│   ├── settings/
│   │   ├── manage_doctor_availability.php
│   │   └── reports.php
│   ├── patients/
│   │   └── view_patient_records.php
│   └── export/
│       └── export_csv.php
│
├── patient/                           # Patient section
│   ├── profile.php                    # Patient profile
│   ├── medical_records.php            # View medical records
│   ├── view_record.php                # View single record
│   ├── request_consultation.php       # Request consultation
│   ├── request_medicine.php           # Request medicine
│   ├── view_appointment_record.php    # View appointment
│   ├── logout.php                     # Patient logout
│   └── medical_records.php            # Medical records list
│
├── includes/                          # PHP includes
│   ├── config.php                     # Database configuration
│   ├── auth.php                       # Authentication class
│   ├── functions.php                  # Helper functions
│   ├── email_config.php               # Email configuration
│   ├── EmailSender.php                # Email sending class
│   ├── ContactMessage.php             # Contact message class
│   ├── check_availability.php         # Availability checker
│   └── logout.php                     # Logout handler
│
├── assets/
│   ├── css/
│   │   └── style.css                  # Custom styles
│   └── img/
│       ├── service_1.jpg              # Gallery images
│       ├── service_2.jpg
│       ├── service_3.jpg
│       ├── service_4.jpg
│       └── service_5.jpg
│
├── bootstrap-5.3.8-dist/              # Bootstrap framework
├── fontawesome-free-7.1.0-web/        # Font Awesome icons
├── PHPMailer-6.9.2/                   # Email library
├── vendor/                            # Composer dependencies
├── BUG_REPORT.md                      # Quality assurance report
└── README.md                          # This file
```

---

## 🗄️ Database Information

### Database Name
`bhc_system`

### Main Tables

**users**
- Stores patient and admin accounts
- Fields: id, username, email, password, first_name, last_name, phone, dob, address, role, status

**appointments**
- Patient appointment records
- Fields: id, patient_id, doctor_id, appointment_date, appointment_time, reason, status, notes

**medical_records**
- Doctor-created medical records
- Fields: id, patient_id, appointment_id, diagnosis, symptoms, treatment, prescriptions, notes, followup_required, followup_date, followup_time

**doctors**
- Doctor profiles
- Fields: id, first_name, last_name, specialization, phone, email, bio, image, status

**doctor_availability**
- Doctor work schedules
- Fields: id, doctor_id, day_of_week, start_time, end_time, is_available

**medicine_requests**
- Patient medicine requests
- Fields: id, patient_id, medicine_name, quantity, reason, status, admin_notes, requested_at

**consultation_requests**
- Patient consultation requests
- Fields: id, patient_id, doctor_id, consultation_type, description, status, preferred_date, admin_notes

**contact_messages**
- Contact form submissions
- Fields: id, user_id, name, email, subject, message, reply, is_read, is_archived

---

## 🔐 Demo Accounts

### Patient Account
- **Username**: patient
- **Password**: patient123
- **Email**: patient@email.com

### Admin Account
- **Username**: admin
- **Password**: admin123
- **Email**: admin@email.com

⚠️ **Important**: Change these credentials immediately after setup for security.

---

## 🔧 Troubleshooting

### Database Connection Issues
- Ensure MySQL service is running
- Check `includes/config.php` for correct credentials
- Verify database user permissions

### Email Not Sending
- Check `includes/email_config.php` configuration
- Ensure SMTP credentials are correct
- Check firewall/antivirus settings
- Enable "Less secure app access" for Gmail

### Broken Links or 404 Errors
- Ensure all files are in correct directories
- Check file permissions
- Clear browser cache (Ctrl+F5)
- Verify relative paths in links

### Cannot Login
- Verify username/email and password
- Check user role (Patient vs Staff)
- Ensure user account status is "active"
- Clear browser cookies and try again

---

## 📧 Email Features

CLINICare sends automated emails for:

1. **Registration Confirmation** - Includes login credentials
2. **Medical Record Notification** - When doctor creates a record
3. **Password Reset** - Reset link sent to email
4. **Appointment Confirmation** - Appointment details
5. **Request Status Update** - Approval/rejection notifications

---

## 🔒 Security Features

✅ Password hashing with bcrypt
✅ Role-based access control (RBAC)
✅ SQL prepared statements (SQL injection prevention)
✅ XSS protection with htmlspecialchars()
✅ Session management
✅ Email verification
✅ CSRF token protection (in forms)

---

## 🎨 Technology Stack

- **Frontend**: HTML5, CSS3, Bootstrap 5, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Email**: PHPMailer 6.9.2
- **Icons**: Font Awesome 7.1.0
- **Framework**: Bootstrap 5.3.8

---

## 📞 Contact & Support

For issues or questions:

1. Check this README first
2. Review the BUG_REPORT.md for known issues
3. Check browser console for JavaScript errors
4. Review PHP error logs in XAMPP folder
5. Contact system administrator

---

## 📝 License

This project is for barangay health center use. All rights reserved.

---

## 🎯 Future Enhancements

Potential features for future versions:
- SMS notifications
- Mobile app
- Telemedicine consultation
- Integration with government health systems
- Analytics dashboard
- Appointment reminders
- Patient health tracking

---

## ✅ System Status

**Last Updated**: November 29, 2025  
**Version**: 1.0  
**Status**: ✅ Production Ready

All tests passed. System is ready for deployment.

---

**Thank you for using CLINICare!** 🏥

For the best experience, please ensure you're using a modern web browser (Chrome, Firefox, Safari, Edge).

#   b h c s y s t e m  
 