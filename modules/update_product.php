<?php
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['product_id'];
    $supplier = $_POST['supplier_id'];
    $name = $_POST['product_name'];
    $desc = $_POST['description'];
    $cat = $_POST['category_id'];
    $subcat = $_POST['subcategory_id'];
    $qty = $_POST['qty'];
    $purchase = $_POST['purchase_price'];
    $selling = $_POST['selling_price'];

    $sql = "UPDATE products SET 
                supplier_id = '$supplier', 
                product_name = '$name', 
                description = '$desc', 
                category_id = '$cat', 
                subcategory_id = '$subcat', 
                qty = '$qty', 
                purchase_price = '$purchase', 
                selling_price = '$selling' 
            WHERE id = '$id'";

    if($conn->query($sql)) {
        header("Location: ../inventory.php?msg=updated");
    } else {
        echo "Error updating product: " . $conn->error;
    }
} else {
    header("Location: ../inventory.php");
}
?>