<?php
include '../includes/db.php';

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    
    $sql = "DELETE FROM products WHERE id = '$id'";
    
    if($conn->query($sql)) {
        header("Location: ../inventory.php?msg=deleted");
    } else {
        echo "Error deleting product: " . $conn->error;
    }
} else {
    header("Location: ../inventory.php");
}
?>