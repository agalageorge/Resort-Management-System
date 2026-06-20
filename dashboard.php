<?php
declare(strict_types=1);
session_start();
require('../../include/database/mysql_db.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

/* =========================
   FETCH ADMIN NAME (only once)
========================= */
$userSql = "
    SELECT s.first_name, s.last_name
    FROM staff s
    JOIN users u ON s.staff_id = u.staff_id
    WHERE u.user_id = ?
    LIMIT 1
";
$stmt = $conn->prepare($userSql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc() ?: ['first_name' => 'User'];
$stmt->close();

/* =========================
   WEEKLY SALES (ALL)
========================= */
$weeklySql = "
    SELECT COALESCE(SUM(total), 0) AS weekly_total
    FROM sales
    WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
";
$stmt = $conn->prepare($weeklySql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$weekly_total = (float) ($result['weekly_total'] ?? 0);
$stmt->close();

/* =========================
   TOP SOLD ITEMS (ALL)
========================= */
$itemSql = "
    SELECT i.item_name, COALESCE(SUM(s.qty), 0) AS total_sold
    FROM sales s
    LEFT JOIN inventory i ON s.item_id = i.item_id
    GROUP BY s.item_id, i.item_name
    ORDER BY total_sold DESC
    LIMIT 8
";
$stmt = $conn->prepare($itemSql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->execute();
$itemResult = $stmt->get_result();
$itemNames = [];
$itemQty = [];
while ($row = $itemResult->fetch_assoc()) {
    $itemNames[] = $row['item_name'] ?: 'Unknown';
    $itemQty[] = (int)$row['total_sold'];
}
$stmt->close();

/* =========================
   PAYMENT METHODS (ALL)
========================= */
$paymentSql = "
    SELECT payment_type, COUNT(*) AS total
    FROM sales
    GROUP BY payment_type
";
$stmt = $conn->prepare($paymentSql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->execute();
$paymentResult = $stmt->get_result();
$payTypes = [];
$payTotals = [];
while ($row = $paymentResult->fetch_assoc()) {
    $payTypes[] = $row['payment_type'] ?: 'Other';
    $payTotals[] = (int)$row['total'];
}
$stmt->close();

/* =========================
   MOST RECENT SALES (ALL)
========================= */
$recentSql = "
    SELECT
        s.created_at,
        s.total,
        i.item_name,
        d.name AS department_name,
        st.first_name,
        st.last_name
    FROM sales s
    LEFT JOIN inventory i ON s.item_id = i.item_id
    LEFT JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN staff st ON s.staff_id = st.staff_id
    ORDER BY s.created_at DESC
    LIMIT 7
";
$stmt = $conn->prepare($recentSql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->execute();
$recentSales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Dashboard  | Bolowei's World Resort Management System">
    <meta name="keywords" content="Bolowei's World Resort Website, Resort portal, Resort Management System, Resort software, Digital Resort System">
    <meta name="author" content="Akamatech Limited by Agala George">
    <title>Admin Dashboard | Bolowei's World Resort Management System</title>
    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
    <style>
       
        /* FIX class_report container */
        #class_report {
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 0px;
            box-sizing: border-box;
        }
        /* Charts row */
        .chart-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            width: 100%;
        }
        /* Chart cards */
        .chart-box1 {
            flex: 2;
            min-width: 320px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 250px; /* IMPORTANT */
        }
        .chart-box2 {
            flex: 1;
            min-width: 320px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: 250px; /* IMPORTANT */
        }
       
        /* Make canvas fill the box */
        .chart-box1 canvas, .chart-box2 canvas {
            width: 100% !important;
            height: 100% !important;
        }
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="admin_main">
        <div class="admin_sideBar">
            <div class="sideBar_header">
                <a href="superadmin_dashboard.php"><img src="../../images/bolowies_logo2.png" height="60px" width="80px"/></a>
            </div>
            <div class="sideBar_links">
                <div class="sideBar_top_links">
                    <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="dashboard.php" style="color: #fff;">
                            <i class="fa fa-dashboard"></i>  Dashboard
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_staff.php">
                            <i class="fa fa-group"></i>  Staff
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money"></i>  Finance
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_store_house.php">
                            <i class="fa fa-book"></i>  Store House
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-pencil-square" aria-hidden="true"></i>  Bookings
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money" aria-hidden="true"></i>  Expenses
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money"></i>  Salary
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="departments.php">
                            <i class="fa fa-gg-circle" aria-hidden="true"></i>  Departments
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_roles.php">
                            <i class="fa fa-newspaper-o"></i>  Add Role
                        </a>
                    </nav>
                     <nav class="sideBar_nav"><a href="report.php"><i class="fa fa-file-text"></i>&nbsp; Report</a></nav>
                    <br>
                    <nav class="sideBar_nav">
                        <a href="../auth/logout.php">
                            <i class="fa fa-sign-out"></i>  Sign Out
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="main_content">
            <div class="content_header">
                <h2>Admin Dashboard</h2>
                <div id="notification_content">
                     <label class="notifications message_notify">
                        <i class="fa fa-envelope"></i>
                    </label>
                    <label class="notifications genaral_notify">
                        <i class="fa fa-bell"></i>
                    </label>
                    <div class="user_prolfile">
                        <img src="../../images/user_image.jfif" height="55px" width="55px"/>
                        <div class="profile" style="color: #333;">
                            <h5>
                                Welcome, <?= htmlspecialchars($user['first_name'] ?? 'Admin') ?>
                            </h5>
                            <p style="color: #5a5a5a;">Admin</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin_contents">
                <div class="admin_left_content">
                    <div class="admin_content_top">
                         <nav id="store_house"><a href="front_desk.php"><i class="fa fa-indent" aria-hidden="true"></i> Ticket House </a></nav>
                        <nav id="store_house"><a href="vip_bar.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> VIP Bar </a></nav>
                        <nav id="store_house"><a href="pool_bar.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Pool Bar</a></nav>
                        <nav id="store_house"><a href="bush_bar.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Bush Bar</a></nav>
                        <nav id="store_house"><a href="ark_bar.php"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Ark Bar</a></nav>
                        <nav id="store_house"><a href="#hotel.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Ark Hotel</a></nav>
                        <nav id="store_house"><a href="#vendor_stand.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Vendor Cashier</a></nav>
                        <nav id="store_house"><a href="reporting.php"><i class="fa fa-bars" aria-hidden="true"></i> Reporting</a></nav>
                    </div>
                    <div class="admin_content_center">
                        <nav id="class_report">
                            <!-- ================= CHARTS ================= -->
                            <div class="chart-row">
                                <div class="chart-box1">
                                    <canvas id="barChart"></canvas>
                                </div>
                                <div class="chart-box2">
                                    <canvas id="pieChart"></canvas>
                                </div>
                            </div>
                            <br>
                        </nav>
                        <nav id="daily_revenue">
                            <h4> Weekly Sales <br> (Mon–Sun):</h4>
                            <h5>₦<?= number_format($weekly_total, 2); ?></h5>
                        </nav>
                    </div>
                    <div class="admin_content_down">
                        <h4>Recent Sales</h4>
                        <table class="table_design">
                            <thead>
                                <tr>
                                <th>Staff</th>
                                <th>Product</th>
                                <th>Department</th>
                                <th>Amount</th>
                                <th>Time</th>
                            </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentSales)): ?>
                                    <?php foreach ($recentSales as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name'] ?: '—') ?></td>
                                            <td><?= htmlspecialchars($row['item_name'] ?: '—') ?></td>
                                            <td><?= htmlspecialchars($row['department_name'] ?: '—') ?></td>
                                            <td>₦<?= number_format((float)$row['total'], 2); ?></td>
                                            <td><?= date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">No recent sales found</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
    // BAR CHART
    new Chart(document.getElementById('barChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($itemNames) ?>,
            datasets: [{
                label: 'Quantity Sold',
                data: <?= json_encode($itemQty) ?>,
                backgroundColor: '#116CE1',
                borderColor: '#0d57c2',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: true, text: 'Top Sold Items', font: { size: 16 } }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // PIE CHART
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($payTypes) ?>,
            datasets: [{
                data: <?= json_encode($payTotals) ?>,
                backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#6f42c1','#fd7e14'],
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: { display: true, text: 'Payment Methods', font: { size: 16 } },
                legend: { position: 'bottom' }
            }
        }
    });
    </script>
</body>
</html>