<?php
session_start();
require('../../include/database/mysql_db.php');

// If user is NOT logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Collect form data
    $sales_date     = $_POST['sales_date'];
    $client_name    = $_POST['client_name'];
    $client_contact = $_POST['client_contact'];
    $product_id     = $_POST['product_id'];
    $rate           = $_POST['rate'];
    $qty            = $_POST['qty'];
    $total          = $_POST['total'];
    $sub_amount     = $_POST['sub_amount'];
    $grand_total    = $_POST['grand_total'];
    $payment_type   = $_POST['payment_type'];

    // Validate product ID
    if (empty($product_id)) {
        die("<h3 style='color:red'>Invalid product selected.</h3>");
    }

    // Fetch current quantity
    $sql = "SELECT quantity, item_name FROM inventory WHERE item_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();

    if (!$item) {
        die("<h3 style='color:red'>Product not found in inventory.</h3>");
    }

    $current_qty = $item['quantity'];
    $item_name   = $item['item_name'];

    // -------------- STOCK CHECK --------------------
    if ($qty > $current_qty) {
        die("
            <h3 style='color:red'>Error: You cannot sell more than the available quantity.</h3>
            <p><strong>Item:</strong> $item_name</p>
            <p><strong>Available:</strong> $current_qty</p>
            <p><strong>Requested:</strong> $qty</p>
        ");
    }
    // ------------------------------------------------

    // Generate unique receipt number
    $receipt_no = "R-" . strtoupper(uniqid());

    // Insert into sales table
    $insert = "
        INSERT INTO sales (
            sales_date, client_name, client_contact, item_id, rate, qty, total, 
            sub_amount, grand_total, payment_type, receipt_no, created_at
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())
    ";

    $stmt2 = $conn->prepare($insert);
    $stmt2->bind_param("sssidddddss",
    $sales_date,
    $client_name,
    $client_contact,
    $product_id,
    $rate,
    $qty,
    $total,
    $sub_amount,
    $grand_total,
    $payment_type,
    $receipt_no
    );


    if ($stmt2->execute()) {

        // Deduct from stock
        $new_qty = $current_qty - $qty;

        $update = "UPDATE inventory SET quantity = ? WHERE item_id = ?";
        $stmt3 = $conn->prepare($update);
        $stmt3->bind_param("di", $new_qty, $product_id);
        $stmt3->execute();

        echo "
            <div style='padding:20px; background:#d1ffd1; border:1px solid #8c8; width:60%; margin:auto; margin-top:40px; border-radius:10px;'>
                <h3 style='color:green'>SALE RECORDED SUCCESSFULLY!</h3>
                <p><strong>Receipt No:</strong> $receipt_no</p>
                <p><strong>Client:</strong> $client_name</p>
                <p><strong>Product:</strong> $item_name</p>
                <p><strong>Qty:</strong> $qty</p>
                <p><strong>Total Amount:</strong> ₦" . number_format($grand_total,2) . "</p>

                <a href='generate_receipt.php?receipt_no=$receipt_no' 
                   class='btn btn-warning mt-3' target='_blank'>Print Receipt</a>

                <a href='add_orders.php' class='btn btn-primary mt-3'>Record Another Sale</a>
            </div>
        ";

    } else {
        echo "<h3 style='color:red'>Database Error: Could not save sale.</h3>";
    }

}
?>
