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
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <header>
        <div class="container">
            <nav>
                <a href="index.php" class="logo"><img src="assets/images/PHSB logo.png" alt="Priority Horizon Logo" class="logo-img">Priority<span>Horizon</span></a>

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

                    const icon = menuToggle.querySelector('i');
                    const header = document.querySelector('header');
                    const hero = document.querySelector('.hero');

                    if (navLinks.classList.contains('active')) {
                        // Open Menu
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');

                        // Prevent scrolling and compensate for scrollbar width
                        const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
                        document.body.style.overflow = 'hidden';
                        document.body.style.paddingRight = scrollbarWidth + 'px';
                        if (header) header.style.paddingRight = scrollbarWidth + 'px';
                        if (hero) hero.style.paddingRight = scrollbarWidth + 'px';

                    } else {
                        // Close Menu
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');

                        // Restore scrolling and remove padding
                        document.body.style.overflow = '';
                        document.body.style.paddingRight = '';
                        if (header) header.style.paddingRight = '';
                        if (hero) hero.style.paddingRight = '';
                    }
                });

                // Close menu when clicking overlay
                navOverlay.addEventListener('click', function() {
                    closeMenu();
                });

                // Close menu when clicking a link
                navLinks.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        closeMenu();
                    });
                });

                function closeMenu() {
                    navLinks.classList.remove('active');
                    navOverlay.classList.remove('active');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');

                    // Restore scrolling and padding
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    const header = document.querySelector('header');
                    const hero = document.querySelector('.hero');
                    if (header) header.style.paddingRight = '';
                    if (hero) hero.style.paddingRight = '';
                }
            }
        });
    </script>