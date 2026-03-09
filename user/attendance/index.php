<?php
session_start();
// Debugging session
// echo "Session ID: " . session_id() . "<br>";
// echo "User ID in session: " . ($_SESSION['user_id'] ?? 'Not set') . "<br>";

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// Auto-login check via device token
if (isset($_COOKIE['device_token'])) {
    require_once 'db_connect.php';
    $token = $_COOKIE['device_token'];
    $stmt = $pdo->prepare("SELECT e.* FROM employees e JOIN device_tokens dt ON e.id = dt.employee_id WHERE dt.token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        header("Location: dashboard.php");
        exit;
    }
}
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login - Attendance System</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container">
        <h1>PHSB Attendance</h1>
        <p style="margin-bottom: 2rem; color: #64748b;">Please login to your account</p>

        <?php if ($error): ?>
            <?php if ($error === 'already_signed_in'): ?>
                <script>
                    alert('this account has already been signed in');
                </script>
                <div class="error-msg">This account is already active on another device.</div>
            <?php else: ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="auth.php" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Enter username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
</body>

</html>