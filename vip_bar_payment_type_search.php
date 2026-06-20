<?php
declare(strict_types=1);
require('../../include/database/mysql_db.php');
session_start();

/* =========================
   AUTH CHECK
========================= */
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

/* =========================
   FORCE VIP BAR DEPARTMENT
========================= */
$frontDeskSql = "SELECT department_id, name FROM departments WHERE name = 'VIP Bar' LIMIT 1";
$frontDeskResult = $conn->query($frontDeskSql);

if (!$frontDeskResult || $frontDeskResult->num_rows === 0) {
    die("Error: VIP Bar department not found.");
}

$frontDeskData   = $frontDeskResult->fetch_assoc();
$department_id   = (int)$frontDeskData['department_id'];
$department_name = $frontDeskData['name'];

/* =========================
   PAGINATION SETTINGS
========================= */
$limit  = 10;
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

/* =========================
   FILTER VALUES
========================= */
$start_date   = $_GET['start_date']   ?? '';
$end_date     = $_GET['end_date']     ?? '';
$payment_type = $_GET['payment_type'] ?? '';

$records       = [];
$total_amount  = 0;
$total_records = 0;

/* =========================
   PROCESS REPORT
========================= */
if ($start_date && $end_date) {

    /* ---------- COUNT TOTAL RECORDS ---------- */
    $count_sql = "
        SELECT COUNT(*) AS total
        FROM sales s
        WHERE s.sales_date BETWEEN ? AND ?
        AND s.department_id = ?
    ";

    $params = [$start_date, $end_date, $department_id];
    $types  = "ssi";

    if ($payment_type !== '') {
        $count_sql .= " AND s.payment_type = ?";
        $params[] = $payment_type;
        $types   .= "s";
    }

    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $count_stmt->close();


    /* ---------- FETCH PAGINATED DATA ---------- */
    $sql = "
        SELECT
            s.sales_date,
            s.receipt_no,
            s.client_name,
            s.client_contact,
            s.rate,
            s.qty,
            s.total,
            s.payment_type,
            i.item_name
        FROM sales s
        LEFT JOIN inventory i ON s.item_id = i.item_id
        WHERE s.sales_date BETWEEN ? AND ?
        AND s.department_id = ?
    ";

    $params = [$start_date, $end_date, $department_id];
    $types  = "ssi";

    if ($payment_type !== '') {
        $sql .= " AND s.payment_type = ?";
        $params[] = $payment_type;
        $types   .= "s";
    }

    $sql .= " ORDER BY s.sales_date ASC, s.receipt_no ASC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types   .= "ii";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    /* ---------- PAGE TOTAL ---------- */
    foreach ($records as $row) {
        $total_amount += (float) $row['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Report by Payment Method | Bolowei's World Resort</title>
    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        form {
            width: 95%;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
            margin: 20px auto;
            align-items: center;
        }
        label { display: flex; align-items: center; gap: 6px; }
        select, input[type="date"] {
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        input[type="submit"] {
            padding: 8px 20px;
            background-color: #116CE1;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        input[type="submit"]:hover { background-color: #0d57c2; }
        table {
            width: 95%;
            border-collapse: collapse;
            margin: 25px auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 9px;
            text-align: left;
        }
        th { background: #116CE1; color: white; }
        .total {
            font-weight: bold;
            background: #eee;
        }
        .pagination {
            text-align: center;
            margin: 30px 0;
        }
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
        .no-data {
            text-align: center;
            margin: 30px;
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
<div class="admin_main">
    <div class="admin_sideBar">
        <!-- Sidebar (unchanged) -->
        <div class="sideBar_header">
            <a href="dashboard.php"><img src="../../images/bolowies_logo2.png" height="60px" width="80px"/></a>
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
                Report by Payment Method – <?= htmlspecialchars($department_name) ?>
            </h2>
            <hr style="border: 1px solid black;">
        </div>

        <div class="user_contents">
            <div class="search_order">
                <h4>Order by Payment Method</h4>
            </div>

            <div class="user_content_top">
                <nav><a href="vip_bar_payment_type_search.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Payment Method </a></nav>
                <nav><a href="vip_bar_item_search.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Item Name</a></nav>
            </div>

            <div class="admin_sales_content_down" style="margin-left: 10px;">
                <h2>Report by Payment Method</h2>

                <form method="GET" class="report-form">
                    <input type="hidden" name="page" value="1">

                    <label>
                        Payment Method:
                        <select name="payment_type">
                            <option value="">All</option>
                            <option value="transfer" <?= $payment_type === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                            <option value="POS"     <?= $payment_type === 'POS'     ? 'selected' : '' ?>>POS</option>
                            <option value="cash"    <?= $payment_type === 'cash'    ? 'selected' : '' ?>>Cash</option>
                        </select>
                    </label>

                    <label>Start Date:
                        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>
                    </label>

                    <label>End Date:
                        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>
                    </label>

                    <input type="submit" value="Generate Report">
                </form>

                <?php if ($start_date && $end_date): ?>
                    <?php if (!empty($records)): ?>
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
                                <?php foreach ($records as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['sales_date'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['receipt_no'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['client_name'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['client_contact'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($row['item_name'] ?? '—') ?></td>
                                        <td>₦<?= number_format((float)($row['rate'] ?? 0), 2) ?></td>
                                        <td><?= (int)($row['qty'] ?? 0) ?></td>
                                        <td>₦<?= number_format((float)($row['total'] ?? 0), 2) ?></td>
                                        <td><?= htmlspecialchars($row['payment_type'] ?? '') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total">
                                    <td colspan="7"><strong>Total Sales (shown page)</strong></td>
                                    <td colspan="2"><strong>₦<?= number_format($total_amount, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- PAGINATION -->
                        <?php
                        $total_pages = ceil($total_records / $limit);
                        if ($total_pages > 1):
                        ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&payment_type=<?= urlencode($payment_type) ?>&page=<?= $page-1 ?>">« Prev</a>
                            <?php endif; ?>

                            <?php
                            $range = 2;
                            $start = max(1, $page - $range);
                            $end   = min($total_pages, $page + $range);

                            if ($start > 1): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&payment_type=<?= urlencode($payment_type) ?>&page=1">1</a>
                                <?php if ($start > 2): ?><span>...</span><?php endif; ?>
                            <?php endif; ?>

                            <?php for ($p = $start; $p <= $end; $p++): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&payment_type=<?= urlencode($payment_type) ?>&page=<?= $p ?>"
                                   class="<?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
                            <?php endfor; ?>

                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?><span>...</span><?php endif; ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&payment_type=<?= urlencode($payment_type) ?>&page=<?= $total_pages ?>"><?= $total_pages ?></a>
                            <?php endif; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>&payment_type=<?= urlencode($payment_type) ?>&page=<?= $page+1 ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                    <?php else: ?>
                        <p class="no-data">
                            No records found for the selected criteria<?= $canViewAll ? '' : ' in this department' ?>.
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>