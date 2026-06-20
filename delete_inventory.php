<?php
session_start();

if (!isset($_SESSION['user_id']) || !is_numeric($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

require('../../include/database/mysql_db.php');

$item_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($item_id <= 0) {
    die("Invalid item ID");
}

// Optional: Confirm item exists
$check = $conn->prepare("SELECT item_id FROM inventory WHERE item_id = ?");
$check->bind_param("i", $item_id);
$check->execute();
$result = $check->get_result();
if ($result->num_rows === 0) {
    die("Item not found");
}

// Perform delete
$delete = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
$delete->bind_param("i", $item_id);

if ($delete->execute()) {
    header("Location: inventory.php?msg=Item deleted successfully");
    exit;
} else {
    echo "Error deleting item: " . $conn->error;
}

$conn->close();
?>
