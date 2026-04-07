<?php
session_start();
require_once __DIR__ . '/../../user/attendance/db_connect.php';
require_once __DIR__ . '/../../user/profile_page/includes/mail_config.php';
require_once __DIR__ . '/../../user/profile_page/includes/PHPMailer/Exception.php';
require_once __DIR__ . '/../../user/profile_page/includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../user/profile_page/includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';

    if (!empty($username)) {
        try {
            // Check if admin user exists
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate OTP
                $otp = sprintf("%06d", mt_rand(0, 999999));
                $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                // Save OTP to the NEW separate table
                // First, clear any existing codes for this username to keep it clean
                $clearStmt = $pdo->prepare("DELETE FROM admin_password_resets WHERE username = ?");
                $clearStmt->execute([$username]);

                $insertStmt = $pdo->prepare("INSERT INTO admin_password_resets (username, otp_code, otp_expires_at) VALUES (?, ?, ?)");
                $insertStmt->execute([$username, $otp, $expires_at]);

                // Send Email to COMPANY email (SMTP_USER)
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = SMTP_PORT;

                    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                    $mail->addAddress(SMTP_USER); // Centered company email

                    $mail->isHTML(true);
                    $mail->Subject = 'Admin Password Reset OTP';
                    $mail->Body    = "
                        <h3>Password Reset Request</h3>
                        <p>An administrator (<strong>$username</strong>) has requested to reset their password. Your OTP code is:</p>
                        <h2 style='letter-spacing: 5px; background: #f4f4f4; padding: 10px; display: inline-block;'>$otp</h2>
                        <p>This code will expire in 15 minutes.</p>
                        <p>If you did not request this, please ignore this email.</p>
                    ";
                    $mail->AltBody = "An administrator ($username) requested a password reset. Your OTP code is: $otp. It expires in 15 minutes.";

                    $mail->send();
                    $_SESSION['reset_username'] = $username;
                    header('Location: verify-otp.php');
                    exit;
                } catch (Exception $e) {
                    $error = "Error sending OTP: " . $mail->ErrorInfo;
                }
            } else {
                $error = "Username not found.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter your username.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --accent-color: #171717;
            --text-color: #171717;
            --text-muted: #737373;
            --bg-color: #fafafa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            border: 1px solid #e5e5e5;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-color);
        }

        .logo span {
            color: var(--text-muted);
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #e5e5e5;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s;
            background: #fafafa;
        }

        .form-group input:focus {
            border-color: var(--accent-color);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background: var(--accent-color);
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-login:hover {
            opacity: 0.9;
        }

        .error-msg {
            background: #fef2f2;
            color: #991b1b;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.85rem;
            text-align: center;
            border: 1px solid #fee2e2;
        }

        .back-to-site {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-site a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.85rem;
        }

        .back-to-site a:hover {
            color: var(--accent-color);
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="logo">Forgot <span>Password</span></div>
        <p style="text-align: center; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 25px;">Enter your username to receive an OTP on the company email.</p>

        <?php if (isset($error)): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter username" required>
            </div>
            <button type="submit" class="btn-login">Send OTP</button>
        </form>

        <div class="back-to-site">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>

</html>
