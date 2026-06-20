<?php
session_start();
include_once('../include/database/mysql_db.php');

try {
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $login = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $pdo->prepare("
            SELECT u.user_id, u.username, u.password_hash, r.role_name
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            WHERE u.username = ? AND u.is_active = 1
        ");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash']) && $user['role_name'] === 'admin') {
            $_SESSION['admin_id'] = $user['user_id'];
            $_SESSION['admin_username'] = $user['username'];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            echo "❌ Invalid credentials or not an admin user.";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

<!-- Simple HTML form -->
<form method="POST">
  <h3>Admin Login</h3>
  <input type="text" name="username" placeholder="Username" required><br><br>
  <input type="password" name="password" placeholder="Password" required><br><br>
  <button type="submit">Login</button>
</form>
