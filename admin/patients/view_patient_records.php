<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

Auth::requireAdmin();

$user = Auth::getCurrentUser();
$patient_id = (int)($_GET['id'] ?? 0);
$error = '';
$success = '';
$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] === '1';

// Get patient information
$stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, phone, status FROM users WHERE id = ? AND role = 'patient'");
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Get patient's consultation requests
$consultation_requests = ConsultationRequest::getPatientRequests($patient_id);

// Filter to only show approved consultation requests with preferred dates
$approved_consultations = array_filter($consultation_requests, function($req) {
    return $req['status'] === 'approved' && !empty($req['preferred_date']);
});

// Filter to show rejected consultation requests
$rejected_consultations = array_filter($consultation_requests, function($req) {
    return $req['status'] === 'rejected';
});

// Get patient's medicine requests
$medicine_requests = MedicineRequest::getPatientRequests($patient_id);

// Filter to show only medicine requests (approved, rejected, pending)
$approved_medicines = array_filter($medicine_requests, function($req) {
    return $req['status'] === 'approved';
});
$rejected_medicines = array_filter($medicine_requests, function($req) {
    return $req['status'] === 'rejected';
});

// Get patient's medical records
$records = MedicalRecord::getPatientRecords($patient_id);

// Handle record deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $record_id = (int)$_GET['delete'];
    
    // Verify record belongs to this patient
    $stmt = $conn->prepare("SELECT patient_id FROM medical_records WHERE id = ?");
    $stmt->bind_param('i', $record_id);
    $stmt->execute();
    $check = $stmt->get_result();
    
    if ($check->num_rows > 0 && $check->fetch_assoc()['patient_id'] == $patient_id) {
        $stmt = $conn->prepare("DELETE FROM medical_records WHERE id = ?");
        $stmt->bind_param('i', $record_id);
        if ($stmt->execute()) {
            $success = 'Medical record deleted successfully';
            $records = MedicalRecord::getPatientRecords($patient_id);
        } else {
            $error = 'Failed to delete record: ' . $conn->error;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Medical Records - BHC System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .container-main {
            max-width: 1000px;
            margin-top: 40px;
            margin-bottom: 40px;
        }
        .header-section {
            margin-bottom: 30px;
        }
        .header-section h1 {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .header-section p {
            color: #666;
            margin-bottom: 0;
        }
        .patient-info {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .patient-info h3 {
            color: #dc3545;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .info-item {
            border-left: 3px solid #dc3545;
            padding-left: 15px;
        }
        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            font-weight: 600;
        }
        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
            margin-top: 5px;
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
        .card-body {
            padding: 25px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #dc3545 100%);
            color: white;
        }
        .btn-secondary {
            padding: 10px 25px;
            font-weight: 600;
            border-radius: 8px;
        }
        .btn-edit {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
        }
        .btn-edit:hover {
            background: #0056b3;
            color: white;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 5px;
        }
        .btn-delete:hover {
            background: #c82333;
            color: white;
        }
        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }
        .back-link {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .record-item {
            background: #f9f9f9;
            border-left: 4px solid #dc3545;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        .record-date {
            color: #999;
            font-size: 12px;
        }
        .record-field {
            margin-bottom: 12px;
        }
        .record-field-label {
            font-weight: 600;
            color: #333;
            font-size: 13px;
            margin-bottom: 5px;
        }
        .record-field-value {
            color: #666;
            font-size: 14px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background: #f9f9f9;
            border-top: none;
            font-weight: 600;
            color: #333;
            padding: 15px;
        }
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
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
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            .record-header {
                flex-direction: column;
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
                        <span class="nav-link"><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container container-main">
        <!-- Navigation Buttons -->
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <a href="../dashboard.php" class="btn btn-info btn-sm" onclick="localStorage.setItem('activeTab', 'records'); localStorage.setItem('activeSubTab', 'patients-tab');"><i class="fas fa-users"></i> View in Patients Tab</a>
        </div>

        <!-- Header -->
        <div class="header-section">
            <h1><i class="fas fa-file-medical"></i> Patient Medical Records</h1>
            <p>View and manage medical records for <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Patient Information -->
        <div class="patient-info">
            <h3><i class="fas fa-user-circle"></i> Patient Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['phone'] ?? 'N/A'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($patient['email']); ?></div>
                </div>
            </div>
        </div>

        <!-- Approved Consultations (Scheduled) -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-stethoscope"></i> Scheduled Consultations
                <span class="badge bg-danger" style="float: right;"><?php echo count($approved_consultations); ?> Scheduled</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($approved_consultations)): ?>
                    <div class="empty-state" style="margin: 0;">
                        <i class="fas fa-inbox"></i>
                        <h5>No Scheduled Consultations</h5>
                        <p>No approved consultations scheduled for this patient</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px;">
                        <?php foreach ($approved_consultations as $request): ?>
                            <div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px solid #e9ecef;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div>
                                        <h5 style="margin: 0; color: #dc3545;"><i class="fas fa-stethoscope"></i> Consultation #<?php echo $request['id']; ?></h5>
                                        <small style="color: #999;"><i class="fas fa-calendar"></i> Scheduled for: <?php echo date('M d, Y', strtotime($request['preferred_date'])); ?></small>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-list"></i> Consultation Type</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($request['consultation_type']); ?></p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-user-md"></i> Doctor</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;">
                                            <?php echo (!empty($request['first_name']) ? htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) : '<span style="color: #999;">Not Assigned</span>'); ?>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-align-left"></i> Description</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                                </div>

                                <?php if (!empty($request['admin_notes'])): ?>
                                    <div style="margin-top: 15px;">
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-sticky-note"></i> Admin Notes</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <style>
                            div:last-of-type {
                                border-bottom: none !important;
                            }
                        </style>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rejected Consultation Requests -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-times-circle"></i> Rejected Consultation Requests
                <span class="badge bg-danger" style="float: right;"><?php echo count($rejected_consultations); ?> Rejected</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($rejected_consultations)): ?>
                    <div class="empty-state" style="margin: 0;">
                        <i class="fas fa-inbox"></i>
                        <h5>No Rejected Requests</h5>
                        <p>No consultation requests have been rejected</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px;">
                        <?php foreach ($rejected_consultations as $request): ?>
                            <div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px solid #e9ecef;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div>
                                        <h5 style="margin: 0; color: #dc3545;"><i class="fas fa-stethoscope"></i> Consultation #<?php echo $request['id']; ?></h5>
                                        <small style="color: #999;"><i class="fas fa-calendar"></i> Requested: <?php echo date('M d, Y g:i A', strtotime($request['requested_at'])); ?></small>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-list"></i> Consultation Type</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($request['consultation_type']); ?></p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-user-md"></i> Doctor</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;">
                                            <?php echo (!empty($request['first_name']) ? htmlspecialchars($request['first_name'] . ' ' . $request['last_name']) : '<span style="color: #999;">Not Assigned</span>'); ?>
                                        </p>
                                    </div>
                                </div>

                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-align-left"></i> Description</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                                </div>

                                <?php if (!empty($request['admin_notes'])): ?>
                                    <div style="margin-top: 15px;">
                                        <label style="font-weight: 600; color: #d32f2f; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-exclamation-circle"></i> Rejection Details</label>
                                        <p style="color: #d32f2f; font-size: 14px; margin: 0; padding: 10px; background: #ffebee; border-radius: 5px; border-left: 3px solid #d32f2f;"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <style>
                            div:last-of-type {
                                border-bottom: none !important;
                            }
                        </style>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Medicine Requests -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-pills"></i> Medicine Requests
                <span class="badge bg-danger" style="float: right;"><?php echo count($medicine_requests); ?> Requests</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($medicine_requests)): ?>
                    <div class="empty-state" style="margin: 0;">
                        <i class="fas fa-inbox"></i>
                        <h5>No Medicine Requests</h5>
                        <p>No medicine requests submitted by this patient</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px;">
                        <?php foreach ($medicine_requests as $request): ?>
                            <div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px solid #e9ecef;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div>
                                        <h5 style="margin: 0; color: #dc3545;"><i class="fas fa-pills"></i> Request #<?php echo $request['id']; ?></h5>
                                        <small style="color: #999;"><i class="fas fa-calendar"></i> Requested: <?php echo date('M d, Y g:i A', strtotime($request['requested_at'])); ?></small>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-prescription-bottle"></i> Medicine Name</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($request['medicine_name']); ?></p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-cubes"></i> Quantity</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($request['quantity']); ?></p>
                                    </div>
                                </div>

                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-align-left"></i> Reason</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                                </div>

                                <?php if (!empty($request['admin_notes'])): ?>
                                    <div style="margin-top: 15px;">
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-sticky-note"></i> Admin Notes</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <style>
                            div:last-of-type {
                                border-bottom: none !important;
                            }
                        </style>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rejected Medicine Requests -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-times-circle"></i> Rejected Medicine Requests
                <span class="badge bg-danger" style="float: right;"><?php echo count($rejected_medicines); ?> Rejected</span>
            </div>
            <div class="card-body" style="padding: 0;">
                <?php if (empty($rejected_medicines)): ?>
                    <div class="empty-state" style="margin: 0;">
                        <i class="fas fa-inbox"></i>
                        <h5>No Rejected Requests</h5>
                        <p>No medicine requests have been rejected</p>
                    </div>
                <?php else: ?>
                    <div style="padding: 20px;">
                        <?php foreach ($rejected_medicines as $request): ?>
                            <div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 2px solid #e9ecef;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <div>
                                        <h5 style="margin: 0; color: #dc3545;"><i class="fas fa-pills"></i> Request #<?php echo $request['id']; ?></h5>
                                        <small style="color: #999;"><i class="fas fa-calendar"></i> Requested: <?php echo date('M d, Y g:i A', strtotime($request['requested_at'])); ?></small>
                                    </div>
                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                        <?php echo ucfirst($request['status']); ?>
                                    </span>
                                </div>

                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-prescription-bottle"></i> Medicine Name</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($request['medicine_name']); ?></p>
                                    </div>
                                    <div>
                                        <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-cubes"></i> Quantity</label>
                                        <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($request['quantity']); ?></p>
                                    </div>
                                </div>

                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-align-left"></i> Reason</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></p>
                                </div>

                                <?php if (!empty($request['admin_notes'])): ?>
                                    <div style="margin-top: 15px;">
                                        <label style="font-weight: 600; color: #d32f2f; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-exclamation-circle"></i> Rejection Details</label>
                                        <p style="color: #d32f2f; font-size: 14px; margin: 0; padding: 10px; background: #ffebee; border-radius: 5px; border-left: 3px solid #d32f2f;"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <style>
                            div:last-of-type {
                                border-bottom: none !important;
                            }
                        </style>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Medical Records -->
        <div class="card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <i class="fas fa-file-medical"></i> Medical Records
                    <span class="badge bg-danger"><?php echo count($records); ?> Records</span>
                </div>
                <a href="../records/create_record.php" class="btn btn-sm btn-success" style="background: #28a745; border: none;">
                    <i class="fas fa-plus-circle"></i> Add Record
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($records)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>No Medical Records</h5>
                        <p>No medical records available for this patient</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($records as $record): ?>
                        <div style="margin-bottom: 30px; padding-bottom: 30px; border-bottom: 2px solid #e9ecef;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <div>
                                    <h5 style="margin: 0; color: #dc3545;"><i class="fas fa-file-medical"></i> Record #<?php echo $record['id']; ?></h5>
                                    <small style="color: #999;"><i class="fas fa-calendar"></i> <?php echo date('M d, Y g:i A', strtotime($record['record_date'])); ?></small>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="../records/view_record.php?id=<?php echo $record['id']; ?>" class="btn btn-sm btn-edit" title="Edit Record">
                                        <i class="fas fa-edit"></i> Modify
                                    </a>
                                    <a href="?id=<?php echo $patient_id; ?>&delete=<?php echo $record['id']; ?>" class="btn btn-sm btn-delete" onclick="return confirm('Are you sure you want to delete this record?');" title="Delete Record">
                                        <i class="fas fa-trash"></i> Remove
                                    </a>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-diagnoses"></i> Diagnosis</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo htmlspecialchars($record['diagnosis'] ?? 'N/A'); ?></p>
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-symptoms"></i> Symptoms</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($record['symptoms'] ?? 'N/A')); ?></p>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 15px;">
                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-prescription-bottle"></i> Treatment</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($record['treatment'] ?? 'N/A')); ?></p>
                                </div>
                                <div>
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-pills"></i> Prescriptions</label>
                                    <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($record['prescriptions'] ?? 'N/A')); ?></p>
                                </div>
                            </div>

                            <div>
                                <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 5px; display: block;"><i class="fas fa-note-medical"></i> Additional Notes</label>
                                <p style="color: #666; font-size: 14px; margin: 0; padding: 10px; background: #f9f9f9; border-radius: 5px; border-left: 3px solid #dc3545;"><?php echo nl2br(htmlspecialchars($record['notes'] ?? 'N/A')); ?></p>
                            </div>

                            <?php if ($record['followup_required']): ?>
                                <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 5px; border-left: 4px solid #ffc107;">
                                    <label style="font-weight: 600; color: #333; font-size: 13px; margin-bottom: 8px; display: block;"><i class="fas fa-calendar-check" style="color: #ffc107;"></i> Follow-up Checkup Scheduled</label>
                                    <p style="color: #666; font-size: 14px; margin: 5px 0 0 0;">
                                        <strong>Date:</strong> <?php echo date('l, F d, Y', strtotime($record['followup_date'])); ?><br>
                                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($record['followup_time'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <!-- Remove last border -->
                    <style>
                        div:last-of-type {
                            border-bottom: none !important;
                        }
                    </style>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>