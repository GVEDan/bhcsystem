<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

Auth::requirePatient();

$user = Auth::getCurrentUser();

// Get all medical records for the patient
$stmt = $conn->prepare("
    SELECT mr.id, mr.diagnosis, mr.symptoms, mr.treatment, mr.record_date, 
           a.appointment_date, a.reason,
           d.first_name as doctor_first, d.last_name as doctor_last, d.specialization
    FROM medical_records mr
    LEFT JOIN appointments a ON mr.appointment_id = a.id
    LEFT JOIN doctors d ON a.doctor_id = d.id
    WHERE mr.patient_id = ?
    ORDER BY mr.record_date DESC
");
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - CLINICare</title>
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
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            transition: all 0.3s ease;
        }
        .nav-link:hover {
            color: white !important;
        }
        .logout-link {
            background: transparent !important;
            color: rgba(255, 255, 255, 0.9) !important;
            padding: 8px 12px !important;
            border: none !important;
            transition: all 0.3s ease;
        }
        .logout-link:hover {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }
        .record-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 15px;
            transition: all 0.3s ease;
            border-left: 5px solid #dc3545;
        }
        .record-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .record-card-body {
            padding: 20px;
        }
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .record-title {
            font-weight: 700;
            color: #333;
            font-size: 16px;
        }
        .record-date {
            color: #666;
            font-size: 13px;
        }
        .doctor-info {
            background: #f9f9f9;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 13px;
        }
        .doctor-name {
            font-weight: 600;
            color: #dc3545;
        }
        .diagnosis-badge {
            background: #f0f8ff;
            border-left: 3px solid #0066cc;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .record-preview {
            color: #666;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 12px;
        }
        .view-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            color: white;
            padding: 8px 20px;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .view-btn:hover {
            background: linear-gradient(135deg, #c82333 0%, #b81c2a 100%);
            color: white;
            text-decoration: none;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state-icon {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        .empty-state-title {
            font-size: 20px;
            font-weight: 700;
            color: #666;
            margin-bottom: 10px;
        }
        .empty-state-text {
            color: #999;
            margin-bottom: 30px;
        }
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }
        .page-subtitle {
            color: #666;
            margin-bottom: 30px;
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
                        <a href="../includes/logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <!-- Page Title -->
                <div class="mb-5">
                    <h1 class="page-title"><i class="fas fa-file-medical"></i> My Medical Records</h1>
                    <p class="page-subtitle">View and manage all your medical records created by doctors</p>
                </div>

                <!-- Records List -->
                <?php if (count($records) > 0): ?>
                    <?php foreach ($records as $record): ?>
                        <div class="record-card">
                            <div class="record-card-body">
                                <div class="record-header">
                                    <div>
                                        <div class="record-title">
                                            <i class="fas fa-stethoscope"></i> Medical Record #<?php echo $record['id']; ?>
                                        </div>
                                        <div class="record-date">
                                            <i class="fas fa-calendar"></i> 
                                            <?php echo htmlspecialchars(date('l, F d, Y', strtotime($record['record_date']))); ?> 
                                            at <?php echo htmlspecialchars(date('g:i A', strtotime($record['record_date']))); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Doctor Information -->
                                <?php if ($record['doctor_first']): ?>
                                    <div class="doctor-info">
                                        <i class="fas fa-user-md"></i> 
                                        <span class="doctor-name">Dr. <?php echo htmlspecialchars($record['doctor_first'] . ' ' . $record['doctor_last']); ?></span>
                                        <br>
                                        <small>Specialization: <?php echo htmlspecialchars($record['specialization']); ?></small>
                                    </div>
                                <?php endif; ?>

                                <!-- Diagnosis -->
                                <?php if ($record['diagnosis']): ?>
                                    <div class="diagnosis-badge">
                                        <strong><i class="fas fa-diagnoses"></i> Diagnosis:</strong> 
                                        <?php echo htmlspecialchars($record['diagnosis']); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Record Preview -->
                                <div class="record-preview">
                                    <?php if ($record['symptoms']): ?>
                                        <strong>Symptoms:</strong> <?php echo htmlspecialchars(substr($record['symptoms'], 0, 80)); ?>...
                                    <?php endif; ?>
                                </div>

                                <!-- View Button -->
                                <a href="view_record.php?id=<?php echo $record['id']; ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> View Full Record
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="empty-state-title">No Medical Records Yet</div>
                        <p class="empty-state-text">
                            Your medical records will appear here once doctors create them during appointments.
                        </p>
                        <a href="../index.php" class="btn btn-primary">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
