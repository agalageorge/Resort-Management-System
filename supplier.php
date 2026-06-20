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
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission.";
    } else {
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $contact_person = trim($_POST['contact_person'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $address        = trim($_POST['address'] ?? '');

        if ($supplier_name === '') {
            $error = "Supplier name is required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $supplier_name, $contact_person, $phone, $email, $address);

            if ($stmt->execute()) {
                $success = "Supplier added successfully!";
            } else {
                $error = "Error adding supplier: " . $conn->error;
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
    <title>Manage Suppliers | Bolowei's World Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .container { max-width: 900px; margin-top: 60px; }
        .form-card {
            background: white; border-radius: 10px; padding: 25px;
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }
        h2 { color: #04337d; font-weight: bold; margin-bottom: 25px; }
        .btn-primary { background-color: #04337d; border: none; }
        .btn-primary:hover { background-color: #022b5e; }
        .alert { margin-top: 15px; }
    </style>
</head>
<body>

<div class="container">
    <div class="form-card">
        <h2>Manage Suppliers</h2>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Supplier Name <span style="color:red;">*</span></label>
                    <input type="text" name="supplier_name" class="form-control" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Add Supplier</button>
            <a href="inventory.php" class="btn btn-secondary">Back</a>
        </form>

        <hr>
        <h4>Existing Suppliers</h4>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM suppliers ORDER BY supplier_id DESC");
                if ($result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                ?>
                <tr>
                    <td><?= $row['supplier_id'] ?></td>
                    <td><?= htmlspecialchars($row['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($row['contact_person']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['address']) ?></td>
                </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center text-muted">No suppliers found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
