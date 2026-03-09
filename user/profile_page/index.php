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
<section class="hero snap-section">
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

            <div class="common-grid-3">
                <!-- Feature 1 -->
                <div class="glass-card feature-card-padding">
                    <i class="fas fa-hard-hat feature-icon"></i>
                    <h3 class="card-title">Safety First</h3>
                    <p class="card-desc">We prioritize the safety of our team and clients in every project we undertake.</p>
                </div>

                <!-- Feature 2 -->
                <div class="glass-card feature-card-padding">
                    <i class="fas fa-tools feature-icon"></i>
                    <h3 class="card-title">Quality Construction</h3>
                    <p class="card-desc">Using the best materials and practices to ensure structural integrity and longevity.</p>
                </div>

                <!-- Feature 3 -->
                <div class="glass-card feature-card-padding">
                    <i class="fas fa-clock feature-icon"></i>
                    <h3 class="card-title">Timely Delivery</h3>
                    <p class="card-desc">Committed to completing projects on schedule without compromising on quality.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Projects Section -->
    <section class="section-padding snap-section">
        <div class="container">
            <h2 class="section-title text-center mb-40">Featured Projects</h2>
            <div class="common-grid-3">
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <div class="glass-card project-card-padding">
                            <div class="project-img-container">
                                <?php if (!empty($project['image_path'])): ?>
                                    <img src="<?php echo htmlspecialchars($project['image_path']); ?>" alt="<?php echo htmlspecialchars($project['title']); ?>" class="project-img">
                                <?php else: ?>
                                    <i class="fas <?php echo strpos($project['title'], 'Office') !== false ? 'fa-building' : (strpos($project['title'], 'Road') !== false ? 'fa-road' : 'fa-home'); ?> project-placeholder-icon"></i>
                                <?php endif; ?>
                            </div>
                            <h3 class="card-title project-title"><?php echo htmlspecialchars($project['title']); ?></h3>
                            <p class="project-client"><?php echo htmlspecialchars($project['client']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center card-desc" style="grid-column: span 3;">No projects to display at the moment.</p>
                <?php endif; ?>
            </div>
            <div class="text-center mt-20">
                <a href="services.php" class="btn btn-outline">View Services</a>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section-padding cta-section snap-section">
        <div class="container text-center">
            <h2 class="section-title mb-20">Ready to build your next project?</h2>
            <p class="card-desc mb-40">Partner with Priority Horizon for reliability and construction excellence.</p>
            <a href="contact.php" class="btn">Get in Touch</a>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</main>