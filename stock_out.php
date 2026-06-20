<?php
session_start();

// === SECURITY: Check login & session ===
if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}
$logged_in_user = (int)$_SESSION['user_id'];

// === Database Connection ===
require('../../include/database/mysql_db.php'); // adjust path as needed

// === CSRF Token ===
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// === Initialize ===
$errors = [];
$success = '';

// === Handle Form Submission ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id        = $_POST['item_id'] ?? null;
    $department_id  = $_POST['department_id'] ?? null;
    $request_item_id = $_POST['request_item_id'] ?? null;
    $issued_qty     = $_POST['issued_qty'] ?? null;
    $issued_date    = $_POST['issued_date'] ?? null;
    $reference_no   = trim($_POST['reference_no'] ?? '');
    $note           = trim($_POST['note'] ?? '');

    // Validate Required Fields
    if (empty($item_id) || empty($issued_qty) || empty($issued_date)) {
        $errors[] = "Item, quantity, and issued date are required.";
    }

    // Validate issued quantity
    if (!empty($issued_qty) && $issued_qty <= 0) {
        $errors[] = "Issued quantity must be greater than zero.";
    }

    if (empty($errors)) {
        // Convert empty selections to NULL for foreign keys
        $department_id   = !empty($department_id) ? (int)$department_id : NULL;
        $request_item_id = !empty($request_item_id) ? (int)$request_item_id : NULL;

        // --- Insert Stock Out ---
        $sql = "INSERT INTO stock_out 
            (item_id, department_id, request_item_id, issued_qty, issued_by, issued_date, reference_no, note)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "iiiidsss",
            $item_id,
            $department_id,
            $request_item_id,
            $issued_qty,
            $logged_in_user,
            $issued_date,
            $reference_no,
            $note
        );

        // Handle optional NULLs properly
        if (empty($department_id)) $department_id = NULL;
        if (empty($request_item_id)) $request_item_id = NULL;

        if ($stmt->execute()) {

            // === Update inventory quantity ===
            $conn->query("UPDATE inventory SET quantity = quantity - $issued_qty WHERE item_id = $item_id");

            // === Optionally update request_items issued_qty ===
            if (!empty($request_item_id)) {
                $conn->query("UPDATE request_items SET issued_qty = issued_qty + $issued_qty WHERE request_item_id = $request_item_id");
            }

            // === Optionally mark store_request as 'issued' if fully processed ===
            if (!empty($request_item_id)) {
                $conn->query("
                    UPDATE store_requests sr
                    JOIN request_items ri ON sr.request_id = ri.request_id
                    SET sr.status = 'issued'
                    WHERE ri.request_item_id = $request_item_id
                    AND ri.issued_qty >= ri.approved_qty
                ");
            }

            $success = "Stock out record added successfully!";
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// === Fetch inventory items, departments, and request items ===
$inventory = $conn->query("
    SELECT item_id, item_name, quantity, unit_of_measure 
    FROM inventory 
    WHERE status='active' 
    ORDER BY item_name ASC
");

$departments = $conn->query("
    SELECT department_id, name 
    FROM departments 
    ORDER BY name ASC
");

$request_items = $conn->query("
    SELECT ri.request_item_id, i.item_name, sr.request_id, sr.department_id
    FROM request_items ri
    JOIN inventory i ON ri.item_id = i.item_id
    JOIN store_requests sr ON ri.request_id = sr.request_id
    WHERE sr.status IN ('approved', 'pending') 
      AND ri.approved_qty > ri.issued_qty
    ORDER BY sr.request_date DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Stock Out | Bolowei's World Resort</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .container { margin-top: 40px; max-width: 750px; }
        h2 { color: #04337d; margin-bottom: 25px; }
        .btn-primary { background-color: #04337d; border: none; }
        .btn-primary:hover { background-color: #022550; }
        .error { color: #d32f2f; background: #ffebee; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
        .success { color: #2e7d32; background: #e8f5e9; padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    </style>
</head>
<body>

<div class="container">
    <h2 class="text-center">Add Stock Out</h2>

    <!-- Feedback Messages -->
    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . "<br>"; ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form action="" method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

        <!-- Item -->
        <div class="mb-3">
            <label for="item_id" class="form-label">Item</label>
            <select name="item_id" id="item_id" class="form-select" required>
                <option value="">-- Select Item --</option>
                <?php while ($row = $inventory->fetch_assoc()): ?>
                    <option value="<?= $row['item_id'] ?>">
                        <?= htmlspecialchars($row['item_name']) ?> 
                        (<?= $row['quantity'] ?> <?= htmlspecialchars($row['unit_of_measure']) ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Department -->
        <div class="mb-3">
            <label for="department_id" class="form-label">Department</label>
            <select name="department_id" id="department_id" class="form-select">
                <option value="">-- Select Department (optional) --</option>
                <?php while ($row = $departments->fetch_assoc()): ?>
                    <option value="<?= $row['department_id'] ?>">
                        <?= htmlspecialchars($row['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Store Request Item -->
        <div class="mb-3">
            <label for="request_item_id" class="form-label">Store Request Item</label>
            <select name="request_item_id" id="request_item_id" class="form-select">
                <option value="">-- Optional --</option>
                <?php while ($row = $request_items->fetch_assoc()): ?>
                    <option value="<?= $row['request_item_id'] ?>">
                        Request #<?= $row['request_id'] ?> - <?= htmlspecialchars($row['item_name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- Quantity -->
        <div class="mb-3">
            <label for="issued_qty" class="form-label">Quantity Issued</label>
            <input type="number" step="0.001" name="issued_qty" id="issued_qty" class="form-control" required>
        </div>

        <!-- Date -->
        <div class="mb-3">
            <label for="issued_date" class="form-label">Issued Date</label>
            <input type="date" name="issued_date" id="issued_date" class="form-control" required>
        </div>

        <!-- Reference -->
        <div class="mb-3">
            <label for="reference_no" class="form-label">Reference No.</label>
            <input type="text" name="reference_no" id="reference_no" class="form-control">
        </div>

        <!-- Note -->
        <div class="mb-3">
            <label for="note" class="form-label">Note</label>
            <textarea name="note" id="note" class="form-control" rows="3"></textarea>
        </div>

        <!-- Buttons -->
        <button type="submit" class="btn btn-primary">Add Stock Out</button>
        <a href="stock_out_list.php" class="btn btn-secondary">Back to List</a>
    </form>
</div>

</body>
</html>

<?php $conn->close(); ?>
