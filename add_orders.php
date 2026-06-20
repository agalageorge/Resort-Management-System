<?php
session_start();
require('../../include/database/mysql_db.php');

// Redirect if user not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Fetch inventory items
$items = $conn->query("
    SELECT item_id, item_name, selling_price, quantity 
    FROM inventory 
    WHERE status='active' 
    ORDER BY item_name ASC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sales / Orders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:#f7f7f7; }
        .card { border-radius:12px; }
    </style>
</head>
<body>

<div class="container my-4">

    <div class="card shadow">
        <div class="card-header bg-primary text-white text-center">
            <h4>Sales / Order Form</h4>
        </div>

        <div class="card-body">

            <form id="salesForm" method="POST" action="process_sales.php">

                <!-- ORDER INFORMATION -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Sales Date</label>
                        <input type="date" name="sales_date" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Client Contact</label>
                        <input type="text" name="client_contact" class="form-control">
                    </div>
                </div>

                <hr>

                <h5>Product Information</h5>

                <!-- PRODUCT ROW -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Product</label>
                        <select name="product_id" id="product" class="form-select" required>
                            <option value="">-- Select Product --</option>
                            <?php while($row = $items->fetch_assoc()): ?>
                                <option value="<?= $row['item_id']; ?>"
                                        data-price="<?= $row['selling_price']; ?>"
                                        data-qty="<?= $row['quantity']; ?>">
                                    <?= $row['item_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Rate</label>
                        <input type="number" id="rate" name="rate" readonly class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Available Qty</label>
                        <input type="text" id="available" readonly class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Qty</label>
                        <input type="number" id="qty" name="qty" required class="form-control">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Total</label>
                        <input type="number" id="total" name="total" readonly class="form-control">
                    </div>
                </div>

                <hr>

                <!-- TOTAL ROW -->
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Sub Amount</label>
                        <input type="number" id="sub_amount" name="sub_amount" readonly class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Grand Total</label>
                        <input type="number" id="grand_total" name="grand_total" readonly class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" required class="form-select">
                            <option value="">-- Select --</option>
                            <option value="Cash">Cash</option>
                            <option value="Transfer">Transfer</option>
                            <option value="POS">POS</option>
                        </select>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <button class="btn btn-success btn-lg">Submit Sale</button>
                </div>

            </form>

        </div>
    </div>
        <br>
    <a href="ticket_house.php" class="btn btn-secondary">Back</a>
</div>

<script>
// Product selection
document.getElementById("product").addEventListener("change", function() {
    let price = this.options[this.selectedIndex].dataset.price;
    let qty   = this.options[this.selectedIndex].dataset.qty;

    document.getElementById("rate").value = price;
    document.getElementById("available").value = qty;

    document.getElementById("qty").value = "";
    document.getElementById("total").value = "";
    document.getElementById("sub_amount").value = "";
    document.getElementById("grand_total").value = "";
});

// Auto-calc
document.getElementById("qty").addEventListener("input", function(){
    let rate = parseFloat(document.getElementById("rate").value) || 0;
    let qty  = parseFloat(this.value) || 0;

    if (rate > 0 && qty > 0) {
        let total = rate * qty;
        document.getElementById("total").value = total.toFixed(2);
        document.getElementById("sub_amount").value = total.toFixed(2);
        document.getElementById("grand_total").value = total.toFixed(2);
    }
});
</script>

</body>
</html>
