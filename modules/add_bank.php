<?php
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bank_name = $_POST['bank_name'];
    $account_no = $_POST['account_no'];
    $ifsc = $_POST['ifsc'];
    $branch = $_POST['branch'];
    $balance = $_POST['balance'];

    $sql = "INSERT INTO bank_accounts (bank_name, account_no, ifsc_code, branch_name, balance) 
            VALUES ('$bank_name', '$account_no', '$ifsc', '$branch', '$balance')";

    if($conn->query($sql)) {
        header("Location: ../bank_management.php");
    } else {
        echo "Error: " . $conn->error;
    }
}
?>