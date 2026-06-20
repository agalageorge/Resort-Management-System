<?php
session_start();
require('../../include/database/mysql_db.php');

// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Initialize variables
$name = $staff_name = "";
$edit_state = false;
$department_id = 0;


// Handle Edit Mode
if (isset($_GET['edit'])) {
    $department_id = (int) $_GET['edit'];
    $edit_state = true;
    $result = $conn->query("SELECT * FROM departments WHERE department_id=$department_id LIMIT 1");
    $record = $result->fetch_assoc();
    $name = $record['name'];
    $staff_name = $record['staff_name'];
}

// Handle Update
if (isset($_POST['update'])) {
    $department_id = (int) $_POST['department_id'];
    $name = trim($_POST['name']);
    $staff_name = trim($_POST['staff_name']);

    $stmt = $conn->prepare("UPDATE departments SET name=?, staff_name=? WHERE department_id=?");
    $stmt->bind_param("ssi", $name, $staff_name, $department_id);
    if ($stmt->execute()) {
        $message = "Department updated successfully.";
        $edit_state = false;
    } else {
        $error = "Error updating: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $department_id = (int) $_GET['delete'];
    if ($conn->query("DELETE FROM departments WHERE department_id=$department_id")) {
        $message = "Department deleted successfully.";
    } else {
        $error = "Error deleting record: " . $conn->error;
    }
}

// Fetch all departments
$departments = $conn->query("SELECT * FROM departments ORDER BY department_id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Dashboard  | Bolowei's World Resort Management System">
    <meta name="keywords" content="Bolowei's World Resort Website, Resort portal, Resort Management System, Resort software, Digital Resort System">
    <meta name="author" content="Akamatech Limited by Agala George">
    <title>Admin Dashboard | Bolowei's World Resort Management System</title>

    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
    <div class="admin_main">
        <div class="admin_sideBar">
            <div class="sideBar_header">
                <a href="superadmin_dashboard.php"><img src="../../images/bolowies_logo2.png" height="60px" width="80px"/></a>
            </div>
            <div class="sideBar_links">
                <div class="sideBar_top_links">
                    <nav class="sideBar_nav">
                        <a href="dashboard.php">
                            <i class="fa fa-dashboard"></i>&nbsp; Dashboard
                        </a>
                    </nav>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="manage_staff.php" style="color: #fff;">
                            <i class="fa fa-group"></i>&nbsp; Staff
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-money"></i>&nbsp; Finance
                        </a>
                    </nav>
                    <nav class="sideBar_nav">
                        <a href="#">
                            <i class="fa fa-newspaper-o aria-hidden="true"></i>&nbsp; Sales
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
                    <nav class="sideBar_nav">
                        <a href="#">
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
            <div class="content_header">
                <h2>Manage Departments</h2>
                <div id="search_content">
                    <label>
                        <i class="fa fa-search"></i>
                        <input type="text" name="email" required placeholder="Search">
                    </label>
                </div>
                <div id="notification_content">
                     <label class="notifications message_notify">
                        <i class="fa fa-envelope"></i>
                    </label>
                    <label class="notifications genaral_notify">
                        <i class="fa fa-bell"></i>
                    </label>
                    <div class="user_prolfile">
                        <img src="../../images/user_image.jfif" height="55px" width="55px"/>
                        <div class="profile" style="color: #333;">
                            <h5>

                                <?php
                                    $user_id = $_SESSION['user_id'];

                                    $sql = "SELECT staff.first_name, staff.last_name 
                                            FROM staff 
                                            JOIN users ON staff.staff_id = users.staff_id
                                            WHERE users.user_id = ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $user = $result->fetch_assoc();

                                    echo "Welcome, " . $user['first_name'];
                                ?>
                            </h5>
                            <p style="color: #5a5a5a;">Admin</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin_content">
                <div class="admin_dept_content_top">
                    <nav id="store_house"><a href="inventory.php"><i class="fa fa-indent" aria-hidden="true"></i> Store Inventory </a></nav>
                    <nav id="store_house"><a href="stock_in.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Stock-In </a></nav>
                    <nav id="store_house"><a href="stock_out.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Stock Out</a></nav>
                    <nav id="store_house"><a href="stock_transfer.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Stock Transfer</a></nav>
                    <nav id="store_house"><a href="category.php"><i class="fa fa-shopping-basket" aria-hidden="true"></i> Category</a></nav>
                    <nav id="store_house"><a href="brand.php"><i class="fa fa-cart-plus" aria-hidden="true"></i> Brand</a></nav>
                    <nav id="store_house"><a href="supplier.php"><i class="fa fa-sign-in" aria-hidden="true"></i> Supplier</a></nav>
                    <nav id="store_house"><a href="reporting.php"><i class="fa fa-bars" aria-hidden="true"></i> Reporting</a></nav>
                </div>
                    <!-- Department List -->
                    <div class="dept_display">
                        <h5>Existing Departments</h5>
                        <a href="department_form.php" class="btn btn-secondary">Add Department</a>
                        <table class="table_design_recent_orders">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Department Name</th>
                                <th>Staff in Charge</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php while ($row = $departments->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['department_id'] ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['staff_name']) ?></td>
                                <td><?= $row['created_at'] ?></td>
                                <td>
                                <a href="departments.php?edit=<?= $row['department_id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="departments.php?delete=<?= $row['department_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this department?');">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
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