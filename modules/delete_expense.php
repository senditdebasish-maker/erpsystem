<?php
include '../includes/db.php';

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "DELETE FROM expenses WHERE id = '$id'";
    
    if($conn->query($sql)) {
        header("Location: ../expenses.php?msg=deleted");
    } else {
        echo "Error deleting expense: " . $conn->error;
    }
} else {
    header("Location: ../expenses.php");
}
?>