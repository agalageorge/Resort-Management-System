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


// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_name = $_POST['item_name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'];
    $supplier_id = $_POST['supplier_id'];
    $quantity = $_POST['quantity'];
    $unit_of_measure = $_POST['unit_of_measure'];
    $location = $_POST['location'];
    $cost_price = $_POST['cost_price'];
    $selling_price = $_POST['selling_price'];
    $reorder_level = $_POST['reorder_level'];
    $expiration_date = $_POST['expiration_date'];
    $status = $_POST['status'];

    $sql = "INSERT INTO inventory (
                item_name, description, category_id, brand_id, supplier_id, quantity, 
                unit_of_measure, location, cost_price, selling_price, reorder_level, 
                expiration_date, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(
            "ssiiidsssddss",
            $item_name,
            $description,
            $category_id,
            $brand_id,
            $supplier_id,
            $quantity,
            $unit_of_measure,
            $location,
            $cost_price,
            $selling_price,
            $reorder_level,
            $expiration_date,
            $status,
        );

        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>✅ Inventory item added successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>❌ Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    $conn->close();
}

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
                        <a href="superadmin_dashboard.php"><i class="fa fa-dashboard"></i>&nbsp; Dashboard</a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="manage_staff.php"><i class="fa fa-group"></i>&nbsp; Staff</a>
                    </nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Finance</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-newspaper-o"></i>&nbsp; Sales</a></nav>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;"><a href="manage_store_house.php" style="color: #fff;"><i class="fa fa-book"></i>&nbsp; Store House</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square"></i>&nbsp; Bookings</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Expenses</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Salary</a></nav>
                    <nav class="sideBar_nav"><a href="departments.php"><i class="fa fa-building"></i>&nbsp; Departments</a></nav>
                    <nav class="sideBar_nav"><a href="reporting.php"><i class="fa fa-file-text"></i>&nbsp; Report</a></nav>
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
                    <nav id="store_house"><a href="stock_adjustment.php"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Stock Adjustment</a></nav>
                    <nav id="store_house"><a href="reorder.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Reorder</a></nav>
                    <nav id="store_house"><a href="supplier.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Supplier</a></nav>
                    <nav id="store_house"><a href="reporting.php"><i class="fa fa-bars" aria-hidden="true"></i> Reporting</a></nav>
                </div>
                <div class="admin_store_inventory">
                    <h2 class="mb-4">Add New Inventory Item</h2>
                    <form method="POST" action="" class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Unit of Measure</label>
                            <input type="text" name="unit_of_measure" class="form-control" placeholder="e.g. kg, pcs, box">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <input type="number" name="category_id" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Brand</label>
                            <input type="number" name="brand_id" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Supplier</label>
                            <input type="number" name="supplier_id" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" step="0.001" name="quantity" class="form-control" value="0.000">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Cost Price</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control" value="0.00">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Selling Price</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" step="0.001" name="reorder_level" class="form-control" value="0.000">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="expiration_date" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="damaged">Damaged</option>
                                <option value="out_of_stock">Out of Stock</option>
                                <option value="discontinued">Discontinued</option>
                            </select>
                        </div>


                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Save Item</button>
                            <a href="inventory_list.php" class="btn btn-secondary">View Inventory</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>