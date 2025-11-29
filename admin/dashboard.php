<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();
$appointments = Appointment::getAllAppointments();
$error = '';
$success = '';

// Get stats
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'patient'")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'];
$total_records = $conn->query("SELECT COUNT(*) as count FROM medical_records")->fetch_assoc()['count'];

// Get detailed appointment statistics
$appointment_stats = [
    'pending' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'")->fetch_assoc()['count'],
    'confirmed' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'confirmed'")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'completed'")->fetch_assoc()['count'],
    'cancelled' => $conn->query("SELECT COUNT(*) as count FROM appointments WHERE status = 'cancelled'")->fetch_assoc()['count']
];

// Get monthly user statistics (last 6 months)
$monthly_users = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'patient' AND DATE_FORMAT(created_at, '%Y-%m') = '$month'")->fetch_assoc()['count'];
    $monthly_users[] = (int)$count;
}

// Get monthly medical records (last 6 months)
$monthly_records = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $count = $conn->query("SELECT COUNT(*) as count FROM medical_records WHERE DATE_FORMAT(record_date, '%Y-%m') = '$month'")->fetch_assoc()['count'];
    $monthly_records[] = (int)$count;
}

// Get request stats
$medicine_stats = MedicineRequest::getStatistics();
$consultation_stats = ConsultationRequest::getStatistics();
$pending_medicine_requests = $medicine_stats['pending'] ?? 0;
$pending_consultation_requests = $consultation_stats['pending'] ?? 0;

// Handle appointment status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $appt_id = (int)$_POST['appointment_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];
    
    if (Appointment::updateAppointmentStatus($appt_id, $status)) {
        $sql = "UPDATE appointments SET notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $notes, $appt_id);
        $stmt->execute();
        
        $success = 'Appointment updated successfully';
        $appointments = Appointment::getAllAppointments();
    } else {
        $error = 'Failed to update appointment';
    }
}

// Handle doctor availability update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_availability'])) {
    $availability_id = (int)$_POST['availability_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $is_available = (int)$_POST['is_available'];
    
    $sql = "UPDATE doctor_availability SET start_time = ?, end_time = ?, is_available = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssii', $start_time, $end_time, $is_available, $availability_id);
    
    if ($stmt->execute()) {
        $success = 'Doctor availability updated successfully';
    } else {
        $error = 'Failed to update doctor availability';
    }
}

// Handle medicine request status update (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_medicine_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if (MedicineRequest::updateStatus($request_id, $status, $admin_notes)) {
        echo json_encode(['success' => true, 'message' => 'Medicine request updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update medicine request']);
    }
    exit;
}

// Handle consultation request status update (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_consultation_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    $admin_notes = $_POST['admin_notes'] ?? '';
    
    if (ConsultationRequest::updateStatus($request_id, $status, $admin_notes)) {
        echo json_encode(['success' => true, 'message' => 'Consultation request updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update consultation request']);
    }
    exit;
}

// Handle archive approved request (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_request'])) {
    $request_id = (int)$_POST['request_id'];
    $request_type = $_POST['request_type'];
    
    try {
        if ($request_type === 'Medicine') {
            $result = MedicineRequest::archive($request_id);
        } else {
            $result = ConsultationRequest::archive($request_id);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => $request_type . ' request archived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to archive ' . strtolower($request_type) . ' request']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle restore archived request (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restore_request'])) {
    $request_id = (int)$_POST['request_id'];
    $request_type = $_POST['request_type'];
    
    try {
        if ($request_type === 'Medicine') {
            $result = MedicineRequest::restore($request_id);
        } else {
            $result = ConsultationRequest::restore($request_id);
        }
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => $request_type . ' request restored successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to restore ' . strtolower($request_type) . ' request']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle appointment deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $appt_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM appointments WHERE id = ?");
    $stmt->bind_param('i', $appt_id);
    if ($stmt->execute()) {
        $success = 'Appointment deleted successfully';
        $appointments = Appointment::getAllAppointments();
    } else {
        $error = 'Failed to delete appointment';
    }
}

// Handle message operations
require_once '../includes/ContactMessage.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mark message as read
    if (isset($_POST['mark_as_read'])) {
        $msg_id = (int)$_POST['mark_as_read'];
        ContactMessage::markAsRead($msg_id);
        exit;
    }
    
    // Save reply
    if (isset($_POST['save_reply'])) {
        $msg_id = (int)$_POST['message_id'];
        $reply = $_POST['reply'] ?? '';
        ContactMessage::saveReply($msg_id, $reply);
        exit;
    }
    
    // Delete message
    if (isset($_POST['delete_message'])) {
        $msg_id = (int)$_POST['delete_message'];
        ContactMessage::delete($msg_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CLINICare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="../assets/css/style.css" rel="stylesheet">
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
        }
        .stats-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .stats-number {
            font-size: 32px;
            font-weight: 700;
            color: #dc3545;
            margin: 10px 0;
        }
        .stats-label {
            color: #666;
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
            color: white;
        }
        .table {
            background: white;
        }
        .appointment-row {
            border-left: 4px solid var(--primary);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .modal-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border: none;
        }
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .form-control:focus, .form-select:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-heartbeat"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user-tie"></i> Admin</span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="sidebar">
                    <div class="text-center mb-3">
                        <div style="width: 80px; height: 80px; margin: 0 auto; background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 36px;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h5 class="mt-3">Admin</h5>
                        <small class="text-muted">Administrator</small>
                    </div>
                    <hr>
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="#dashboard" data-bs-toggle="tab"><i class="fas fa-chart-line"></i> Dashboard</a>
                        <a class="nav-link" href="#appointments" data-bs-toggle="tab"><i class="fas fa-calendar-list"></i> Appointments</a>
                        <a class="nav-link" href="#records" data-bs-toggle="tab"><i class="fas fa-file-medical"></i> Records</a>
                        <a class="nav-link" href="#reports" data-bs-toggle="tab"><i class="fas fa-file-export"></i> Reports & Export</a>
                        <a class="nav-link" href="#messages" data-bs-toggle="tab"><i class="fas fa-envelope"></i> Feedback Messages</a>
                        <a class="nav-link" href="#doctor-availability" data-bs-toggle="tab"><i class="fas fa-calendar-check"></i> Doctor Availability</a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>

                        <h3 class="mb-4">Dashboard</h3>

                        <!-- Stats -->
                        <div class="row mb-4">
                            <div class="col-md-6 col-lg-3">
                                <div class="stats-card" onclick="toggleAdminStatGraph('users')" style="cursor: pointer; transition: all 0.3s ease;">
                                    <i class="fas fa-users fa-2x" style="color: var(--primary);"></i>
                                    <div class="stats-number"><?php echo $total_users; ?></div>
                                    <div class="stats-label">Total Patients</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="stats-card" onclick="toggleAdminStatGraph('appointments')" style="cursor: pointer; transition: all 0.3s ease;">
                                    <i class="fas fa-calendar-check fa-2x" style="color: var(--secondary);"></i>
                                    <div class="stats-number"><?php echo $total_appointments; ?></div>
                                    <div class="stats-label">Total Appointments</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="stats-card" onclick="toggleAdminStatGraph('pending')" style="cursor: pointer; transition: all 0.3s ease;">
                                    <i class="fas fa-clock fa-2x" style="color: #f0ad4e;"></i>
                                    <div class="stats-number"><?php echo $pending_appointments; ?></div>
                                    <div class="stats-label">Pending Appointments</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="stats-card" onclick="toggleAdminStatGraph('records')" style="cursor: pointer; transition: all 0.3s ease;">
                                    <i class="fas fa-file-medical fa-2x" style="color: #28a745;"></i>
                                    <div class="stats-number"><?php echo $total_records; ?></div>
                                    <div class="stats-label">Medical Records</div>
                                </div>
                            </div>
                        </div>

                        <!-- Request Stats -->
                        <div class="row mb-4">
                            <div class="col-md-6 col-lg-3">
                                <div class="stats-card">
                                    <i class="fas fa-pills fa-2x" style="color: #17a2b8;"></i>
                                    <div class="stats-number"><?php echo $pending_medicine_requests; ?></div>
                                    <div class="stats-label">Pending Medicine Requests</div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="stats-card">
                                    <i class="fas fa-stethoscope fa-2x" style="color: #e83e8c;"></i>
                                    <div class="stats-number"><?php echo $pending_consultation_requests; ?></div>
                                    <div class="stats-label">Pending Consultation Requests</div>
                                </div>
                            </div>
                        </div>

                        <!-- Analytics & Statistics -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Analytics & Statistics</h5>
                            </div>
                            <div class="card-body">
                                <!-- Graph View -->
                                <div id="adminStatGraphView" style="display: none; margin-bottom: 30px;">
                                    <div id="adminSelectedStatCard" class="stats-card" style="padding: 20px; text-align: center; margin-bottom: 20px;">
                                    </div>
                                    <div id="adminGraphContainer" style="padding: 20px; background: #ffffff; border-radius: 8px; border: 1px solid #e0e0e0;">
                                    </div>
                                    <div style="text-align: center; margin-top: 15px;">
                                        <button onclick="closeAdminStatGraph()" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-times"></i> Close
                                        </button>
                                    </div>
                                </div>

                                <hr>
                                <p><strong>BHC System Status:</strong> <span class="badge bg-success">Active</span></p>
                                <p><strong>Database Connection:</strong> <span class="badge bg-success">Connected</span></p>
                                <p class="mb-0"><strong>Last Updated:</strong> <?php echo date('M d, Y g:i A'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Appointments Tab -->
                    <div class="tab-pane fade" id="appointments">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-list"></i> Manage Appointments</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Patient Name</th>
                                                <th>Doctor</th>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            // Merge appointments and approved requests
                                            $all_items = [];
                                            
                                            // Add appointments
                                            if (!empty($appointments)):
                                                foreach ($appointments as $appt):
                                                    $all_items[] = [
                                                        'id' => $appt['id'],
                                                        'patient_name' => $appt['first_name'] . ' ' . $appt['last_name'],
                                                        'doctor' => !empty($appt['doctor_first_name']) ? 'Dr. ' . $appt['doctor_first_name'] . ' ' . $appt['doctor_last_name'] : 'N/A',
                                                        'date' => date('M d, Y', strtotime($appt['appointment_date'])),
                                                        'time' => date('g:i A', strtotime($appt['appointment_time'])),
                                                        'type' => 'Appointment',
                                                        'status' => $appt['status'],
                                                        'item_type' => 'appointment',
                                                        'reason' => $appt['reason']
                                                    ];
                                                endforeach;
                                            endif;
                                            
                                            // Add approved requests
                                            $approved_medicine = MedicineRequest::getAllRequests();
                                            $approved_medicine = array_filter($approved_medicine, function($item) { 
                                                return $item['status'] === 'approved' && (!isset($item['is_archived']) || $item['is_archived'] == 0); 
                                            });
                                            
                                            $approved_consultation = ConsultationRequest::getAllRequests();
                                            $approved_consultation = array_filter($approved_consultation, function($item) { 
                                                return $item['status'] === 'approved' && (!isset($item['is_archived']) || $item['is_archived'] == 0); 
                                            });
                                            
                                            $all_approved = array_merge($approved_medicine, $approved_consultation);
                                            foreach ($all_approved as $request):
                                                $type = isset($request['medicine_name']) ? 'Medicine' : 'Consultation';
                                                // For consultations, use the doctor_name field; for medicine, use 'N/A'
                                                $doctor = ($type === 'Consultation') ? $request['doctor_name'] : 'N/A';
                                                $all_items[] = [
                                                    'id' => $request['id'],
                                                    'patient_name' => $request['first_name'] . ' ' . $request['last_name'],
                                                    'doctor' => $doctor,
                                                    'date' => date('M d, Y', strtotime($request['approved_at'])),
                                                    'time' => date('g:i A', strtotime($request['approved_at'])),
                                                    'type' => $type,
                                                    'status' => 'approved',
                                                    'item_type' => strtolower($type),
                                                    'request_id' => $request['id'],
                                                    'request_type' => $type
                                                ];
                                            endforeach;
                                            
                                            // Sort by date (most recent first)
                                            usort($all_items, function($a, $b) {
                                                return strtotime($b['date']) - strtotime($a['date']);
                                            });
                                            
                                            if (empty($all_items)): 
                                            ?>
                                                <tr><td colspan="7" class="text-center text-muted py-5">No appointments or approved requests found</td></tr>
                                            <?php else: 
                                                foreach ($all_items as $item): 
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['patient_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['doctor']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['date']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['time']); ?></td>
                                                    <td><span class="badge bg-<?php echo ($item['type'] === 'Appointment') ? 'primary' : (($item['type'] === 'Medicine') ? 'info' : 'warning'); ?>"><?php echo $item['type']; ?></span></td>
                                                    <td><span class="badge bg-<?php echo ($item['status'] === 'approved') ? 'success' : 'warning'; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                                                    <td>
                                                        <?php if ($item['item_type'] === 'appointment'): ?>
                                                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $item['id']; ?>">Edit</button>
                                                            <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this appointment?')">Delete</a>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-warning archiveRequestBtn" data-request-id="<?php echo $item['request_id']; ?>" data-request-type="<?php echo $item['request_type']; ?>">
                                                                <i class="fas fa-box"></i> Archive
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <?php if ($item['item_type'] === 'appointment'): ?>
                                                    <!-- Edit Modal -->
                                                    <div class="modal fade" id="editModal<?php echo $item['id']; ?>" tabindex="-1">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Edit Appointment</h5>
                                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form method="POST">
                                                                    <div class="modal-body">
                                                                        <input type="hidden" name="appointment_id" value="<?php echo $item['id']; ?>">
                                                                        <div class="mb-3">
                                                                            <label for="status<?php echo $item['id']; ?>" class="form-label">Status</label>
                                                                            <select class="form-select" id="status<?php echo $item['id']; ?>" name="status" required>
                                                                                <option value="pending" <?php if ($item['status'] === 'pending') echo 'selected'; ?>>Pending</option>
                                                                                <option value="confirmed" <?php if ($item['status'] === 'confirmed') echo 'selected'; ?>>Confirmed</option>
                                                                                <option value="completed" <?php if ($item['status'] === 'completed') echo 'selected'; ?>>Completed</option>
                                                                                <option value="cancelled" <?php if ($item['status'] === 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="notes<?php echo $item['id']; ?>" class="form-label">Notes</label>
                                                                            <textarea class="form-control" id="notes<?php echo $item['id']; ?>" name="notes" rows="3"></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                        <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Archived Requests View -->
                                <hr class="my-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0"><i class="fas fa-archive"></i> Archived Requests</h6>
                                    <button class="btn btn-light btn-sm" id="toggleApprovedArchive" title="View all requests">
                                        <i class="fas fa-arrow-left"></i> Show/Hide Archived Requests
                                    </button>
                                </div>

                                <div id="approvedRequestsView" style="display: none;">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="approvedRequestsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Patient Name</th>
                                                    <th>Doctor</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $archived_medicine = MedicineRequest::getArchivedRequests();
                                                $archived_consultation = ConsultationRequest::getArchivedRequests();
                                                $all_archived = array_merge($archived_medicine, $archived_consultation);
                                                usort($all_archived, function($a, $b) {
                                                    return strtotime($b['approved_at']) - strtotime($a['approved_at']);
                                                });
                                                
                                                if (empty($all_archived)):
                                                ?>
                                                    <tr><td colspan="7" class="text-center text-muted py-5">No archived requests</td></tr>
                                                <?php else:
                                                    foreach ($all_archived as $request):
                                                        $type = isset($request['medicine_name']) ? 'Medicine' : 'Consultation';
                                                        // For consultations, use the doctor_name field; for medicine, use 'N/A'
                                                        $doctor = ($type === 'Consultation') ? $request['doctor_name'] : 'N/A';
                                                ?>
                                                    <tr class="archived-request-row">
                                                        <td><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($doctor); ?></td>
                                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($request['approved_at']))); ?></td>
                                                        <td><?php echo htmlspecialchars(date('g:i A', strtotime($request['approved_at']))); ?></td>
                                                        <td><span class="badge bg-secondary"><?php echo $type; ?></span></td>
                                                        <td><span class="badge bg-secondary">Archived</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-success restoreRequestBtn" data-request-id="<?php echo $request['id']; ?>" data-request-type="<?php echo $type; ?>">
                                                                <i class="fas fa-undo"></i> Restore
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Medicine Requests Section -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-pills"></i> Medicine Requests</h5>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#medicinRequestsModal">View All</button>
                            </div>
                            <div class="card-body">
                                <p><strong>Pending Medicine Requests:</strong> <span class="badge bg-warning text-dark"><?php echo $pending_medicine_requests; ?></span></p>
                                <p class="mb-0"><small class="text-muted">Click "View All" to manage medicine requests</small></p>
                            </div>
                        </div>

                        <!-- Consultation Requests Section -->
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-stethoscope"></i> Consultation Requests</h5>
                                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#consultationRequestsModal">View All</button>
                            </div>
                            <div class="card-body">
                                <p><strong>Pending Consultation Requests:</strong> <span class="badge bg-warning text-dark"><?php echo $pending_consultation_requests; ?></span></p>
                                <p class="mb-0"><small class="text-muted">Click "View All" to manage consultation requests</small></p>
                            </div>
                        </div>
                    </div>

                    <!-- Records Tab -->
                    <div class="tab-pane fade" id="records">
                        <!-- Records Subtabs -->
                        <ul class="nav nav-tabs mb-4" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="patients-tab" data-bs-toggle="tab" data-bs-target="#patients-content" type="button" role="tab">
                                    <i class="fas fa-users"></i> Patients
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="medicine-history-tab" data-bs-toggle="tab" data-bs-target="#medicine-history-content" type="button" role="tab">
                                    <i class="fas fa-pills"></i> Medicine Records
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="consultation-history-tab" data-bs-toggle="tab" data-bs-target="#consultation-history-content" type="button" role="tab">
                                    <i class="fas fa-stethoscope"></i> Consultation Records
                                </button>
                            </li>
                        </ul>

                        <!-- Medical Records Content -->
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="patients-content" role="tabpanel">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center gap-3">
                                        <h5 class="mb-0"><i class="fas fa-users"></i> Manage Patients</h5>
                                        <div style="width: 300px;">
                                            <input type="text" class="form-control form-control-sm" id="patientSearchInput" placeholder="Search patients...">
                                        </div>
                                        <a href="records/create_record.php" class="btn btn-light btn-sm"><i class="fas fa-plus"></i> Create Record</a>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="patientTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Phone</th>
                                                        <th>Status</th>
                                                        <th>Joined</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $patients = $conn->query("SELECT id, first_name, last_name, email, phone, status, created_at FROM users WHERE role = 'patient' ORDER BY created_at DESC");
                                                    if ($patients->num_rows > 0):
                                                        while ($patient = $patients->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></td>
                                                            <td><span class="badge bg-<?php echo $patient['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($patient['status']); ?></span></td>
                                                            <td><?php echo htmlspecialchars(date('M d, Y', strtotime($patient['created_at']))); ?></td>
                                                            <td><button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#patientRecordsModal<?php echo $patient['id']; ?>"><i class="fas fa-file-medical"></i> View Records</button></td>
                                                        </tr>

                                                        <!-- Patient Records Modal -->
                                                        <div class="modal fade" id="patientRecordsModal<?php echo $patient['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <div>
                                                                            <h5 class="modal-title"><i class="fas fa-file-medical"></i> Medical Records</h5>
                                                                            <small class="text-white-50"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></small>
                                                                        </div>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                                                                        <?php
                                                                        $patient_records = MedicalRecord::getPatientRecords($patient['id']);
                                                                        if (!empty($patient_records)):
                                                                            foreach ($patient_records as $rec):
                                                                        ?>
                                                                        <div class="mb-3" style="background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; border-left: 4px solid #dc3545;">
                                                                            <!-- Record Header -->
                                                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                                                <div>
                                                                                    <h6 class="mb-1" style="color: #dc3545;"><i class="fas fa-calendar"></i> <?php echo htmlspecialchars(date('M d, Y', strtotime($rec['record_date']))); ?></h6>
                                                                                    <small class="text-muted"><?php echo htmlspecialchars(date('g:i A', strtotime($rec['record_date']))); ?></small>
                                                                                </div>
                                                                            </div>

                                                                            <!-- Diagnosis -->
                                                                            <div class="mb-3">
                                                                                <p class="mb-1"><strong style="color: #333;"><i class="fas fa-stethoscope"></i> Diagnosis</strong></p>
                                                                                <p class="mb-0 p-2" style="background: #f8f9fa; border-radius: 5px; border-left: 3px solid #dc3545; color: #555;"><?php echo htmlspecialchars($rec['diagnosis'] ?? 'N/A'); ?></p>
                                                                            </div>

                                                                            <!-- Symptoms -->
                                                                            <div class="mb-3">
                                                                                <p class="mb-1"><strong style="color: #333;"><i class="fas fa-list"></i> Symptoms</strong></p>
                                                                                <p class="mb-0 p-2" style="background: #f8f9fa; border-radius: 5px; border-left: 3px solid #007bff; white-space: pre-wrap; color: #555;"><?php echo htmlspecialchars($rec['symptoms'] ?? 'N/A'); ?></p>
                                                                            </div>

                                                                            <!-- Treatment -->
                                                                            <div class="mb-3">
                                                                                <p class="mb-1"><strong style="color: #333;"><i class="fas fa-prescription-bottle"></i> Treatment</strong></p>
                                                                                <p class="mb-0 p-2" style="background: #f8f9fa; border-radius: 5px; border-left: 3px solid #28a745; white-space: pre-wrap; color: #555;"><?php echo htmlspecialchars($rec['treatment'] ?? 'N/A'); ?></p>
                                                                            </div>

                                                                            <!-- Prescriptions -->
                                                                            <?php if (!empty($rec['prescriptions'])): ?>
                                                                            <div class="mb-3">
                                                                                <p class="mb-1"><strong style="color: #333;"><i class="fas fa-pills"></i> Prescriptions</strong></p>
                                                                                <p class="mb-0 p-2" style="background: #f8f9fa; border-radius: 5px; border-left: 3px solid #ffc107; white-space: pre-wrap; color: #555;"><?php echo htmlspecialchars($rec['prescriptions']); ?></p>
                                                                            </div>
                                                                            <?php endif; ?>

                                                                            <!-- Notes -->
                                                                            <?php if (!empty($rec['notes'])): ?>
                                                                            <div>
                                                                                <p class="mb-1"><strong style="color: #333;"><i class="fas fa-sticky-note"></i> Additional Notes</strong></p>
                                                                                <p class="mb-0 p-2" style="background: #f8f9fa; border-radius: 5px; border-left: 3px solid #6c757d; white-space: pre-wrap; color: #555;"><?php echo htmlspecialchars($rec['notes']); ?></p>
                                                                            </div>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <?php
                                                                            endforeach;
                                                                        else:
                                                                        ?>
                                                                        <div class="alert alert-info text-center mb-0">
                                                                            <i class="fas fa-inbox fa-2x mb-2" style="display: block;"></i> 
                                                                            <strong>No medical records found</strong><br>
                                                                            <small>No medical records have been created for this patient yet.</small>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <a href="patients/view_patient_records.php?id=<?php echo $patient['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Full Details</a>
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                        <tr><td colspan="7" class="text-center text-muted py-5">No patients found</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Medicine Requests History Content -->
                            <div class="tab-pane fade" id="medicine-history-content" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-pills"></i> Medicine Request History</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Patient Name</th>
                                                        <th>Medicine</th>
                                                        <th>Quantity</th>
                                                        <th>Status</th>
                                                        <th>Requested</th>
                                                        <th>View</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $medicine_history = $conn->query("SELECT mr.id, mr.patient_id, u.first_name, u.last_name, mr.medicine_name, mr.quantity, mr.status, mr.requested_at, mr.reason, mr.admin_notes FROM medicine_requests mr JOIN users u ON mr.patient_id = u.id ORDER BY mr.requested_at DESC LIMIT 30");
                                                    if ($medicine_history && $medicine_history->num_rows > 0):
                                                        while ($med_req = $medicine_history->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($med_req['first_name'] . ' ' . $med_req['last_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($med_req['medicine_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($med_req['quantity']); ?></td>
                                                            <td><span class="badge bg-<?php echo $med_req['status'] === 'pending' ? 'warning' : ($med_req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($med_req['status']); ?></span></td>
                                                            <td><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($med_req['requested_at']))); ?></td>
                                                            <td><button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#medicineHistoryModal<?php echo $med_req['id']; ?>">View</button></td>
                                                        </tr>

                                                        <!-- Medicine Request Detail Modal -->
                                                        <div class="modal fade" id="medicineHistoryModal<?php echo $med_req['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title"><i class="fas fa-pills"></i> Medicine Request Details</h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Patient Information</h6>
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($med_req['first_name'] . ' ' . $med_req['last_name']); ?></p>
                                                                        </div>
                                                                        <hr>
                                                                        <div class="mb-3">
                                                                            <h6 class="text-muted mb-3"><i class="fas fa-pills"></i> Request Details</h6>
                                                                            <p><strong>Medicine:</strong> <?php echo htmlspecialchars($med_req['medicine_name']); ?></p>
                                                                            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($med_req['quantity']); ?></p>
                                                                            <p><strong>Requested Date:</strong> <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($med_req['requested_at']))); ?></p>
                                                                            <p><strong>Status:</strong> <span class="badge bg-<?php echo $med_req['status'] === 'pending' ? 'warning' : ($med_req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($med_req['status']); ?></span></p>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <p><strong>Reason:</strong></p>
                                                                            <p class="text-break" style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($med_req['reason']); ?></p>
                                                                        </div>
                                                                        <?php if (!empty($med_req['admin_notes'])): ?>
                                                                        <div class="mb-3">
                                                                            <p><strong>Doctor Notes:</strong></p>
                                                                            <p class="text-break" style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($med_req['admin_notes']); ?></p>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                        <tr><td colspan="6" class="text-center text-muted py-5">No medicine requests found</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Consultation Records Content -->
                            <div class="tab-pane fade" id="consultation-history-content" role="tabpanel">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-stethoscope"></i> Consultation Request History</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Patient Name</th>
                                                        <th>Consultation Type</th>
                                                        <th>Doctor</th>
                                                        <th>Status</th>
                                                        <th>Requested</th>
                                                        <th>View</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $consultation_history = $conn->query("SELECT cr.id, cr.patient_id, u.first_name, u.last_name, cr.consultation_type, cr.status, cr.requested_at, cr.description, cr.admin_notes, d.first_name as doc_first, d.last_name as doc_last FROM consultation_requests cr JOIN users u ON cr.patient_id = u.id LEFT JOIN doctors d ON cr.doctor_id = d.id ORDER BY cr.requested_at DESC LIMIT 30");
                                                    if ($consultation_history && $consultation_history->num_rows > 0):
                                                        while ($cons_req = $consultation_history->fetch_assoc()):
                                                    ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($cons_req['first_name'] . ' ' . $cons_req['last_name']); ?></td>
                                                            <td><?php echo htmlspecialchars($cons_req['consultation_type']); ?></td>
                                                            <td><?php echo !empty($cons_req['doc_first']) ? htmlspecialchars($cons_req['doc_first'] . ' ' . $cons_req['doc_last']) : '<span class="text-muted">Not Assigned</span>'; ?></td>
                                                            <td><span class="badge bg-<?php echo $cons_req['status'] === 'pending' ? 'warning' : ($cons_req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($cons_req['status']); ?></span></td>
                                                            <td><?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($cons_req['requested_at']))); ?></td>
                                                            <td><button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#consultationHistoryModal<?php echo $cons_req['id']; ?>">View</button></td>
                                                        </tr>

                                                        <!-- Consultation Request Detail Modal -->
                                                        <div class="modal fade" id="consultationHistoryModal<?php echo $cons_req['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title"><i class="fas fa-stethoscope"></i> Consultation Request Details</h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <h6 class="text-muted mb-3"><i class="fas fa-user"></i> Patient Information</h6>
                                                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($cons_req['first_name'] . ' ' . $cons_req['last_name']); ?></p>
                                                                        </div>
                                                                        <hr>
                                                                        <div class="mb-3">
                                                                            <h6 class="text-muted mb-3"><i class="fas fa-stethoscope"></i> Request Details</h6>
                                                                            <p><strong>Consultation Type:</strong> <?php echo htmlspecialchars($cons_req['consultation_type']); ?></p>
                                                                            <p><strong>Assigned Doctor:</strong> <?php echo !empty($cons_req['doc_first']) ? htmlspecialchars($cons_req['doc_first'] . ' ' . $cons_req['doc_last']) : '<span class="text-muted">Not Assigned</span>'; ?></p>
                                                                            <p><strong>Requested Date:</strong> <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($cons_req['requested_at']))); ?></p>
                                                                            <p><strong>Status:</strong> <span class="badge bg-<?php echo $cons_req['status'] === 'pending' ? 'warning' : ($cons_req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($cons_req['status']); ?></span></p>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <p><strong>Description:</strong></p>
                                                                            <p class="text-break" style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($cons_req['description']); ?></p>
                                                                        </div>
                                                                        <?php if (!empty($cons_req['admin_notes'])): ?>
                                                                        <div class="mb-3">
                                                                            <p><strong>Admin Notes:</strong></p>
                                                                            <p class="text-break" style="background: #f8f9fa; padding: 10px; border-radius: 5px;"><?php echo htmlspecialchars($cons_req['admin_notes']); ?></p>
                                                                        </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php 
                                                        endwhile;
                                                    else:
                                                    ?>
                                                        <tr><td colspan="6" class="text-center text-muted py-5">No consultation requests found</td></tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports & Export Tab -->
                    <div class="tab-pane fade" id="reports">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-export"></i> Reports & Export</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h6 class="mb-3"><i class="fas fa-users"></i> Users</h6>
                                        <p class="text-muted mb-3">Export all users to CSV format</p>
                                        <a href="export/export_csv.php?type=users" class="btn btn-sm btn-primary" download><i class="fas fa-download"></i> Download CSV</a>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <h6 class="mb-3"><i class="fas fa-calendar-alt"></i> Appointments</h6>
                                        <p class="text-muted mb-3">Export all appointments to CSV format</p>
                                        <a href="export/export_csv.php?type=appointments" class="btn btn-sm btn-primary" download><i class="fas fa-download"></i> Download CSV</a>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h6 class="mb-3"><i class="fas fa-file-medical"></i> Medical Records</h6>
                                        <p class="text-muted mb-3">Export all medical records to CSV format</p>
                                        <a href="export/export_csv.php?type=medical_records" class="btn btn-sm btn-primary" download><i class="fas fa-download"></i> Download CSV</a>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <h6 class="mb-3"><i class="fas fa-pills"></i> Medicine Requests</h6>
                                        <p class="text-muted mb-3">Export all medicine requests to CSV format</p>
                                        <a href="export/export_csv.php?type=medicine_requests" class="btn btn-sm btn-primary" download><i class="fas fa-download"></i> Download CSV</a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <h6 class="mb-3"><i class="fas fa-stethoscope"></i> Consultation Requests</h6>
                                        <p class="text-muted mb-3">Export all consultation requests to CSV format</p>
                                        <a href="export/export_csv.php?type=consultation_requests" class="btn btn-sm btn-primary" download><i class="fas fa-download"></i> Download CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Messages Tab (Patient Feedback) -->
                    <div class="tab-pane fade" id="messages">
                        <?php
                        require_once '../includes/config.php';
                        require_once '../includes/ContactMessage.php';
                        
                        // Handle filter - check URL parameter first, then use default
                        $filter = $_GET['filter'] ?? $_POST['filter'] ?? 'all';
                        
                        // Get messages based on filter
                        if ($filter === 'unread') {
                            $messages = ContactMessage::getAllMessages(100, 0, 'unread');
                        } elseif ($filter === 'read') {
                            $messages = ContactMessage::getAllMessages(100, 0, 'read');
                        } elseif ($filter === 'replied') {
                            $messages = ContactMessage::getAllMessages(100, 0, 'replied');
                        } else {
                            $messages = ContactMessage::getAllMessages(100, 0);
                        }
                        
                        $unread_count = ContactMessage::getUnreadCount();
                        ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><i class="fas fa-envelope"></i> Patient Feedback & Messages 
                                        <span class="badge bg-warning text-dark ms-2"><?php echo $unread_count; ?> Unread</span>
                                    </h5>
                                </div>
                            </div>
                            <div class="card-body">
                            <!-- Filter Buttons -->
                                <div class="mb-4 d-flex gap-2 flex-wrap filter-messages">
                                    <button class="btn btn-sm btn-outline-danger <?php echo $filter === 'unread' ? 'active' : ''; ?>" data-filter="unread" onclick="filterMessages('unread')">
                                        <i class="fas fa-inbox"></i> Unread
                                    </button>
                                    <button class="btn btn-sm btn-outline-info <?php echo $filter === 'read' ? 'active' : ''; ?>" data-filter="read" onclick="filterMessages('read')">
                                        <i class="fas fa-envelope-open"></i> Read
                                    </button>
                                    <button class="btn btn-sm btn-outline-success <?php echo $filter === 'replied' ? 'active' : ''; ?>" data-filter="replied" onclick="filterMessages('replied')">
                                        <i class="fas fa-reply"></i> Replied
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary <?php echo $filter === 'all' ? 'active' : ''; ?>" data-filter="all" onclick="filterMessages('all')">
                                        <i class="fas fa-list"></i> All
                                    </button>
                                </div>

                                <!-- Messages List -->
                                <?php if (!empty($messages)): ?>
                                    <div class="messages-list">
                                        <?php foreach ($messages as $msg): ?>
                                            <div class="message-item mb-3 p-3 border rounded" style="background: #f9f9f9; border-left: 4px solid <?php echo $msg['status'] === 'unread' ? '#dc3545' : ($msg['status'] === 'replied' ? '#28a745' : '#6c757d'); ?>;">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div>
                                                        <h6 class="mb-1"><strong><?php echo htmlspecialchars($msg['name']); ?></strong></h6>
                                                        <small class="text-muted"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($msg['email']); ?></small>
                                                    </div>
                                                    <span class="badge bg-<?php echo $msg['status'] === 'unread' ? 'danger' : ($msg['status'] === 'replied' ? 'success' : 'secondary'); ?>">
                                                        <?php echo ucfirst($msg['status']); ?>
                                                    </span>
                                                </div>
                                                <p class="mb-2 mt-2"><strong>Subject:</strong> <?php echo htmlspecialchars($msg['subject']); ?></p>
                                                <p class="mb-2 p-2 bg-white rounded" style="border-left: 3px solid #dc3545;"><strong>Message:</strong><br><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                                <small class="text-muted"><i class="fas fa-calendar"></i> <?php echo date('M d, Y g:i A', strtotime($msg['created_at'])); ?></small>
                                                
                                                <?php if (!empty($msg['reply'])): ?>
                                                    <div class="mt-3 p-2 bg-success bg-opacity-10 rounded border border-success">
                                                        <p class="mb-1"><strong style="color: #28a745;"><i class="fas fa-reply"></i> Your Reply:</strong></p>
                                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['reply'])); ?></p>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="mt-3 d-flex gap-2">
                                                    <?php if ($msg['status'] === 'unread'): ?>
                                                        <button class="btn btn-sm btn-outline-primary" onclick="markAsRead(<?php echo $msg['id']; ?>)">
                                                            <i class="fas fa-check"></i> Mark as Read
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-outline-success" onclick="openReplyModal(<?php echo $msg['id']; ?>, '<?php echo htmlspecialchars($msg['name'], ENT_QUOTES); ?>')">
                                                        <i class="fas fa-reply"></i> Reply
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMessage(<?php echo $msg['id']; ?>)">
                                                        <i class="fas fa-archive"></i> Archive
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3" style="display: block;"></i>
                                        <p class="text-muted"><strong>No messages found</strong></p>
                                        <small class="text-muted">Messages from the contact form will appear here</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Reply Modal -->
                        <div class="modal fade" id="replyModal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><i class="fas fa-reply"></i> Reply to Message</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="POST" onsubmit="sendReply(event)">
                                        <div class="modal-body">
                                            <input type="hidden" id="reply_msg_id" name="message_id">
                                            <div class="mb-3">
                                                <label class="form-label">To: <span id="reply_to_name"></span></label>
                                            </div>
                                            <div class="mb-3">
                                                <label for="replyText" class="form-label">Your Reply</label>
                                                <textarea class="form-control" id="replyText" name="reply" rows="5" required placeholder="Type your reply here..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary"><i class="fas fa-send"></i> Send Reply</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <script>
                        function markAsRead(messageId) {
                            fetch('dashboard.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'mark_as_read=' + messageId
                            })
                            .then(() => location.reload())
                            .catch(error => alert('Error: ' + error));
                        }

                        function openReplyModal(messageId, senderName) {
                            document.getElementById('reply_msg_id').value = messageId;
                            document.getElementById('reply_to_name').textContent = senderName;
                            document.getElementById('replyText').value = '';
                            new bootstrap.Modal(document.getElementById('replyModal')).show();
                        }

                        function sendReply(event) {
                            event.preventDefault();
                            const messageId = document.getElementById('reply_msg_id').value;
                            const reply = document.getElementById('replyText').value;

                            fetch('dashboard.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'save_reply=1&message_id=' + messageId + '&reply=' + encodeURIComponent(reply)
                            })
                            .then(() => location.reload())
                            .catch(error => alert('Error: ' + error));
                        }

                        function deleteMessage(messageId) {
                            if (confirm('Are you sure you want to archive this message?')) {
                                // Store current filter in localStorage before reload
                                const currentFilter = document.querySelector('.filter-messages button.active')?.dataset.filter || 'all';
                                localStorage.setItem('messagesFilter', currentFilter);
                                
                                fetch('dashboard.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: 'delete_message=' + messageId
                                })
                                .then(() => location.reload())
                                .catch(error => alert('Error: ' + error));
                            }
                        }

                        function filterMessages(filterType) {
                            // Store the current filter in localStorage
                            localStorage.setItem('messagesFilter', filterType);
                            location.reload();
                        }
                        </script>
                    </div>

                    <!-- Doctor Availability Tab -->
                    <div class="tab-pane fade" id="doctor-availability">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-md"></i> Select Doctor</h5>
                            </div>
                            <div class="card-body">
                                <form method="GET" class="row g-3 align-items-end" id="doctorSelectForm">
                                    <div class="col-md-12">
                                        <label for="dashboard_doctor_select" class="form-label">Choose a Doctor to Edit Schedule</label>
                                        <select class="form-select form-select-lg" id="dashboard_doctor_select" name="doctor_id" onchange="updateDoctorSchedule(this.value)" required>
                                            <option value="">-- Select a Doctor --</option>
                                            <?php 
                                            $all_docs = $conn->query("SELECT d.id, d.first_name, d.last_name, d.specialization FROM doctors d ORDER BY d.first_name");
                                            while ($doc = $all_docs->fetch_assoc()):
                                            ?>
                                                <option value="<?php echo $doc['id']; ?>">
                                                    Dr. <?php echo htmlspecialchars($doc['first_name'] . ' ' . $doc['last_name']); ?> - <?php echo htmlspecialchars($doc['specialization']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Doctor Schedule Display (Hidden by default) -->
                        <div id="scheduleContainer" style="display: none;">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user-md"></i> <span id="doctorName"></span>
                                        <span class="text-muted" style="font-size: 0.9rem; font-weight: 400;" id="doctorSpec"></span>
                                    </h5>
                                </div>
                            </div>

                            <div id="saveMessage" class="alert alert-info alert-dismissible fade show" role="alert" style="display: none;">
                                <i class="fas fa-check-circle"></i> <span id="messageText"></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>

                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar-week"></i> Weekly Schedule</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="weeklyScheduleForm" onsubmit="saveSchedule(event)">
                                        <input type="hidden" name="doctor_id" id="formDoctorId" value="">
                                        <p class="text-muted mb-4"><i class="fas fa-info-circle"></i> Set the doctor's recurring weekly hours. Configure the start time, end time, and availability for each day.</p>
                                        
                                        <div id="scheduleGrid" class="row">
                                        </div>
                                        
                                        <div class="mt-4 pt-3 border-top">
                                            <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                                                <i class="fas fa-save"></i> Save Weekly Schedule
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Medicine Requests Modal -->
    <div class="modal fade" id="medicinRequestsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-pills"></i> All Medicine Requests</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                    <?php
                    $medicine_requests = MedicineRequest::getAllRequests();
                    if (!empty($medicine_requests)):
                        foreach ($medicine_requests as $req):
                    ?>
                    <div class="mb-3" style="background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; border-left: 4px solid #17a2b8;">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Patient:</strong> <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($req['phone'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-<?php echo $req['status'] === 'pending' ? 'warning' : ($req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($req['status']); ?></span></p>
                                <p class="mb-0"><strong>Requested:</strong> <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($req['requested_at']))); ?></p>
                            </div>
                        </div>
                        <hr class="my-2">
                        <p class="mb-1"><strong>Medicine:</strong> <?php echo htmlspecialchars($req['medicine_name']); ?></p>
                        <p class="mb-1"><strong>Quantity:</strong> <?php echo htmlspecialchars($req['quantity']); ?></p>
                        <p class="mb-1"><strong>Reason:</strong></p>
                        <p class="p-2" style="background: #f8f9fa; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($req['reason']); ?></p>
                        <?php if (!empty($req['admin_notes'])): ?>
                        <p class="mb-1"><strong>Admin Notes:</strong></p>
                        <p class="p-2" style="background: #f8f9fa; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($req['admin_notes']); ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <?php if ($req['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success" onclick="approveMedicineRequest(<?php echo $req['id']; ?>)"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn btn-sm btn-danger" onclick="rejectMedicineRequest(<?php echo $req['id']; ?>)"><i class="fas fa-times"></i> Reject</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-inbox fa-2x mb-2" style="display: block;"></i>
                        <strong>No medicine requests found</strong>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Consultation Requests Modal -->
    <div class="modal fade" id="consultationRequestsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-stethoscope"></i> All Consultation Requests</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                    <?php
                    $consultation_requests = ConsultationRequest::getAllRequests();
                    if (!empty($consultation_requests)):
                        foreach ($consultation_requests as $req):
                    ?>
                    <div class="mb-3" style="background: #fff; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; border-left: 4px solid #e83e8c;">
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Patient:</strong> <?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($req['phone'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-<?php echo $req['status'] === 'pending' ? 'warning' : ($req['status'] === 'approved' ? 'success' : 'danger'); ?>"><?php echo ucfirst($req['status']); ?></span></p>
                                <p class="mb-0"><strong>Requested:</strong> <?php echo htmlspecialchars(date('M d, Y g:i A', strtotime($req['requested_at']))); ?></p>
                            </div>
                        </div>
                        <hr class="my-2">
                        <p class="mb-1"><strong>Consultation Type:</strong> <?php echo htmlspecialchars($req['consultation_type'] ?? 'N/A'); ?></p>
                        <p class="mb-1"><strong>Description:</strong></p>
                        <p class="p-2" style="background: #f8f9fa; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($req['description'] ?? 'N/A'); ?></p>
                        <?php if (!empty($req['admin_notes'])): ?>
                        <p class="mb-1"><strong>Admin Notes:</strong></p>
                        <p class="p-2" style="background: #f8f9fa; border-radius: 5px; margin-bottom: 1rem;"><?php echo htmlspecialchars($req['admin_notes']); ?></p>
                        <?php endif; ?>
                        <div class="d-flex gap-2">
                            <?php if ($req['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success" onclick="approveConsultationRequest(<?php echo $req['id']; ?>)"><i class="fas fa-check"></i> Approve</button>
                            <button class="btn btn-sm btn-danger" onclick="rejectConsultationRequest(<?php echo $req['id']; ?>)"><i class="fas fa-times"></i> Reject</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-inbox fa-2x mb-2" style="display: block;"></i>
                        <strong>No consultation requests found</strong>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        
        function updateDoctorSchedule(doctorId) {
            if (!doctorId) {
                document.getElementById('scheduleContainer').style.display = 'none';
                return;
            }
            
            // Fetch doctor schedule
            const formData = new FormData();
            formData.append('get_schedule', '1');
            formData.append('doctor_id', doctorId);
            
            fetch('get_doctor_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error: ' + response.status);
                return response.json();
            })
            .then(schedule => {
                if (schedule.error) {
                    throw new Error(schedule.error);
                }
                
                // Get doctor name
                const select = document.getElementById('dashboard_doctor_select');
                const option = select.options[select.selectedIndex];
                const doctorText = option.text;
                const parts = doctorText.split(' - ');
                const doctorName = parts[0];
                const doctorSpec = parts[1] || '';
                
                document.getElementById('doctorName').textContent = doctorName;
                document.getElementById('doctorSpec').textContent = doctorSpec ? '- ' + doctorSpec : '';
                document.getElementById('formDoctorId').value = doctorId;
                
                // Build schedule grid
                let gridHtml = '';
                for (let day = 0; day <= 6; day++) {
                    const daySchedule = schedule[day] || {};
                    const startTime = daySchedule.start_time || '08:00';
                    const endTime = daySchedule.end_time || '17:00';
                    const isAvailable = daySchedule.is_available ? true : false;
                    
                    gridHtml += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                                    <h6 class="mb-0">${days[day]}</h6>
                                </div>
                                <div class="card-body" style="background: #f9f9f9;">
                                    <div class="mb-3">
                                        <label class="form-label small"><i class="fas fa-play-circle" style="color: #28a745;"></i> Start Time</label>
                                        <input type="time" class="form-control form-control-sm" name="start_time[${day}]" value="${startTime}">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small"><i class="fas fa-stop-circle" style="color: #dc3545;"></i> End Time</label>
                                        <input type="time" class="form-control form-control-sm" name="end_time[${day}]" value="${endTime}">
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="available[${day}]" id="available${day}" ${isAvailable ? 'checked' : ''}>
                                        <label class="form-check-label small" for="available${day}">
                                            <i class="fas fa-check" style="color: #28a745;"></i> Available
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }
                
                document.getElementById('scheduleGrid').innerHTML = gridHtml;
                document.getElementById('scheduleContainer').style.display = 'block';
                document.getElementById('saveMessage').style.display = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading schedule: ' + error.message);
            });
        }
        
        function saveSchedule(event) {
            event.preventDefault();
            
            const doctorId = document.getElementById('formDoctorId').value;
            if (!doctorId) {
                alert('Please select a doctor first');
                return;
            }
            
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            // Collect form data
            const formData = new FormData();
            formData.append('save_weekly_schedule', '1');
            formData.append('doctor_id', doctorId);
            
            // Collect all time inputs
            for (let day = 0; day <= 6; day++) {
                const startTimeInput = document.querySelector(`input[name="start_time[${day}]"]`);
                const endTimeInput = document.querySelector(`input[name="end_time[${day}]"]`);
                const availableInput = document.querySelector(`input[name="available[${day}]"]`);
                
                if (startTimeInput) {
                    formData.append(`start_time[${day}]`, startTimeInput.value);
                }
                if (endTimeInput) {
                    formData.append(`end_time[${day}]`, endTimeInput.value);
                }
                if (availableInput && availableInput.checked) {
                    formData.append(`available[${day}]`, '1');
                }
            }
            
            // Send to server
            fetch('get_doctor_schedule.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) throw new Error('Network error: ' + response.status);
                return response.json();
            })
            .then(result => {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Weekly Schedule';
                
                if (result.success) {
                    document.getElementById('messageText').textContent = result.message;
                    document.getElementById('saveMessage').className = 'alert alert-success alert-dismissible fade show';
                    document.getElementById('saveMessage').style.display = 'block';
                    
                    // Hide message after 4 seconds
                    setTimeout(() => {
                        document.getElementById('saveMessage').style.display = 'none';
                    }, 4000);
                } else {
                    document.getElementById('messageText').textContent = result.message || 'Error saving schedule';
                    document.getElementById('saveMessage').className = 'alert alert-danger alert-dismissible fade show';
                    document.getElementById('saveMessage').style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Weekly Schedule';
                alert('Error saving schedule: ' + error.message);
            });
        }
    </script>    <script>
        // Check if there's a tab to activate from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = localStorage.getItem('activeTab');
            const activeSubTab = localStorage.getItem('activeSubTab');
            
            if (activeTab) {
                localStorage.removeItem('activeTab');
                const tabElement = document.querySelector(`[href="#${activeTab}"]`);
                if (tabElement) {
                    const tab = new bootstrap.Tab(tabElement);
                    tab.show();
                }
            }
            
            // Activate subtab if specified
            if (activeSubTab) {
                localStorage.removeItem('activeSubTab');
                const subTabElement = document.getElementById(activeSubTab);
                if (subTabElement) {
                    const subTab = new bootstrap.Tab(subTabElement);
                    subTab.show();
                }
            }
            
            // Initialize DataTables for patient table if it exists
            const patientTableElement = document.getElementById('patientTable');
            if (patientTableElement && $.fn.DataTable.isDataTable(patientTableElement) === false) {
                $('#patientTable').DataTable({
                    "paging": true,
                    "pageLength": 10,
                    "ordering": true,
                    "searching": true,
                    "info": true,
                    "lengthChange": true,
                    "order": [[4, "desc"]], // Order by Joined date descending
                    "columnDefs": [
                        { "orderable": false, "targets": 5 } // Disable sorting for Actions column
                    ]
                });
            }

            // Patient search functionality
            const searchInput = document.getElementById('patientSearchInput');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const patientTable = $('#patientTable').DataTable();
                    patientTable.search(this.value).draw();
                });
            }
        });
    </script>
    <script>
        // Medicine Request Functions
        function approveMedicineRequest(requestId) {
            if (confirm('Are you sure you want to approve this medicine request?')) {
                // Make request to dashboard handler
                const formData = new FormData();
                formData.append('update_medicine_status', '1');
                formData.append('request_id', requestId);
                formData.append('status', 'approved');
                formData.append('admin_notes', '');
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close any open modals
                            const modals = document.querySelectorAll('.modal.show');
                            modals.forEach(modal => {
                                bootstrap.Modal.getInstance(modal)?.hide();
                            });
                            
                            // Click the Records tab
                            const recordsLink = document.querySelector('a[href="#records"][data-bs-toggle="tab"]');
                            if (recordsLink) {
                                recordsLink.click();
                            }
                            
                            // Show success message
                            showNotification('Medicine request approved successfully!', 'success');
                            
                            // Reload the page after 2 seconds to update data
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message || 'Error approving medicine request', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error approving medicine request', 'error');
                    });
            }
        }

        function rejectMedicineRequest(requestId) {
            const notes = prompt('Enter rejection reason:');
            if (notes !== null) {
                // Make request to dashboard handler
                const formData = new FormData();
                formData.append('update_medicine_status', '1');
                formData.append('request_id', requestId);
                formData.append('status', 'rejected');
                formData.append('admin_notes', notes);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close any open modals
                            const modals = document.querySelectorAll('.modal.show');
                            modals.forEach(modal => {
                                bootstrap.Modal.getInstance(modal)?.hide();
                            });
                            
                            // Click the Records tab
                            const recordsLink = document.querySelector('a[href="#records"][data-bs-toggle="tab"]');
                            if (recordsLink) {
                                recordsLink.click();
                            }
                            
                            // Show success message
                            showNotification('Medicine request rejected successfully!', 'success');
                            
                            // Reload the page after 2 seconds to update data
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message || 'Error rejecting medicine request', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error rejecting medicine request', 'error');
                    });
            }
        }

        // Consultation Request Functions
        function approveConsultationRequest(requestId) {
            if (confirm('Are you sure you want to approve this consultation request?')) {
                // Make request to dashboard handler
                const formData = new FormData();
                formData.append('update_consultation_status', '1');
                formData.append('request_id', requestId);
                formData.append('status', 'approved');
                formData.append('admin_notes', '');
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close any open modals
                            const modals = document.querySelectorAll('.modal.show');
                            modals.forEach(modal => {
                                bootstrap.Modal.getInstance(modal)?.hide();
                            });
                            
                            // Click the Appointments tab to refresh
                            const appointmentsLink = document.querySelector('a[href="#appointments"][data-bs-toggle="tab"]');
                            if (appointmentsLink) {
                                appointmentsLink.click();
                            }
                            
                            // Show success message
                            showNotification('Consultation request approved successfully!', 'success');
                            
                            // Reload the page after 2 seconds to update data
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message || 'Error approving consultation request', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error approving consultation request', 'error');
                    });
            }
        }

        function rejectConsultationRequest(requestId) {
            const notes = prompt('Enter rejection reason:');
            if (notes !== null) {
                // Make request to dashboard handler
                const formData = new FormData();
                formData.append('update_consultation_status', '1');
                formData.append('request_id', requestId);
                formData.append('status', 'rejected');
                formData.append('admin_notes', notes);
                
                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close any open modals
                            const modals = document.querySelectorAll('.modal.show');
                            modals.forEach(modal => {
                                bootstrap.Modal.getInstance(modal)?.hide();
                            });
                            
                            // Click the Appointments tab to refresh
                            const appointmentsLink = document.querySelector('a[href="#appointments"][data-bs-toggle="tab"]');
                            if (appointmentsLink) {
                                appointmentsLink.click();
                            }
                            
                            // Show success message
                            showNotification('Consultation request rejected successfully!', 'success');
                            
                            // Reload the page after 2 seconds to update data
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification(data.message || 'Error rejecting consultation request', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Error rejecting consultation request', 'error');
                    });
            }
        }

        // Handle archive approved request button
        document.addEventListener('click', function(e) {
            if (e.target.closest('.archiveRequestBtn')) {
                const btn = e.target.closest('.archiveRequestBtn');
                const requestId = btn.getAttribute('data-request-id');
                const requestType = btn.getAttribute('data-request-type');
                
                if (confirm(`Archive this ${requestType} request?`)) {
                    const formData = new FormData();
                    formData.append('archive_request', '1');
                    formData.append('request_id', requestId);
                    formData.append('request_type', requestType);
                    
                    fetch('dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Request archived successfully!', 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showNotification(data.message || 'Error archiving request', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Error archiving request', 'error');
                        });
                }
            }

            // Handle restore archived request button
            if (e.target.closest('.restoreRequestBtn')) {
                const btn = e.target.closest('.restoreRequestBtn');
                const requestId = btn.getAttribute('data-request-id');
                const requestType = btn.getAttribute('data-request-type');
                
                if (confirm(`Restore this ${requestType} request?`)) {
                    const formData = new FormData();
                    formData.append('restore_request', '1');
                    formData.append('request_id', requestId);
                    formData.append('request_type', requestType);
                    
                    fetch('dashboard.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification('Request restored successfully!', 'success');
                                setTimeout(() => location.reload(), 1500);
                            } else {
                                showNotification(data.message || 'Error restoring request', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('Error restoring request', 'error');
                        });
                }
            }

            // Toggle between approved and archived requests view
            if (e.target.id === 'toggleApprovedArchive' || e.target.closest('#toggleApprovedArchive')) {
                const approvedView = document.getElementById('approvedRequestsView');
                const archivedView = document.getElementById('archivedRequestsView');
                const btn = document.getElementById('toggleApprovedArchive');
                
                if (approvedView.style.display !== 'none') {
                    approvedView.style.display = 'none';
                    archivedView.style.display = 'block';
                    btn.innerHTML = '<i class="fas fa-clipboard-list"></i> View Active';
                } else {
                    approvedView.style.display = 'block';
                    archivedView.style.display = 'none';
                    btn.innerHTML = '<i class="fas fa-archive"></i> View Archived';
                }
            }
        });

        // Admin stat graph data - Dynamic from server
        const adminAppointmentsData = <?php echo json_encode($appointments); ?>;
        const adminTotalUsers = <?php echo (int)$total_users; ?>;
        const adminTotalAppointments = <?php echo (int)$total_appointments; ?>;
        const adminTotalRecords = <?php echo (int)$total_records; ?>;
        
        // Appointment status breakdown
        const appointmentStats = {
            pending: <?php echo (int)$appointment_stats['pending']; ?>,
            confirmed: <?php echo (int)$appointment_stats['confirmed']; ?>,
            completed: <?php echo (int)$appointment_stats['completed']; ?>,
            cancelled: <?php echo (int)$appointment_stats['cancelled']; ?>
        };
        
        // Monthly statistics (last 6 months)
        const monthlyUsers = <?php echo json_encode($monthly_users); ?>;
        const monthlyRecords = <?php echo json_encode($monthly_records); ?>;
        const monthLabels = <?php 
            $labels = [];
            for ($i = 5; $i >= 0; $i--) {
                $labels[] = date('M', strtotime("-$i months"));
            }
            echo json_encode($labels);
        ?>;

        function toggleAdminStatGraph(statId) {
            const graphView = document.getElementById('adminStatGraphView');
            const selectedCard = document.getElementById('adminSelectedStatCard');
            const graphContainer = document.getElementById('adminGraphContainer');

            if (!graphView || !selectedCard || !graphContainer) return;

            // Scroll to analytics section
            const analyticsCard = graphView.closest('.card');
            if (analyticsCard) {
                analyticsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            if (statId === 'users') {
                selectedCard.innerHTML = `
                    <i class="fas fa-users fa-3x" style="color: #9c27b0; margin-bottom: 10px;"></i>
                    <div class="stats-number">${adminTotalUsers}</div>
                    <div class="stats-label">Total Patients Registered</div>
                `;

                graphContainer.innerHTML = `
                    <canvas id="adminUsersCanvas" width="700" height="320" style="width:100%; height:auto; background:#fafafa; border-radius:6px; border:1px solid #eaeaea"></canvas>
                `;

                setTimeout(() => drawAdminUsersGraph(adminTotalUsers), 80);
            } else if (statId === 'appointments') {
                selectedCard.innerHTML = `
                    <i class="fas fa-calendar-check fa-3x" style="color: #2196f3; margin-bottom:10px;"></i>
                    <div class="stats-number">${adminTotalAppointments}</div>
                    <div class="stats-label">Total Appointments</div>
                `;

                graphContainer.innerHTML = `
                    <canvas id="adminAppointmentsCanvas" width="700" height="340" style="width:100%; height:auto; background:#fafafa; border-radius:6px; border:1px solid #eaeaea"></canvas>
                `;

                setTimeout(() => drawAdminAppointmentsGraph(), 80);
            } else if (statId === 'records') {
                selectedCard.innerHTML = `
                    <i class="fas fa-file-medical fa-3x" style="color: #4caf50; margin-bottom:10px;"></i>
                    <div class="stats-number">${adminTotalRecords}</div>
                    <div class="stats-label">Medical Records Created</div>
                `;

                graphContainer.innerHTML = `
                    <canvas id="adminRecordsCanvas" width="700" height="320" style="width:100%; height:auto; background:#fafafa; border-radius:6px; border:1px solid #eaeaea"></canvas>
                `;

                setTimeout(() => drawAdminRecordsGraph(adminTotalRecords), 80);
            } else if (statId === 'pending') {
                const totalAppts = appointmentStats.pending + appointmentStats.confirmed + appointmentStats.completed + appointmentStats.cancelled;
                selectedCard.innerHTML = `
                    <i class="fas fa-chart-pie fa-3x" style="color: #ff9800; margin-bottom:10px;"></i>
                    <div class="stats-number">${totalAppts}</div>
                    <div class="stats-label">Appointment Distribution</div>
                `;

                graphContainer.innerHTML = `
                    <canvas id="adminPendingCanvas" width="700" height="320" style="width:100%; height:auto; background:#fafafa; border-radius:6px; border:1px solid #eaeaea"></canvas>
                `;

                setTimeout(() => drawAdminPendingGraph(), 80);
            }

            graphView.style.display = 'block';
        }

        function closeAdminStatGraph() {
            const graphView = document.getElementById('adminStatGraphView');
            if (!graphView) return;
            graphView.style.display = 'none';
        }

        function drawAdminAppointmentsGraph(pending, confirmed, completed, total) {
            const canvas = document.getElementById('adminAppointmentsCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,canvas.width,canvas.height);

            // Professional bar chart with actual statistics
            const data = [appointmentStats.pending, appointmentStats.confirmed, appointmentStats.completed, appointmentStats.cancelled];
            const labels = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
            const colors = ['#ff9800', '#2196f3', '#4caf50', '#f44336'];
            
            // Calculate dimensions
            const padding = 60;
            const chartWidth = canvas.width - 2 * padding;
            const chartHeight = canvas.height - 2 * padding;
            const barWidth = chartWidth / (data.length * 1.5);
            const maxVal = Math.max(...data, 1);
            const scale = chartHeight / maxVal;

            // Draw Y-axis line
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, canvas.height - padding);
            ctx.stroke();

            // Draw X-axis line
            ctx.beginPath();
            ctx.moveTo(padding, canvas.height - padding);
            ctx.lineTo(canvas.width - padding, canvas.height - padding);
            ctx.stroke();

            // Draw grid lines and Y-axis labels
            ctx.fillStyle = '#999';
            ctx.font = '12px Arial';
            ctx.textAlign = 'right';
            for (let i = 0; i <= 5; i++) {
                const val = Math.floor((maxVal / 5) * i);
                const y = canvas.height - padding - (i * chartHeight / 5);
                
                // Grid line
                ctx.strokeStyle = '#f0f0f0';
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(canvas.width - padding, y);
                ctx.stroke();
                
                // Y-axis label
                ctx.fillText(val, padding - 10, y + 4);
            }

            // Draw bars
            data.forEach((val, idx) => {
                const x = padding + idx * (barWidth * 1.5) + barWidth * 0.25;
                const h = val * scale;
                const y = canvas.height - padding - h;

                // Bar
                ctx.fillStyle = colors[idx];
                ctx.fillRect(x, y, barWidth, h);
                
                // Value label on bar
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 13px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(val, x + barWidth / 2, y + 15);

                // X-axis label
                ctx.fillStyle = '#333';
                ctx.font = '12px Arial';
                ctx.fillText(labels[idx], x + barWidth / 2, canvas.height - padding + 20);
            });

            // Title
            ctx.fillStyle = '#333';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'left';
            ctx.fillText('Appointment Status Distribution', padding, 25);
        }

        function drawAdminRecordsGraph(totalRecords) {
            const canvas = document.getElementById('adminRecordsCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,canvas.width,canvas.height);

            // Professional line/area chart with monthly data
            const data = monthlyRecords;
            const labels = monthLabels;
            
            const padding = 60;
            const chartWidth = canvas.width - 2 * padding;
            const chartHeight = canvas.height - 2 * padding;
            const pointSpacing = chartWidth / (data.length - 1);
            const maxVal = Math.max(...data, 1);
            const scale = chartHeight / maxVal;

            // Draw axes
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, canvas.height - padding);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(padding, canvas.height - padding);
            ctx.lineTo(canvas.width - padding, canvas.height - padding);
            ctx.stroke();

            // Draw grid lines and Y-axis labels
            ctx.fillStyle = '#999';
            ctx.font = '12px Arial';
            ctx.textAlign = 'right';
            for (let i = 0; i <= 5; i++) {
                const val = Math.floor((maxVal / 5) * i);
                const y = canvas.height - padding - (i * chartHeight / 5);
                
                ctx.strokeStyle = '#f0f0f0';
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(canvas.width - padding, y);
                ctx.stroke();
                
                ctx.fillText(val, padding - 10, y + 4);
            }

            // Draw area under line
            ctx.fillStyle = 'rgba(76, 175, 80, 0.15)';
            ctx.beginPath();
            ctx.moveTo(padding, canvas.height - padding);
            
            data.forEach((val, idx) => {
                const x = padding + idx * pointSpacing;
                const y = canvas.height - padding - (val * scale);
                if (idx === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.lineTo(padding + (data.length - 1) * pointSpacing, canvas.height - padding);
            ctx.closePath();
            ctx.fill();

            // Draw line
            ctx.strokeStyle = '#4caf50';
            ctx.lineWidth = 3;
            ctx.beginPath();
            
            data.forEach((val, idx) => {
                const x = padding + idx * pointSpacing;
                const y = canvas.height - padding - (val * scale);
                if (idx === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();

            // Draw points
            data.forEach((val, idx) => {
                const x = padding + idx * pointSpacing;
                const y = canvas.height - padding - (val * scale);
                
                ctx.fillStyle = '#4caf50';
                ctx.beginPath();
                ctx.arc(x, y, 5, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fill();
                
                // Data label
                ctx.fillStyle = '#333';
                ctx.font = 'bold 11px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(val, x, y - 12);
            });

            // X-axis labels
            ctx.fillStyle = '#333';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            labels.forEach((label, idx) => {
                const x = padding + idx * pointSpacing;
                ctx.fillText(label, x, canvas.height - padding + 20);
            });

            // Title
            ctx.fillStyle = '#333';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'left';
            ctx.fillText('Medical Records Trend (6 Months)', padding, 25);
        }

        function drawAdminUsersGraph(totalUsers) {
            const canvas = document.getElementById('adminUsersCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,canvas.width,canvas.height);

            // Professional line chart with monthly user registration data
            const data = monthlyUsers;
            const labels = monthLabels;
            
            const padding = 60;
            const chartWidth = canvas.width - 2 * padding;
            const chartHeight = canvas.height - 2 * padding;
            const pointSpacing = chartWidth / (data.length - 1);
            const maxVal = Math.max(...data, 1);
            const scale = chartHeight / maxVal;

            // Draw axes
            ctx.strokeStyle = '#ddd';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, canvas.height - padding);
            ctx.stroke();

            ctx.beginPath();
            ctx.moveTo(padding, canvas.height - padding);
            ctx.lineTo(canvas.width - padding, canvas.height - padding);
            ctx.stroke();

            // Draw grid lines and Y-axis labels
            ctx.fillStyle = '#999';
            ctx.font = '12px Arial';
            ctx.textAlign = 'right';
            for (let i = 0; i <= 5; i++) {
                const val = Math.floor((maxVal / 5) * i);
                const y = canvas.height - padding - (i * chartHeight / 5);
                
                ctx.strokeStyle = '#f0f0f0';
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(canvas.width - padding, y);
                ctx.stroke();
                
                ctx.fillText(val, padding - 10, y + 4);
            }

            // Draw area under line
            ctx.fillStyle = 'rgba(156, 39, 176, 0.15)';
            ctx.beginPath();
            ctx.moveTo(padding, canvas.height - padding);
            
            data.forEach((val, idx) => {
                const x = padding + idx * pointSpacing;
                const y = canvas.height - padding - (val * scale);
                if (idx === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.lineTo(padding + (data.length - 1) * pointSpacing, canvas.height - padding);
            ctx.closePath();
            ctx.fill();

            // Draw line
            ctx.strokeStyle = '#9c27b0';
            ctx.lineWidth = 3;
            ctx.beginPath();
            
            data.forEach((val, idx) => {
                const x = padding + idx * pointSpacing;
                const y = canvas.height - padding - (val * scale);
                if (idx === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            ctx.stroke();

            // Draw points
            data.forEach((val, idx) => {
                const x = padding + idx * pointSpacing;
                const y = canvas.height - padding - (val * scale);
                
                ctx.fillStyle = '#9c27b0';
                ctx.beginPath();
                ctx.arc(x, y, 5, 0, Math.PI * 2);
                ctx.fill();
                
                ctx.fillStyle = '#fff';
                ctx.beginPath();
                ctx.arc(x, y, 3, 0, Math.PI * 2);
                ctx.fill();
                
                // Data label
                ctx.fillStyle = '#333';
                ctx.font = 'bold 11px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(val, x, y - 12);
            });

            // X-axis labels
            ctx.fillStyle = '#333';
            ctx.font = '12px Arial';
            ctx.textAlign = 'center';
            labels.forEach((label, idx) => {
                const x = padding + idx * pointSpacing;
                ctx.fillText(label, x, canvas.height - padding + 20);
            });

            // Title
            ctx.fillStyle = '#333';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'left';
            ctx.fillText('Patient Registration Trend (6 Months)', padding, 25);
        }

        function drawAdminPendingGraph() {
            const canvas = document.getElementById('adminPendingCanvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0,0,canvas.width,canvas.height);

            // Professional pie/donut chart for appointment status
            const pending = appointmentStats.pending;
            const confirmed = appointmentStats.confirmed;
            const completed = appointmentStats.completed;
            const cancelled = appointmentStats.cancelled;
            const total = pending + confirmed + completed + cancelled;

            if (total === 0) {
                ctx.fillStyle = '#999';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('No appointment data available', canvas.width / 2, canvas.height / 2);
                return;
            }

            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = 80;
            
            const data = [pending, confirmed, completed, cancelled];
            const labels = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];
            const colors = ['#ff9800', '#2196f3', '#4caf50', '#f44336'];
            
            let currentAngle = -Math.PI / 2;

            // Draw pie slices
            data.forEach((val, idx) => {
                const sliceAngle = (val / total) * 2 * Math.PI;
                
                // Draw slice
                ctx.fillStyle = colors[idx];
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
                ctx.closePath();
                ctx.fill();

                // Draw percentage label
                const labelAngle = currentAngle + sliceAngle / 2;
                const labelX = centerX + Math.cos(labelAngle) * (radius * 0.65);
                const labelY = centerY + Math.sin(labelAngle) * (radius * 0.65);
                
                const percentage = ((val / total) * 100).toFixed(1);
                ctx.fillStyle = '#fff';
                ctx.font = 'bold 12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(percentage + '%', labelX, labelY);

                currentAngle += sliceAngle;
            });

            // Draw legend
            ctx.font = '12px Arial';
            ctx.textAlign = 'left';
            const legendX = canvas.width - 180;
            const legendY = 30;
            
            data.forEach((val, idx) => {
                // Color box
                ctx.fillStyle = colors[idx];
                ctx.fillRect(legendX, legendY + idx * 25, 15, 15);
                
                // Label and count
                ctx.fillStyle = '#333';
                ctx.fillText(labels[idx] + ': ' + val, legendX + 20, legendY + idx * 25 + 12);
            });

            // Title
            ctx.fillStyle = '#333';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'left';
            ctx.fillText('Appointment Status Overview', 20, 25);
        }

        // Notification helper
        function showNotification(message, type = 'info') {
            // Create a simple alert-like notification
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            // Auto dismiss after 5 seconds
            setTimeout(() => alertDiv.remove(), 5000);
        }
    </script>
</body>
</html>
