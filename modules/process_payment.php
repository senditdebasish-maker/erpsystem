<?php
// Set correct timezone for accurate timestamps
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $invoice_id = $conn->real_escape_string($_POST['invoice_id']);
    $amount = (float)$_POST['amount'];
    $bank_id = !empty($_POST['bank_id']) ? $conn->real_escape_string($_POST['bank_id']) : 'NULL';
    $payment_date = date('Y-m-d H:i:s');

    if($amount > 0) {
        // 1. Log this specific payment instance
        $conn->query("INSERT INTO invoice_payments (invoice_id, amount, bank_id, payment_date) VALUES ('$invoice_id', '$amount', $bank_id, '$payment_date')");
        
        // 2. Add to the total paid amount on the invoice
        $conn->query("UPDATE invoices SET paid_amount = paid_amount + $amount WHERE id = '$invoice_id'");
        
        // 3. Update Bank Balance
        if($bank_id != 'NULL') {
            $conn->query("UPDATE bank_accounts SET balance = balance + $amount WHERE id = $bank_id");
        }
        
        echo "success";
    } else {
        echo "Invalid payment amount.";
    }
} else {
    echo "Invalid request.";
}
?>