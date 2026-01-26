<?php
include 'includes/db.php';
include 'includes/header.php';

$success = false;
$error = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $date = date('Y-m-d H:i:s');

    if (!empty($name) && !empty($email) && !empty($message)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $subject, $message, $date])) {

                // --- SMTP Email Sending ---
                require 'includes/mail_config.php';
                require 'includes/PHPMailer/Exception.php';
                require 'includes/PHPMailer/PHPMailer.php';
                require 'includes/PHPMailer/SMTP.php';

                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = SMTP_PORT;

                    // Recipients
                    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                    $mail->addAddress(SMTP_USER); // Sending to company email
                    $mail->addReplyTo($email, $name);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = "New Inquiry: $subject";
                    $mail->Body    = "
                        <h3>New Contact Form Submission</h3>
                        <p><strong>Name:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Subject:</strong> $subject</p>
                        <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                        <p><small>Sent from Priority Horizon Website at $date</small></p>
                    ";
                    $mail->AltBody = "New Inquiry from $name ($email):\n\nMsg: $message";

                    $mail->send();
                    $success = true;
                } catch (Exception $e) {
                    // Message saved to DB but email failed
                    $error = "Message saved, but email notification failed. Error: {$mail->ErrorInfo}";
                }
                // --- End SMTP ---

            } else {
                $error = "Failed to save message. Please try again.";
            }
        } catch (Exception $e) {
            $error = "An error occurred: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<!-- Page Header -->
<section style="padding: 150px 0 80px; text-align: center;">
    <div class="container">
        <h1 class="ani-float-up" style="font-size: 3rem; margin-bottom: 20px;">Contact Us</h1>
        <p class="ani-float-up ani-delay-2" style="color: var(--text-muted); max-width: 600px; margin: 0 auto;">Have a project in mind? Let's talk.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px;">

            <!-- Contact Info -->
            <div>
                <h2 style="margin-bottom: 30px;">Get in touch</h2>

                <div style="margin-bottom: 40px;">
                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                        <i class="fas fa-map-marker-alt" style="color: var(--accent-color); font-size: 1.5rem; width: 24px; text-align: center; margin-top: 4px;"></i>
                        <div>
                            <h4 style="margin-bottom: 5px;">Visit Us</h4>
                            <p style="color: var(--text-muted);">No. 17, Jalan IMP 1/1, <br>Pusat Teknologi Sinar Meranti,<br>Taman Industri Meranti Perdana,<br>47120 Puchong, Selangor Darul Ehsan.</p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
                        <i class="fas fa-envelope" style="color: var(--accent-color); font-size: 1.5rem; width: 24px; text-align: center; margin-top: 4px;"></i>
                        <div>
                            <h4 style="margin-bottom: 5px;">Email Us</h4>
                            <p style="color: var(--text-muted);">priorityhorizon@gmail.com</p>
                        </div>
                    </div>

                    <div style="display: flex; gap: 20px;">
                        <i class="fas fa-phone-alt" style="color: var(--accent-color); font-size: 1.5rem; width: 24px; text-align: center; margin-top: 4px;"></i>
                        <div>
                            <h4 style="margin-bottom: 5px;">Call Us</h4>
                            <p style="color: var(--text-muted);">+603-1234 5678<br>Mon-Fri, 9am - 6pm</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="glass-card" style="padding: 40px;">
                <?php if ($success): ?>
                    <div style="background: #ecfdf5; color: #065f46; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #d1fae5;">
                        <i class="fas fa-check-circle" style="margin-bottom: 10px; font-size: 2rem; display: block;"></i>
                        <h4 style="margin-bottom: 5px;">Message Sent!</h4>
                        <p style="font-size: 0.9rem;">Thank you for reaching out. We'll get back to you shortly.</p>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div style="background: #fef2f2; color: #991b1b; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; border: 1px solid #fee2e2;">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form action="contact.php" method="POST">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 0.9rem; font-weight: 500;">Name</label>
                            <input type="text" name="name" placeholder="John Doe" required style="padding: 12px; background: #f4f4f4; border: 1px solid #f4f4f4; border-radius: 8px; color: #333; outline: none;">
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <label style="font-size: 0.9rem; font-weight: 500;">Email</label>
                            <input type="email" name="email" placeholder="john@example.com" required style="padding: 12px; background: #f4f4f4; border: 1px solid #f4f4f4; border-radius: 8px; color: #333; outline: none;">
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                        <label style="font-size: 0.9rem; font-weight: 500;">Subject</label>
                        <input type="text" name="subject" placeholder="Inquiry" style="padding: 12px; background: #f4f4f4; border: 1px solid #f4f4f4; border-radius: 8px; color: #333; outline: none;">
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 30px;">
                        <label style="font-size: 0.9rem; font-weight: 500;">Message</label>
                        <textarea name="message" rows="5" placeholder="Tell us about your topic..." required style="padding: 12px; background: #f4f4f4; border: 1px solid #f4f4f4; border-radius: 8px; color: #333; outline: none; resize: vertical;"></textarea>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>