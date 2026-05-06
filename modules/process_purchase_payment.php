<?php
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if(empty($_POST['purchase_id']) || empty($_POST['amount'])) {
        die("Missing required data.");
    }

    $purchase_id = $conn->real_escape_string($_POST['purchase_id']);
    $amount = (float)$_POST['amount'];
    $bank_id = !empty($_POST['bank_id']) ? $conn->real_escape_string($_POST['bank_id']) : 'NULL';
    $payment_date = date('Y-m-d H:i:s');

    if($amount > 0) {
        
        $conn->begin_transaction();

        try {
            // 1. Insert into history log
            $sql_hist = "INSERT INTO purchase_payments (purchase_id, amount, bank_id, payment_date) VALUES ('$purchase_id', '$amount', $bank_id, '$payment_date')";
            $conn->query($sql_hist);

            // 2. Update purchase total paid
            $sql_pur = "UPDATE purchases SET paid_amount = paid_amount + $amount WHERE id = '$purchase_id'";
            $conn->query($sql_pur);

            // 3. Deduct from Bank Account
            if($bank_id != 'NULL') {
                $sql_bank = "UPDATE bank_accounts SET balance = balance - $amount WHERE id = $bank_id";
                $conn->query($sql_bank);
            }

            $conn->commit();
            echo "success";

        } catch (Exception $e) {
            $conn->rollback();
            echo "Transaction failed: " . $e->getMessage();
        }

    } else {
        echo "Amount must be greater than zero.";
    }
} else {
    echo "Invalid request.";
}
?>