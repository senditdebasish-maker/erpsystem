<?php
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $desc = $_POST['desc'];
    $amount = $_POST['amount'];

    $sql = "UPDATE expenses SET description = '$desc', amount = '$amount' WHERE id = '$id'";

    if($conn->query($sql)) {
        header("Location: ../expenses.php?msg=updated");
    } else {
        echo "Error updating expense: " . $conn->error;
    }
}
?>