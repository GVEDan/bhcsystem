<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();

// Get statistics for reporting
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'patient'")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM appointments")->fetch_assoc()['count'];
$total_records = $conn->query("SELECT COUNT(*) as count FROM medical_records")->fetch_assoc()['count'];
$pending_medicine = $conn->query("SELECT COUNT(*) as count FROM medicine_requests WHERE status = 'pending'")->fetch_assoc()['count'];
$pending_consultation = $conn->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'pending'")->fetch_assoc()['count'];

$appointment_stats = $conn->query("SELECT status, COUNT(*) as count FROM appointments GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$medicine_stats = $conn->query("SELECT status, COUNT(*) as count FROM medicine_requests GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$consultation_stats = $conn->query("SELECT status, COUNT(*) as count FROM consultation_requests GROUP BY status")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Export - CLINICare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
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
        .navbar-nav .nav-link:hover, .navbar-nav .nav-link.active {
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
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .card-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #b01d2b 100%);
        }
        .export-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .export-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .export-icon {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
        }
        .export-title {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }
        .export-desc {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .stat-row:last-child {
            border-bottom: none;
        }
        .stat-label {
            color: #666;
            font-size: 14px;
        }
        .stat-value {
            font-weight: 600;
            color: var(--primary);
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
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
                        <a class="nav-link active" href="reports.php">Reports & Export</a>
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
                    <a href="reports.php" class="nav-link active"><i class="fas fa-chart-bar"></i> Reports & Export</a>
                    <a href="manage_doctor_availability.php" class="nav-link"><i class="fas fa-calendar"></i> Doctor Availability</a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Header -->
                <div class="mb-4">
                    <h1 class="h2 mb-2"><i class="fas fa-file-export"></i> Reports & Export</h1>
                    <p class="text-muted">Generate and export system reports to CSV format</p>
                </div>

                <!-- System Summary -->
                <div class="row mb-4">
                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bar-chart"></i> System Summary</h5>
                            </div>
                            <div class="card-body">
                                <div class="stat-row">
                                    <span class="stat-label">Total Patients:</span>
                                    <span class="stat-value"><?php echo $total_users; ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Total Appointments:</span>
                                    <span class="stat-value"><?php echo $total_appointments; ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Medical Records:</span>
                                    <span class="stat-value"><?php echo $total_records; ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Pending Medicine Requests:</span>
                                    <span class="stat-value"><?php echo $pending_medicine; ?></span>
                                </div>
                                <div class="stat-row">
                                    <span class="stat-label">Pending Consultation Requests:</span>
                                    <span class="stat-value"><?php echo $pending_consultation; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-pie-chart"></i> Report Date</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Generated:</strong> <?php echo date('F d, Y g:i A'); ?></p>
                                <p><strong>Generated By:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                <p><strong>System:</strong> Barangay Health Center Management System</p>
                                <p><strong>Version:</strong> 1.0</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Options -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Export Data</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Users Export -->
                            <div class="col-lg-4 col-md-6">
                                <div class="export-card">
                                    <div class="export-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="export-title">Patients</div>
                                    <div class="export-desc">
                                        Export all patient information<br>
                                        <small class="text-muted">Total: <?php echo $total_users; ?> patients</small>
                                    </div>
                                    <a href="../export/export_csv.php?type=users" class="btn btn-sm btn-primary" download>
                                        <i class="fas fa-download"></i> Export CSV
                                    </a>
                                </div>
                            </div>

                            <!-- Appointments Export -->
                            <div class="col-lg-4 col-md-6">
                                <div class="export-card">
                                    <div class="export-icon">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="export-title">Appointments</div>
                                    <div class="export-desc">
                                        Export all appointment records<br>
                                        <small class="text-muted">Total: <?php echo $total_appointments; ?> appointments</small>
                                    </div>
                                    <a href="../export/export_csv.php?type=appointments" class="btn btn-sm btn-primary" download>
                                        <i class="fas fa-download"></i> Export CSV
                                    </a>
                                </div>
                            </div>

                            <!-- Medical Records Export -->
                            <div class="col-lg-4 col-md-6">
                                <div class="export-card">
                                    <div class="export-icon">
                                        <i class="fas fa-file-medical"></i>
                                    </div>
                                    <div class="export-title">Medical Records</div>
                                    <div class="export-desc">
                                        Export all medical records<br>
                                        <small class="text-muted">Total: <?php echo $total_records; ?> records</small>
                                    </div>
                                    <a href="../export/export_csv.php?type=medical_records" class="btn btn-sm btn-primary" download>
                                        <i class="fas fa-download"></i> Export CSV
                                    </a>
                                </div>
                            </div>

                            <!-- Medicine Requests Export -->
                            <div class="col-lg-4 col-md-6">
                                <div class="export-card">
                                    <div class="export-icon">
                                        <i class="fas fa-pills"></i>
                                    </div>
                                    <div class="export-title">Medicine Requests</div>
                                    <div class="export-desc">
                                        Export all medicine requests<br>
                                        <small class="text-muted">Total: <?php echo MedicineRequest::getStatistics()['pending'] + MedicineRequest::getStatistics()['approved'] + MedicineRequest::getStatistics()['rejected']; ?> requests</small>
                                    </div>
                                    <a href="../export/export_csv.php?type=medicine_requests" class="btn btn-sm btn-primary" download>
                                        <i class="fas fa-download"></i> Export CSV
                                    </a>
                                </div>
                            </div>

                            <!-- Consultation Requests Export -->
                            <div class="col-lg-4 col-md-6">
                                <div class="export-card">
                                    <div class="export-icon">
                                        <i class="fas fa-stethoscope"></i>
                                    </div>
                                    <div class="export-title">Consultation Requests</div>
                                    <div class="export-desc">
                                        Export all consultation requests<br>
                                        <small class="text-muted">Total: <?php echo ConsultationRequest::getStatistics()['pending'] + ConsultationRequest::getStatistics()['approved'] + ConsultationRequest::getStatistics()['rejected']; ?> requests</small>
                                    </div>
                                    <a href="../export/export_csv.php?type=consultation_requests" class="btn btn-sm btn-primary" download>
                                        <i class="fas fa-download"></i> Export CSV
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-calendar-list"></i> Appointment Status</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($appointment_stats as $stat): ?>
                                    <div class="stat-row">
                                        <span class="stat-label"><?php echo ucfirst($stat['status']); ?>:</span>
                                        <span class="stat-value"><?php echo $stat['count']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-pills"></i> Medicine Request Status</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($medicine_stats as $stat): ?>
                                    <div class="stat-row">
                                        <span class="stat-label"><?php echo ucfirst($stat['status']); ?>:</span>
                                        <span class="stat-value"><?php echo $stat['count']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-stethoscope"></i> Consultation Request Status</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($consultation_stats as $stat): ?>
                                    <div class="stat-row">
                                        <span class="stat-label"><?php echo ucfirst($stat['status']); ?>:</span>
                                        <span class="stat-value"><?php echo $stat['count']; ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
