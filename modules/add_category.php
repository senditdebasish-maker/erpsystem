<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
include '../includes/db.php';

if(isset($_POST['category_name'])) {
    $name = $_POST['category_name'];
    if($conn->query("INSERT INTO categories (category_name) VALUES ('$name')")) {
        // Return the new ID and Name to the Javascript
        echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'name' => $name]);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
exit;
?>