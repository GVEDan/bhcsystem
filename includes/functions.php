<?php
require_once 'config.php';

class Appointment {
    public static function bookAppointment($patient_id, $doctor_id, $appointment_date, $appointment_time, $reason) {
        global $conn;
        
        if (empty($appointment_date) || empty($appointment_time) || empty($reason) || empty($doctor_id)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        // Check if slot is available for this doctor
        $check = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
        $stmt = $conn->prepare($check);
        $stmt->bind_param('iss', $doctor_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] >= 5) {
            return ['success' => false, 'message' => 'This time slot is full for this doctor'];
        }
        
        // Check if patient already has an appointment at this time
        $check_patient = "SELECT COUNT(*) as count FROM appointments WHERE patient_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
        $stmt = $conn->prepare($check_patient);
        $stmt->bind_param('iss', $patient_id, $appointment_date, $appointment_time);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            return ['success' => false, 'message' => 'You already have an appointment at this time'];
        }
        
        // Book appointment
        $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iisss', $patient_id, $doctor_id, $appointment_date, $appointment_time, $reason);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Appointment booked successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to book appointment'];
    }
    
    public static function getPatientAppointments($patient_id) {
        global $conn;
        
        $sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.reason, a.status, a.notes, d.first_name, d.last_name, d.specialization 
                FROM appointments a 
                LEFT JOIN doctors d ON a.doctor_id = d.id 
                WHERE a.patient_id = ? 
                ORDER BY a.appointment_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getAllAppointments() {
        global $conn;
        
        $sql = "SELECT a.id, a.patient_id, u.first_name, u.last_name, a.appointment_date, a.appointment_time, a.reason, a.status, a.notes, COALESCE(d.first_name, 'N/A') as doctor_first_name, COALESCE(d.last_name, '') as doctor_last_name, COALESCE(d.specialization, 'Not Assigned') as specialization
                FROM appointments a 
                JOIN users u ON a.patient_id = u.id 
                LEFT JOIN doctors d ON a.doctor_id = d.id 
                ORDER BY a.appointment_date DESC";
        
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function updateAppointmentStatus($appointment_id, $status) {
        global $conn;
        
        $sql = "UPDATE appointments SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $status, $appointment_id);
        
        return $stmt->execute();
    }
    
    public static function cancelAppointment($appointment_id) {
        return self::updateAppointmentStatus($appointment_id, 'cancelled');
    }
}

class MedicalRecord {
    public static function addRecord($patient_id, $appointment_id, $diagnosis, $symptoms, $treatment, $prescriptions, $notes, $vital_signs = [], $followup_required = 0, $followup_date = null, $followup_time = null) {
        global $conn;
        
        $vital_signs_json = json_encode($vital_signs);
        
        // Handle null appointment_id - convert to null for database to handle properly
        if (empty($appointment_id) || $appointment_id === 0 || $appointment_id === '0') {
            $appointment_id = null;
        } else {
            $appointment_id = (int)$appointment_id;
        }
        
        // Build dynamic query based on whether we have appointment_id
        if ($appointment_id === null) {
            $sql = "INSERT INTO medical_records (patient_id, diagnosis, symptoms, treatment, prescriptions, notes, vital_signs, followup_required, followup_date, followup_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('issssssiis', $patient_id, $diagnosis, $symptoms, $treatment, $prescriptions, $notes, $vital_signs_json, $followup_required, $followup_date, $followup_time);
        } else {
            $sql = "INSERT INTO medical_records (patient_id, appointment_id, diagnosis, symptoms, treatment, prescriptions, notes, vital_signs, followup_required, followup_date, followup_time) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iissssssiis', $patient_id, $appointment_id, $diagnosis, $symptoms, $treatment, $prescriptions, $notes, $vital_signs_json, $followup_required, $followup_date, $followup_time);
        }
        
        if ($stmt->execute()) {
            $record_id = $conn->insert_id;
            return ['success' => true, 'message' => 'Medical record created', 'record_id' => $record_id];
        }
        
        return ['success' => false, 'message' => 'Failed to create record: ' . $stmt->error];
    }
    
    public static function getPatientRecords($patient_id) {
        global $conn;
        
        $sql = "SELECT id, appointment_id, diagnosis, symptoms, treatment, prescriptions, notes, vital_signs, followup_required, followup_date, followup_time, record_date 
                FROM medical_records WHERE patient_id = ? ORDER BY record_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getRecord($record_id) {
        global $conn;
        
        $sql = "SELECT * FROM medical_records WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $record_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public static function updateRecord($record_id, $diagnosis, $symptoms, $treatment, $prescriptions, $notes) {
        global $conn;
        
        $sql = "UPDATE medical_records SET diagnosis = ?, symptoms = ?, treatment = ?, prescriptions = ?, notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssi', $diagnosis, $symptoms, $treatment, $prescriptions, $notes, $record_id);
        
        return $stmt->execute();
    }
}

class Doctor {
    public static function getAllDoctors() {
        global $conn;
        
        // Try to select with image column, fall back if it doesn't exist
        $sql = "SELECT id, first_name, last_name, specialization, phone, email, bio, COALESCE(image, '') as image, status FROM doctors WHERE status = 'active' ORDER BY first_name";
        $result = $conn->query($sql);
        
        // If query fails (column doesn't exist), try without image column
        if (!$result) {
            $sql = "SELECT id, first_name, last_name, specialization, phone, email, bio, status FROM doctors WHERE status = 'active' ORDER BY first_name";
            $result = $conn->query($sql);
        }
        
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getDoctorById($doctor_id) {
        global $conn;
        
        $sql = "SELECT * FROM doctors WHERE id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $doctor_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }
    
    public static function getDoctorAvailability($doctor_id) {
        global $conn;
        
        $sql = "SELECT day_of_week, start_time, end_time, is_available FROM doctor_availability WHERE doctor_id = ? AND is_available = TRUE ORDER BY day_of_week";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $doctor_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function isDoctorAvailableOnDay($doctor_id, $date) {
        global $conn;
        
        // Get day of week (0 = Sunday, 1 = Monday, etc.)
        $day_of_week = date('w', strtotime($date));
        
        $sql = "SELECT COUNT(*) as count FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ? AND is_available = TRUE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $doctor_id, $day_of_week);
        $stmt->execute();
        
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }
    
    public static function getAvailableDoctorsForSlot($appointment_date, $appointment_time) {
        global $conn;
        
        // Get all active doctors
        $doctors = self::getAllDoctors();
        $available = [];
        
        foreach ($doctors as $doctor) {
            // Check if doctor is available on this day
            if (!self::isDoctorAvailableOnDay($doctor['id'], $appointment_date)) {
                continue;
            }
            
            // Check how many appointments this doctor has at this time
            $check = "SELECT COUNT(*) as count FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status != 'cancelled'";
            $stmt = $conn->prepare($check);
            $stmt->bind_param('iss', $doctor['id'], $appointment_date, $appointment_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            // If less than 5 appointments at this slot, doctor is available
            if ($row['count'] < 5) {
                $doctor['available_slots'] = 5 - $row['count'];
                $available[] = $doctor;
            }
        }
        
        return $available;
    }
}

class MedicineRequest {
    public static function submitRequest($patient_id, $medicine_name, $quantity, $reason) {
        global $conn;
        
        if (empty($medicine_name) || empty($quantity) || empty($reason) || $quantity <= 0) {
            return ['success' => false, 'message' => 'All fields are required and quantity must be greater than 0'];
        }
        
        $sql = "INSERT INTO medicine_requests (patient_id, medicine_name, quantity, reason) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isss', $patient_id, $medicine_name, $quantity, $reason);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Medicine request submitted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to submit medicine request'];
    }
    
    public static function getPatientRequests($patient_id) {
        global $conn;
        
        $sql = "SELECT id, medicine_name, quantity, reason, status, admin_notes, requested_at, approved_at 
                FROM medicine_requests WHERE patient_id = ? ORDER BY requested_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getAllRequests() {
        global $conn;
        
        $sql = "SELECT mr.id, mr.patient_id, u.first_name, u.last_name, u.phone, mr.medicine_name, mr.quantity, mr.reason, mr.status, mr.admin_notes, mr.requested_at, mr.approved_at 
                FROM medicine_requests mr 
                JOIN users u ON mr.patient_id = u.id 
                WHERE mr.is_archived = FALSE
                ORDER BY mr.requested_at DESC";
        
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function updateStatus($request_id, $status, $admin_notes = '') {
        global $conn;
        
        $approved_at = ($status === 'approved') ? date('Y-m-d H:i:s') : null;
        
        $sql = "UPDATE medicine_requests SET status = ?, admin_notes = ?, approved_at = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssi', $status, $admin_notes, $approved_at, $request_id);
        
        return $stmt->execute();
    }
    
    public static function getStatistics() {
        global $conn;
        
        $stats = [];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM medicine_requests WHERE status = 'pending' AND is_archived = FALSE");
        $stats['pending'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM medicine_requests WHERE status = 'approved' AND is_archived = FALSE");
        $stats['approved'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM medicine_requests WHERE status = 'rejected' AND is_archived = FALSE");
        $stats['rejected'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
    
    public static function archive($request_id) {
        global $conn;
        
        $sql = "UPDATE medicine_requests SET is_archived = TRUE WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $request_id);
        
        return $stmt->execute();
    }
    
    public static function restore($request_id) {
        global $conn;
        
        $sql = "UPDATE medicine_requests SET is_archived = FALSE WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $request_id);
        
        return $stmt->execute();
    }
    
    public static function getArchivedRequests() {
        global $conn;
        
        $sql = "SELECT mr.id, mr.patient_id, u.first_name, u.last_name, u.phone, mr.medicine_name, mr.quantity, mr.reason, mr.status, mr.admin_notes, mr.requested_at, mr.approved_at 
                FROM medicine_requests mr 
                JOIN users u ON mr.patient_id = u.id 
                WHERE mr.is_archived = TRUE
                ORDER BY mr.requested_at DESC";
        
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}

class ConsultationRequest {
    public static function submitRequest($patient_id, $consultation_type, $description, $preferred_date = null, $doctor_id = null) {
        global $conn;
        
        if (empty($consultation_type) || empty($description)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        $preferred_date = (!empty($preferred_date)) ? $preferred_date : null;
        $doctor_id = (!empty($doctor_id) && $doctor_id !== 0) ? (int)$doctor_id : null;
        
        // Build dynamic query based on whether we have doctor_id
        if ($doctor_id === null) {
            $sql = "INSERT INTO consultation_requests (patient_id, consultation_type, description, preferred_date) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isss', $patient_id, $consultation_type, $description, $preferred_date);
        } else {
            $sql = "INSERT INTO consultation_requests (patient_id, doctor_id, consultation_type, description, preferred_date) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iisss', $patient_id, $doctor_id, $consultation_type, $description, $preferred_date);
        }
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Consultation request submitted successfully'];
        }
        
        return ['success' => false, 'message' => 'Failed to submit consultation request'];
    }
    
    public static function getPatientRequests($patient_id) {
        global $conn;
        
        $sql = "SELECT cr.id, cr.consultation_type, cr.description, cr.status, cr.preferred_date, cr.admin_notes, cr.requested_at, cr.approved_at, d.first_name, d.last_name, d.specialization 
                FROM consultation_requests cr 
                LEFT JOIN doctors d ON cr.doctor_id = d.id 
                WHERE cr.patient_id = ? 
                ORDER BY cr.requested_at DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $patient_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function getAllRequests() {
        global $conn;
        
        $sql = "SELECT cr.id, cr.patient_id, u.first_name, u.last_name, u.phone, cr.consultation_type, cr.description, cr.status, cr.preferred_date, cr.admin_notes, cr.requested_at, cr.approved_at, cr.doctor_id, CASE WHEN cr.doctor_id IS NULL THEN 'Any Available' ELSE CONCAT(d.first_name, ' ', d.last_name) END as doctor_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
                FROM consultation_requests cr 
                JOIN users u ON cr.patient_id = u.id 
                LEFT JOIN doctors d ON cr.doctor_id = d.id 
                WHERE cr.is_archived = FALSE
                ORDER BY cr.requested_at DESC";
        
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
    
    public static function updateStatus($request_id, $status, $admin_notes = '', $doctor_id = null, $preferred_date = null) {
        global $conn;
        
        $approved_at = ($status === 'approved') ? date('Y-m-d H:i:s') : null;
        $doctor_id = (!empty($doctor_id) && $doctor_id !== 0) ? (int)$doctor_id : null;
        $preferred_date = (!empty($preferred_date)) ? $preferred_date : null;
        
        $sql = "UPDATE consultation_requests SET status = ?, admin_notes = ?, approved_at = ?, doctor_id = ?, preferred_date = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssii', $status, $admin_notes, $approved_at, $doctor_id, $preferred_date, $request_id);
        
        return $stmt->execute();
    }
    
    public static function getStatistics() {
        global $conn;
        
        $stats = [];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'pending' AND is_archived = FALSE");
        $stats['pending'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'approved' AND is_archived = FALSE");
        $stats['approved'] = $result->fetch_assoc()['count'];
        
        $result = $conn->query("SELECT COUNT(*) as count FROM consultation_requests WHERE status = 'rejected' AND is_archived = FALSE");
        $stats['rejected'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
    
    public static function archive($request_id) {
        global $conn;
        
        $sql = "UPDATE consultation_requests SET is_archived = TRUE WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $request_id);
        
        return $stmt->execute();
    }
    
    public static function restore($request_id) {
        global $conn;
        
        $sql = "UPDATE consultation_requests SET is_archived = FALSE WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $request_id);
        
        return $stmt->execute();
    }
    
    public static function getArchivedRequests() {
        global $conn;
        
        $sql = "SELECT cr.id, cr.patient_id, u.first_name, u.last_name, u.phone, cr.consultation_type, cr.description, cr.status, cr.preferred_date, cr.admin_notes, cr.requested_at, cr.approved_at, cr.doctor_id, CASE WHEN cr.doctor_id IS NULL THEN 'Any Available' ELSE CONCAT(d.first_name, ' ', d.last_name) END as doctor_name, d.first_name as doctor_first_name, d.last_name as doctor_last_name 
                FROM consultation_requests cr 
                JOIN users u ON cr.patient_id = u.id 
                LEFT JOIN doctors d ON cr.doctor_id = d.id 
                WHERE cr.is_archived = TRUE
                ORDER BY cr.requested_at DESC";
        
        return $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    }
}
?>
