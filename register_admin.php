<?php
/*********************************************
 * Admin Registration Script
 * Auto-creates 'admin' role and adds admin user
 *********************************************/

include_once('../include/database/mysql_db.php');

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Validate required fields
    $required = ['username', 'password', 'email', 'full_name'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $pdo->beginTransaction();

    // Step 1: Ensure admin role exists
    $stmt = $pdo->prepare("SELECT role_id FROM roles WHERE role_name = ?");
    $stmt->execute(['admin']);
    $role = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$role) {
        $stmt = $pdo->prepare("INSERT INTO roles (role_name, description) VALUES (?, ?)");
        $stmt->execute(['admin', 'Administrator role with full system access']);
        $role_id = $pdo->lastInsertId();
    } else {
        $role_id = $role['role_id'];
    }

    // Step 2: Create user account
    $username = strtolower(trim($_POST['username']));
    $email = $_POST['email'];
    $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Prevent duplicates
    $checkUser = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $checkUser->execute([$username, $email]);
    if ($checkUser->fetch()) {
        throw new Exception("Username or email already exists!");
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$username, $email, $password_hash, $role_id]);
    $user_id = $pdo->lastInsertId();

    // Step 3: Create admin profile
    $stmt = $pdo->prepare("INSERT INTO admins (user_id, surname, other_names, phone, email, password) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $_POST['surname'],
        $_POST['other_names'],
        $_POST['phone'] ?? null,
        $_POST['email'],
        $_POST['password']
    ]);

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Admin Successfully Registered.",
        "user_id" => $user_id
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Registration</title>
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/css/style_2.css"/>

</head>
<body>
  <div class="admin_reg">
    <h2>Register New Admin</h2>
  <form action="register_admin.php" method="POST">
    <label>Surame:</label><br>
    <input type="text" name="surname" required><br><br>

    <label>Other Names:</label><br>
    <input type="text" name="other_names" required><br><br>

    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <label>Phone:</label><br>
    <input type="text" name="phone"><br><br>

    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <button type="submit">Register Admin</button>
  </form>
  </div>
</body>
</html>
