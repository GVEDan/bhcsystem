<?php
/**
 * Email Configuration for CLINICare
 * 
 * IMPORTANT: Configure these settings to enable email functionality
 * 
 * For Gmail:
 * 1. Enable 2-Factor Authentication on your Google account
 * 2. Generate an App Password: https://myaccount.google.com/apppasswords
 * 3. Use the App Password (without spaces) in EMAIL_PASSWORD
 */

// Email Configuration
define('EMAIL_CONFIG', [
    // SMTP Server Settings
    'smtp_host'     => 'smtp.gmail.com',              // SMTP server (Gmail: smtp.gmail.com)
    'smtp_port'     => 587,                           // SMTP port (Gmail: 587 for TLS, 465 for SSL)
    'smtp_secure'   => 'tls',                         // 'tls' or 'ssl'
    'smtp_auth'     => true,                          // Enable SMTP authentication
    
    // Sender Email (Your clinic email)
    'sender_email'  => 'delacruz7306@gmail.com',          // UPDATE THIS: Your Gmail address
    'sender_name'   => 'CLINICare Barangay Health Center',
    
    // SMTP Credentials
    'username'      => 'delacruz7306@gmail.com',        // UPDATE THIS: Your Gmail address
    'password'      => 'gcrhypxwfgxewplu',           // UPDATE THIS: Your Gmail App Password (16 characters, no spaces)
    
    // Admin Email (for receiving contact form messages)
    'admin_email'   => 'delacruz7306@gmail.com',            // UPDATE THIS: Email to receive contact form messages
]);

/**
 * SETUP INSTRUCTIONS FOR GMAIL:
 * 
 * 1. Go to https://myaccount.google.com/security
 * 2. Enable 2-Step Verification
 * 3. Go to https://myaccount.google.com/apppasswords
 * 4. Select "Mail" and "Windows Computer"
 * 5. Copy the generated 16-character password
 * 6. Use this password in the 'password' field above (remove spaces)
 * 
 * IMPORTANT: Never commit this file with real credentials to version control!
 * Keep your app passwords secret and secure.
 */
?>
