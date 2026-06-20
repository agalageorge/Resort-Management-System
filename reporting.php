<?php
session_start();
require('../../include/database/mysql_db.php');

// ============================
// LOGIN CHECK
// ============================
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = (int) $_SESSION['user_id'];

// ============================
// FETCH USER ROLE
// ============================
$userSql = "
    SELECT r.role_id, r.role_name
    FROM users u
    JOIN roles r ON r.role_id = u.role_id
    WHERE u.user_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user) {
    die("Error: User not found.");
}

$role_id   = (int) $current_user['role_id'];
$role_name = strtolower(trim($current_user['role_name']));

// ============================
// ADMIN ACCESS CONTROL
// ============================
// CHANGE 1,2 if your role_id values are different
if (!in_array($role_id, [1, 2])) {
    die("Access Denied. This page is for Admin only.");
}

// ============================
// PAGINATION SETTINGS
// ============================
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

$sales_records = [];
$total_amount  = 0;
$start_date    = $_GET['start_date'] ?? '';
$end_date      = $_GET['end_date'] ?? '';
$error         = '';
$total_records = 0;

// ============================
// FETCH SALES (ALL DEPARTMENTS)
// ============================
if ($start_date && $end_date) {

    // Count total records
    $count_sql = "
        SELECT COUNT(*) AS total
        FROM sales
        WHERE sales_date BETWEEN ? AND ?
    ";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("ss", $start_date, $end_date);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();

    // Fetch paginated records
    $sql = "
        SELECT s.*, i.item_name, d.name AS department_name
        FROM sales s
        LEFT JOIN inventory i ON s.item_id = i.item_id
        LEFT JOIN departments d ON s.department_id = d.department_id
        WHERE s.sales_date BETWEEN ? AND ?
        ORDER BY s.sales_date ASC, s.receipt_no ASC
        LIMIT ? OFFSET ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $start_date, $end_date, $limit, $offset);
    $stmt->execute();
    $sales_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($sales_records as $record) {
        $total_amount += (float) ($record['total'] ?? 0);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report | Bolowei's World Resort Management System</title>
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
        table { width: 95%; border-collapse: collapse; margin: 25px auto; }
        th, td { border: 1px solid #ccc; padding: 9px; text-align: left; }
        th { background: #116CE1; color: white; }
        .total { font-weight: bold; background: #eee; }
        .error { color: red; text-align: center; margin: 15px 0; font-weight: bold; }
        .pagination { text-align: center; margin: 25px 0; }
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
</head>
<body>
<div class="admin_main">
    <div class="admin_sideBar">
        <!-- Your sidebar remains the same -->
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
                        <a href="departments.php">
                            <i class="fa fa-gg-circle" aria-hidden="true"></i>&nbsp; Departments
                        </a>
                    </nav>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="reporting.php" style="color: #fff; font-weight: 600;">
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
        <div class="user_content_header">
           <h2 style="margin: 20px;">
                Sales Report - All Departments
            </h2>
            <hr style="border: 1px solid black;">
        </div>

        <div class="user_contents">
            <div class="search_order">
                <h4>Sales Search Order</h4>
            </div>

            <div class="user_content_top">
                <nav><a href="payment_type_search.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Payment Method </a></nav>
                <nav><a href="item_search.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Item Name</a></nav>
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

                <?php if ($start_date && $end_date && !empty($sales_records)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Department</th>
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
                                    <td><?= htmlspecialchars($sale['department_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($sale['receipt_no'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($sale['client_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($sale['client_contact'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($sale['item_name'] ?? '—') ?></td>
                                    <td>₦<?= number_format($sale['rate'] ?? 0, 2) ?></td>
                                    <td><?= htmlspecialchars($sale['qty'] ?? 0) ?></td>
                                    <td>₦<?= number_format($sale['total'] ?? 0, 2) ?></td>
                                    <td><?= htmlspecialchars($sale['payment_type'] ?? '') ?></td>
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
                        $range = 2; // show 2 pages before/after current
                        $start = max(1, $page - $range);
                        $end   = min($total_pages, $page + $range);

                        if ($start > 1): ?>
                            <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=1">1</a>
                            <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                        <?php endif; ?>

                        <?php for ($p = $start; $p <= $end; $p++): ?>
                            <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&page=<?= $p ?>" 
                               class="<?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
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

                <?php elseif ($start_date && $end_date): ?>
                    <p style="text-align:center; margin:30px; color:#555;">
                        No sales records found for the selected period<?= $canViewAll ? '' : ' in this department' ?>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>