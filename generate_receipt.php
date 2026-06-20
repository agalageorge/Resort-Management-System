<?php
session_start();
require('../../include/database/mysql_db.php');
require('../../include/phpqrcode/qrlib.php'); // QR Code library

// Validate receipt number
if (!isset($_GET['receipt_no'])) {
    die("<h3 style='color:red'>Invalid receipt number.</h3>");
}

$receipt_no = $_GET['receipt_no'];

// Fetch receipt data
$sql = "
    SELECT s.*, i.item_name 
    FROM sales s 
    LEFT JOIN inventory i ON s.item_id = i.item_id
    WHERE s.receipt_no = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $receipt_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("<h3 style='color:red'>Receipt not found.</h3>");
}

$receipt = $result->fetch_assoc();


// ===== Generate QR Code =====
$qrTempDir = "temp_qr/";
if (!file_exists($qrTempDir)) mkdir($qrTempDir, 0755, true);

$qrFile = $qrTempDir . $receipt_no . ".png";

QRcode::png($receipt_no, $qrFile, QR_ECLEVEL_L, 4);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt - <?php echo $receipt_no; ?></title>
    <style>
        body {
            font-family: monospace;
            padding: 0;
            margin: 0;
            background: #fff;
        }
        .receipt {
            width: 300px; /* 80mm paper width */
            padding: 10px;
            margin: auto;
        }
        .center {
            text-align: center;
        }
        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        table {
            width: 100%;
            font-size: 13px;
        }
        td {
            padding: 3px 0;
        }
        .bigtotal {
            font-size: 18px;
            font-weight: bold;
        }
        @media print {
            .no-print {display:none;}
            body { margin:0; }
        }
    </style>
</head>
<body>

<div class="receipt">

    <div class="center">
         <img src="../../images/bolowies_logo.png" height="70px" width="100px"/>
        <h3 style="margin:0;">SALES RECEIPT</h3>
        <small><?php echo date("M d, Y h:i A", strtotime($receipt['created_at'])); ?></small>
    </div>

    <div class="line"></div>

    <table>
        <tr><td>Receipt No:</td><td><?php echo $receipt_no; ?></td></tr>
        <tr><td>Client:</td><td><?php echo $receipt['client_name']; ?></td></tr>
        <tr><td>Contact:</td><td><?php echo $receipt['client_contact']; ?></td></tr>
        <tr><td>Item:</td><td><?php echo $receipt['item_name']; ?></td></tr>
        <tr><td>Rate:</td><td>₦<?php echo number_format($receipt['rate'],2); ?></td></tr>
        <tr><td>Qty:</td><td><?php echo $receipt['qty']; ?></td></tr>
        <tr><td>Subtotal:</td><td>₦<?php echo number_format($receipt['sub_amount'],2); ?></td></tr>
        <tr><td>Payment:</td><td><?php echo $receipt['payment_type']; ?></td></tr>
    </table>

    <div class="line"></div>

    <p class="bigtotal center">TOTAL: ₦<?php echo number_format($receipt['grand_total'],2); ?></p>

    <div class="line"></div>

    <div class="center">
        <img src="<?php echo $qrFile; ?>" width="120"><br>
        <small>Scan to verify receipt</small>
    </div>

    <div class="line"></div>

    <p class="center">Thank you for your purchase!</p>

    <div class="center no-print">
        <button onclick="window.print()" style="
            padding:10px;
            background:black;
            color:white;
            border:none;
            width:100%;
            margin-top:10px;
            cursor:pointer;">
            Print Receipt
        </button>
    </div>

</div>

</body>
</html>
