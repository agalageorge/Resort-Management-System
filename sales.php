<?php
session_start();
require('../../include/database/mysql_db.php');

// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}
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

</head>
<body>
    <div class="admin_main">
        <div class="admin_sideBar">
            <div class="sideBar_header">
                <a href="superadmin_dashboard.php"><img src="../../images/bolowies_logo2.png" height="60px" width="80px"/></a>
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
                <h2>Sales Management</h2>
                <div id="search_content">
                    <label>
                        <i class="fa fa-search"></i>
                        <input type="text" name="email" required placeholder="Search">
                    </label>
                </div>
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
                        <nav id="store_house"><a href="ticket_house.php"><i class="fa fa-indent" aria-hidden="true"></i> Ticket House </a></nav>
                        <nav id="store_house"><a href="vip_bar.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> VIP Bar </a></nav>
                        <nav id="store_house"><a href="pool_bar.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Pool Bar</a></nav>
                        <nav id="store_house"><a href="bush_bar.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Bush Bar</a></nav>
                        <nav id="store_house"><a href="ark_bar.php"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Ark Bar</a></nav>
                        <nav id="store_house"><a href="hotel.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Ark Hotel</a></nav>
                        <nav id="store_house"><a href="vendor_stand.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Vendor Cashier</a></nav>
                        <nav id="store_house"><a href="reporting.php"><i class="fa fa-bars" aria-hidden="true"></i> Reporting</a></nav>
                    </div>
                    <div class="admin_content_center">
                        <nav id="class_report">
                            <img src="../../images/IELTS-bar-chart.jpg"/>
                        </nav>
                        <nav id="daily_revenue">
                            <h4> Weekly Revenue</h4>
                            <h5>
                                
                            <?php

                                    // Get weekly total sales (Monday–Sunday)
                                    $sql = "
                                        SELECT 
                                            SUM(grand_total) AS weekly_total
                                        FROM sales
                                        WHERE YEARWEEK(sales_date, 1) = YEARWEEK(CURDATE(), 1)
                                    ";

                                    $result = $conn->query($sql);
                                    $row = $result->fetch_assoc();

                                    // If no sales yet this week, set to 0
                                    $weekly_total = $row['weekly_total'] ?? 0;

                                    echo "<h5>₦" . number_format($weekly_total, 2) . "</h5>";

                                    $conn->close();
                                    ?>
                        
                            </h5>
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
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                                <tr>
                                <td>Jerry Raymond</td>
                                <td>Maltina</td>
                                <td>VIP Bar</td>
                                <td>2,000</td>
                                <td>10:30am</td>
                                <td><a href="#"><i class="fa fa-sign-in">&nbsp; View</i></a></td>
                            </tr>
                            <tr>
                                <td>Micah Joy</td>
                                <td>Water</td>
                                <td>Bush bar</td>
                                <td>1,000</td>
                                <td>10:25am</td>
                                <td><a href="#"><i class="fa fa-sign-in">&nbsp; View</i></a></td>
                            </tr>
                            <tr>
                                <td>Victor Ken</td>
                                <td>Boat Cruise</td>
                                <td>Front Desk</td>
                                <td>40,000</td>
                                <td>10:01am</td>
                                <td><a href="#"><i class="fa fa-sign-in">&nbsp; View</i></a></td>
                            </tr>
                            <tr>
                                <td>Destiny Jude</td>
                                <td>Swimming</td>
                                <td>Adult Pool</td>
                                <td>3,000</td>
                                <td>9:56am</td>
                                <td><a href="#"><i class="fa fa-sign-in">&nbsp; View</i></a></td>
                            </tr>
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
</body>
</html>