<?php
include '../includes/db.php';
$cat = $conn->real_escape_string($_POST['category_id']);
$name = $conn->real_escape_string($_POST['subcategory_name']);
if($conn->query("INSERT INTO subcategories (category_id, subcategory_name) VALUES ('$cat', '$name')")) {
    echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'cat_id' => $cat, 'name' => $name]);
} else {
    echo json_encode(['status' => 'error']);
}
?>