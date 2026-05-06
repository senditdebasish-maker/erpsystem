<?php
include '../includes/db.php';

// Check if an ID was passed in the URL
if(isset($_GET['id']) && !empty($_GET['id'])) {
    
    $id = $_GET['id'];
    
    // Write the DELETE query
    $sql = "DELETE FROM products WHERE id = '$id'";
    
    // Execute and redirect back to the inventory page
    if($conn->query($sql)) {
        header("Location: ../manage_inventory.php?msg=deleted");
        exit;
    } else {
        // If it fails (usually because the product is attached to an existing invoice)
        die("Error deleting product. It may be linked to an existing invoice. Error: " . $conn->error);
    }
    
} else {
    // If no ID was passed, just send them back
    header("Location: ../manage_inventory.php");
    exit;
}
?>