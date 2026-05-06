<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
include '../includes/db.php';

if(isset($_POST['category_id']) && isset($_POST['subcategory_name'])) {
    $cat_id = $_POST['category_id'];
    $name = $_POST['subcategory_name'];
    
    if($conn->query("INSERT INTO subcategories (category_id, subcategory_name) VALUES ('$cat_id', '$name')")) {
        // Return the new data to the Javascript
        echo json_encode([
            'status' => 'success', 
            'id' => $conn->insert_id, 
            'cat_id' => $cat_id, 
            'name' => $name
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
exit;
?>