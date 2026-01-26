<?php

/**
 * Gmail SMTP Configuration
 * 
 * IMPORTANT: Use an App Password, not your regular Gmail password.
 * Instructions:
 * 1. Enable 2-Step Verification in Google Account.
 * 2. Go to Security > App Passwords.
 * 3. Select 'Mail' and 'Other (Custom name)'.
 * 4. Use the 16-character password generated.
 */

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 465); // SSL
define('SMTP_USER', 'priorityhorizon@gmail.com');
define('SMTP_PASS', 'YOUR_APP_PASSWORD_HERE'); // Replace with your 16-character App Password
define('SMTP_FROM', 'priorityhorizon@gmail.com');
define('SMTP_FROM_NAME', 'Priority Horizon Web');
