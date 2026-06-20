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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .container { margin-top: 50px; }
        h2 { color: #04337d; margin-bottom: 25px; }
        .btn-primary { background-color: #04337d; border: none; }
        .btn-primary:hover { background-color: #022550; }
        .btn-danger { background-color: #d32f2f; border: none; }
        .btn-danger:hover { background-color: #a0251a; }
        table th, table td { vertical-align: middle !important; }
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
                <h2>Inventory</h2>
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
                <div class="admin_store_inventory">
                        <h2 style="font-size: 25px;">Stock Out Records</h2>
                        <div class="table-responsive">
                            <table class="inventory_table_design">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th>Department</th>
                                        <th>Request ID</th>
                                        <th>Quantity Issued</th>
                                        <th>Issued By</th>
                                        <th>Issued Date</th>
                                        <th>Reference No.</th>
                                        <th>Note</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= $row['stock_out_id'] ?></td>
                                                <td><?= htmlspecialchars($row['item_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['department_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['request_id'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['issued_qty']) ?></td>
                                                <td><?= htmlspecialchars($row['issued_by_name'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['issued_date']) ?></td>
                                                <td><?= htmlspecialchars($row['reference_no'] ?? '—') ?></td>
                                                <td><?= htmlspecialchars($row['note'] ?? '—') ?></td>
                                                <td>
                                                    <a href="edit_stock_out.php?id=<?= $row['stock_out_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                                    <a href="delete_stock_out.php?id=<?= $row['stock_out_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this record?');">Delete</a>
                                                </td>
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

                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>