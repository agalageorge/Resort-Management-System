<?php
session_start();
require '../../include/database/mysql_db.php';

// If user is NOT logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Assume this is Pool Bar restricted view (change if some users can see all)
$canViewAll = false; // ← change to true if admin / superadmin

/* ================================
   GET POOL BAR DEPARTMENT ID
================================ */
$deptSql = "SELECT department_id, name FROM departments WHERE name = 'Pool Bar' LIMIT 1";
$deptResult = $conn->query($deptSql);
if (!$deptResult) {
    die("Database error: " . $conn->error);
}
if ($deptResult->num_rows === 0) {
    die("Pool Bar department not found in database.");
}
$deptRow = $deptResult->fetch_assoc();
$department_id   = (int)$deptRow['department_id'];
$department_name = $deptRow['name'];

/* ================================
   DATE FILTER & VALIDATION
================================ */
$start_date = trim($_GET['start_date'] ?? '');
$end_date   = trim($_GET['end_date'] ?? '');
$error = '';
if ($start_date && $end_date) {
    if ($start_date > $end_date) {
        $error = "Start date cannot be after End date.";
    }
}

/* ================================
   PAGINATION
================================ */
$limit  = 10;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$sales_records = [];
$total_amount  = 0.0;
$total_records = 0;

if ($start_date && $end_date && !$error) {
    /* ================================
       TOTAL RECORDS COUNT
    ================================= */
    $count_sql = "
        SELECT COUNT(*) as total
        FROM sales
        WHERE department_id = ?
          AND sales_date >= ?
          AND sales_date <= ?
    ";
    $stmtCount = $conn->prepare($count_sql);
    if (!$stmtCount) {
        die("Prepare count failed: " . $conn->error);
    }
    $stmtCount->bind_param("iss", $department_id, $start_date, $end_date);
    $stmtCount->execute();
    $total_records = $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    /* ================================
       FETCH SALES RECORDS WITH ITEM NAME
    ================================= */
    $sql = "
        SELECT 
            s.*,
            i.item_name
        FROM sales s
        LEFT JOIN inventory i ON s.item_id = i.item_id
        WHERE s.department_id = ?
          AND s.sales_date >= ?
          AND s.sales_date <= ?
        ORDER BY s.sales_date DESC, s.receipt_no DESC
        LIMIT ? OFFSET ?
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare main query failed: " . $conn->error);
    }
    $stmt->bind_param("issii", $department_id, $start_date, $end_date, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $sales_records[] = $row;
        $total_amount += (float)($row['total'] ?? 0);
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report | Bolowei's World Resort</title>
    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        form { width: 90%; display: flex; justify-content: space-between; margin: auto; flex-wrap: wrap; gap: 15px; align-items: center; }
        label { margin-right: 10px; }
        input[type="date"] { padding: 6px; border-radius: 5px; border: 1px solid #ccc; }
        input[type="submit"] {
            padding: 8px 20px;
            border-radius: 5px;
            background-color: #116CE1;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover { background-color: #0d57c2; }
        table { width: 90%; border-collapse: collapse; margin: 25px auto; }
        th, td { border: 1px solid #ccc; padding: 5px; text-align: left; }
        th { background: #116CE1; color: white; }
        .total { font-weight: bold; background: #eee; }
        .error { color: red; text-align: center; margin: 15px 0; font-weight: bold; }
        .pagination { text-align: center; margin: 25px 50px; }
        .pagination a {
            margin: 0 6px;
            padding: 6px 12px;
            border: 1px solid #ccc;
            text-decoration: none;
            border-radius: 4px;
            color: #333;
        }
        .pagination a:hover { background: #f0f0f0; }
        .pagination a.active {
            background: #116CE1;
            color: white;
            border-color: #116CE1;
        }
        .sideBar_nav { margin-bottom: 10px; }
    </style>
    <script>
        function toggleItems(id) {
            var box = document.getElementById("items-" + id);
            box.style.display = (box.style.display === "none" || box.style.display === "") ? "block" : "none";
        }
        function printReceipt(receiptNo) {
            window.open('print_receipt.php?receipt_no=' + encodeURIComponent(receiptNo), '_blank', 'width=400,height=600');
        }
    </script>
</head>
<body>
<div class="admin_main">
    <div class="admin_sideBar">
        <div class="sideBar_header">
            <a href="dashboard.php">
                <img src="../../images/bolowies_logo2.png" height="60px" width="80px"/>
            </a>
        </div>
        <div class="sideBar_links">
            <div class="sideBar_top_links">
                <nav class="sideBar_nav"><a href="dashboard.php"><i class="fa fa-dashboard"></i>  Dashboard</a></nav>
                <nav class="sideBar_nav"><a href="manage_staff.php"><i class="fa fa-group"></i>  Staff</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>  Finance</a></nav>
                <nav class="sideBar_nav" id="active" style="background: #04337d;">
                    <a href="sales.php" style="color: #fff; font-weight: 600;">
                        <i class="fa fa-newspaper-o"></i>  Sales
                    </a>
                </nav>
                <nav class="sideBar_nav"><a href="manage_store_house.php"><i class="fa fa-book"></i>  Store House</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square"></i>  Bookings</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>  Expenses</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>  Salary</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-gg-circle"></i>  Departments</a></nav>
                <nav class="sideBar_nav"><a href="#"><i class="fa fa-newspaper-o"></i>  Report</a></nav>
                <br>
                <nav class="sideBar_nav"><a href="../auth/logout.php"><i class="fa fa-sign-out"></i>  Sign Out</a></nav>
            </div>
        </div>
    </div>

    <div class="main_content">
        <div class="user_content_header">
            <h2 style="margin: 20px;">
                Sales Report<?= $canViewAll ? '' : " - " . htmlspecialchars($department_name) ?>
            </h2>
            <hr style="border: 1px solid black;">
        </div>

        <div class="user_contents">
            <div class="search_order">
                <h4>Sales Search Order</h4>
            </div>

            <div class="user_content_top">
                <nav><a href="pool_bar_payment_type_search.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Payment Method</a></nav>
                <nav><a href="pool_bar_item_search.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Item Name</a></nav>
            </div>

            <div class="admin_sales_content_down">
                <h2>Report by Date</h2>

                <?php if ($error): ?>
                    <p class="error"><?= htmlspecialchars($error) ?></p>
                <?php endif; ?>

                <form method="GET" class="report-form">
                    <input type="hidden" name="page" value="1">
                    <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required></label>
                    <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required></label>
                    <input type="submit" value="Generate Report">
                </form>

                <?php if ($start_date && $end_date && !$error): ?>
                    <?php if (!empty($sales_records)): ?>
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
                                <?php foreach ($sales_records as $sale): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($sale['sales_date'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($sale['receipt_no'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($sale['client_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($sale['client_contact'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($sale['item_name'] ?? '—') ?></td>
                                        <td>₦<?= number_format((float)($sale['rate'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($sale['qty'] ?? 0) ?></td>
                                        <td>₦<?= number_format((float)($sale['total'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($sale['payment_type'] ?? '—') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total">
                                    <td colspan="7">TOTAL AMOUNT SOLD (shown records)</td>
                                    <td colspan="2">₦<?= number_format($total_amount, 2) ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <?php
                        $total_pages = ceil($total_records / $limit);
                        if ($total_pages > 1):
                        ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=<?= $page-1 ?>">« Prev</a>
                            <?php endif; ?>

                            <?php
                            $range = 2;
                            $start = max(1, $page - $range);
                            $end   = min($total_pages, $page + $range);

                            if ($start > 1): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=1">1</a>
                                <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $start; $p <= $end; $p++): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=<?= $p ?>"
                                   class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                            <?php endfor; ?>

                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?><span>...</span><?php endif; ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=<?= $page+1 ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p style="text-align:center; margin:30px; color:#555;">
                            No sales records found for the selected period<?= $canViewAll ? '' : ' in ' . htmlspecialchars($department_name) ?>.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>