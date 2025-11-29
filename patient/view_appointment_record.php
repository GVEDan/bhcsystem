<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requirePatient();

$user = Auth::getCurrentUser();
$appointment_id = (int)($_GET['id'] ?? 0);

// Get appointment details
$appointment_query = "SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, a.notes, 
                            d.first_name as doctor_first, d.last_name as doctor_last, d.specialization 
                            FROM appointments a 
                            LEFT JOIN doctors d ON a.doctor_id = d.id 
                            WHERE a.id = ? AND a.patient_id = ?";
$stmt = $conn->prepare($appointment_query);
$stmt->bind_param('ii', $appointment_id, $user['id']);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();

if (!$appointment) {
    header('Location: dashboard.php');
    exit();
}

// Get medical records for this appointment
$records_query = "SELECT * FROM medical_records WHERE appointment_id = ? ORDER BY record_date DESC";
$stmt = $conn->prepare($records_query);
$stmt->bind_param('i', $appointment_id);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - CLINICare</title>
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
        }
        .card-header h5 {
            margin: 0;
        }
        .detail-row {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            gap: 20px;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #dc3545;
            min-width: 150px;
        }
        .detail-value {
            color: #333;
            flex-grow: 1;
        }
        .status-badge {
            padding: 8px 12px;
            border-radius: 5px;
            font-weight: 600;
            display: inline-block;
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
        .record-section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #c82333 0%, #b81c2a 100%);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-heartbeat"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Appointment Details Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Appointment Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-calendar-alt"></i> Date & Time:</span>
                            <span class="detail-value">
                                <strong><?php echo htmlspecialchars(date('l, F d, Y', strtotime($appointment['appointment_date']))); ?></strong>
                                at <strong><?php echo htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))); ?></strong>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-user-md"></i> Doctor:</span>
                            <span class="detail-value">
                                <?php if (!empty($appointment['doctor_first'])): ?>
                                    Dr. <strong><?php echo htmlspecialchars($appointment['doctor_first'] . ' ' . $appointment['doctor_last']); ?></strong>
                                    <br><small class="text-muted">Specialization: <?php echo htmlspecialchars($appointment['specialization'] ?? 'Not specified'); ?></small>
                                <?php else: ?>
                                    <em class="text-muted">Doctor not assigned yet</em>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-clipboard"></i> Reason:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($appointment['reason']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-info-circle"></i> Status:</span>
                            <span class="detail-value">
                                <span class="status-badge status-<?php echo htmlspecialchars($appointment['status']); ?>">
                                    <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?>
                                </span>
                            </span>
                        </div>
                        <?php if (!empty($appointment['notes'])): ?>
                            <div class="detail-row">
                                <span class="detail-label"><i class="fas fa-sticky-note"></i> Notes:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($appointment['notes']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Medical Records Card -->
                <?php if (!empty($records)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-file-medical"></i> Medical Records</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($records as $record): ?>
                                <div class="record-section">
                                    <h6 class="mb-3">
                                        <i class="fas fa-clock"></i>
                                        Recorded on: <strong><?php echo htmlspecialchars(date('F d, Y g:i A', strtotime($record['record_date']))); ?></strong>
                                    </h6>
                                    
                                    <?php if (!empty($record['symptoms'])): ?>
                                        <div class="mb-3">
                                            <strong class="d-block mb-1"><i class="fas fa-stethoscope"></i> Symptoms:</strong>
                                            <p class="ms-3 mb-0"><?php echo htmlspecialchars($record['symptoms']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($record['diagnosis'])): ?>
                                        <div class="mb-3">
                                            <strong class="d-block mb-1"><i class="fas fa-diagnoses"></i> Diagnosis:</strong>
                                            <p class="ms-3 mb-0"><?php echo htmlspecialchars($record['diagnosis']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($record['treatment'])): ?>
                                        <div class="mb-3">
                                            <strong class="d-block mb-1"><i class="fas fa-pills"></i> Treatment:</strong>
                                            <p class="ms-3 mb-0"><?php echo htmlspecialchars($record['treatment']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($record['prescriptions'])): ?>
                                        <div class="mb-3">
                                            <strong class="d-block mb-1"><i class="fas fa-prescription-bottle"></i> Prescriptions:</strong>
                                            <p class="ms-3 mb-0"><?php echo nl2br(htmlspecialchars($record['prescriptions'])); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($record['notes'])): ?>
                                        <div class="mb-3">
                                            <strong class="d-block mb-1"><i class="fas fa-note-medical"></i> Additional Notes:</strong>
                                            <p class="ms-3 mb-0"><?php echo htmlspecialchars($record['notes']); ?></p>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($record['followup_required']): ?>
                                        <div class="mb-0 p-3 bg-light border-start border-4 border-warning">
                                            <strong class="d-block mb-2"><i class="fas fa-calendar-check text-warning"></i> Follow-up Checkup Required</strong>
                                            <p class="ms-3 mb-0">
                                                <strong>Scheduled Date:</strong> <?php echo htmlspecialchars(date('l, F d, Y', strtotime($record['followup_date']))); ?><br>
                                                <strong>Scheduled Time:</strong> <?php echo htmlspecialchars(date('g:i A', strtotime($record['followup_time']))); ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-file-medical"></i> Medical Records</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                No medical records for this appointment yet.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Back Button -->
                <div class="text-center mt-4">
                    <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
