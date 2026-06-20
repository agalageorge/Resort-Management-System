<?php
session_start();
require('../../include/database/mysql_db.php'); // adjust path if needed

// === CSRF Token ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = $error = "";

// === Handle Form Submission ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // === Security: Verify CSRF Token ===
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // === Clean Input ===
        $category_name = trim($_POST['category_name'] ?? '');
        $description   = trim($_POST['description'] ?? '');

        if ($category_name === '') {
            $error = "Category name is required.";
        } else {
            // === Insert into database ===
            $stmt = $conn->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $category_name, $description);

            if ($stmt->execute()) {
                $success = "Category added successfully!";
            } else {
                $error = "Error adding category: " . $conn->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Brands | Bolowei's World Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .container { max-width: 800px; margin-top: 60px; }
        .form-card {
            background: white; border-radius: 10px; padding: 25px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        h2 { color: #007bff; font-weight: bold; margin-bottom: 25px; }
        .btn-primary { background-color: #04337d; border: none; }
        .btn-primary:hover { background-color: #022b5e; }
        .alert { margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <div class="form-card">
        <h2>Manage Catergory</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label class="form-label">Category Name <span style="color:red;">*</span></label>
                <input type="text" name="category_name" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Brand</button>
            <a href="inventory.php" class="btn btn-secondary">Back</a>
        </form>

        <hr>
        <h4>Existing Brands</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM categories ORDER BY category_id DESC");
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $row['category_id'] ?></td>
                    <td><?= htmlspecialchars($row['category_name']) ?></td>
                    <td><?= htmlspecialchars($row['description']) ?></td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" class="text-center text-muted">No Category found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
