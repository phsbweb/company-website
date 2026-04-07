<?php
require_once 'session_bootstrap.php';
attendanceStartSession();
if (!isset($_SESSION['user_id'])) {
    // If no session, try to re-hydrate from cookie (Vercel/Serverless fix)
    if (isset($_COOKIE['device_token'])) {
        require_once 'db_connect.php';
        $token = $_COOKIE['device_token'];
        $stmt = $pdo->prepare("SELECT e.* FROM employees e JOIN device_tokens dt ON e.id = dt.employee_id WHERE dt.token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
        } else {
            header("Location: index.php?trace=no_session");
            exit;
        }
    } else {
        header("Location: index.php?trace=no_session");
        exit;
    }
}

require_once 'db_connect.php';

// Fetch all active employees and their departments
$stmt = $pdo->query("
    SELECT e.full_name, e.username, d.name as dept_name 
    FROM employees e 
    LEFT JOIN departments d ON e.department_id = d.id 
    ORDER BY e.full_name ASC
");
$employees = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Directory - Attendance System</title>
    <link rel="stylesheet" href="../../../assets/user/attendance/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .directory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .employee-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
            text-align: left;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .employee-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .avatar {
            width: 50px;
            height: 50px;
            background: #eff6ff;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .emp-info h3 {
            font-size: 1rem;
            margin-bottom: 2px;
            color: #1e293b;
        }

        .emp-info p {
            font-size: 0.8rem;
            color: #64748b;
            margin: 0;
        }

        .dept-tag {
            display: inline-block;
            padding: 2px 8px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 5px;
        }

        .search-box {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            outline: none;
        }

        .search-box:focus {
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="container" style="max-width: 900px;">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <div style="text-align: left;">
                <h2 style="font-size: 1.25rem;">Employee Directory</h2>
                <p style="color: #64748b; font-size: 0.875rem;">Connect with your colleagues</p>
            </div>
            <a href="dashboard.php" style="text-decoration: none; color: var(--primary-color); font-weight: 500;"><i class="fas fa-arrow-left"></i> Back</a>
        </header>

        <div class="nav-tabs" style="display: flex; gap: 10px; margin-bottom: 2rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
            <a href="dashboard.php" class="nav-tab" style="padding: 0.5rem 1rem; text-decoration: none; color: #64748b; font-weight: 500; border-radius: 6px;">Attendance</a>
            <a href="leaves.php" class="nav-tab" style="padding: 0.5rem 1rem; text-decoration: none; color: #64748b; font-weight: 500; border-radius: 6px;">Leave Requests</a>
            <a href="directory.php" class="nav-tab active" style="padding: 0.5rem 1rem; text-decoration: none; color: var(--primary-color); font-weight: 500; border-radius: 6px; background: #f1f5f9;">Directory</a>
        </div>

        <input type="text" id="directorySearch" class="search-box" placeholder="Search by name or department..." onkeyup="filterDirectory()">

        <div class="directory-grid" id="directoryGrid">
            <?php foreach ($employees as $emp): ?>
                <div class="employee-card">
                    <div class="avatar">
                        <?php echo strtoupper(substr($emp['full_name'], 0, 1)); ?>
                    </div>
                    <div class="emp-info">
                        <h3><?php echo htmlspecialchars($emp['full_name']); ?></h3>
                        <p><i class="far fa-envelope"></i> <?php echo htmlspecialchars($emp['username']); ?>@company.com</p>
                        <span class="dept-tag"><?php echo htmlspecialchars($emp['dept_name'] ?? 'General'); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function filterDirectory() {
            const input = document.getElementById('directorySearch');
            const filter = input.value.toLowerCase();
            const grid = document.getElementById('directoryGrid');
            const cards = grid.getElementsByClassName('employee-card');

            for (let i = 0; i < cards.length; i++) {
                const name = cards[i].getElementsByTagName('h3')[0].innerText.toLowerCase();
                const dept = cards[i].getElementsByClassName('dept-tag')[0].innerText.toLowerCase();

                if (name.includes(filter) || dept.includes(filter)) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }
    </script>
    <script src="../../../assets/shared/nav-prefetch.js"></script>
</body>

</html>
