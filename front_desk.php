<?php
session_start();
require('../../include/database/mysql_db.php');

// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

/* =========================
   GET FRONT DESK DEPARTMENT ID
========================= */
$deptSql = "SELECT department_id FROM departments WHERE name = 'Front Desk' LIMIT 1";
$deptResult = $conn->query($deptSql);
$deptRow = $deptResult->fetch_assoc();

if (!$deptRow) {
    die("Front Desk department not found.");
}

$department_id = (int) $deptRow['department_id'];


/* =========================
   WEEKLY SALES (FRONT DESK ONLY)
========================= */
$weeklySql = "
    SELECT SUM(total) AS weekly_total
    FROM sales
    WHERE department_id = ?
      AND YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)
";

$stmt = $conn->prepare($weeklySql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$weekly_total = (float) ($result['weekly_total'] ?? 0);
$stmt->close();


/* =========================
   TOP ITEMS SOLD (FRONT DESK ONLY)
========================= */
$itemSql = "
    SELECT i.item_name, SUM(s.qty) AS total_sold
    FROM sales s
    LEFT JOIN inventory i ON s.item_id = i.item_id
    WHERE s.department_id = ?
    GROUP BY s.item_id
    ORDER BY total_sold DESC
";

$stmt = $conn->prepare($itemSql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$itemResult = $stmt->get_result();

$itemNames = [];
$itemQty = [];

while ($row = $itemResult->fetch_assoc()) {
    $itemNames[] = $row['item_name'];
    $itemQty[] = $row['total_sold'];
}
$stmt->close();


/* =========================
   PAYMENT METHODS (FRONT DESK ONLY)
========================= */
$paymentSql = "
    SELECT payment_type, COUNT(*) AS total
    FROM sales
    WHERE department_id = ?
    GROUP BY payment_type
";

$stmt = $conn->prepare($paymentSql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$paymentResult = $stmt->get_result();

$payTypes = [];
$payTotals = [];

while ($row = $paymentResult->fetch_assoc()) {
    $payTypes[] = $row['payment_type'];
    $payTotals[] = $row['total'];
}
$stmt->close();


/* =========================
   RECENT SALES (FRONT DESK ONLY)
========================= */
$recentSql = "
    SELECT s.created_at, s.client_name, s.qty, s.rate, s.total,
           s.payment_type, i.item_name
    FROM sales s
    LEFT JOIN inventory i ON s.item_id = i.item_id
    WHERE s.department_id = ?
    ORDER BY s.created_at DESC
    LIMIT 7
";

$stmt = $conn->prepare($recentSql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$recentSales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <style>
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .role_form label { display: block; margin: 10px 0 5px; font-weight: bold; }
        .role_form input, .role_form textarea {
            width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;
        }
        .role_form textarea { height: 100px; resize: vertical; }
        .role_form button {
            background: #04337d; color: white; padding: 12px 25px; border: none; border-radius: 4px;
            cursor: pointer; font-size: 16px; margin-top: 15px;
        }
        .role_form button:hover { background: #022550; }
        .back-link { display: inline-block; margin-top: 15px; color: #04337d; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }

        .sideBar_nav{
            margin-bottom: 10px;
       }


        .calendar {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: #007bff;
            font-size: 20px;
            color: white;
            padding: 5px;
            text-align: center;
            font-weight: bold;
        }
        .today {
            background-color: #007bff !important;
            color: white !important;
            font-weight: bold;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: inline-block;
            line-height: 30px;
        }
        .day {
            background: #fff;
            justify-content: center;
        }
        .empty {
            background: #f9f9f9;
        }
        table { 
            width: 99%; 
            border-collapse: collapse;
             margin-top: 20px; 
            }
        th, td { 
            border: 1px solid #ccc; 
            padding: 8px; 
            text-align: left; 
        }
        th { 
            background: #116CE1; 
            color: white; 
        }
        
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
                <a href="dashboard.php"><img src="../../images/bolowies_logo2.png" height="60px" width="80px"/></a>
            </div>
            <div class="sideBar_links">
                <div class="sideBar_top_links">
                    <nav class="sideBar_nav">
                        <a href="dashboard.php">
                            <i class="fa fa-dashboard"></i>&nbsp; Dashboard
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_staff.php">
                            <i class="fa fa-group"></i>&nbsp; Staff
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money"></i>&nbsp; Finance
                        </a>
                    </nav>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="sales.php" style="color: #fff; font-weight: 600;">
                            <i class="fa fa-newspaper-o aria-hidden="true"></i>&nbsp; Sales
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_store_house.php">
                            <i class="fa fa-book"></i>&nbsp; Store House
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-pencil-square" aria-hidden="true"></i>&nbsp; Bookings
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money" aria-hidden="true"></i>&nbsp; Expenses
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money"></i>&nbsp; Salary
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-gg-circle" aria-hidden="true"></i>&nbsp; Departments
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-newspaper-o"></i>&nbsp; Report
                        </a>
                    </nav>

                    <br>
                    <nav class="sideBar_nav">
                        <a href="../auth/logout.php">
                            <i class="fa fa-sign-out"></i>&nbsp; Sign Out
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="main_content">
            <div class="content_header">
                <h2>Ticket House Management</h2>
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

                                <?php
                                    $user_id = $_SESSION['user_id'];

                                    $sql = "SELECT staff.first_name, staff.last_name 
                                            FROM staff 
                                            JOIN users ON staff.staff_id = users.staff_id
                                            WHERE users.user_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $user = $result->fetch_assoc();

                                    echo "Welcome, " . $user['first_name'];
                                ?>
                            </h5>
                            <p style="color: #5a5a5a;">Admin</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin_contents">
                <div class="admin_left_content">
                    <div class="admin_store_content_top">
                        <nav id="store_house"><a href="front_desk_orders.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Manage Orders </a></nav>
                        <nav id="store_house"><a href="front_desk_report.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Report</a></nav>
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
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Client</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Rate (₦)</th>
                                    <th>Amount (₦)</th>
                                    <th>Payment Method</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($recentSales)): ?>
                                <?php foreach ($recentSales as $row): ?>
                                    <tr>
                                        <td><?= date('d M Y, h:i A', strtotime($row['created_at'])); ?></td>
                                        <td><?= htmlspecialchars($row['client_name']); ?></td>
                                        <td><?= htmlspecialchars($row['item_name']); ?></td>
                                        <td><?= (int)$row['qty']; ?></td>
                                        <td>₦<?= number_format((float)$row['rate'], 2); ?></td>
                                        <td>₦<?= number_format((float)$row['total'], 2); ?></td>
                                        <td><?= htmlspecialchars($row['payment_type']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">No recent sales found</td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="admin_right_content">
                    <div class="admin_calender">
                        <div class="calendar">
                                <div class="header">
                                    <?php
                                        // Set timezone (adjust to your location, e.g., Africa/Lagos for Nigeria)
                                        date_default_timezone_set('Africa/Lagos');

                                        $month = date('F Y'); // e.g., November 2025
                                        echo $month;
                                    ?>
                                </div>

                                <table>
                                    <tr>
                                        <th>Sun</th>
                                        <th>Mon</th>
                                        <th>Tue</th>
                                        <th>Wed</th>
                                        <th>Thu</th>
                                        <th>Fri</th>
                                        <th>Sat</th>
                                    </tr>

                                    <?php
                                        // Current date info
                                        $today = date('j');           // Day without leading zeros (1-31)
                                        $currentMonth = date('n');    // Month (1-12)
                                        $currentYear = date('Y');     // Year

                                        // First day of the month
                                        $firstDay = mktime(0, 0, 0, $currentMonth, 1, $currentYear);
                                        $daysInMonth = date('t', $firstDay); // Total days in month
                                        $startDay = date('w', $firstDay);    // Weekday of first day (0=Sun, 6=Sat)

                                        $day = 1;
                                        echo '<tr>';

                                        // Empty cells before the 1st
                                        for ($i = 0; $i < $startDay; $i++) {
                                            echo '<td class="empty"></td>';
                                        }

                                        // Fill the calendar
                                        while ($day <= $daysInMonth) {
                                            // Start new row on Sunday
                                            if (($day + $startDay - 1) % 7 == 0 && $day != 1) {
                                                echo '</tr><tr>';
                                            }

                                            // Highlight today
                                            if ($day == $today) {
                                                echo '<td><span class="today">' . $day . '</span></td>';
                                            } else {
                                                echo '<td class="day">' . $day . '</td>';
                                            }

                                            $day++;
                                        }

                                        // Fill remaining cells
                                        $remaining = (7 - ($day + $startDay - 1) % 7) % 7;
                                        for ($i = 0; $i < $remaining; $i++) {
                                            echo '<td class="empty"></td>';
                                        }

                                        echo '</tr>';
                                    ?>
                                </table>
                            </div>
                    </div>
                    <div class="admin_ads">
                        <img src="../../images/ad-1.png"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                title: { display: true, text: 'Top Items Sold', font: { size: 16 } }
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
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14'],
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