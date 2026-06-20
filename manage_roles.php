<?php
declare(strict_types=1);
session_start();

/* =======================
   AUTHENTICATION CHECK
======================= */
if (empty($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

/* =======================
   DATABASE CONNECTION
======================= */
require '../../include/database/mysql_db.php';

/* =======================
   CSRF TOKEN
======================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =======================
   FETCH LOGGED-IN USER
======================= */
$current_user = ['first_name' => 'Admin'];

$stmt = $conn->prepare("
    SELECT s.first_name
    FROM staff s
    INNER JOIN users u ON s.staff_id = u.staff_id
    WHERE u.user_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $current_user = $row;
}
$stmt->close();

/* =======================
   FORM PROCESSING
======================= */
$error = '';
$success = '';
$role_name = '';
$description = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_role'])) {

    /* CSRF VALIDATION */
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Invalid request. Please refresh and try again.";
    } else {

        $role_name   = trim($_POST['role_name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        /* INPUT VALIDATION */
        if ($role_name === '') {
            $error = "Role name is required.";
        } elseif (strlen($role_name) > 50) {
            $error = "Role name must not exceed 50 characters.";
        } elseif (strlen($description) > 255) {
            $error = "Description must not exceed 255 characters.";
        } else {

            /* CHECK FOR DUPLICATE ROLE */
            $check = $conn->prepare("
                SELECT role_id FROM roles
                WHERE LOWER(role_name) = LOWER(?)
                LIMIT 1
            ");
            $check->bind_param("s", $role_name);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "This role already exists.";
            } else {

                /* INSERT ROLE */
                $insert = $conn->prepare("
                    INSERT INTO roles (role_name, description)
                    VALUES (?, ?)
                ");
                $insert->bind_param("ss", $role_name, $description);

                if ($insert->execute()) {
                    $success = "Role added successfully!";
                    $role_name = '';
                    $description = '';
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                } else {
                    $error = "Failed to add role. Please try again.";
                }
                $insert->close();
            }
            $check->close();
        }
    }
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
                <a href="dashboard.php">
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
                    <nav class="sideBar_nav"><a href="manage_store_house.php"><i class="fa fa-book"></i>&nbsp; Store House</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square"></i>&nbsp; Bookings</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Expenses</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Salary</a></nav>
                    <nav class="sideBar_nav"><a href="departments.php"><i class="fa fa-building"></i>&nbsp; Departments</a></nav>
                    <nav class="sideBar_nav"><a href="report"><i class="fa fa-file-text"></i>&nbsp; Report</a></nav>
                    <br>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="manage_roles.php" style="color: #fff;"><i class="fa fa-user-shield"></i>&nbsp; Add Role</a>
                    </nav>
                    <nav class="sideBar_nav"><a href="../auth/logout.php"><i class="fa fa-sign-out"></i>&nbsp; Sign Out</a></nav>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main_content">
            <div class="content_header">
                <h2>Add New Role</h2>
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
                <div class="admin_staff_content_top">
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-plus-circle"></i><p>80</p></div><div class="staff_count_down"><a href="add_staff.php"><i class="fa fa-sign-in"></i> Add Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-list-alt"></i><p>34</p></div><div class="staff_count_down"><a href="view_staff.php"><i class="fa fa-sign-in"></i> View Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-pencil-square"></i><p>36</p></div><div class="staff_count_down"><a href="edit_staff.php"><i class="fa fa-sign-in"></i> Update Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-user-shield"></i><p>15</p></div><div class="staff_count_down"><a href="manage_roles.php"><i class="fa fa-sign-in"></i> Add Role</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-trash"></i><p>3</p></div><div class="staff_count_down"><a href="delete_staff.php"><i class="fa fa-sign-in"></i> Delete Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-calendar"></i><p>64</p></div><div class="staff_count_down"><a href="staff.html"><i class="fa fa-sign-in"></i> Attendance</a></div></nav>
                </div>

                <div class="admin_staff_content_down">
                    <div class="staff_edit">
                        <h2 style="color: #116CE1; font-size: 20px; margin: 0 0 15px 0;">Create New Role</h2>

                        <?php if ($success): ?>
                            <div class="success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="error"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>

                        <form method="POST" class="role_form">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            <label for="role_name">Role Name <span style="color:red;">*</span></label>
                            <select name="role_name" required>
                                <option value="">Select a Role</option>
                                <option value="admin">Admin</option>
                                <option value="managing_director">Managing Director</option>
                                <option value="manager">Manager</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="accountant">Accountant</option>
                                <option value="vip_bar">VIP Bar</option>
                                <option value="bush_bar">Bush Bar</option>
                                <option value="pool_bar">Pool Bar</option>
                                <option value="ark_bar">Ark Bar</option>
                                <option value="boat_captain">Boat Captain</option>
                                <option value="front_desk">Front Desk</option>
                                <option value="house_keeping">House Keeping</option>
                                <option value="logistics ">Logistics</option>
                                <option value="management">Management</option>
                                <option value="mechanic">Merchanic</option>
                                <option value="security">Security</option>
                                <option value="store_keeper">Store</option>
                                <option value="vendor_cashier">Vendor Cashier</option>
                                <option value="others">Others</option>
                            </select>

                                
                            <label for="description">Description (Optional)</label>
                            <textarea name="description" id="description" placeholder="Brief description of the role..." maxlength="255"><?= htmlspecialchars($description ?? '') ?></textarea>

                            <button type="submit" name="save_role">Save Role</button>
                        </form>

                        <a href="view_roles.php" class="back-link">View Roles List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>