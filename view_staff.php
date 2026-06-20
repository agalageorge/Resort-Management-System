<?php
session_start();
require('../../include/database/mysql_db.php'); // DB connection

// ---- Access Control (Only Admin / Superadmin) ----
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] > 2) {
    header("Location: ../../pages/login.php");
    exit();
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
                        <a href="sales.php">
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
                        <?php
                            // ---- Fetch Staff ----
                            $sql = "SELECT staff_id, staff_code, first_name, last_name, email, phone, department, position, status, profile_photo 
                                    FROM staff ORDER BY staff_id DESC";
                            $result = $conn->query($sql);

                            // Debug: Show SQL error if query fails
                            if (!$result) {
                                die("Query Failed: " . $conn->error);
                            }
                            ?>
                        <h4 style="color: #116CE1; font-size: 20px; margin-top: -5px; margin-bottom: 5px;">All Staff</h4>

                        <?php if ($result->num_rows > 0): ?>
                        <table class="table_design_2">
                            <thead>
                                <tr>
                                    <th>Photo</th>
                                    <th>Staff Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($row['profile_photo'])): ?>
                                            <img src="../../<?= htmlspecialchars($row['profile_photo']) ?>" height="45" width="45">
                                        <?php else: ?>
                                            <img src="../../assets/default_user.png" height="45" width="45">
                                        <?php endif; ?>
                                    </td>
                                   <td><?= htmlspecialchars($row['staff_code'] ?? '') ?></td>
                                    <td><?= htmlspecialchars(($row['first_name'] ?? '') . " " . ($row['last_name'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars($row['email'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['phone'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['department'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($row['position'] ?? '') ?></td>
                                    <td><?= ucfirst(htmlspecialchars($row['status'] ?? '')) ?></td>

                                    <td>
                                        <a class="btn view" href="view_single_staff.php?id=<?= $row['staff_id'] ?>">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <?php else: ?>
                        <p style="padding:10px;">No staff record found.</p>
                        <?php endif; ?>



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