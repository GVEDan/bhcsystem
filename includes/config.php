<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bhc_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    $conn->select_db(DB_NAME);
} else {
    die("Error creating database: " . $conn->error);
}

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        dob DATE,
        address VARCHAR(255),
        role ENUM('admin', 'patient') DEFAULT 'patient',
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT,
        appointment_date DATE NOT NULL,
        appointment_time TIME NOT NULL,
        reason VARCHAR(255) NOT NULL,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS medical_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        appointment_id INT,
        diagnosis VARCHAR(255),
        symptoms TEXT,
        treatment VARCHAR(255),
        prescriptions TEXT,
        notes TEXT,
        vital_signs JSON,
        followup_required TINYINT DEFAULT 0,
        followup_date DATE,
        followup_time TIME,
        record_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE SET NULL
    )",
    
    "CREATE TABLE IF NOT EXISTS time_slots (
        id INT AUTO_INCREMENT PRIMARY KEY,
        day_of_week INT NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        max_appointments INT DEFAULT 5,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS doctors (
        id INT AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(100) NOT NULL,
        last_name VARCHAR(100) NOT NULL,
        specialization VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        email VARCHAR(100),
        bio TEXT,
        image VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS doctor_availability (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doctor_id INT NOT NULL,
        day_of_week INT NOT NULL COMMENT '0=Sunday, 1=Monday, ..., 6=Saturday',
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        is_available BOOLEAN DEFAULT TRUE,
        UNIQUE KEY unique_schedule (doctor_id, day_of_week),
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS medicine_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        medicine_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        admin_notes TEXT,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
    )",
    
    "CREATE TABLE IF NOT EXISTS consultation_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT,
        consultation_type VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
        preferred_date DATE,
        admin_notes TEXT,
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        approved_at TIMESTAMP NULL,
        FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
    )",

    "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    )",

    "CREATE TABLE IF NOT EXISTS contact_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL,
        subject VARCHAR(255),
        message TEXT NOT NULL,
        reply TEXT,
        is_read BOOLEAN DEFAULT FALSE,
        is_archived BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )"
];

foreach ($tables as $sql) {
    if ($conn->query($sql) !== TRUE) {
        die("Error creating table: " . $conn->error);
    }
}

// Add is_archived column to medicine_requests if it doesn't exist
$check_medicine_archived = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='medicine_requests' AND COLUMN_NAME='is_archived' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_medicine_archived);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE medicine_requests ADD COLUMN is_archived BOOLEAN DEFAULT FALSE AFTER approved_at";
    if ($conn->query($alter_sql) !== TRUE) {
        // Column might already exist, continue
    }
}

// Add is_archived column to consultation_requests if it doesn't exist
$check_consultation_archived = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='consultation_requests' AND COLUMN_NAME='is_archived' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_consultation_archived);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE consultation_requests ADD COLUMN is_archived BOOLEAN DEFAULT FALSE AFTER approved_at";
    if ($conn->query($alter_sql) !== TRUE) {
        // Column might already exist, continue
    }
}

// Add dob column to users if it doesn't exist
$check_dob = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='dob' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_dob);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE users ADD COLUMN dob DATE AFTER phone";
    if ($conn->query($alter_sql) !== TRUE) {
        // Column might already exist, continue
    }
}

// Add address column to users if it doesn't exist
$check_address = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='address' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_address);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE users ADD COLUMN address VARCHAR(255) AFTER dob";
    if ($conn->query($alter_sql) !== TRUE) {
        // Column might already exist, continue
    }
}

// Add missing columns to contact_messages if they don't exist
$check_is_read = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='is_read' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_is_read);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER message";
    if ($conn->query($alter_sql) !== TRUE) {
        error_log("Error adding is_read column: " . $conn->error);
    }
}

$check_reply = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='reply' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_reply);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN reply TEXT AFTER is_read";
    if ($conn->query($alter_sql) !== TRUE) {
        error_log("Error adding reply column: " . $conn->error);
    }
}

$check_is_archived = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='is_archived' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_is_archived);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN is_archived BOOLEAN DEFAULT FALSE AFTER reply";
    if ($conn->query($alter_sql) !== TRUE) {
        error_log("Error adding is_archived column: " . $conn->error);
    }
}

$check_updated_at = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='contact_messages' AND COLUMN_NAME='updated_at' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_updated_at);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE contact_messages ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
    if ($conn->query($alter_sql) !== TRUE) {
        error_log("Error adding updated_at column: " . $conn->error);
    }
}

// Add image column to doctors table if it doesn't exist
$check_image_column = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='doctors' AND COLUMN_NAME='image' AND TABLE_SCHEMA='" . DB_NAME . "'";
$result = $conn->query($check_image_column);
if ($result && $result->num_rows == 0) {
    $alter_sql = "ALTER TABLE doctors ADD COLUMN image VARCHAR(255) AFTER bio";
    if ($conn->query($alter_sql) !== TRUE) {
        // Column might already exist, continue
    }
}

// Insert default time slots if they don't exist
$check_slots = "SELECT COUNT(*) as count FROM time_slots";
$result = $conn->query($check_slots);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Monday to Friday, 8 AM to 5 PM with slots
    $slots = [
        [1, '08:00:00', '12:00:00', 10],
        [1, '13:00:00', '17:00:00', 10],
        [2, '08:00:00', '12:00:00', 10],
        [2, '13:00:00', '17:00:00', 10],
        [3, '08:00:00', '12:00:00', 10],
        [3, '13:00:00', '17:00:00', 10],
        [4, '08:00:00', '12:00:00', 10],
        [4, '13:00:00', '17:00:00', 10],
        [5, '08:00:00', '12:00:00', 10],
        [5, '13:00:00', '17:00:00', 10]
    ];
    
    foreach ($slots as $slot) {
        $sql = "INSERT INTO time_slots (day_of_week, start_time, end_time, max_appointments) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('issi', $slot[0], $slot[1], $slot[2], $slot[3]);
        $stmt->execute();
    }
}

// Insert default doctors if they don't exist
$check_doctors = "SELECT COUNT(*) as count FROM doctors";
$result = $conn->query($check_doctors);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $doctors = [
        ['Jesse', 'Eresmas', 'General Physician', '09123456789', 'jesse.eresmas@bhc.com', 'Experienced general physician with 15 years of practice'],
        ['Norleah', 'Disomimba', 'Family Medicine', '09123456790', 'norleah.disomimba@bhc.com', 'Specialized in family healthcare and preventive medicine'],
        ['Kier', 'Diocales', 'Internal Medicine', '09123456791', 'kier.diocales@bhc.com', 'Expert in internal medicine and chronic disease management'],
        ['Daniela', 'Dela Cruz', 'Pediatrics', '09123456792', 'daniela.delacruz@bhc.com', 'Pediatric specialist caring for children and adolescents'],
        ['Jhonas', 'Deocareza', 'Mental Health', '09123456793', 'jhonas.deocareza@bhc.com', 'Mental health professional and counselor']
    ];
    
    foreach ($doctors as $doctor) {
        $sql = "INSERT INTO doctors (first_name, last_name, specialization, phone, email, bio) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssss', $doctor[0], $doctor[1], $doctor[2], $doctor[3], $doctor[4], $doctor[5]);
        $stmt->execute();
    }
    
    // Add default availability for all doctors (Monday-Friday, 8 AM - 5 PM)
    $doctor_ids = range(1, 5); // Assuming 5 doctors are inserted
    foreach ($doctor_ids as $doctor_id) {
        for ($day = 1; $day <= 5; $day++) { // Monday to Friday (1-5)
            $sql = "INSERT IGNORE INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, '08:00:00', '17:00:00', TRUE)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $doctor_id, $day);
            $stmt->execute();
        }
    }
}


// Insert demo accounts if they don't exist
$check_demo = "SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'patient')";
$result = $conn->query($check_demo);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Create demo patient account
    $patient_username = 'patient';
    $patient_email = 'patient@email.com';
    $patient_password = password_hash('patient123', PASSWORD_DEFAULT);
    $patient_first = 'Juan';
    $patient_last = 'Dela Cruz';
    $patient_phone = '09123456789';
    
    $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'patient', 'active')";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ssssss', $patient_username, $patient_email, $patient_password, $patient_first, $patient_last, $patient_phone);
        $stmt->execute();
        $stmt->close();
    }
    
    // Create demo admin account
    $admin_username = 'admin';
    $admin_email = 'admin@email.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $admin_first = 'Dr. Maria';
    $admin_last = 'Santos';
    $admin_phone = '09198765432';
    
    $sql = "INSERT INTO users (username, email, password, first_name, last_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'admin', 'active')";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ssssss', $admin_username, $admin_email, $admin_password, $admin_first, $admin_last, $admin_phone);
        $stmt->execute();
        $stmt->close();
    }
}

// Migration: Add follow-up checkup columns to medical_records table if they don't exist
$check_followup_columns = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='medical_records' AND COLUMN_NAME='followup_required'";
$result = $conn->query($check_followup_columns);

if ($result && $result->num_rows == 0) {
    // Add followup columns if they don't exist
    $alter_table = "ALTER TABLE medical_records ADD COLUMN followup_required TINYINT DEFAULT 0 AFTER vital_signs, ADD COLUMN followup_date DATE AFTER followup_required, ADD COLUMN followup_time TIME AFTER followup_date";
    if ($conn->query($alter_table) === TRUE) {
        // Migration successful - columns added
    }
}

// Site configuration
define('SITE_NAME', 'Barangay Health Center');
define('SITE_URL', 'http://localhost/bhcsystem/');
define('ADMIN_ROLE', 'admin');
define('PATIENT_ROLE', 'patient');

// Email configuration
require_once __DIR__ . '/email_config.php';

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
