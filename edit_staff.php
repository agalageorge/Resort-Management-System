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
$maxFileSize = 2 * 1024 * 1024;
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
   LOAD STAFF (NO SELECT *)
========================= */
$stmt = $conn->prepare("
    SELECT 
        staff_id, first_name, last_name, email, phone,
        gender, dob, department, position, role_id,
        address, emergency_contact, date_hired,
        date_terminated, status, salary,
        bank_name, bank_account, documents, profile_photo
    FROM staff
    WHERE staff_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $staff_id);
$stmt->execute();

$stmt->bind_result(
    $db_staff_id, $db_first_name, $db_last_name, $db_email, $db_phone,
    $db_gender, $db_dob, $db_department, $db_position, $db_role_id,
    $db_address, $db_emergency_contact, $db_date_hired,
    $db_date_terminated, $db_status, $db_salary,
    $db_bank_name, $db_bank_account, $db_documents, $db_profile_photo
);

if ($stmt->fetch()) {
    $staff = [
        'staff_id' => $db_staff_id,
        'first_name' => $db_first_name,
        'last_name' => $db_last_name,
        'email' => $db_email,
        'phone' => $db_phone,
        'gender' => $db_gender,
        'dob' => $db_dob,
        'department' => $db_department,
        'position' => $db_position,
        'role_id' => $db_role_id,
        'address' => $db_address,
        'emergency_contact' => $db_emergency_contact,
        'date_hired' => $db_date_hired,
        'date_terminated' => $db_date_terminated,
        'status' => $db_status,
        'salary' => $db_salary,
        'bank_name' => $db_bank_name,
        'bank_account' => $db_bank_account,
        'documents' => $db_documents,
        'profile_photo' => $db_profile_photo
    ];
} else {
    die("Staff not found.");
}
$stmt->close();

/* =========================
   LOAD CURRENT USER
========================= */
$current_user = ['first_name' => 'Admin'];

$stmt = $conn->prepare("
    SELECT s.first_name
    FROM staff s
    JOIN users u ON s.staff_id = u.staff_id
    WHERE u.user_id = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($admin_name);
    if ($stmt->fetch()) {
        $current_user['first_name'] = $admin_name ?: 'Admin';
    }
    $stmt->close();
}

/* =========================
   HANDLE UPDATE
========================= */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die("CSRF token mismatch");
    }

    $first = trim($_POST['first_name']);
    $last  = trim($_POST['last_name']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone']);
    $gender = in_array($_POST['gender'], ['male','female','other']) ? $_POST['gender'] : $staff['gender'];
    $dob = $_POST['dob'];
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $role_id = intval($_POST['role_id']);
    $address = trim($_POST['address']);
    $emergency_contact = trim($_POST['emergency_contact']);
    $date_hired = $_POST['date_hired'];
    $date_terminated = $_POST['date_terminated'];
    $status = in_array($_POST['status'], ['active','on_leave','terminated','suspended']) ? $_POST['status'] : $staff['status'];
    $salary = floatval($_POST['salary']);
    $bank_name = trim($_POST['bank_name']);
    $bank_account = trim($_POST['bank_account']);
    $documents = $_POST['documents'] ?? '[]';

    if (!$email) {
        $error = "Invalid email address.";
    }

    /* PHOTO UPLOAD */
    $profile_photo_path = $staff['profile_photo'];

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
                }
            } else {
                $error = "Invalid file type.";
            }
        } else {
            $error = "File too large or upload error.";
        }
    }

    /* UPDATE DATABASE */
    if (empty($error)) {

        $upd = $conn->prepare("
            UPDATE staff SET
                first_name=?, last_name=?, email=?, phone=?, gender=?,
                dob=?, department=?, position=?, role_id=?, address=?,
                emergency_contact=?, date_hired=?, date_terminated=?, status=?,
                salary=?, bank_name=?, bank_account=?, documents=?, profile_photo=?,
                updated_at=NOW()
            WHERE staff_id=?
        ");

        $upd->bind_param(
            "ssssssssisssssdssssi",
            $first, $last, $email, $phone, $gender,
            $dob, $department, $position, $role_id, $address,
            $emergency_contact, $date_hired, $date_terminated, $status,
            $salary, $bank_name, $bank_account, $documents,
            $profile_photo_path, $staff_id
        );

        if ($upd->execute()) {
            header("Location: edit_staff.php?id=$staff_id&success=1");
            exit;
        } else {
            $error = "Database update failed.";
        }

        $upd->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin Dashboard | Bolowei's World Resort Management System">
    <meta name="keywords" content="Bolowei's World Resort Website, Resort portal, Resort Management System, Resort software, Digital Resort System">
    <meta name="author" content="Akamatech Limited by Agala George">
    <title>Edit Staff | Bolowei's World Resort</title>
    <link rel="shortcut icon" href="../../images/bolowies_logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../../assets/font-awesome/css/font-awesome.css?version=0.0.4">
    <link rel="stylesheet" type="text/css" href="../../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../assets/css/style_2.css"/>
    <style>
        .error { color: red; font-weight: bold; }
        .success { color: green; font-weight: bold; }
        .staff_edit input, .staff_edit select, .staff_edit textarea {
            width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ccc; border-radius: 4px;
        }
        .staff_edit button {
            background: #04337d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;
        }
        .staff_edit button:hover { background: #022550; }
    </style>
</head>
<body>
    <div class="admin_main">
        <div class="admin_sideBar">
            <div class="sideBar_header">
                <a href="superadmin_dashboard.php"><img src="../../images/bolowies_logo2.png" height="60" width="80" alt="Logo"/></a>
            </div>
            <div class="sideBar_links">
                <div class="sideBar_top_links">
                    <nav class="sideBar_nav">
                        <a href="dashboard.php"><i class="fa fa-dashboard"></i>&nbsp; Dashboard</a>
                    </nav>
                    <nav class="sideBar_nav" id="active" style="background: #04337d;">
                        <a href="manage_staff.php" style="color: #fff;"><i class="fa fa-group"></i>&nbsp; Staff</a>
                    </nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Finance</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-newspaper-o"></i>&nbsp; Sales</a></nav>
                    <nav class="sideBar_nav"><a href="manage_store_house.php"><i class="fa fa-book"></i>&nbsp; Store House</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-pencil-square"></i>&nbsp; Bookings</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Expenses</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-money"></i>&nbsp; Salary</a></nav>
                    <nav class="sideBar_nav"><a href="departments.php"><i class="fa fa-building"></i>&nbsp; Departments</a></nav>
                    <nav class="sideBar_nav"><a href="#"><i class="fa fa-file-text"></i>&nbsp; Report</a></nav>
                    <br>
                    <nav class="sideBar_nav"><a href="../auth/logout.php"><i class="fa fa-sign-out"></i>&nbsp; Sign Out</a></nav>
                </div>
            </div>
        </div>

        <div class="main_content">
            <div class="content_header">
                <h2>Edit Staff</h2>
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
                <div class="admin_staff_content_top">
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-plus-circle"></i><p>80</p></div><div class="staff_count_down"><a href="add_staff.php"><i class="fa fa-sign-in"></i> Add Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-list-alt"></i><p>34</p></div><div class="staff_count_down"><a href="view_staff.php"><i class="fa fa-sign-in"></i> View Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-pencil-square"></i><p>36</p></div><div class="staff_count_down"><a href="edit_staff.php"><i class="fa fa-sign-in"></i> Update Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-user"></i><p>15</p></div><div class="staff_count_down"><a href="manage_roles.php"><i class="fa fa-sign-in"></i> Manage Roles</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-trash"></i><p>3</p></div><div class="staff_count_down"><a href="delete_staff.php"><i class="fa fa-sign-in"></i> Delete Staff</a></div></nav>
                    <nav id="staff_count"><div class="staff_count_top"><i class="fa fa-calendar"></i><p>64</p></div><div class="staff_count_down"><a href="staff.html"><i class="fa fa-sign-in"></i> Attendance</a></div></nav>
                </div>

                <div class="admin_staff_content_down">
                    <div class="staff_edit">
                        <?php if (isset($_GET['success'])): ?>
                            <p class="success">Staff updated successfully!</p>
                        <?php endif; ?>
                        <?php if (!empty($error)): ?>
                            <p class="error"><?= htmlspecialchars($error) ?></p>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                            First Name: <input type="text" name="first_name" value="<?= htmlspecialchars($staff['first_name'] ?? '') ?>" required><br>
                            Last Name: <input type="text" name="last_name" value="<?= htmlspecialchars($staff['last_name'] ?? '') ?>" required><br>
                            Email: <input type="email" name="email" value="<?= htmlspecialchars($staff['email'] ?? '') ?>" required><br>
                            Phone: <input type="text" name="phone" value="<?= htmlspecialchars($staff['phone'] ?? '') ?>"><br>
                            Gender:
                            <select name="gender">
                                <option value="male" <?= ($staff['gender'] == 'male' ? 'selected' : '') ?>>Male</option>
                                <option value="female" <?= ($staff['gender'] == 'female' ? 'selected' : '') ?>>Female</option>
                                <option value="other" <?= ($staff['gender'] == 'other' ? 'selected' : '') ?>>Other</option>
                            </select><br>
                            DOB: <input type="date" name="dob" value="<?= htmlspecialchars($staff['dob'] ?? '') ?>"><br>
                            Address: <input type="text" name="address" value="<?= htmlspecialchars($staff['address'] ?? '') ?>"><br>
                            Emergency Contact: <input type="text" name="emergency_contact" value="<?= htmlspecialchars($staff['emergency_contact'] ?? '') ?>"><br>
                            Department: <input type="text" name="department" value="<?= htmlspecialchars($staff['department'] ?? '') ?>"><br>
                            Position: <input type="text" name="position" value="<?= htmlspecialchars($staff['position'] ?? '') ?>"><br>
                            Date Hired: <input type="date" name="date_hired" value="<?= htmlspecialchars($staff['date_hired'] ?? '') ?>"><br>
                            Date Terminated: <input type="date" name="date_terminated" value="<?= htmlspecialchars($staff['date_terminated'] ?? '') ?>"><br>
                            Status:
                            <select name="status">
                                <option value="active" <?= ($staff['status'] == 'active' ? 'selected' : '') ?>>Active</option>
                                <option value="on_leave" <?= ($staff['status'] == 'on_leave' ? 'selected' : '') ?>>On Leave</option>
                                <option value="terminated" <?= ($staff['status'] == 'terminated' ? 'selected' : '') ?>>Terminated</option>
                                <option value="suspended" <?= ($staff['status'] == 'suspended' ? 'selected' : '') ?>>Suspended</option>
                            </select><br>
                            Salary: <input type="number" step="0.01" name="salary" value="<?= htmlspecialchars($staff['salary'] ?? '') ?>"><br>
                            Bank Name: <input type="text" name="bank_name" value="<?= htmlspecialchars($staff['bank_name'] ?? '') ?>"><br>
                            Bank Account: <input type="text" name="bank_account" value="<?= htmlspecialchars($staff['bank_account'] ?? '') ?>"><br>
                            Role:
                            <select name="role_id">
                                <option value="1" <?= ($staff['role_id'] == 1 ? 'selected' : '') ?>>Admin</option>
                                <option value="2" <?= ($staff['role_id'] == 2 ? 'selected' : '') ?>>Manager</option>
                                <option value="3" <?= ($staff['role_id'] == 3 ? 'selected' : '') ?>>Staff</option>
                            </select><br>

                            Profile Photo: <input type="file" name="profile_photo" accept="image/jpeg,image/png,image/gif"><br>
                            <?php if ($staff['profile_photo']): ?>
                                <p>Current: <img src="../../<?= htmlspecialchars($staff['profile_photo']) ?>" width="100" alt="Current Photo"></p>
                            <?php endif; ?>

                            Documents (JSON array, e.g. ["doc1.pdf"]):
                            <textarea name="documents" rows="3"><?= htmlspecialchars($staff['documents'] ?? '[]') ?></textarea><br>

                            <button type="submit">Update Staff</button>
                        </form>
                        <br>
                        <a href="view_staff.php">Back to Staff List</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>