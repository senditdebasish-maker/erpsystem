<?php
ob_start(); // SAFETY MEASURE: Prevents silent redirect crashes
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Sanitize text inputs
    $product_name = $conn->real_escape_string($_POST['product_name']);
    $supplier_id = $conn->real_escape_string($_POST['supplier_id']);
    $category_id = $conn->real_escape_string($_POST['category_id']);
    $subcategory_id = $conn->real_escape_string($_POST['subcategory_id']);
    $description = $conn->real_escape_string($_POST['description']);
    $qty = $conn->real_escape_string($_POST['qty']);
    $selling_price = $conn->real_escape_string($_POST['selling_price']);
    
    // NEW: Capture the GST Rate from the form (default to 0 if empty)
    $gst_rate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : 0.00;

    // 2. Handle Image Upload safely
    $product_image = ""; 
    
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        
        // Auto-create the folder if it doesn't exist
        if(!file_exists($target_dir)) { 
            mkdir($target_dir, 0777, true); 
        }
        
        // Extract extension and generate a clean, unique file name
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $valid_ext = array("jpg", "jpeg", "png", "webp");
        
        if(in_array($ext, $valid_ext)) {
            $product_image = "prod_" . time() . "_" . rand(1000,9999) . "." . $ext;
            move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $product_image);
        }
    }

    // 3. Insert into database (UPDATED to include gst_rate)
    $sql = "INSERT INTO products (product_name, supplier_id, category_id, subcategory_id, description, product_image, qty, selling_price, gst_rate) 
            VALUES ('$product_name', '$supplier_id', '$category_id', '$subcategory_id', '$description', '$product_image', '$qty', '$selling_price', '$gst_rate')";

    // 4. Redirect with Success or Error message
    if($conn->query($sql)) {
        header("Location: ../inventory.php?msg=success");
        exit;
    } else {
        header("Location: ../inventory.php?msg=error&details=" . urlencode($conn->error));
        exit;
    }
    
} else {
    header("Location: ../inventory.php");
    exit;
}
ob_end_flush();
?>