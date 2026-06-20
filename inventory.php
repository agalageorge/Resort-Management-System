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


// Fetch inventory data
$sql = "SELECT 
            i.item_id, 
            i.item_name, 
            i.description,
            i.quantity,
            i.unit_of_measure,
            i.cost_price,
            i.selling_price,
            i.reorder_level,
            i.expiration_date,
            i.status,
            i.last_updated,
            c.category_name,
            b.brand_name,
            s.supplier_name
        FROM inventory i
        LEFT JOIN categories c ON i.category_id = c.category_id
        LEFT JOIN brands b ON i.brand_id = b.brand_id
        LEFT JOIN suppliers s ON i.supplier_id = s.supplier_id
        ORDER BY i.last_updated DESC";

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

        background-color: #f9f9f9;
        }
        .status-active { color: green; font-weight: bold; }
        .status-damaged { color: darkred; font-weight: bold; }
        .status-out_of_stock { color: orange; font-weight: bold; }
        .status-discontinued { color: gray; font-weight: bold; }
        .table-responsive {
            max-height: 80vh;
            overflow-y: auto;
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
                    <div class="store_inventory_header">
                        <h2>Inventory List</h2>
                        <a href="stock_in.php" class="btn btn-primary"> <i class="fa fa-plus" aria-hidden="true"></i>  Add New Item</a>
                    </div>

                    <div class="table_responsive">
                        <table class="inventory_table_design">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Supplier</th>
                                    <th>Qty</th>
                                    <th>Unit</th>
                                    <th>Cost Price</th>
                                    <th>Selling Price</th>
                                    <th>Reorder Level</th>
                                    <th>Expiration</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['item_name'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($row['category_name'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($row['brand_name'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($row['supplier_name'] ?? '—') ?></td>
                                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                                            <td><?= htmlspecialchars($row['unit_of_measure']) ?></td>
                                            <td>$<?= number_format($row['cost_price'], 2) ?></td>
                                            <td>$<?= number_format($row['selling_price'], 2) ?></td>
                                            <td><?= htmlspecialchars($row['reorder_level']) ?></td>
                                            <td><?= htmlspecialchars($row['expiration_date'] ?? '') ?></td>
                                            <td class="status-<?= $row['status']; ?>"><?= ucfirst(str_replace('_', ' ', $row['status'])) ?></td>
                                            <td><?= htmlspecialchars($row['last_updated']) ?></td>
                                            <td>
                                                <a href="edit_inventory.php?id=<?= $row['item_id'] ?>" class="edit_button">Edit</a>
                                                <a href="delete_inventory.php?id=<?= $row['item_id'] ?>" 
                                                class="delete_button"
                                                onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="14" class="text-center text-muted">No inventory items found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>