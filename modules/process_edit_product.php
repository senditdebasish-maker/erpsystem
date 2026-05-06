<?php
ob_start(); // SAFETY MEASURE: Prevents silent redirect crashes
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $product_id = $conn->real_escape_string($_POST['product_id']);
    $old_image = $conn->real_escape_string($_POST['old_image']);

    $product_name = $conn->real_escape_string($_POST['product_name']);
    $supplier_id = $conn->real_escape_string($_POST['supplier_id']);
    $category_id = $conn->real_escape_string($_POST['category_id']);
    $subcategory_id = $conn->real_escape_string($_POST['subcategory_id']);
    $description = $conn->real_escape_string($_POST['description']);
    $qty = $conn->real_escape_string($_POST['qty']);
    $selling_price = $conn->real_escape_string($_POST['selling_price']);
    
    // NEW: Capture the GST Rate
    $gst_rate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : 0.00;

    // IMAGE UPLOAD LOGIC
    $product_image = $old_image; // Keep old image by default
    
    // Check if the user uploaded a new image
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        
        // Auto-create folder if it doesn't exist
        if(!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $valid_ext = array("jpg", "jpeg", "png", "webp");
        
        if(in_array($ext, $valid_ext)) {
            // Generate a clean, unique file name
            $new_image_name = "prod_" . time() . "_" . rand(1000,9999) . "." . $ext;
            
            if(move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $new_image_name)) {
                $product_image = $new_image_name;
                
                // Delete the old image to save server space!
                if(!empty($old_image) && file_exists($target_dir . $old_image)) {
                    unlink($target_dir . $old_image);
                }
            }
        }
    }

    // UPDATE QUERY (Now includes gst_rate)
    $sql = "UPDATE products SET 
            product_name = '$product_name', 
            supplier_id = '$supplier_id', 
            category_id = '$category_id', 
            subcategory_id = '$subcategory_id', 
            description = '$description', 
            product_image = '$product_image', 
            qty = '$qty', 
            selling_price = '$selling_price',
            gst_rate = '$gst_rate' 
            WHERE id = '$product_id'";

    if($conn->query($sql)) {
        // Redirect back to edit page with a success message
        header("Location: ../edit_product.php?id=$product_id&msg=success");
        exit;
    } else {
        header("Location: ../edit_product.php?id=$product_id&msg=error&details=" . urlencode($conn->error));
        exit;
    }

} else {
    header("Location: ../manage_inventory.php");
    exit;
}
ob_end_flush();
?>