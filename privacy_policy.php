<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - CLINICare | Barangay Health Center</title>
    <link href="bootstrap-5.3.8-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="fontawesome-free-7.1.0-web/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: white;
            color: #333;
            overflow-x: hidden;
        }

        html {
            scroll-behavior: smooth;
        }

        /* Navbar */
        .navbar {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
            padding: 15px 20px !important;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            color: white !important;
            font-weight: 700;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.5px;
        }

        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            padding: 8px 12px !important;
            margin: 0 5px;
            transition: all 0.3s ease;
            border-radius: 5px;
            font-size: 15px;
        }

        .navbar-nav .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.2);
            color: white !important;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            padding: 60px 20px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 500px;
            height: 500px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .hero-content h1 {
            font-size: 48px;
            font-weight: 700;
            color: white;
            margin-bottom: 15px;
            line-height: 1.3;
            position: relative;
            z-index: 1;
        }

        .hero-content p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.9);
            position: relative;
            z-index: 1;
        }

        /* Main Content */
        .policy-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 60px 20px;
        }

        .policy-section {
            margin-bottom: 50px;
        }

        .policy-section h2 {
            color: #b00020;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 3px solid #dc3545;
        }

        .policy-section h3 {
            color: #333;
            font-weight: 700;
            font-size: 20px;
            margin-top: 25px;
            margin-bottom: 15px;
        }

        .policy-section p {
            color: #555;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 15px;
            text-align: justify;
        }

        .policy-section ul {
            color: #555;
            font-size: 16px;
            line-height: 1.8;
            margin-left: 25px;
            margin-bottom: 15px;
        }

        .policy-section ul li {
            margin-bottom: 10px;
        }

        .policy-section ol {
            color: #555;
            font-size: 16px;
            line-height: 1.8;
            margin-left: 25px;
            margin-bottom: 15px;
        }

        .policy-section ol li {
            margin-bottom: 10px;
        }

        .highlight-box {
            background: #fff5f5;
            border-left: 4px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .highlight-box strong {
            color: #b00020;
        }

        /* Table Styles */
        .table {
            margin: 20px 0;
            border-collapse: collapse;
        }

        .table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #dc3545;
        }

        .table th {
            color: #b00020;
            font-weight: 700;
            padding: 15px;
            text-align: left;
        }

        .table td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }

        .table tr:hover {
            background: #f9f9f9;
        }

        /* Contact Info */
        .contact-info {
            background: linear-gradient(135deg, #f5f7fa 0%, #fff 100%);
            border: 2px solid #dc3545;
            border-radius: 12px;
            padding: 30px;
            margin-top: 30px;
        }

        .contact-info h3 {
            color: #b00020;
            margin-top: 0;
        }

        .contact-info p {
            color: #555;
            margin-bottom: 10px;
        }

        .contact-info a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 600;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        /* Footer */
        .footer {
            background: #1a1a1a;
            color: white;
            padding: 30px 20px;
            text-align: center;
            font-size: 14px;
            margin-top: 60px;
        }

        /* Table of Contents */
        .toc {
            background: #f5f5f5;
            border: 2px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 40px;
        }

        .toc h3 {
            color: #b00020;
            margin-top: 0;
        }

        .toc ul {
            margin-left: 20px;
        }

        .toc ul li {
            margin-bottom: 8px;
        }

        .toc a {
            color: #dc3545;
            text-decoration: none;
            font-weight: 500;
        }

        .toc a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 32px;
            }

            .hero-content p {
                font-size: 16px;
            }

            .policy-container {
                padding: 30px 15px;
            }

            .policy-section h2 {
                font-size: 22px;
            }

            .policy-section h3 {
                font-size: 18px;
            }

            .policy-section p {
                font-size: 15px;
            }
        }

        .last-updated {
            color: #999;
            font-size: 14px;
            font-style: italic;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid px-3 px-md-4">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-heartbeat"></i> CLINICare
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#services-section">Services</a></li>
                    <li class="nav-item"><a class="nav-link" href="index.php#contact-section">Contact</a></li>
                    <?php if (Auth::isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="index.php"><i class="fas fa-user"></i> My Account</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-content">
            <h1>Privacy Policy</h1>
            <p>Your privacy and data protection is our top priority</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="policy-container">
        <div class="last-updated">Last Updated: November 29, 2024</div>

        <!-- Table of Contents -->
        <div class="toc">
            <h3><i class="fas fa-list"></i> Table of Contents</h3>
            <ul>
                <li><a href="#introduction">Introduction</a></li>
                <li><a href="#information-collection">Information We Collect</a></li>
                <li><a href="#how-we-use">How We Use Your Information</a></li>
                <li><a href="#data-sharing">Data Sharing and Disclosure</a></li>
                <li><a href="#data-security">Data Security</a></li>
                <li><a href="#user-rights">Your Rights</a></li>
                <li><a href="#cookies">Cookies and Tracking</a></li>
                <li><a href="#retention">Data Retention</a></li>
                <li><a href="#children">Children's Privacy</a></li>
                <li><a href="#changes">Changes to This Policy</a></li>
                <li><a href="#contact">Contact Us</a></li>
            </ul>
        </div>

        <!-- Introduction -->
        <div class="policy-section" id="introduction">
            <h2><i class="fas fa-shield-alt"></i> Introduction</h2>
            <p>
                Welcome to CLINICare, the Barangay Health Center's appointment and medical records management system. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our website and services.
            </p>
            <p>
                We are committed to protecting your privacy and ensuring you have a positive experience on our platform. Please read this Privacy Policy carefully. If you do not agree with our policies and practices, please do not use our Services.
            </p>
            <div class="highlight-box">
                <strong><i class="fas fa-exclamation-circle"></i> Important Notice:</strong> By accessing and using CLINICare, you acknowledge that you have read and understood this Privacy Policy and agree to its terms.
            </div>
        </div>

        <!-- Information Collection -->
        <div class="policy-section" id="information-collection">
            <h2><i class="fas fa-database"></i> Information We Collect</h2>

            <h3>1. Personal Information You Provide</h3>
            <p>We collect information you voluntarily provide, including:</p>
            <ul>
                <li><strong>Account Registration:</strong> Full name, email address, phone number, date of birth, gender, address</li>
                <li><strong>Medical Information:</strong> Medical history, current medications, allergies, health conditions, symptoms</li>
                <li><strong>Appointment Data:</strong> Preferred dates, times, consultation types, doctor preferences</li>
                <li><strong>Medicine Requests:</strong> Requested medications, quantities, reasons for request</li>
                <li><strong>Contact Information:</strong> Messages sent through our contact form</li>
                <li><strong>Payment Information:</strong> If applicable, billing and payment details (processed securely)</li>
            </ul>

            <h3>2. Automatically Collected Information</h3>
            <p>When you use our services, we automatically collect certain information:</p>
            <ul>
                <li><strong>Device Information:</strong> Device type, operating system, browser type, IP address</li>
                <li><strong>Usage Information:</strong> Pages visited, time spent, links clicked, referring URL</li>
                <li><strong>Location Information:</strong> General location data (not precise GPS tracking)</li>
                <li><strong>Cookies and Similar Technologies:</strong> Session data, user preferences, authentication tokens</li>
            </ul>

            <h3>3. Information from Third Parties</h3>
            <p>We may receive information about you from:</p>
            <ul>
                <li>Healthcare providers and medical facilities</li>
                <li>Government health databases (with proper authorization)</li>
                <li>Family members or authorized representatives</li>
                <li>Emergency contacts you designate</li>
            </ul>
        </div>

        <!-- How We Use Information -->
        <div class="policy-section" id="how-we-use">
            <h2><i class="fas fa-cogs"></i> How We Use Your Information</h2>
            <p>We use the information we collect for various purposes:</p>

            <h3>Healthcare Services</h3>
            <ul>
                <li>Scheduling and managing appointments</li>
                <li>Providing medical consultations and treatment</li>
                <li>Maintaining medical records and health history</li>
                <li>Processing medicine requests and prescriptions</li>
                <li>Emergency medical care and communication</li>
            </ul>

            <h3>Account Management</h3>
            <ul>
                <li>Creating and maintaining your account</li>
                <li>Verifying your identity</li>
                <li>Processing account requests and updates</li>
                <li>Sending account notifications and confirmations</li>
            </ul>

            <h3>Communication</h3>
            <ul>
                <li>Appointment reminders and updates</li>
                <li>Health and wellness notifications</li>
                <li>Responding to your inquiries</li>
                <li>Important service announcements</li>
            </ul>

            <h3>Improvement and Analytics</h3>
            <ul>
                <li>Improving our services and user experience</li>
                <li>Analyzing usage patterns and trends</li>
                <li>Conducting research and development</li>
                <li>Technical troubleshooting and support</li>
            </ul>

            <h3>Legal and Compliance</h3>
            <ul>
                <li>Complying with legal obligations</li>
                <li>Protecting against fraud and security threats</li>
                <li>Enforcing our terms and conditions</li>
                <li>Protecting the rights and safety of users</li>
            </ul>
        </div>

        <!-- Data Sharing and Disclosure -->
        <div class="policy-section" id="data-sharing">
            <h2><i class="fas fa-share-alt"></i> Data Sharing and Disclosure</h2>

            <h3>We Share Your Information With:</h3>
            <ul>
                <li><strong>Healthcare Professionals:</strong> Doctors, nurses, and medical staff necessary for your care</li>
                <li><strong>Authorized Family Members:</strong> Only those you've designated for medical decisions</li>
                <li><strong>Service Providers:</strong> Third parties who assist in operating our platform (hosting, email, analytics)</li>
                <li><strong>Government Health Authorities:</strong> When required by law or public health needs</li>
                <li><strong>Insurance Providers:</strong> With your consent, for insurance claims</li>
            </ul>

            <h3>We Do NOT Share Your Information With:</h3>
            <ul>
                <li>Marketing companies or advertisers</li>
                <li>Data brokers or commercial third parties</li>
                <li>Unrelated healthcare providers</li>
                <li>Social media platforms (without explicit consent)</li>
                <li>Any entity without legal authorization</li>
            </ul>

            <h3>Legal Requirements</h3>
            <p>
                We may disclose your information when required by law, court order, government request, or when we believe in good faith that such disclosure is necessary to:
            </p>
            <ul>
                <li>Comply with legal obligations</li>
                <li>Protect against legal liability</li>
                <li>Prevent fraud or security violations</li>
                <li>Protect the health and safety of individuals</li>
            </ul>

            <div class="highlight-box">
                <strong>Data Privacy Act of 2012 Compliance:</strong> We handle all medical information in compliance with applicable healthcare privacy regulations and similar local healthcare laws.
            </div>
        </div>

        <!-- Data Security -->
        <div class="policy-section" id="data-security">
            <h2><i class="fas fa-lock"></i> Data Security</h2>
            <p>
                Your security is extremely important to us. We implement multiple layers of technical, administrative, and physical security measures to protect your personal information.
            </p>

            <h3>Security Measures We Implement</h3>
            <ul>
                <li><strong>Encryption:</strong> SSL/TLS encryption for data in transit</li>
                <li><strong>Password Security:</strong> Hashed password storage with strong encryption algorithms</li>
                <li><strong>Access Control:</strong> Role-based access restrictions (staff, doctors, patients)</li>
                <li><strong>Firewalls:</strong> Network security protocols and firewall protection</li>
                <li><strong>Regular Updates:</strong> Frequent security patches and software updates</li>
                <li><strong>Staff Training:</strong> Regular privacy and security training for employees</li>
                <li><strong>Monitoring:</strong> Continuous system monitoring for suspicious activity</li>
                <li><strong>Backup Systems:</strong> Regular data backups for disaster recovery</li>
            </ul>

            <h3>What You Should Do</h3>
            <ul>
                <li>Keep your password confidential and strong</li>
                <li>Log out after using shared computers</li>
                <li>Never share your login credentials</li>
                <li>Report suspicious activity immediately</li>
                <li>Use secure internet connections when accessing your account</li>
            </ul>

            <div class="highlight-box">
                <strong>Important:</strong> While we implement comprehensive security measures, no method of transmission over the Internet is 100% secure. We cannot guarantee absolute security of your information.
            </div>
        </div>

        <!-- User Rights -->
        <div class="policy-section" id="user-rights">
            <h2><i class="fas fa-user-check"></i> Your Rights</h2>
            <p>You have the following rights regarding your personal information:</p>

            <h3>1. Right to Access</h3>
            <p>You have the right to request and access a copy of your personal information that we hold about you at any time.</p>

            <h3>2. Right to Correction</h3>
            <p>You can request that we correct, update, or amend any inaccurate or incomplete information in your account.</p>

            <h3>3. Right to Deletion</h3>
            <p>You may request the deletion of your account and associated personal information, subject to legal retention requirements.</p>

            <h3>4. Right to Withdraw Consent</h3>
            <p>You can withdraw your consent for specific data processing activities at any time.</p>

            <h3>5. Right to Data Portability</h3>
            <p>You have the right to receive your data in a structured, commonly used, and machine-readable format.</p>

            <h3>6. Right to Privacy</h3>
            <p>You have the right to expect that your medical information remains confidential and private.</p>

            <h3>How to Exercise Your Rights</h3>
            <p>To exercise any of these rights, please contact us using the information provided in the "Contact Us" section below. We will respond to your request within 30 days.</p>
        </div>

        <!-- Cookies and Tracking -->
        <div class="policy-section" id="cookies">
            <h2><i class="fas fa-cookie"></i> Cookies and Tracking Technologies</h2>

            <h3>What Are Cookies?</h3>
            <p>
                Cookies are small data files stored on your device that help us remember your preferences, keep you logged in, and improve your experience.
            </p>

            <h3>Types of Cookies We Use</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Cookie Type</th>
                        <th>Purpose</th>
                        <th>Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Session Cookies</strong></td>
                        <td>Keep you logged in during your visit</td>
                        <td>Until browser closes</td>
                    </tr>
                    <tr>
                        <td><strong>Preference Cookies</strong></td>
                        <td>Remember your settings and preferences</td>
                        <td>Up to 1 year</td>
                    </tr>
                    <tr>
                        <td><strong>Analytics Cookies</strong></td>
                        <td>Track usage to improve services</td>
                        <td>Up to 2 years</td>
                    </tr>
                    <tr>
                        <td><strong>Security Cookies</strong></td>
                        <td>Prevent unauthorized access and fraud</td>
                        <td>Until browser closes</td>
                    </tr>
                </tbody>
            </table>

            <h3>Managing Cookies</h3>
            <p>
                Most web browsers allow you to control cookies through settings. You can:
            </p>
            <ul>
                <li>Accept or decline cookies</li>
                <li>Delete existing cookies</li>
                <li>Receive alerts before cookies are stored</li>
            </ul>
            <p>
                Note: Disabling cookies may affect the functionality of our platform and your user experience.
            </p>

            <h3>Other Tracking Technologies</h3>
            <p>We may use other technologies such as:</p>
            <ul>
                <li><strong>Web Beacons:</strong> Small graphics that track page usage</li>
                <li><strong>Pixel Tags:</strong> Track the effectiveness of communications</li>
                <li><strong>Log Files:</strong> Track server activity and user behavior</li>
            </ul>
        </div>

        <!-- Data Retention -->
        <div class="policy-section" id="retention">
            <h2><i class="fas fa-history"></i> Data Retention</h2>
            <p>We retain your personal information for as long as necessary to provide our services and fulfill the purposes outlined in this Privacy Policy.</p>

            <h3>Retention Schedule</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Information Type</th>
                        <th>Retention Period</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Medical Records</strong></td>
                        <td>10+ years after last visit</td>
                        <td>Legal requirement for healthcare records</td>
                    </tr>
                    <tr>
                        <td><strong>Appointment History</strong></td>
                        <td>7 years</td>
                        <td>Healthcare regulatory compliance</td>
                    </tr>
                    <tr>
                        <td><strong>Account Information</strong></td>
                        <td>Until account deletion</td>
                        <td>Account maintenance and service delivery</td>
                    </tr>
                    <tr>
                        <td><strong>Payment Records</strong></td>
                        <td>7 years</td>
                        <td>Financial and tax compliance</td>
                    </tr>
                    <tr>
                        <td><strong>Session/Log Data</strong></td>
                        <td>90 days</td>
                        <td>Security and troubleshooting</td>
                    </tr>
                </tbody>
            </table>

            <h3>After Retention Period</h3>
            <p>
                Once the retention period expires, we securely delete or anonymize your information unless we are required to retain it by law.
            </p>
        </div>

        <!-- Children's Privacy -->
        <div class="policy-section" id="children">
            <h2><i class="fas fa-child"></i> Children's Privacy</h2>
            <p>
                CLINICare is not intended for children under the age of 18. We do not knowingly collect personal information from children without verifiable parental consent.
            </p>

            <h3>For Children Under 18</h3>
            <p>
                If a child requires our services, a parent, guardian, or authorized representative must create the account and provide consent. They will have access to the child's medical information.
            </p>

            <h3>Parental Rights</h3>
            <p>
                Parents or guardians of minors have the right to:
            </p>
            <ul>
                <li>Review their child's account information</li>
                <li>Request correction of information</li>
                <li>Request deletion of the account</li>
                <li>Prevent further collection of information</li>
            </ul>

            <h3>If We Discover Unauthorized Child Access</h3>
            <p>
                If we discover that a child has created an account without parental consent, we will delete the account and any associated information immediately.
            </p>
        </div>

        <!-- Changes to This Policy -->
        <div class="policy-section" id="changes">
            <h2><i class="fas fa-sync-alt"></i> Changes to This Privacy Policy</h2>
            <p>
                We may update this Privacy Policy from time to time to reflect changes in our practices, technology, legal requirements, or other factors. We will notify you of any material changes by:
            </p>
            <ul>
                <li>Posting the updated policy on our website</li>
                <li>Updating the "Last Updated" date at the top of this page</li>
                <li>Sending you an email notification (for significant changes)</li>
                <li>Requesting your consent (if required by law)</li>
            </ul>

            <h3>Your Continued Use</h3>
            <p>
                Your continued use of CLINICare after changes become effective constitutes your acceptance of the updated Privacy Policy.
            </p>
        </div>

        <!-- Contact Us -->
        <div class="policy-section" id="contact">
            <h2><i class="fas fa-envelope"></i> Contact Us</h2>
            <p>
                If you have questions, concerns, or requests regarding this Privacy Policy or our privacy practices, please contact us:
            </p>

            <div class="contact-info">
                <h3><i class="fas fa-hospital"></i> CLINICare - Barangay Health Center</h3>
                <p>
                    <strong><i class="fas fa-map-marker-alt"></i> Address:</strong><br>
                    Barangay Health Center<br>
                    City of Dasmariñas, Cavite, Philippines
                </p>
                <p>
                    <strong><i class="fas fa-envelope"></i> Email:</strong><br>
                    <a href="mailto:privacy@clinicare.com">privacy@clinicare.com</a><br>
                    <a href="mailto:support@clinicare.com">support@clinicare.com</a>
                </p>
                <p>
                    <strong><i class="fas fa-phone"></i> Phone:</strong><br>
                    (+63) 912 345 6789 (Office Hours: Mon-Fri, 8AM-5PM)
                </p>
                <p>
                    <strong><i class="fas fa-clock"></i> Response Time:</strong><br>
                    We aim to respond to all privacy inquiries within 3-5 business days.
                </p>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="policy-section">
            <h2><i class="fas fa-info-circle"></i> Additional Information</h2>

            <h3>Data Protection Authority</h3>
            <p>
                If you are not satisfied with how we handle your personal information, you have the right to lodge a complaint with the relevant data protection authority in your jurisdiction.
            </p>

            <h3>Your Responsibility</h3>
            <p>
                While we take extensive steps to protect your privacy, you are also responsible for:
            </p>
            <ul>
                <li>Maintaining confidentiality of your account credentials</li>
                <li>Reporting any suspicious activity immediately</li>
                <li>Keeping your contact information up to date</li>
                <li>Being cautious with shared devices</li>
            </ul>

            <h3>Third-Party Links</h3>
            <p>
                CLINICare may contain links to third-party websites. We are not responsible for their privacy practices. Please review their privacy policies before providing your information.
            </p>

            <h3>National Privacy Rights (NPR)</h3>
            <p>
                If you are a Filipino residing here in Philippines, you have additional rights under the  1987 Constitution and Republic Act No. 10173, also known as the <a href="https://privacy.gov.ph/data-privacy-act/" title="click this to view the page" target="_blank">Data Privacy Act of 2012</a>. This includes the right to know, delete, and opt-out of the sale of your personal information.
            </p>

            <div class="highlight-box">
                <strong>Acknowledgment:</strong> By using CLINICare, you acknowledge that you have read, understood, and agree to be bound by this Privacy Policy.
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 Barangay Health Center. All rights reserved.</p>
        <p style="margin-top: 10px; font-size: 12px;">
            <a href="privacy_policy.php" style="color: #dc3545; text-decoration: none; margin: 0 10px;">Privacy Policy</a> |
            <a href="index.php" style="color: #dc3545; text-decoration: none; margin: 0 10px;">Home</a> |
            <a href="index.php#contact-section" style="color: #dc3545; text-decoration: none; margin: 0 10px;">Contact</a>
        </p>
    </div>

    <script src="bootstrap-5.3.8-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for table of contents links
        document.querySelectorAll('.toc a').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
</body>
</html>
