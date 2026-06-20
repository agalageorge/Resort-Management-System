<?php
session_start();
require('../../include/database/mysql_db.php');

// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

/* ================================
   GET POOL BAR DEPARTMENT ID
================================ */
$deptSql = "SELECT department_id FROM departments WHERE name = 'Pool Bar' LIMIT 1";
$deptResult = $conn->query($deptSql);

if (!$deptResult || $deptResult->num_rows == 0) {
    die("Pool Bar department not found.");
}

$department_id = (int)$deptResult->fetch_assoc()['department_id'];


/* ================================
   PAGINATION SETTINGS
================================ */
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;


/* ================================
   TOTAL GROUPED ORDERS (POOL BAR ONLY)
================================ */
$total_sql = "
    SELECT COUNT(*) AS total_orders
    FROM (
        SELECT receipt_no
        FROM sales
        WHERE department_id = ?
        GROUP BY receipt_no
    ) AS grouped_orders
";

$stmtTotal = $conn->prepare($total_sql);
$stmtTotal->bind_param("i", $department_id);
$stmtTotal->execute();
$total_result = $stmtTotal->get_result();
$total_orders = $total_result->fetch_assoc()['total_orders'];
$stmtTotal->close();

$total_pages = ceil($total_orders / $limit);


/* ================================
   FETCH ORDERS (POOL BAR ONLY)
================================ */
$sql = "
    SELECT 
        sales_date,
        client_name,
        client_contact,
        payment_type,
        receipt_no,
        SUM(grand_total) AS total_order_items
    FROM sales
    WHERE department_id = ?
    GROUP BY receipt_no, sales_date, client_name, client_contact, payment_type
    ORDER BY sales_date DESC
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $department_id, $limit, $offset);
$stmt->execute();
$orders = $stmt->get_result();
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
        table { width: 95%; border-collapse: collapse; margin: 20px auto; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #ccc; color: #116CE1; }
        .btn-print { padding: 6px 12px; background: darkblue; color: white; text-decoration: none; border-radius: 4px; }
        .toggle-btn { cursor: pointer; color: blue; text-decoration: underline; }
        .items-box { display: none; background: #f9f9f9; padding: 10px; }
        .items-table th { background: #eee; }
        .pagination { text-align: center; margin: 20px; }
        .pagination a { margin: 0 5px; padding: 5px 10px; border: 1px solid #ccc; text-decoration: none; }
        .pagination a.active { background: #116CE1; color: #fff; border-color: #116CE1; }
    </style>

    <script>
        function toggleItems(id) {
            var box = document.getElementById("items-" + id);
            box.style.display = (box.style.display === "none") ? "block" : "none";
        }

        function printReceipt(receiptNo) {
            window.open('print_receipt.php?receipt_no=' + receiptNo, '_blank', 'width=400,height=600');
        }
    </script>
</head>

<body>
<div class="admin_main">
    <div class="admin_sideBar">
        <div class="sideBar_header">
            <a href="superadmin_dashboard.php">
                <img src="../../images/bolowies_logo2.png" height="60px" width="80px"/>
            </a>
        </div>

        <div class="sideBar_links">
            <div class="sideBar_top_links">
                <nav class="sideBar_nav"><a href="dashboard.php"><i class="fa fa-dashboard"></i>&nbsp; Dashboard</a></nav>
                <nav class="sideBar_nav"><a href="manage_staff.php"><i class="fa fa-group"></i>&nbsp; Staff</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Finance</a></nav>
                <nav class="sideBar_nav" id="active" style="background: #04337d;">
                    <a href="sales.php" style="color: #fff; font-weight: 600;">
                        <i class="fa fa-newspaper-o"></i>&nbsp; Sales
                    </a>
                </nav>
                <nav class="sideBar_nav"><a href="manage_store_house.php"><i class="fa fa-book"></i>&nbsp; Store House</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square"></i>&nbsp; Bookings</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Expenses</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Salary</a></nav>
                <nav class="sideBar_nav"><a href="departments.php"><i class="fa fa-gg-circle"></i>&nbsp; Departments</a></nav>
                <nav class="sideBar_nav"><a href="report.php"><i class="fa fa-newspaper-o"></i>&nbsp; Report</a></nav>
                <br>
                <nav class="sideBar_nav"><a href="../auth/logout.php"><i class="fa fa-sign-out"></i>&nbsp; Sign Out</a></nav>
            </div>
        </div>
    </div>

    <div class="main_content">
        <div class="content_header">
            <h2>Pool Bar Management</h2>
        </div>

        <div class="admin_sales_contents">
            <div class="admin_sales_content_top">
                <nav><a href="pool_bar_orders.php"><i class="fa fa-cart-plus"></i> Manage Orders </a></nav>
                <nav><a href="pool_bar_report.php"><i class="fa fa-cart-plus"></i> Report</a></nav>
            </div>

            <div class="admin_sales_content_down">
                <h2>Manage Orders (Pool Bar Only)</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Order Date</th>
                            <th>Client</th>
                            <th>Client Contact</th>
                            <th>Items Ordered</th>
                            <th>Total Order (₦)</th>
                            <th>Payment Type</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php while ($row = $orders->fetch_assoc()) { 
                        $receipt_no = $row['receipt_no'];

                        $item_sql = "
                            SELECT s.*, i.item_name 
                            FROM sales s 
                            LEFT JOIN inventory i ON s.item_id = i.item_id 
                            WHERE s.receipt_no = ?
                              AND s.department_id = ?
                        ";

                        $item_stmt = $conn->prepare($item_sql);
                        $item_stmt->bind_param("si", $receipt_no, $department_id);
                        $item_stmt->execute();
                        $items = $item_stmt->get_result();
                    ?>

                        <tr>
                            <td><?php echo $row['sales_date']; ?></td>
                            <td><?php echo $row['client_name']; ?></td>
                            <td><?php echo $row['client_contact']; ?></td>
                            <td><span class="toggle-btn" onclick="toggleItems('<?php echo $receipt_no; ?>')">View Items</span></td>
                            <td>₦<?php echo number_format($row['total_order_items'], 2); ?></td>
                            <td><?php echo $row['payment_type']; ?></td>
                        </tr>

                        <tr>
                            <td colspan="7">
                                <div class="items-box" id="items-<?php echo $receipt_no; ?>">
                                    <table class="items-table" width="100%">
                                        <tr>
                                            <th>Item Name</th>
                                            <th>Rate (₦)</th>
                                            <th>Qty</th>
                                            <th>Total (₦)</th>
                                            <th>Staff</th>
                                        </tr>

                                        <?php while ($item = $items->fetch_assoc()) { ?>
                                        <tr>
                                            <td><?php echo $item['item_name']; ?></td>
                                            <td>₦<?php echo number_format($item['rate'], 2); ?></td>
                                            <td><?php echo $item['qty']; ?></td>
                                            <td>₦<?php echo number_format($item['total'], 2); ?></td>
                                            <td><?php echo $item['staff_first_name'] . ' ' . $item['staff_last_name']; ?></td>
                                        </tr>
                                        <?php } ?>

                                    </table>
                                </div>
                            </td>
                        </tr>

                    <?php 
                        $item_stmt->close();
                    } ?>

                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($page > 1) { ?>
                        <a href="?page=<?php echo $page-1; ?>">&laquo; Prev</a>
                    <?php } ?>

                    <?php for ($p=1; $p<=$total_pages; $p++) { ?>
                        <a href="?page=<?php echo $p; ?>" class="<?php if($p==$page) echo 'active'; ?>">
                            <?php echo $p; ?>
                        </a>
                    <?php } ?>

                    <?php if ($page < $total_pages) { ?>
                        <a href="?page=<?php echo $page+1; ?>">Next &raquo;</a>
                    <?php } ?>
                </div>

                <br>
                <a href="pool_bar.php" class="btn btn-secondary" style="margin-left: 20px;">Back</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>