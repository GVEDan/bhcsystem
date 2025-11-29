<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = (int)$_POST['doctor_id'] ?? 0;
    $success_count = 0;
    $error_count = 0;
    
    if ($doctor_id) {
        // Handle Weekly Schedule (recurring)
        if (isset($_POST['save_weekly_schedule'])) {
            for ($day = 0; $day <= 6; $day++) {
                $start_time = $_POST['start_time'][$day] ?? '';
                $end_time = $_POST['end_time'][$day] ?? '';
                $is_available = isset($_POST['available'][$day]) ? 1 : 0;
                
                if ($start_time && $end_time) {
                    $sql = "INSERT INTO doctor_availability (doctor_id, day_of_week, start_time, end_time, is_available) 
                            VALUES (?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE start_time = ?, end_time = ?, is_available = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('iisssssi', $doctor_id, $day, $start_time, $end_time, $is_available, $start_time, $end_time, $is_available);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $error_count++;
                    }
                }
            }
            
            if ($success_count > 0 && $error_count == 0) {
                $message = "Weekly schedule saved successfully! ($success_count days updated)";
                $message_type = 'success';
            } elseif ($success_count > 0) {
                $message = "Partial save: $success_count days updated, $error_count failed";
                $message_type = 'warning';
            } else {
                $message = 'Error saving schedule: ' . $conn->error;
                $message_type = 'danger';
            }
        }
        
        // Handle Specific Date Schedule
        if (isset($_POST['save_date_schedule'])) {
            $specific_date = $_POST['specific_date'] ?? '';
            $date_start_time = $_POST['date_start_time'] ?? '';
            $date_end_time = $_POST['date_end_time'] ?? '';
            $date_is_available = isset($_POST['date_available']) ? 1 : 0;
            
            if ($specific_date && $date_start_time && $date_end_time) {
                // Check if table exists for specific date availability
                $table_check = "CREATE TABLE IF NOT EXISTS doctor_date_availability (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    doctor_id INT NOT NULL,
                    availability_date DATE NOT NULL,
                    start_time TIME NOT NULL,
                    end_time TIME NOT NULL,
                    is_available BOOLEAN DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_doctor_date (doctor_id, availability_date)
                )";
                $conn->query($table_check);
                
                $sql = "INSERT INTO doctor_date_availability (doctor_id, availability_date, start_time, end_time, is_available) 
                        VALUES (?, ?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE start_time = ?, end_time = ?, is_available = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('isssssi', $doctor_id, $specific_date, $date_start_time, $date_end_time, $date_is_available, $date_start_time, $date_end_time, $date_is_available);
                
                if ($stmt->execute()) {
                    $message = "Schedule for " . date('M d, Y', strtotime($specific_date)) . " saved successfully!";
                    $message_type = 'success';
                } else {
                    $message = 'Error saving date schedule: ' . $conn->error;
                    $message_type = 'danger';
                }
            }
        }
    }
}

// Get all doctors with their availability
$doctors_query = "SELECT d.id, d.first_name, d.last_name, d.specialization FROM doctors d ORDER BY d.first_name";
$doctors_result = $conn->query($doctors_query);
$all_doctors = [];
while ($doc = $doctors_result->fetch_assoc()) {
    $all_doctors[] = $doc;
}

// Get availability for selected doctor if editing
$selected_doctor_id = $_GET['doctor_id'] ?? 0;
$doctor_availability = [];
$date_availability = [];
if ($selected_doctor_id) {
    $doctor_availability = Doctor::getDoctorAvailability($selected_doctor_id);
    
    // Get specific date availability if table exists
    $table_check = "SHOW TABLES LIKE 'doctor_date_availability'";
    $result = $conn->query($table_check);
    if ($result && $result->num_rows > 0) {
        $sql = "SELECT availability_date, start_time, end_time, is_available FROM doctor_date_availability 
                WHERE doctor_id = ? AND availability_date >= CURDATE()
                ORDER BY availability_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $selected_doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $date_availability[] = $row;
        }
    }
}

$days = [0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Availability - CLINICare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        :root {
            --primary: #dc3545;
            --secondary: #c82333;
        }
        body {
            background: #f5f7fa;
        }
        .navbar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 20px;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .navbar-nav .nav-link:hover {
            color: white !important;
        }
        .logout-link {
            background: transparent !important;
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 8px 12px !important;
            margin: 0 !important;
            border: none !important;
            transition: all 0.3s ease;
        }
        .logout-link:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            transform: translateY(-2px);
        }
        .sidebar {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .nav-link {
            color: #555;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .nav-link:hover, .nav-link.active {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
            padding: 20px;
            font-weight: 600;
        }
        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .schedule-day-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .schedule-day-card:hover {
            border-color: #dc3545;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.1);
        }
        .day-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
        }
        .day-body {
            padding: 15px;
            background: #f9f9f9;
        }
        .form-control-lg {
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php">
                <i class="fas fa-heartbeat"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../../index.php"><i class="fas fa-arrow-left"></i> Back to Homepage</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 col-md-4">
                <div class="sidebar">
                    <div style="margin-bottom: 20px;">
                        <strong style="color: #dc3545;">MENU</strong>
                    </div>
                    <a href="../dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a href="../requests/manage_medicine_requests.php" class="nav-link"><i class="fas fa-pills"></i> Medicine Requests</a>
                    <a href="../requests/manage_consultation_requests.php" class="nav-link"><i class="fas fa-stethoscope"></i> Consultation Requests</a>
                    <a href="reports.php" class="nav-link"><i class="fas fa-chart-bar"></i> Reports & Export</a>
                    <a href="manage_doctor_availability.php" class="nav-link active"><i class="fas fa-calendar"></i> Doctor Availability</a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-check"></i> Doctor Availability Schedule</h2>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Doctor Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-md"></i> Select Doctor</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-10">
                                <label for="doctor_select" class="form-label">Choose a Doctor to Edit Schedule</label>
                                <select class="form-select form-select-lg" id="doctor_select" name="doctor_id" onchange="this.form.submit()" required>
                                    <option value="">-- Select a Doctor --</option>
                                    <?php foreach ($all_doctors as $doctor): ?>
                                        <option value="<?php echo $doctor['id']; ?>" <?php echo ($doctor['id'] == $selected_doctor_id) ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_doctor_id): ?>
                    <!-- Get doctor info -->
                    <?php
                    $doctor_info = null;
                    foreach ($all_doctors as $doc) {
                        if ($doc['id'] == $selected_doctor_id) {
                            $doctor_info = $doc;
                            break;
                        }
                    }
                    if ($doctor_info):
                    ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-md"></i> Dr. <?php echo htmlspecialchars($doctor_info['first_name'] . ' ' . $doctor_info['last_name']); ?>
                                <span class="text-muted" style="font-size: 0.9rem; font-weight: 400;">- <?php echo htmlspecialchars($doctor_info['specialization']); ?></span>
                            </h5>
                        </div>
                    </div>

                    <!-- Weekly Schedule Configuration -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-calendar-week"></i> Weekly Schedule</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" id="weeklyScheduleForm">
                                <input type="hidden" name="doctor_id" value="<?php echo $selected_doctor_id; ?>">
                                <p class="text-muted mb-4"><i class="fas fa-info-circle"></i> Set the doctor's recurring weekly hours. Configure the start time, end time, and availability for each day.</p>
                                
                                <div class="schedule-grid">
                                    <?php for ($day = 0; $day <= 6; $day++): 
                                        $daySchedule = array_filter($doctor_availability, function($a) use ($day) {
                                            return $a['day_of_week'] == $day;
                                        });
                                        $daySchedule = reset($daySchedule);
                                    ?>
                                        <div class="schedule-day-card">
                                            <div class="day-header">
                                                <h6 class="mb-0"><?php echo $days[$day]; ?></h6>
                                            </div>
                                            <div class="day-body">
                                                <div class="mb-3">
                                                    <label class="form-label small"><i class="fas fa-play-circle" style="color: #28a745;"></i> Start Time</label>
                                                    <input type="time" class="form-control form-control-sm" name="start_time[<?php echo $day; ?>]" 
                                                        value="<?php echo ($daySchedule ? substr($daySchedule['start_time'], 0, 5) : '08:00'); ?>">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small"><i class="fas fa-stop-circle" style="color: #dc3545;"></i> End Time</label>
                                                    <input type="time" class="form-control form-control-sm" name="end_time[<?php echo $day; ?>]" 
                                                        value="<?php echo ($daySchedule ? substr($daySchedule['end_time'], 0, 5) : '17:00'); ?>">
                                                </div>
                                                <div class="form-check mt-2">
                                                    <input class="form-check-input" type="checkbox" name="available[<?php echo $day; ?>]" 
                                                        <?php echo ($daySchedule && $daySchedule['is_available'] ? 'checked' : ''); ?> 
                                                        id="available<?php echo $day; ?>">
                                                    <label class="form-check-label small" for="available<?php echo $day; ?>">
                                                        <i class="fas fa-check" style="color: #28a745;"></i> Available
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="mt-4 pt-3 border-top">
                                    <button type="submit" class="btn btn-primary btn-lg" name="save_weekly_schedule">
                                        <i class="fas fa-save"></i> Save Weekly Schedule
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
