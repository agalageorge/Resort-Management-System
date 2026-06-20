<?php
session_start();
require('../../include/database/mysql_db.php');

// CONFIG
$uploadDir = __DIR__ . '/../../uploads/staff_photos/';
$maxFileSize = 2 * 1024 * 1024;
$allowedMime = ['image/jpeg','image/png','image/gif'];

$errors = [];
$success = "";

// HANDLE POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // SANITIZE INPUTS
    $first = trim($_POST['first_name'] ?? '');
    $last  = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? 'male';
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $role_id = intval($_POST['role_id'] ?? 3);

    $create_user = isset($_POST['create_user']);
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // VALIDATION
    if ($first === '' || $last === '') $errors[] = "First and Last name required.";
    if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email.";
    if ($create_user && ($username === '' || $password === '')) $errors[] = "Username & password required.";

    // PHOTO UPLOAD
    $profile_photo_path = null;
    if (!empty($_FILES['profile_photo']['name'])) {
        $file = $_FILES['profile_photo'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "File upload error.";
        } elseif ($file['size'] > $maxFileSize) {
            $errors[] = "File too large (max 2MB).";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMime)) {
                $errors[] = "Only JPG, PNG, GIF allowed.";
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $safeName = 'staff_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;

                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                $dest = $uploadDir . $safeName;

                if (move_uploaded_file($file['tmp_name'], $dest)) {
                    $profile_photo_path = "uploads/staff_photos/$safeName";
                } else {
                    $errors[] = "Failed to save photo.";
                }
            }
        }
    }

    // IF NO ERRORS
    if (empty($errors)) {

        $conn->begin_transaction();

        try {

            // INSERT STAFF FIRST (WITHOUT staff_code)
            $sql = "INSERT INTO staff 
                    (first_name, last_name, email, phone, gender, department, position, role_id, profile_photo, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssis",
                $first, $last, $email, $phone, $gender, $department, $position, $role_id, $profile_photo_path
            );
            $stmt->execute();
            $staff_id = $stmt->insert_id;
            $stmt->close();

            // GENERATE SAFE STAFF CODE FROM AUTO ID
            $staff_code = "STR-" . str_pad($staff_id, 4, "0", STR_PAD_LEFT);
            $upd = $conn->prepare("UPDATE staff SET staff_code=? WHERE staff_id=?");
            $upd->bind_param("si", $staff_code, $staff_id);
            $upd->execute();
            $upd->close();

            // CREATE USER ACCOUNT
            if ($create_user) {

                // CHECK DUPLICATE USERNAME/EMAIL
                $chk = $conn->prepare("SELECT user_id FROM users WHERE username=? OR email=? LIMIT 1");
                $chk->bind_param("ss", $username, $email);
                $chk->execute();
                $chk->store_result();
                if ($chk->num_rows > 0) {
                    throw new Exception("Username or Email already exists.");
                }
                $chk->close();

                $hash = password_hash($password, PASSWORD_DEFAULT);

                $ins = $conn->prepare("INSERT INTO users (staff_id, username, email, password_hash, role_id, is_active)
                                       VALUES (?, ?, ?, ?, ?, 1)");
                $ins->bind_param("isssi", $staff_id, $username, $email, $hash, $role_id);
                $ins->execute();
                $ins->close();
            }

            $conn->commit();
            $success = "Staff created successfully. Staff Code: $staff_code";

        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Transaction failed: " . $e->getMessage();
        }
    }
}
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
                        <a href="#">
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
                <h2>Add Staff</h2>
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
                                    $stmt = $conn->prepare($sql);
                                    if (!$stmt) die("Prepare failed: " . $conn->error);
                                    $stmt->bind_param("i", $user_id);
                                    $stmt->execute();
                                    $stmt->bind_result($first_name, $last_name);
                                    $stmt->fetch();
                                    $stmt->close();

                                    echo "Welcome, " . htmlspecialchars($first_name);
                                ?>
                            </h5>
                            <p style="color: #5a5a5a;">Admin</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="admin_content">
                <div class="admin_staff_content_top">
                    <nav id="staff_count">
                        <div class="staff_count_top">
                            <i class="fa fa-plus-circle aria-hidden="true"></i>
                            <p>80</p>
                        </div>
                        <div class="staff_count_down">
                            <a href="add_staff.php  ">
                                <i class="fa fa-sign-in"></i> Add Staff
                            </a>
                        </div>
                    </nav>
                    <nav id="staff_count">
                        <div class="staff_count_top">
                            <i class="fa fa-th-list"></i>
                            <p>34</p>
                        </div>
                        <div class="staff_count_down">
                            <a href="view_staff.php">
                                <i class="fa fa-sign-in"></i> View Staff
                            </a>
                        </div>
                    </nav>
                    <nav id="staff_count">
                        <div class="staff_counter_top">
                            <i class="fa fa-pencil-square"></i>
                            <p>36</p>
                        </div>
                        <div class="staff_counter_down">
                            <a href="edit_staff.php">
                                <i class="fa fa-sign-in"></i> Update Staff
                            </a>
                        </div>
                    </nav>
                    <nav id="staff_count">
                        <div class="staff_count_top">
                            <i class="fa fa-user"></i>
                            <p>15</p>
                        </div>
                        <div class="staff_count_down">
                            <a href="manage_roles.php">
                                <i class="fa fa-sign-in"></i> Manage Roles
                            </a>
                        </div>
                    </nav>
                    <nav id="staff_count">
                        <div class="staff_count_top">
                            <i class="fa fa-trash"></i>
                            <p>3</p>
                        </div>
                        <div class="staff_count_down">
                            <a href="delete_staff.php">
                                <i class="fa fa-sign-in"></i> Delete Staff
                            </a>
                        </div>
                    </nav>
                    <nav id="staff_count">
                        <div class="staff_count_top">
                            <i class="fa fa-calendar"></i>
                            <p>64</p>
                        </div>
                        <div class="staff_count_down">
                            <a href="staff.html">
                                <i class="fa fa-sign-in"></i> Attendance
                            </a>
                        </div>
                    </nav>
                </div>
                <div class="admin_staff_content_down">
                    <div class="staff_reg">
                        <div class="wrap">
                            <h2>Register Staff</h2>

                            <?php if(!empty($success)): ?>
                                <p style="color:green"><?= htmlspecialchars($success) ?></p>
                            <?php endif; ?>

                            <?php if(!empty($errors)): ?>
                                <?php foreach($errors as $e): ?>
                                    <p style="color:red"><?= htmlspecialchars($e) ?></p>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <form method="post" enctype="multipart/form-data">
                                <label>First name *</label>
                                <input type="text" name="first_name" required>

                                <label>Last name *</label>
                                <input type="text" name="last_name" required>

                                <label>Email *</label>
                                <input type="email" name="email">

                                <label>Phone</label>
                                <input type="text" name="phone">

                                <label>Gender</label>
                                <select name="gender">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>

                                <label>Department *</label>
                                <select name="department" required>
                                    <option value="" selected>Select Department</option>
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

                                <label>Position</label>
                                <input type="text" name="position">

                                <label>Role *</label>
                                <select name="role_id" required>
                                    <option value="1">Admin</option>
                                    <option value="4">Managing Director</option>
                                    <option value="3">Manager</option>
                                    <option value="6">Accountant</option>
                                    <option value="7">Supervisor</option>
                                    <option value="8">Store Keeper</option>
                                    <option value="9">Front Desk</option>
                                    <option value="10">VIP Bar</option>
                                    <option value="11">Bush Bar</option>
                                    <option value="12">Pool Bar</option>
                                    <option value="13">Vendor Cashier</option>
                                    <option value="14">Ark Bar</option>
                                    <option value="" selected>Select a Role</option>
                                </select>

                                <label>Profile photo (optional, jpg/png/gif &lt;2MB)</label>
                                <input type="file" name="profile_photo" accept="image/*">

                                <hr>
                                <label><input type="checkbox" name="create_user" id="create_user"> Create user account for this staff</label>

                                <div id="user_fields" style="display:none;">
                                    <label>Username</label>
                                    <input type="text" name="username" autocomplete="off">

                                    <label>Password</label>
                                    <input type="password" name="password" autocomplete="new-password">
                                </div>

                                <br>
                                <button type="submit">Register Staff</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('create_user').addEventListener('change', function(){
            document.getElementById('user_fields').style.display = this.checked ? 'block' : 'none';
        });
    </script>
</body>
</html>