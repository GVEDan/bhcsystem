<?php
require_once 'includes/config.php';
require_once 'includes/email_config.php';
require_once 'includes/EmailSender.php';
require_once 'includes/ContactMessage.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    error_log("Contact form submitted - Name: " . $name . ", Email: " . $email);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        error_log("Contact form: Missing fields");
        header('Location: index.php?contact=error&msg=missing_fields');
        exit();
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Contact form: Invalid email - " . $email);
        header('Location: index.php?contact=error&msg=invalid_email');
        exit();
    }
    
    // Sanitize inputs
    $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    
    try {
        // Save to database
        error_log("Attempting to save contact message to database");
        if (!ContactMessage::save($name, $email, $subject, $message)) {
            throw new Exception("Failed to save message to database");
        }
        error_log("Contact message saved successfully");
        
        // Send email to admin
        try {
            error_log("Attempting to send email notification");
            $emailSender = new EmailSender();
            $emailSender->sendContactFormToAdmin($name, $email, $subject, $message);
            error_log("Email sent successfully");
        } catch (Exception $e) {
            error_log("Contact form email error: " . $e->getMessage());
            // Don't throw - email failure shouldn't stop the form submission
        }
        
        // Redirect with success message
        error_log("Redirecting to success page");
        header('Location: index.php?contact=success');
        exit();
        
    } catch (Exception $e) {
        // Log the error and redirect
        error_log('Contact form error: ' . $e->getMessage());
        error_log('Stack trace: ' . $e->getTraceAsString());
        header('Location: index.php?contact=error&msg=send_failed');
        exit();
    }
} else {
    // Direct access not allowed
    header('Location: index.php');
    exit();
}
?>
