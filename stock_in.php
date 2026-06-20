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
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-newspaper-o"></i>&nbsp; Sales</a></nav>
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
                <h2>Stock In</h2>
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
                    <?php

                        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

                            // Safely collect POST data
                            $item_name = trim($_POST['item_name'] ?? '');
                            $description = trim($_POST['description'] ?? '');
                            $quantity = floatval($_POST['quantity'] ?? 0);
                            $unit_of_measure = trim($_POST['unit_of_measure'] ?? '');
                            $cost_price = floatval($_POST['cost_price'] ?? 0);
                            $selling_price = floatval($_POST['selling_price'] ?? 0);
                            $invoice_no = trim($_POST['invoice_no'] ?? '');
                            $date_received = $_POST['date_received'] ?? date('Y-m-d');
                            $received_by = intval($_POST['received_by'] ?? $user_id);

                            // Validate
                            if (empty($item_name) || $quantity <= 0) {
                                echo "❌ Please enter a valid item name and quantity.";
                                exit;
                            }

                            // 1️⃣ Check if item already exists in inventory
                            $check_sql = "SELECT item_id FROM inventory WHERE item_name = ?";
                            $stmt_check = $conn->prepare($check_sql);
                            $stmt_check->bind_param("s", $item_name);
                            $stmt_check->execute();
                            $result = $stmt_check->get_result();

                            if ($result->num_rows > 0) {
                                // Item already exists
                                $row = $result->fetch_assoc();
                                $item_id = $row['item_id'];
                            } else {
                                // 2️⃣ Insert new item into inventory
                                $insert_sql = "INSERT INTO inventory (item_name, description, unit_of_measure, quantity, cost_price, selling_price)
                                            VALUES (?, ?, ?, ?, ?, ?)";
                                $stmt_insert = $conn->prepare($insert_sql);
                                $stmt_insert->bind_param("sssddd", $item_name, $description, $unit_of_measure, $quantity, $cost_price, $selling_price);
                                $stmt_insert->execute();
                                $item_id = $stmt_insert->insert_id;
                            }

                            // 3️⃣ Insert stock_in record
                            // 3️⃣ Insert stock_in record
                                $sql_in = "INSERT INTO stock_in (item_id, quantity, cost_price, invoice_no, date_received, received_by)
                                        VALUES (?, ?, ?, ?, ?, ?)";
                                $stmt_in = $conn->prepare($sql_in);
                                $stmt_in->bind_param("iddssi", $item_id, $quantity, $cost_price, $invoice_no, $date_received, $received_by);


                            if ($stmt_in->execute()) {
                                // 4️⃣ Update inventory quantity (add new stock)
                                $sql_update = "UPDATE inventory SET quantity = quantity + ?, cost_price = ?, last_updated = NOW() WHERE item_id = ?";
                                $stmt_up = $conn->prepare($sql_update);
                                $stmt_up->bind_param("ddi", $quantity, $cost_price, $item_id);
                                $stmt_up->execute();

                                echo "
                                <div class='success' id='successMessage'>✅ Stock added successfully and inventory updated!</div>
                                <script>
                                    // Hide the success message after 5 seconds (5,000 ms)
                                    setTimeout(function() {
                                        var msg = document.getElementById('successMessage');
                                        if (msg) {
                                            msg.style.transition = 'opacity 0.5s ease';
                                            msg.style.opacity = '0';
                                            setTimeout(function(){ msg.remove(); }, 500); // Remove from DOM after fade-out
                                        }
                                    }, 10000);
                                </script>
                                ";

                            } else {
                                echo "<div class='error'>❌ Error: " . $stmt_in->error . "</div>";
                            }
                        }

                    ?>

                    <form method="POST" action="stock_in.php">
                        <label>Item Name:</label>
                        <input type="text" name="item_name" required><br>

                        <label>Description:</label>
                        <input type="text" name="description"><br>

                        <label>Quantity:</label>
                        <input type="number" step="0.001" name="quantity" required><br>

                        <label>Unit of Measure:</label>
                        <input type="text" name="unit_of_measure" placeholder="e.g. Litre, Bottle, Carton"><br>

                        <label>Cost Price:</label>
                        <input type="number" step="0.01" name="cost_price" required><br>

                        <label>Selling Price:</label>
                        <input type="number" step="0.01" name="selling_price" placeholder="Optional"><br>

                        <label>Invoice No:</label>
                        <input type="text" name="invoice_no"><br>

                        <label>Date Received:</label>
                        <input type="date" name="date_received" required><br>

                        <label>Received By (User ID):</label>
                        <input type="number" name="received_by"><br><br>

                        <button type="submit">Add Stock</button>
                    </form>

              
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>