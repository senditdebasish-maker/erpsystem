<?php
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Capture the form data safely (No purchase_price, as you requested!)
    $supplier_id    = $_POST['supplier_id'] ?? NULL;
    $product_name   = $_POST['product_name'] ?? '';
    $description    = $_POST['description'] ?? '';
    $category_id    = $_POST['category_id'] ?? NULL;
    $subcategory_id = $_POST['subcategory_id'] ?? NULL;
    $qty            = $_POST['qty'] ?? 0;
    $selling_price  = $_POST['selling_price'] ?? 0;

    // 2. Prepare the secure SQL Statement
    $stmt = $conn->prepare("INSERT INTO products (supplier_id, product_name, description, category_id, subcategory_id, qty, selling_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        // 3. Bind the parameters securely
        $stmt->bind_param("issiiid", $supplier_id, $product_name, $description, $category_id, $subcategory_id, $qty, $selling_price);
        
        // 4. Execute and Redirect
        if ($stmt->execute()) {
            header("Location: ../manage_inventory.php?msg=success");
        } else {
            header("Location: ../manage_inventory.php?msg=error&details=" . urlencode($stmt->error));
        }
        $stmt->close();
    } else {
        die("Database Error: " . $conn->error);
    }
    exit;
} else {
    header("Location: ../manage_inventory.php");
    exit;
}
?>