<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

Auth::requireAdmin();

// Get export type from query parameter
$export_type = $_GET['type'] ?? 'appointments';
$filename = '';
$data = [];

switch($export_type) {
    case 'users':
        $filename = 'patients_' . date('Y-m-d-H-i-s') . '.csv';
        $result = $conn->query("SELECT id, username, email, first_name, last_name, phone, created_at, status FROM users WHERE role = 'patient' ORDER BY created_at DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $headers = ['ID', 'Username', 'Email', 'First Name', 'Last Name', 'Phone', 'Registration Date', 'Status'];
        break;
        
    case 'appointments':
        $filename = 'appointments_' . date('Y-m-d-H-i-s') . '.csv';
        $result = $conn->query("SELECT a.id, CONCAT(u.first_name, ' ', u.last_name) as patient_name, u.email, u.phone, COALESCE(CONCAT(d.first_name, ' ', d.last_name), 'Not Assigned') as doctor_name, a.appointment_date, a.appointment_time, a.reason, a.status, a.created_at FROM appointments a JOIN users u ON a.patient_id = u.id LEFT JOIN doctors d ON a.doctor_id = d.id ORDER BY a.appointment_date DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $headers = ['ID', 'Patient Name', 'Email', 'Phone', 'Doctor Name', 'Appointment Date', 'Time', 'Reason', 'Status', 'Created Date'];
        break;
        
    case 'medicine_requests':
        $filename = 'medicine_requests_' . date('Y-m-d-H-i-s') . '.csv';
        $result = $conn->query("SELECT mr.id, CONCAT(u.first_name, ' ', u.last_name) as patient_name, u.email, u.phone, mr.medicine_name, mr.quantity, mr.reason, mr.status, mr.admin_notes, mr.requested_at FROM medicine_requests mr JOIN users u ON mr.patient_id = u.id ORDER BY mr.requested_at DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $headers = ['ID', 'Patient Name', 'Email', 'Phone', 'Medicine', 'Quantity', 'Reason', 'Status', 'Admin Notes', 'Requested Date'];
        break;
        
    case 'consultation_requests':
        $filename = 'consultation_requests_' . date('Y-m-d-H-i-s') . '.csv';
        $result = $conn->query("SELECT cr.id, CONCAT(u.first_name, ' ', u.last_name) as patient_name, u.email, u.phone, cr.consultation_type, COALESCE(CONCAT(d.first_name, ' ', d.last_name), 'Not Assigned') as doctor_name, cr.description, cr.preferred_date, cr.status, cr.admin_notes, cr.requested_at FROM consultation_requests cr JOIN users u ON cr.patient_id = u.id LEFT JOIN doctors d ON cr.doctor_id = d.id ORDER BY cr.requested_at DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $headers = ['ID', 'Patient Name', 'Email', 'Phone', 'Consultation Type', 'Doctor Name', 'Description', 'Preferred Date', 'Status', 'Admin Notes', 'Requested Date'];
        break;
        
    case 'medical_records':
        $filename = 'medical_records_' . date('Y-m-d-H-i-s') . '.csv';
        $result = $conn->query("SELECT mr.id, CONCAT(u.first_name, ' ', u.last_name) as patient_name, u.email, mr.diagnosis, mr.symptoms, mr.treatment, mr.prescriptions, mr.notes, mr.record_date FROM medical_records mr JOIN users u ON mr.patient_id = u.id ORDER BY mr.record_date DESC");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $headers = ['ID', 'Patient Name', 'Email', 'Diagnosis', 'Symptoms', 'Treatment', 'Prescriptions', 'Notes', 'Record Date'];
        break;
        
    default:
        die('Invalid export type');
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Open output stream
$output = fopen('php://output', 'w');

// Write header row
fputcsv($output, $headers);

// Write data rows
foreach ($data as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit();
?>
