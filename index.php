<?php
include 'includes/db.php';
include 'includes/header.php';

$projects = [];

// Try to fetch featured projects
try {
    $stmt_feat = $pdo->query("
        SELECT p.* 
        FROM projects p
        INNER JOIN featured_projects fp ON p.id = fp.project_id
        ORDER BY fp.slot ASC 
        LIMIT 3
    ");
    if ($stmt_feat) {
        $projects = $stmt_feat->fetchAll();
    }
} catch (Exception $e) {
    // If table missing or query fails, $projects stays empty
}

// Fallback to latest 3 if no featured projects found or query failed
if (empty($projects)) {
    try {
        $stmt = $pdo->query("SELECT * FROM projects ORDER BY id DESC LIMIT 3");
        $projects = $stmt->fetchAll();
    } catch (Exception $e) {
        // Ultimate fallback: empty array
        $projects = [];
    }
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <h1 style="font-size: 3.5rem; margin-bottom: 20px; background: linear-gradient(to right, #171717, #525252); background-clip: text; -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
            Building the Future <br> with Excellence & Integrity
        </h1>
        <p style="font-size: 1.25rem; color: var(--text-muted); max-width: 800px; margin: 0 auto 40px;">
            A CIDB-registered G7 contractor providing end-to-end construction services with a focus on safe delivery and transparent communication.
        </p>
        <div class="hero-btns">
            <a href="contact.php" class="btn">Start a Project</a>
            <a href="about.php" class="btn btn-outline" style="margin-left: 20px;">Learn More</a>
        </div>
        <a href="#main-content" class="scroll-down-arrow">
            <i class="fas fa-chevron-down"></i>
        </a>
    </div>
</section>

<main class="main-content" id="main-content">
    <!-- Features / Services Teaser -->
    <section class="section-padding">
        <div class="container">
            <div style="text-align: center; margin-bottom: 60px;">
                <h2 style="font-size: 2.5rem; margin-bottom: 15px;">Why Choose Priority Horizon?</h2>
                <p style="color: var(--text-muted);">Delivering construction excellence with precision and reliability.</p>
            </div>

            <!-- Introduction Section -->
            <div style="margin-bottom: 60px; text-align: center; max-width: 850px; margin-left: auto; margin-right: auto;">
                <p style="font-size: 1.15rem; line-height: 1.8; color: var(--text-muted);">
                    <strong>Priority Horizon</strong> provides end-to-end construction services, from site preparation and structural works to finishing and scheduled maintenance.
                    As a <strong>CIDB-registered G7 contractor</strong>, we focus on safe delivery, transparent communication, and neat workmanship—suitable for residential, commercial, and light civil projects.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <!-- Feature 1 -->
                <div class="glass-card" style="padding: 40px;">
                    <i class="fas fa-hard-hat" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 15px;">Safety First</h3>
                    <p style="color: var(--text-muted);">We prioritize the safety of our team and clients in every project we undertake.</p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card" style="padding: 40px;">
                    <i class="fas fa-tools" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 15px;">Quality Construction</h3>
                    <p style="color: var(--text-muted);">Using the best materials and practices to ensure structural integrity and longevity.</p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card" style="padding: 40px;">
                    <i class="fas fa-clock" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 20px;"></i>
                    <h3 style="margin-bottom: 15px;">Timely Delivery</h3>
                    <p style="color: var(--text-muted);">Committed to completing projects on schedule without compromising on quality.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects Section -->
    <section class="section-padding">
        <div class="container">
            <h2 style="margin-bottom: 40px; text-align: center;">Featured Projects</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="glass-card" style="padding: 20px;">
                            <div style="height: 200px; background: #e5e5e5; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
                                <?php if (!empty($project['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas <?php echo strpos($project['title'], 'Office') !== false ? 'fa-building' : (strpos($project['title'], 'Road') !== false ? 'fa-road' : 'fa-home'); ?> " style="font-size: 3rem; color: #a3a3a3;"></i>
                                <?php endif; ?>
                            </div>
                            <h3 style="margin-bottom: 10px;"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p style="color: var(--text-muted);"><?php echo htmlspecialchars($project['client']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: var(--text-muted); grid-column: span 3;">No projects to display at the moment.</p>
                <?php endif; ?>
            </div>
            <div style="text-align: center; margin-top: 40px;">
                <a href="services.php" class="btn btn-outline">View Services</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-padding" style="background: rgba(163, 163, 163, 0.05);">
        <div class="container" style="text-align: center;">
            <h2 style="margin-bottom: 20px;">Ready to build your next project?</h2>
            <p style="color: var(--text-muted); margin-bottom: 40px;">Partner with Priority Horizon for reliability and construction excellence.</p>
            <a href="contact.php" class="btn">Get in Touch</a>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</main>

<script>
    function updateHeroOpacity() {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        const scrollPos = window.scrollY;
        const heroHeight = hero.offsetHeight;

        // Calculate opacity (reaches 0 when scrolled halfway through heroHeight)
        let opacity = 1 - (scrollPos / (heroHeight / 2));

        // Clamp opacity between 0 and 1
        if (opacity < 0) opacity = 0;
        if (opacity > 1) opacity = 1;

        hero.style.opacity = opacity;
    }

    window.addEventListener('scroll', updateHeroOpacity);
    window.addEventListener('DOMContentLoaded', function() {
        updateHeroOpacity();

        // Custom smooth scroll for the arrow
        const arrow = document.querySelector('.scroll-down-arrow');
        const target = document.querySelector('#main-content');

        if (arrow && target) {
            arrow.addEventListener('click', function(e) {
                e.preventDefault();
                const targetPosition = target.getBoundingClientRect().top + window.pageYOffset;
                const startPosition = window.pageYOffset;
                const distance = targetPosition - startPosition;
                const duration = 1200; // Slower duration (1.2 seconds)
                let start = null;

                window.requestAnimationFrame(step);

                function step(timestamp) {
                    if (!start) start = timestamp;
                    const progress = timestamp - start;
                    window.scrollTo(0, ease(progress, startPosition, distance, duration));
                    if (progress < duration) window.requestAnimationFrame(step);
                }

                // Quadratic easing - change this for different effects
                function ease(t, b, c, d) {
                    t /= d / 2;
                    if (t < 1) return c / 2 * t * t + b;
                    t--;
                    return -c / 2 * (t * (t - 2) - 1) + b;
                }
            });
        }
    });
</script>