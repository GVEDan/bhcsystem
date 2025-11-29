<?php
class EmailSender {
    
    private $mailer;
    private $config;
    
    public function __construct() {
        require_once __DIR__ . '/../PHPMailer-6.9.2/src/Exception.php';
        require_once __DIR__ . '/../PHPMailer-6.9.2/src/PHPMailer.php';
        require_once __DIR__ . '/../PHPMailer-6.9.2/src/SMTP.php';
        
        $this->config = EMAIL_CONFIG;
        $this->setupSMTP();
    }
    
    private function setupSMTP() {
        try {
            $this->mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = $this->config['smtp_auth'];
            $this->mailer->Username = $this->config['username'];
            $this->mailer->Password = $this->config['password'];
            $this->mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->config['smtp_port'];
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            throw new Exception("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    /**
     * Send Registration Confirmation Email
     * 
     * @param string $email - Recipient email
     * @param string $name - Recipient name
     * @param string $username - User username
     * @param string $password - User password (optional, for display only)
     * @return bool
     */
    public function sendRegistrationConfirmation($email, $name, $username = null, $password = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAllRecipients();
            
            // Recipients
            $this->mailer->setFrom($this->config['sender_email'], $this->config['sender_name']);
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Welcome to CLINICare - Registration Confirmation';
            
            $htmlBody = $this->getRegistrationTemplate($name, $username);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);
            
            return $this->mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Registration Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send Medical Record Notification Email
     * 
     * @param string $email - Patient email
     * @param string $patientName - Patient name
     * @param string $doctorName - Doctor name
     * @param string $recordDetails - Medical record details
     * @return bool
     */
    public function sendMedicalRecordNotification($email, $patientName, $doctorName, $recordDetails, $recordId = null) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAllRecipients();
            
            // Recipients
            $this->mailer->setFrom($this->config['sender_email'], $this->config['sender_name']);
            $this->mailer->addAddress($email, $patientName);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'New Medical Record Created - CLINICare';
            
            $htmlBody = $this->getMedicalRecordTemplate($patientName, $doctorName, $recordDetails, $recordId);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);
            
            return $this->mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Medical Record Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Send Contact Form Submission to Admin
     * 
     * @param string $name - Sender name
     * @param string $email - Sender email
     * @param string $subject - Message subject
     * @param string $message - Message body
     * @return bool
     */
    public function sendContactFormToAdmin($name, $email, $subject, $message) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAllRecipients();
            
            // Recipients
            $this->mailer->setFrom($email, $name);
            $this->mailer->addAddress($this->config['admin_email'], 'CLINICare Admin');
            $this->mailer->addReplyTo($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = "New Contact Form Submission: " . $subject;
            
            $htmlBody = $this->getContactFormTemplate($name, $email, $subject, $message);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);
            
            return $this->mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Contact Form Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }
    
    /**
     * Get registration confirmation email template
     */
    private function getRegistrationTemplate($name, $username, $password = null) {
        $loginLink = SITE_URL . 'login.php';
        
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f9f9f9; padding: 10px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; }
                .credentials { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
                .btn { display: inline-block; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to CLINICare!</h1>
                    <p>Barangay Health Center</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>$name</strong>,</p>
                    <p>Your account has been successfully registered! You can now access the CLINICare system to manage your health appointments and records.</p>
                    
                    <div class='credentials'>
                        <h3>Your Login Information:</h3>
                        <p><strong>Username:</strong> $username</p>";
        
        if ($password) {
            $html .= "<p><strong>Password:</strong> $password</p><p style='color: #dc3545; font-size: 12px;'><em>Please change this password on your first login.</em></p>";
        }
        
        $html .= "</div>
                    
                    <p><a href='$loginLink' class='btn'>Login to CLINICare</a></p>
                    
                    <p>If you have any questions or need assistance, please contact us.</p>
                    <p>Best regards,<br><strong>CLINICare Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Barangay Health Center. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Get medical record notification template
     */
    private function getMedicalRecordTemplate($patientName, $doctorName, $recordDetails, $recordId = null) {
        $viewRecordLink = $recordId 
            ? SITE_URL . 'patient/view_record.php?id=' . $recordId
            : SITE_URL . 'index.php';
        
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f9f9f9; padding: 10px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; }
                .record-info { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #28a745; margin: 15px 0; }
                .btn { display: inline-block; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Medical Record</h1>
                    <p>CLINICare - Barangay Health Center</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>$patientName</strong>,</p>
                    <p>A new medical record has been created for you by <strong>Dr. $doctorName</strong>.</p>
                    
                    <div class='record-info'>
                        <h3>Record Details:</h3>
                        <pre style='white-space: pre-wrap;'>$recordDetails</pre>
                    </div>
                    
                    <p>You can view your complete medical record details by clicking the button below.</p>
                    <p><a href='$viewRecordLink' class='btn'>View Your Record</a></p>
                    
                    <p>If you have any questions about your medical record, please contact us.</p>
                    <p>Best regards,<br><strong>CLINICare Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Barangay Health Center. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Send Password Reset Email
     * 
     * @param string $email - Recipient email
     * @param string $name - Recipient name
     * @param string $resetLink - Password reset link
     * @return bool
     */
    public function sendPasswordResetEmail($email, $name, $resetLink) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAllRecipients();
            
            // Recipients
            $this->mailer->setFrom($this->config['sender_email'], $this->config['sender_name']);
            $this->mailer->addAddress($email, $name);
            
            // Content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = 'Password Reset Request - CLINICare';
            
            $htmlBody = $this->getPasswordResetTemplate($name, $resetLink);
            $this->mailer->Body = $htmlBody;
            $this->mailer->AltBody = strip_tags($htmlBody);
            
            return $this->mailer->send();
        } catch (\PHPMailer\PHPMailer\Exception $e) {
            error_log("Password Reset Email Error: " . $this->mailer->ErrorInfo);
            error_log("PHPMailer Exception: " . $e->getMessage());
            return false;
        } catch (Exception $e) {
            error_log("Password Reset Email General Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get password reset email template
     */
    private function getPasswordResetTemplate($name, $resetLink) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f9f9f9; padding: 10px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #ddd; }
                .reset-box { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
                .btn { display: inline-block; background-color: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-top: 15px; }
                .warning { color: #dc3545; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                    <p>CLINICare - Barangay Health Center</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>$name</strong>,</p>
                    <p>We received a request to reset your password. Click the button below to reset your password:</p>
                    
                    <div class='reset-box'>
                        <p><a href='$resetLink' class='btn'>Reset Password</a></p>
                        <p style='margin-top: 15px; font-size: 12px;'>Or copy and paste this link in your browser:</p>
                        <p style='word-break: break-all; font-size: 12px;'>$resetLink</p>
                    </div>
                    
                    <p class='warning'><strong>Important:</strong> This link will expire in 1 hour.</p>
                    <p>If you didn't request this password reset, please ignore this email and your password will remain unchanged.</p>
                    <p>If you have any questions, please contact us.</p>
                    <p>Best regards,<br><strong>CLINICare Team</strong></p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Barangay Health Center. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
    
    /**
     * Get contact form template
     */
    private function getContactFormTemplate($name, $email, $subject, $message) {
        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
                .content { padding: 20px; }
                .footer { background-color: #f9f9f9; padding: 10px; text-align: center; font-size: 12px; color: #666; }
                .info-box { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #dc3545; margin: 15px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>New Contact Form Submission</h1>
                </div>
                <div class='content'>
                    <div class='info-box'>
                        <p><strong>From:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Subject:</strong> $subject</p>
                    </div>
                    
                    <h3>Message:</h3>
                    <p style='white-space: pre-wrap;'>$message</p>
                </div>
                <div class='footer'>
                    <p>Reply directly to $email to respond to this message.</p>
                </div>
            </div>
        </body>
        </html>";
        
        return $html;
    }
}
?>
