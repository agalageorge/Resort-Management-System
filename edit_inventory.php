<?php
// === Database Connection ===
require('../../include/database/mysql_db.php');
session_start();

// Optional: generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get item_id
if (!isset($_GET['id'])) {
    die("Invalid request.");
}
$item_id = (int)$_GET['id'];

// Fetch item details
$stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id = ?");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    die("Item not found.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Security check failed.");
    }

    // Sanitize and collect input values
    $item_name      = trim($_POST['item_name'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $quantity       = (int)($_POST['quantity'] ?? 0);
    $unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
    $cost_price     = (float)($_POST['cost_price'] ?? 0);
    $selling_price  = (float)($_POST['selling_price'] ?? 0);
    $reorder_level  = (int)($_POST['reorder_level'] ?? 0);
    $expiration_date = $_POST['expiration_date'] ?: null;
    $status         = trim($_POST['status'] ?? 'active');

    // Dropdown values
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brand_id    = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;

    // Prepare UPDATE statement
    $sql = "UPDATE inventory 
            SET item_name=?, description=?, quantity=?, unit_of_measure=?, cost_price=?, selling_price=?, 
                reorder_level=?, expiration_date=?, status=?, category_id=?, brand_id=?, supplier_id=?, last_updated=NOW()
            WHERE item_id=?";

    $update = $conn->prepare($sql);
    $update->bind_param(
        "ssisssissiiii",
        $item_name, $description, $quantity, $unit_of_measure, $cost_price, $selling_price,
        $reorder_level, $expiration_date, $status, $category_id, $brand_id, $supplier_id, $item_id
    );

    if ($update->execute()) {
        echo "<script>alert('Item updated successfully!'); window.location='inventory.php';</script>";
        exit;
    } else {
        echo "<div style='color:red;'>Error updating item: " . htmlspecialchars($update->error) . "</div>";
    }
}

// Fetch dropdown data
$categories = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name ASC");
$brands = $conn->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name ASC");
$suppliers = $conn->query("SELECT supplier_id, supplier_name FROM suppliers ORDER BY supplier_name ASC");

// Null-safe local vars
$category_id = $item['category_id'] ?? null;
$brand_id    = $item['brand_id'] ?? null;
$supplier_id = $item['supplier_id'] ?? null;

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
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-building"></i>&nbsp; Departments</a></nav>
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
                <h2>Edit Inventory Item</h2>
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
                    <div class="store_inventory_edit">
                        <h2>Edit Inventory Item</h2>
                        <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" name="item_name" class="form-control" required
                                value="<?= htmlspecialchars($item['item_name'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control"><?= htmlspecialchars($item['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" name="quantity" class="form-control"
                                value="<?= htmlspecialchars($item['quantity'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Unit of Measure</label>
                            <input type="text" name="unit_of_measure" class="form-control"
                                value="<?= htmlspecialchars($item['unit_of_measure'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Cost Price</label>
                            <input type="number" step="0.01" name="cost_price" class="form-control"
                                value="<?= htmlspecialchars($item['cost_price'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Selling Price</label>
                            <input type="number" step="0.01" name="selling_price" class="form-control"
                                value="<?= htmlspecialchars($item['selling_price'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reorder Level</label>
                            <input type="number" name="reorder_level" class="form-control"
                                value="<?= htmlspecialchars($item['reorder_level'] ?? '0') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="expiration_date" class="form-control"
                                value="<?= htmlspecialchars($item['expiration_date'] ?? '') ?>">
                        </div>

                        <!-- Category -->
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">-- Select Category --</option>
                                <?php while ($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?= $cat['category_id'] ?>" <?= ($category_id == $cat['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Brand -->
                        <div class="mb-3">
                            <label class="form-label">Brand</label>
                            <select name="brand_id" class="form-select">
                                <option value="">-- Select Brand --</option>
                                <?php while ($br = $brands->fetch_assoc()): ?>
                                    <option value="<?= $br['brand_id'] ?>" <?= ($brand_id == $br['brand_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($br['brand_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Supplier -->
                        <div class="mb-3">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select">
                                <option value="">-- Select Supplier --</option>
                                <?php while ($sup = $suppliers->fetch_assoc()): ?>
                                    <option value="<?= $sup['supplier_id'] ?>" <?= ($supplier_id == $sup['supplier_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($sup['supplier_name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php
                                $statusValue = $item['status'] ?? '';
                                ?>
                                <option value="active" <?= ($statusValue === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="out_of_stock" <?= ($statusValue === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
                                <option value="damaged" <?= ($statusValue === 'damaged') ? 'selected' : '' ?>>Damaged</option>
                                <option value="discontinued" <?= ($statusValue === 'discontinued') ? 'selected' : '' ?>>Discontinued</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">Update Item</button><br><br>
                        <a href="inventory.php" class="btn-cancel">Cancel</a>
                    </form>



                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>