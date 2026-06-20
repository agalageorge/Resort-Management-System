<?php
session_start();

// === SECURITY: Check login & session ===
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

// === Database Connection ===
require('../../include/database/mysql_db.php');

// === CSRF Token ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}



// === Fetch Stock Out Records ===
$sql = "SELECT 
            so.stock_out_id,
            so.issued_qty,
            so.issued_date,
            so.reference_no,
            so.note,
            i.item_name,
            d.name AS department_name,
            ri.request_id,
            u.username AS issued_by_name
        FROM stock_out so
        LEFT JOIN inventory i ON so.item_id = i.item_id
        LEFT JOIN departments d ON so.department_id = d.department_id
        LEFT JOIN request_items ri ON so.request_item_id = ri.request_item_id
        LEFT JOIN users u ON so.issued_by = u.user_id
        ORDER BY so.issued_date DESC, so.stock_out_id DESC";


$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Dashboard | Bolowei's World Resort Management System">
    <meta name="keywords" content="Resort Management, Staff Roles, Admin Panel">
    <meta name="author" content="Akamatech Limited by Agala George">
    <title>Add Role | Bolowei's World Resort</title>
    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
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
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: center;
            padding: 8px;
            border: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: normal;
            color: #555;
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
        }
        .empty {
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="admin_main">
        <!-- Sidebar -->
        <div class="admin_sideBar">
            <div class="sideBar_header">
                <a href="superadmin_dashboard.php">
                    <img src="../../images/bolowies_logo2.png" height="60" width="80" alt="Logo"/>
                </a>
            </div>
            <div class="sideBar_links">
                <div class="sideBar_top_links">
                    <nav class="sideBar_nav">
                        <a href="dashboard.php"><i class="fa fa-dashboard"></i>&nbsp; Dashboard</a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_staff.php"><i class="fa fa-group"></i>&nbsp; Staff</a>
                    </nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Finance</a></nav>
                    <nav class="sideBar_nav"><a href="sales.php"><i class="fa fa-newspaper-o"></i>&nbsp; Sales</a></nav>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;"><a href="manage_store_house.php" style="color: #fff;"><i class="fa fa-book"></i>&nbsp; Store House</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square"></i>&nbsp; Bookings</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Expenses</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Salary</a></nav>
                    <nav class="sideBar_nav"><a href="departments.php"><i class="fa fa-building"></i>&nbsp; Departments</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-file-text"></i>&nbsp; Report</a></nav>
                    <br>
                    <nav class="sideBar_nav">
                        <a href="manage_roles.php"><i class="fa fa-user-shield"></i>&nbsp; Add Role</a>
                    </nav>
                    <nav class="sideBar_nav"><a href="../auth/logout.php"><i class="fa fa-sign-out"></i>&nbsp; Sign Out</a></nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main_content">
            <div class="content_header">
                <h2>Store House</h2>
                <div id="search_content">
                    <label><i class="fa fa-search"></i><input type="text" placeholder="Search"></label>
                </div>
                <div id="notification_content">
                    <label class="notifications message_notify"><i class="fa fa-envelope"></i></label>
                    <label class="notifications general_notify"><i class="fa fa-bell"></i></label>
                    <div class="user_prolfile">
                        <img src="../../images/user_image.jfif" height="55" width="55" alt="User"/>
                        <div class="profile" style="color: #333;">
                            <h5>Welcome, <?= htmlspecialchars($current_user['first_name'] ?? 'Admin') ?></h5>
                            <p style="color: #5a5a5a;">Admin</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin_content">
                <div class="admin_store_content_top">
                    <nav id="store_house"><a href="inventory.php"><i class="fa fa-indent" aria-hidden="true"></i> Store Inventory </a></nav>
                    <nav id="store_house"><a href="stock_in.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Stock-In </a></nav>
                    <nav id="store_house"><a href="stock_out.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Stock Out</a></nav>
                    <nav id="store_house"><a href="stock_transfer.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Stock Transfer</a></nav>
                    <nav id="store_house"><a href="category.php"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Category</a></nav>
                    <nav id="store_house"><a href="brand.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Brand</a></nav>
                    <nav id="store_house"><a href="supplier.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Supplier</a></nav>
                    <nav id="store_house"><a href="reporting.php"><i class="fa fa-bars" aria-hidden="true"></i> Reporting</a></nav>
                </div>
                <div class="admin_store_content_down">
                    <div class="store_left">
                        <h2 style="font-size: 25px;">Stock Out Records</h2>
                        <div class="table-responsive">
                            <table class="table_design_recent_orders">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Department</th>
                                        <th>Request ID</th>
                                        <th>Quantity Issued</th>
                                        <th>Issued By</th>
                                        <th>Issued Date</th>
                                        <th>Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['item_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['department_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['request_id'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['issued_qty']) ?></td>
                                                <td><?= htmlspecialchars($row['issued_by_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['issued_date']) ?></td>
                                                <td><?= htmlspecialchars($row['note'] ?? '—') ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center text-muted">No stock out records found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="store_right">
                        <div class="store_right_top">
                            <nav id="products">
                                <a href="#"><span id="span_item">Total Products</span><span id="span_figure">502</span></a>
                            </nav>
                            <nav id="ordered">
                                <a href="#"><span id="span_item">Total Ordered</span><span id="span_figure">102</span></a>
                            </nav>
                            <nav id="today_order">
                                <a href="#"><span id="span_item">Today's Ordered</span><span id="span_figure">22</span></a>
                            </nav>
                            <nav id="low_stock">
                                <a href="#"><span id="span_item">Low Stock</span><span id="span_figure">5</span></a>
                            </nav>
                        </div>
                        <div class="store_right_bottom">
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
                    </div>


                </div>
            </div>
        </div>
    </div>
</body>
</html>