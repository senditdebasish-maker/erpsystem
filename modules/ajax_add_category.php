<?php
include '../includes/db.php';
$name = $conn->real_escape_string($_POST['category_name']);
if($conn->query("INSERT INTO categories (category_name) VALUES ('$name')")) {
    echo json_encode(['status' => 'success', 'id' => $conn->insert_id, 'name' => $name]);
} else {
    echo json_encode(['status' => 'error']);
}
?>