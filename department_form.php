<?php
session_start();
require('../../include/database/mysql_db.php'); // Adjust path as needed

// Optional: Protect page (require login)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// ========== Initialize ==========
$errors = [];
$success = "";

// Fetch all unique department names (if any exist in staff table)
$dept_query = $conn->query("
    SELECT DISTINCT department 
    FROM staff 
    WHERE department IS NOT NULL AND department <> ''
    ORDER BY department ASC
");

// Fetch all staff (for “in charge” dropdown)
$staff_query = $conn->query("
    SELECT staff_id, first_name, last_name, department 
    FROM staff 
    WHERE status = 'active' 
    ORDER BY department, first_name ASC
");

// ========== Handle Form Submission ==========
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $department_name = trim($_POST['department_name'] ?? '');
    $staff_id        = $_POST['staff_id'] ?? null;

    // Basic validation
    if (empty($department_name)) {
        $errors[] = "Department name is required.";
    }
    if (empty($staff_id)) {
        $errors[] = "Please select a staff in charge.";
    }

    if (empty($errors)) {
        // Get staff full name
        $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM staff WHERE staff_id = ?");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $staff = $result->fetch_assoc();
        $stmt->close();

        if ($staff) {
            $staff_name = $staff['full_name'];

            // Insert into departments table
            $stmt = $conn->prepare("
                INSERT INTO departments (name, staff_name)
                VALUES (?, ?)
            ");
            $stmt->bind_param("ss", $department_name, $staff_name);

            if ($stmt->execute()) {
                $success = "Department added successfully!";
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Invalid staff selected.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Department | Resort Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f9fb; }
        .container { max-width: 600px; margin-top: 50px; background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #04337d; margin-bottom: 20px; }
        .btn-primary { background: #04337d; border: none; }
        .btn-primary:hover { background: #03285f; }
        .error { color: #b71c1c; background: #ffebee; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { color: #1b5e20; background: #e8f5e9; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Add Department</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="" method="post">

        <!-- Department Name -->
        <div class="mb-3">
            <label for="department_name" class="form-label">Department Name</label>
            <select name="department_name" id="department_name" class="form-select" required>
                <option value="">-- Select Department --</option>
                <?php while ($d = $dept_query->fetch_assoc()): ?>
                    <option value="<?= htmlspecialchars($d['department']) ?>">
                        <?= htmlspecialchars($d['department']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <div class="form-text">Select from existing staff departments or type a new one below.</div>
        </div>

        <!-- OR type new department -->
        <div class="mb-3">
            <input type="text" name="department_name" class="form-control" placeholder="Or enter new department name">
        </div>

        <!-- Staff In Charge -->
        <div class="mb-3">
            <label for="staff_id" class="form-label">Staff In Charge</label>
            <select name="staff_id" id="staff_id" class="form-select" required>
                <option value="">-- Select Staff --</option>
                <?php while ($s = $staff_query->fetch_assoc()): ?>
                    <option value="<?= $s['staff_id'] ?>">
                        <?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['department'] . ')') ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary">Add Department</button>
        <a href="departments.php" class="btn btn-secondary">Back</a>

    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
