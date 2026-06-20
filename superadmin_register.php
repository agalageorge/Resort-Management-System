<?php
require('../include/database/mysql_db.php'); 

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $first = $_POST['first_name'];
    $last = $_POST['last_name'];
    $email = $_POST['email'];
    $username = $_POST['username'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert into staff
    $sqlStaff = "INSERT INTO staff (staff_code, first_name, last_name, email, role_id, status)
                VALUES ('STR-0000', ?, ?, ?, 1, 'active')";
    $stmt = $conn->prepare($sqlStaff);
    $stmt->bind_param("sss", $first, $last, $email);
    $stmt->execute();
    $staff_id = $stmt->insert_id;

    // Insert user
    $sqlUser = "INSERT INTO users (staff_id, username, email, password_hash, role_id)
                VALUES (?, ?, ?, ?, 1)";
    $stmt2 = $conn->prepare($sqlUser);
    $stmt2->bind_param("isss", $staff_id, $username, $email, $pass);
    $stmt2->execute();

    echo "Super Admin account created. <a href='login.php'>Login</a>";
}
?>
<form method="POST">
<input name="first_name" placeholder="First Name" required>
<input name="last_name" placeholder="Last Name" required>
<input name="email" type="email" placeholder="Email" required>
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Create Super Admin</button>
</form>
