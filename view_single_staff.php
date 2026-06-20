<?php
session_start();

/* =========================
   SECURITY CHECK
========================= */
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$user_id = (int) $_SESSION['user_id'];

/* =========================
   CSRF TOKEN
========================= */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

/* =========================
   DATABASE
========================= */
require('../../include/database/mysql_db.php');

/* =========================
   CONFIG
========================= */
$uploadDir = __DIR__ . '/../../uploads/staff_photos/';
$maxFileSize = 2 * 1024 * 1024; // 2MB
$allowedMime = ['image/jpeg', 'image/png', 'image/gif'];

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/* =========================
   GET STAFF ID
========================= */
$staff_id = intval($_GET['id'] ?? 0);
if ($staff_id <= 0) {
    die("Invalid staff ID");
}

/* =========================
   LOAD STAFF (NO DOCUMENTS FIELD)
========================= */
$sql = "SELECT 
    staff_id, staff_code, first_name, last_name, email, phone, 
    gender, dob, department, position, role_id, address, 
    emergency_contact, bank_name, bank_account, salary, 
    status, date_hired, date_terminated, profile_photo
FROM staff 
WHERE staff_id = ? 
LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$stmt->bind_result(
    $db_staff_id, $db_staff_code, $db_first_name, $db_last_name, $db_email, $db_phone,
    $db_gender, $db_dob, $db_department, $db_position, $db_role_id, $db_address,
    $db_emergency_contact, $db_bank_name, $db_bank_account, $db_salary, $db_status,
    $db_date_hired, $db_date_terminated, $db_profile_photo
);

if ($stmt->fetch()) {
    $staff = [
        'staff_id'          => $db_staff_id,
        'staff_code'        => $db_staff_code,
        'first_name'        => $db_first_name,
        'last_name'         => $db_last_name,
        'email'             => $db_email,
        'phone'             => $db_phone,
        'gender'            => $db_gender,
        'dob'               => $db_dob,
        'department'        => $db_department,
        'position'          => $db_position,
        'role_id'           => $db_role_id,
        'address'           => $db_address,
        'emergency_contact' => $db_emergency_contact,
        'bank_name'         => $db_bank_name,
        'bank_account'      => $db_bank_account,
        'salary'            => $db_salary,
        'status'            => $db_status,
        'date_hired'        => $db_date_hired,
        'date_terminated'   => $db_date_terminated,
        'profile_photo'     => $db_profile_photo
    ];
} else {
    die("Staff not found.");
}
$stmt->close();

/* =========================
   HANDLE UPDATE
========================= */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "CSRF token mismatch";
    } else {

        $first   = trim($_POST['first_name']);
        $last    = trim($_POST['last_name']);
        $email   = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
        $phone   = trim($_POST['phone']);
        $gender  = in_array($_POST['gender'], ['male','female','other']) ? $_POST['gender'] : $staff['gender'];
        $salary  = floatval($_POST['salary']);
        $status  = in_array($_POST['status'], ['active','on_leave','terminated','suspended']) ? $_POST['status'] : $staff['status'];

        if (!$email) {
            $error = "Invalid email address.";
        }

        /* =========================
           PHOTO UPLOAD
        ========================= */
        $profile_photo_path = $staff['profile_photo'] ?? '';

        if (!empty($_FILES['profile_photo']['name']) && empty($error)) {
            $file = $_FILES['profile_photo'];

            if ($file['error'] === UPLOAD_ERR_OK && $file['size'] <= $maxFileSize) {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (in_array($mime, $allowedMime)) {

                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $safeName = 'staff_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $destination = $uploadDir . $safeName;

                    if (move_uploaded_file($file['tmp_name'], $destination)) {

                        if ($profile_photo_path && file_exists(__DIR__ . '/../../' . $profile_photo_path)) {
                            @unlink(__DIR__ . '/../../' . $profile_photo_path);
                        }

                        $profile_photo_path = 'uploads/staff_photos/' . $safeName;

                    } else {
                        $error = "Failed to save photo.";
                    }
                } else {
                    $error = "Invalid file type.";
                }
            } else {
                $error = "Upload error or file too large.";
            }
        }

        /* =========================
           UPDATE DATABASE
        ========================= */
        if (empty($error)) {

            $upd_sql = "
                UPDATE staff SET
                    first_name=?, last_name=?, email=?, phone=?, 
                    gender=?, salary=?, status=?, profile_photo=?,
                    updated_at=NOW()
                WHERE staff_id=?
            ";

            $upd = $conn->prepare($upd_sql);

            if (!$upd) {
                $error = "Update prepare failed: " . $conn->error;
            } else {

                $upd->bind_param(
                    "sssssdssi",
                    $first, $last, $email, $phone,
                    $gender, $salary, $status,
                    $profile_photo_path,
                    $staff_id
                );

                if ($upd->execute()) {
                    header("Location: view_single_staff.php?id=$staff_id&success=1");
                    exit;
                } else {
                    $error = "Update failed: " . $upd->error;
                }

                $upd->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="widtd=device-widtd, initial-scale=1.0">
    <meta name="description" content="Admin Dashboard  | Bolowei's World Resort Management System">
    <meta name="keywords" content="Bolowei's World Resort Website, Resort portal, Resort Management System, Resort software, Digital Resort System">
    <meta name="autdor" content="Akamatech Limited by Agala George">
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
                <a href="dashboard.php"><img src="../../images/bolowies_logo2.png" height="60px" widtd="80px"/></a>
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
                        <a href="../autd/logout.php">
                            <i class="fa fa-sign-out"></i>&nbsp; Sign Out
                        </a>
                    </nav>
                </div>
            </div>
        </div>
        <div class="main_content">
            <div class="content_header">
                <h2>View Staff</h2>
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
                        <img src="../../images/user_image.jfif" height="55px" widtd="55px"/>
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
                            <i class="fa fa-td-list"></i>
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
                    <h2 style="color: #116CE1; font-size: 20px; margin-top: -5px; margin-bottom: 5px;">Staff Profile</h2>
                    <div class="staff_view">
                        <div class="staff_photo">
                            <img src="../../<?= $staff['profile_photo'] ?: 'assets/default_user.png' ?>" widtd="120">
                        </div>
                        <div class="staff_info">
                            <table class="table_design_3" >
                            <tr><td>Staff Code:</td><td><?= htmlspecialchars($staff['staff_code'] ?? '') ?></td></tr>
                            <tr><td>Name:</td><td><?= htmlspecialchars($staff['first_name'].' '.$staff['last_name']) ?></td></tr>
                            <tr><td>Email:</td><td><?= htmlspecialchars($staff['email'] ?? '') ?></td></tr>
                            <tr><td>Phone:</td><td><?= htmlspecialchars($staff['phone'] ?? '') ?></td></tr>
                            <tr><td>Gender:</td><td><?= htmlspecialchars($staff['gender'] ?? '') ?></td></tr>
                            <tr><td>Date of Birth:</td><td><?= htmlspecialchars($staff['dob'] ?? '') ?></td></tr>
                            <tr><td>Department:</td><td><?= htmlspecialchars($staff['department'] ?? '') ?></td></tr>
                            <tr><td>Position:</td><td><?= htmlspecialchars($staff['position'] ?? '') ?></td></tr>
                            <tr><td>Address:</td><td><?= htmlspecialchars($staff['address'] ?? '') ?></td></tr>
                            <tr><td>Emmergency Contact:</td><td><?= htmlspecialchars($staff['emergency_contact'] ?? '') ?></td></tr>
                            <tr><td>Bank Name:</td><td><?= htmlspecialchars($staff['bank_name'] ?? '') ?></td></tr>
                            <tr><td>Bank Account:</td><td><?= htmlspecialchars($staff['bank_account'] ?? '') ?></td></tr>
                            <tr><td>Salary:</td><td><?= htmlspecialchars($staff['salary'] ?? '') ?></td></tr>
                            <tr><td>Status:</td><td><?= htmlspecialchars($staff['status'] ?? '') ?></td></tr>
                            <tr><td>Date Hired:</td><td><?= htmlspecialchars($staff['date_hired'] ?? '') ?></td></tr>
                            <tr><td>Document:</td><td><?= htmlspecialchars($staff['document'] ?? '') ?></td></tr>
                            </table>
                            <br>
                            <a href="edit_staff.php?id=<?= $staff['staff_id'] ?>">✏ Edit Staff</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</body>
</html>