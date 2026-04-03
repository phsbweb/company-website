<?php
// Function to check active page
function isActive($page)
{
    $current_file = basename($_SERVER['PHP_SELF'], ".php");
    return $current_file == $page ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Priority Horizon | Construction Excellence</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="../../../assets/user/profile_page/assets/css/style.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo"><img src="../../../assets/user/profile_page/assets/images/PHSB logo.png" alt="Priority Horizon Logo" class="logo-img">Priority Horizon</a>

                <div class="menu-toggle" id="mobile-menu">
                    <i class="fas fa-bars"></i>
                </div>

                <div class="nav-overlay" id="nav-overlay"></div>

                <ul class="nav-links">
                    <li><a href="index.php" class="<?php echo isActive('index'); ?>">Home</a></li>
                    <li><a href="about.php" class="<?php echo isActive('about'); ?>">About</a></li>
                    <li><a href="services.php" class="<?php echo isActive('services'); ?>">Services</a></li>

                    <li><a href="contact.php" class="<?php echo isActive('contact'); ?>">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobile-menu');
            const navLinks = document.querySelector('.nav-links');
            const navOverlay = document.getElementById('nav-overlay');

            if (menuToggle && navLinks && navOverlay) {
                menuToggle.addEventListener('click', function() {
                    navLinks.classList.toggle('active');
                    navOverlay.classList.toggle('active');

                    // Toggle icon between bars and times
                    const icon = menuToggle.querySelector('i');
                    if (navLinks.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                        document.body.style.overflow = 'hidden'; // Prevent scrolling when menu is open
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                        document.body.style.overflow = '';
                    }
                });

                // Close menu when clicking overlay
                navOverlay.addEventListener('click', function() {
                    navLinks.classList.remove('active');
                    navOverlay.classList.remove('active');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');
                    document.body.style.overflow = '';
                });

                // Close menu when clicking a link
                navLinks.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        navLinks.classList.remove('active');
                        navOverlay.classList.remove('active');
                        menuToggle.querySelector('i').classList.remove('fa-times');
                        menuToggle.querySelector('i').classList.add('fa-bars');
                        document.body.style.overflow = '';
                    });
                });
            }
        });
    </script>