<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requirePatient();

$user = Auth::getCurrentUser();
$record_id = (int)($_GET['id'] ?? 0);

// Get medical record
$record = MedicalRecord::getRecord($record_id);

if (!$record || $record['patient_id'] != $user['id']) {
    header('Location: medical_records.php');
    exit();
}

// Get patient information
$stmt = $conn->prepare("SELECT id, first_name, last_name, email, phone FROM users WHERE id = ?");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Get appointment information if exists
$appointment = null;
if ($record['appointment_id']) {
    $stmt = $conn->prepare("SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, 
                                  d.first_name as doctor_first, d.last_name as doctor_last, d.specialization 
                          FROM appointments a 
                          LEFT JOIN doctors d ON a.doctor_id = d.id 
                          WHERE a.id = ?");
    $stmt->bind_param('i', $record['appointment_id']);
    $stmt->execute();
    $appointment = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Record - CLINICare</title>
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
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .record-info {
            background: #f0f8ff;
            border-left: 4px solid #0066cc;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .followup-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
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
                        <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Homepage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php"><i class="fas fa-user"></i> My Account</a>
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

                <!-- Medical Record Details Card -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-file-medical"></i> Medical Record #<?php echo $record['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <!-- Record Date Information -->
                        <div class="record-info">
                            <p class="mb-0"><strong>Record Created:</strong> <?php echo htmlspecialchars(date('l, F d, Y g:i A', strtotime($record['record_date']))); ?></p>
                        </div>

                        <!-- Appointment Information (if applicable) -->
                        <?php if ($appointment): ?>
                            <div class="detail-row">
                                <span class="detail-label"><i class="fas fa-calendar-check"></i> Related Appointment:</span>
                                <span class="detail-value">
                                    <strong><?php echo htmlspecialchars(date('l, F d, Y', strtotime($appointment['appointment_date']))); ?></strong>
                                    at <strong><?php echo htmlspecialchars(date('g:i A', strtotime($appointment['appointment_time']))); ?></strong><br>
                                    <small class="text-muted">Reason: <?php echo htmlspecialchars($appointment['reason']); ?></small>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Symptoms -->
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-stethoscope"></i> Symptoms:</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($record['symptoms'] ?? 'N/A')); ?></span>
                        </div>

                        <!-- Diagnosis -->
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-diagnoses"></i> Diagnosis:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($record['diagnosis'] ?? 'N/A'); ?></span>
                        </div>

                        <!-- Treatment -->
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-pills"></i> Treatment:</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($record['treatment'] ?? 'N/A')); ?></span>
                        </div>

                        <!-- Prescriptions -->
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-prescription-bottle"></i> Prescriptions:</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($record['prescriptions'] ?? 'N/A')); ?></span>
                        </div>

                        <!-- Notes -->
                        <div class="detail-row">
                            <span class="detail-label"><i class="fas fa-note-medical"></i> Notes:</span>
                            <span class="detail-value"><?php echo nl2br(htmlspecialchars($record['notes'] ?? 'N/A')); ?></span>
                        </div>

                        <!-- Follow-up Information -->
                        <?php if ($record['followup_required']): ?>
                            <div class="followup-info">
                                <h6 class="mb-2"><i class="fas fa-calendar-check" style="color: #ffc107;"></i> Follow-up Checkup Required</h6>
                                <p class="mb-0">
                                    <strong>Scheduled Date:</strong> <?php echo htmlspecialchars(date('l, F d, Y', strtotime($record['followup_date']))); ?><br>
                                    <strong>Scheduled Time:</strong> <?php echo htmlspecialchars(date('g:i A', strtotime($record['followup_time']))); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="text-center mt-4">
                    <a href="medical_records.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Medical Records</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
