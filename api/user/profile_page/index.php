<?php
require_once __DIR__ . '/includes/db.php';
include __DIR__ . '/includes/header.php';

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

<!-- Firefox Scroll-Snap Fix: Invisible anchor at the top of the viewport -->
<div class="snap-section" style="position: absolute; top: 0; height: 1px; width: 1px; visibility: hidden;"></div>

<!-- Hero Section -->
<section class="hero snap-section">
    <div class="container">
        <h1 class="hero-title">
            Building the Future <br> with Excellence & Integrity
        </h1>
        <p class="hero-description">
            A CIDB-registered G7 contractor providing end-to-end construction services with a focus on safe delivery and transparent communication.
        </p>
        <div class="hero-btns">
            <a href="contact.php" class="btn">Start a Project</a>
            <a href="about.php" class="btn btn-outline">Learn More</a>
        </div>
        <a href="#main-content" class="scroll-down-arrow">
            <i class="fas fa-chevron-down"></i>
        </a>
    </div>
</section>

<main class="main-content" id="main-content">
    <!-- Features / Services Teaser -->
    <section class="section-padding snap-section">
        <div class="container">
            <div class="features-intro">
                <h2 class="section-title">Why Choose Priority Horizon?</h2>
                <p class="section-subtitle">Delivering construction excellence with precision and reliability.</p>
            </div>

            <!-- Introduction Section -->
            <div class="features-intro">
                <p class="features-intro-text">
                    <strong>Priority Horizon</strong> provides end-to-end construction services, from site preparation and structural works to finishing and scheduled maintenance.
                    As a <strong>CIDB-registered G7 contractor</strong>, we focus on safe delivery, transparent communication, and neat workmanship—suitable for residential, commercial, and light civil projects.
                </p>
            </div>

        </div><!-- End Container -->

        <div class="feature-panels">
            <!-- Feature 1 -->
            <div class="feature-panel" style="background-image: url('../../../user/profile_page/assets/images/safety.webp');">
                <div class="feature-content">
                    <h3 class="panel-title">Safety First</h3>
                    <p class="panel-desc">We prioritize the safety of our team and clients in every project we undertake.</p>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="feature-panel" style="background-image: url('../../../user/profile_page/assets/images/quality.webp');">
                <div class="feature-content">
                    <h3 class="panel-title">Quality Construction</h3>
                    <p class="panel-desc">Using the best materials and practices to ensure structural integrity and longevity.</p>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="feature-panel" style="background-image: url('../../../user/profile_page/assets/images/delivery.webp');">
                <div class="feature-content">
                    <h3 class="panel-title">Timely Delivery</h3>
                    <p class="panel-desc">Committed to completing projects on schedule without compromising on quality.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects Section -->
    <section class="section-padding snap-section bg-light">
        <div class="container">
            <h2 class="section-title text-center mb-40">Featured Projects</h2>
        </div><!-- End Initial Container -->

        <div class="feature-panels">
            <?php if (count($projects) > 0): ?>
                <?php foreach ($projects as $project): ?>
                    <div class="feature-panel project-panel" style="background-image: url('<?php 
                        $img = !empty($project['image_path']) ? htmlspecialchars($project['image_path']) : '../../../assets/user/profile_page/assets/images/cta-bg.webp'; 
                        // If it's a relative path to assets, prefix it to reach the root
                        if (strpos($img, 'assets/') === 0) { $img = '../../../assets/user/profile_page/' . $img; }
                        echo $img; 
                    ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
                        <div class="feature-content project-content">
                            <h3 class="panel-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="panel-desc"><?php echo htmlspecialchars($project['client']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="container">
                    <p class="text-center card-desc">No projects to display at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="container text-center mt-60">
            <a href="services.php" class="btn btn-outline">Other Services</a>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-padding cta-section snap-section">
        <div class="container text-center">
            <h2 class="section-title mb-20">Ready to build your next project?</h2>
            <p class="card-desc mb-40">Partner with Priority Horizon for reliability and construction excellence.</p>
            <a href="contact.php" class="btn btn-white">Get in Touch</a>
        </div>
    </section>

<?php include __DIR__ . '/includes/footer.php'; ?>
</main>
