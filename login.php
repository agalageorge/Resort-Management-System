<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta-name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login | Bolowei's World Resort Management System">
    <meta name="keywords" content="Bolowei's World Resort Website, Resort portal, Resort Management System, Resort software">
    <meta name="author" content="Akamatech Limited by Agala George">
    <title>Login | Bolowei's World Resort Management System</title>

    <link rel="shortcut icon" href="../images/bolowies_logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="../assets/font-awesome/css/font-awesome.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../assets/css/style_2.css"/>
</head>

<body>
    <div class="login-main">
        <!-- LEFT SIDE -->
        <div class="login-left">
            <div id="login-header">
                <div class="home-icon">
                    <a href="../index.html"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" ...></svg></a>
                </div>
                <div class="logo">
                    <a href="../index.html"><img src="../images/bolowies_logo.png" height="80" width="80"></a>
                </div>
            </div>

            <div class="login-content">
                <h2>LOGIN PAGE</h2>
            </div>

            <div id="login-content-2">
                <h2>Bolowei's World Resort Management System</h2>
                <p>An advanced system that boosts engagement & saves time.</p>
                <ul>
                    <li>✔ Billing & Accounting</li>
                    <li>✔ Reporting & Analytics</li>
                    <li>✔ Inventory Management</li>
                    <li>✔ Guest CRM</li>
                </ul>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="login-right">

            <div class="login-right-top">
                <img src="../images/login-img.png" height="50" width="50">
                <h2>Portal Login</h2>
            </div>

            <div class="google-login">
                <a href="#../module/admin/index.html">
                    <button type="button">
                        <img src="../images/login_with_google_image.svg" class="svg-logo">
                    </button>
                </a>
            </div>

            <h5 style="color:#bdbdbd;text-align:center;font-size:20px;margin:20px;">OR</h5>

            <!-- ✅ PHP LOGIN SCRIPT -->
            <?php
            session_start();
            require('../include/database/mysql_db.php');


            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                $username = $_POST['username'];
                $password = $_POST['password'];

                $sql = "SELECT user_id, password_hash, role_id FROM users 
                        WHERE username=? AND is_active=1 LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s",$username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['role_id'] = $user['role_id'];

                    if ($user['role_id'] == 1) {
                        header("Location: superadmin_dashboard.php");
                    } elseif ($user['role_id'] == 2) {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: staff_dashboard.php");
                    }
                    exit;
                } else {
                    echo "Invalid login";
                }
            }
            ?>
            <?php if (!empty($error)) { ?>
                <p style="color:red; text-align:center;"><?= $error ?></p>
            <?php } ?>

            <!-- LOGIN FORM -->
            <form method="POST">
                <div class="form-container">
                    <input type="text" name="email" placeholder="Email / Username" required>
                </div>
                <br>

                <div class="form-container" style="display:flex">
                    <input type="password" name="password" id="password" placeholder="Password" required>
                    <a href="#" onclick="togglePass();return false;" class="show_password">
                        <i class="fa fa-eye" id="eye_icon"></i> <span id="show_txt">show</span>
                    </a>
                </div>

                <br><br>

                <div class="checkbox-custom">
                    <input type="checkbox" name="remember_me"> &nbsp; Keep me logged in &nbsp;&nbsp;
                    <a href="password_recovery.html" style="float:right;" class="forgot_password"> Forgot Password?</a>
                </div>

                <br>

                <button class="login100-form-btn login-btn" type="submit">
                    <i class="fa fa-sign-in">&nbsp; Sign in</i>
                </button>
            </form>
        </div>
    </div>

<script>
function togglePass() {
    let pass = document.getElementById("password");
    let text = document.getElementById("show_txt");
    pass.type = pass.type === "password" ? "text" : "password";
    text.innerText = text.innerText === "show" ? "hide" : "show";
}
</script>

</body>
</html>
