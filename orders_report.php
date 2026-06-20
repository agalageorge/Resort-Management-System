<?php
session_start();
require('../../include/database/mysql_db.php');

// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Initialize variables
$sales_records = [];
$total_amount = 0;
$start_date = '';
$end_date = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];

    // Validate dates
    if (!$start_date || !$end_date) {
        $error = "Please select both start and end dates.";
    } else {
        // Fetch sales between dates
        $sql = "
            SELECT s.*, i.item_name 
            FROM sales s 
            LEFT JOIN inventory i ON s.item_id = i.item_id
            WHERE sales_date BETWEEN ? AND ?
            ORDER BY sales_date ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $stmt->execute();
        $sales_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Calculate total amount sold
        $total_amount = 0;
        foreach ($sales_records as $record) {
            $total_amount += $record['grand_total'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Bolowei's World Resort Management System</title>
    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
       <style>
        form{ width: 90%; display: flex; justify-content: space-between; margin: auto;}
        label { margin-right: 10px; }
        input[type="date"] { padding: 5px; border-radius: 5px; border-color: #ccc;}
        input[type="submit"] { padding: 5px 10px; width: 30%; border-radius: 5px; border-color: #ccc; 
            background-color: #116CE1; color: #fff;}
        table { width: 95%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background: #116CE1; color: white; }
        .total { font-weight: bold; background: #eee; }
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
                <nav class="sideBar_nav"><a href="superadmin_dashboard.php"><i class="fa fa-dashboard"></i>&nbsp; Dashboard</a></nav>
                <nav class="sideBar_nav"><a href="manage_staff.php"><i class="fa fa-group"></i>&nbsp; Staff</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Finance</a></nav>
                <nav class="sideBar_nav"><a href="manage_store_house.php"><i class="fa fa-book"></i>&nbsp; Store House</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square" aria-hidden="true"></i>&nbsp; Bookings</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money" aria-hidden="true"></i>&nbsp; Expenses</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Salary</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-gg-circle" aria-hidden="true"></i>&nbsp; Departments</a></nav>
                <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="reporting.php" style="color: #fff; font-weight: 600;">
                            <i class="fa fa-newspaper-o"></i>&nbsp; Report
                        </a>
                    </nav>
                <br>
                <nav class="sideBar_nav"><a href="../auth/logout.php"><i class="fa fa-sign-out"></i>&nbsp; Sign Out</a></nav>
            </div>
        </div>
    </div>

    <div class="main_content">
        <div class="content_header">
            <h2>Ticket House Management</h2>
        </div>

        <div class="admin_sales_contents">
            <div class="admin_sales_content_top">
                <nav><a href="add_orders.php"><i class="fa fa-indent" aria-hidden="true"></i> Orders </a></nav>
                <nav><a href="manage_orders.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Manage Orders </a></nav>
                <nav><a href="orders_report.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Report</a></nav>
            </div>

            <div class="admin_sales_content_down">
                <h2>Order Report</h2>

                <form method="POST" class="report-form">
                    <label>Start Date: <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required></label>
                    <label>End Date: <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required></label>
                    <input type="submit" value="Generate Report">
                </form>

                <?php if (!empty($sales_records)) { ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Receipt No</th>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Item</th>
                                <th>Rate (₦)</th>
                                <th>Qty</th>
                                <th>Subtotal (₦)</th>
                                <th>Payment Type</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_records as $sale) { ?>
                                <tr>
                                    <td><?php echo $sale['sales_date']; ?></td>
                                    <td><?php echo $sale['receipt_no']; ?></td>
                                    <td><?php echo $sale['client_name']; ?></td>
                                    <td><?php echo $sale['client_contact']; ?></td>
                                    <td><?php echo $sale['item_name']; ?></td>
                                    <td>₦<?php echo number_format($sale['rate'],2); ?></td>
                                    <td><?php echo $sale['qty']; ?></td>
                                    <td>₦<?php echo number_format($sale['total'],2); ?></td>
                                    <td><?php echo $sale['payment_type']; ?></td>
                                </tr>
                            <?php } ?>
                            <tr class="total">
                                <td colspan="7">TOTAL AMOUNT SOLD</td>
                                <td colspan="2">₦<?php echo number_format($total_amount,2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') { ?>
                    <p>No sales records found for the selected period.</p>
                <?php } ?>
                <br>
               <a href="ticket_house.php" class="btn btn-secondary" style="margin-left: 20px;">Back</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
