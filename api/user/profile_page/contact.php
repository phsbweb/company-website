<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

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
                require_once __DIR__ . '/includes/mail_config.php';
                require_once __DIR__ . '/includes/PHPMailer/Exception.php';
                require_once __DIR__ . '/includes/PHPMailer/PHPMailer.php';
                require_once __DIR__ . '/includes/PHPMailer/SMTP.php';

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
<section class="page-header snap-section">
    <div class="container">
        <h1 class="ani-float-up page-title">Contact Us</h1>
        <p class="ani-float-up ani-delay-2 page-subtitle">Have a project in mind? Let's talk.</p>
        <a href="#main-content" class="scroll-down-arrow">
            <i class="fas fa-chevron-down"></i>
        </a>
    </div>
</section>

<main id="main-content">
    <section class="section-padding snap-section">
        <div class="container">
            <div class="contact-grid">

                <!-- Contact Info -->
                <div>
                    <h2 class="mb-30">Get in touch</h2>

                    <div class="mb-40">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt contact-icon"></i>
                            <div>
                                <h4 class="contact-label">Visit Us</h4>
                                <p class="card-desc">No. 12, Jalan IMP 1/1, <br>Pusat Teknologi Sinar Meranti,<br>Taman Industri Meranti Perdana,<br>47120 Puchong, Selangor Darul Ehsan.</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <i class="fas fa-envelope contact-icon"></i>
                            <div>
                                <h4 class="contact-label">Email Us</h4>
                                <p class="card-desc">priorityhorizon@gmail.com</p>
                            </div>
                        </div>

                        <div class="contact-item">
                            <i class="fas fa-phone-alt contact-icon"></i>
                            <div>
                                <h4 class="contact-label">Call Us</h4>
                                <p class="card-desc">+603-1234 5678<br>Mon-Fri, 9am - 6pm</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="glass-card feature-card-padding">
                    <?php if ($success): ?>
                        <div class="alert-success">
                            <i class="fas fa-check-circle mb-10" style="font-size: 2rem; display: block;"></i>
                            <h4 class="mb-10">Message Sent!</h4>
                            <p style="font-size: 0.9rem;">Thank you for reaching out. We'll get back to you shortly.</p>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert-error">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="contact.php" method="POST">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" name="name" placeholder="John Doe" required class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" placeholder="john@example.com" required class="form-input">
                            </div>
                        </div>

                        <div class="form-group mb-20">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" placeholder="Inquiry" class="form-input">
                        </div>

                        <div class="form-group mb-30">
                            <label class="form-label">Message</label>
                            <textarea name="message" rows="5" placeholder="Tell us about your topic..." required class="form-input form-textarea"></textarea>
                        </div>

                        <button type="submit" class="btn" style="width: 100%;">Send Message</button>
                    </form>
                </div>

            </div>
        </div>
    </section>

</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
