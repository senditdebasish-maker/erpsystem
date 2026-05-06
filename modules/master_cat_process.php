<?php
include '../includes/db.php';

// --- ACTION: SAVE NEW CATEGORY ---
if(isset($_POST['save_cat'])) {
    $name = $_POST['category_name'];
    $conn->query("INSERT INTO categories (category_name) VALUES ('$name')");
    header("Location: ../manage_categories.php?msg=added");
}

// --- ACTION: UPDATE EXISTING CATEGORY ---
if(isset($_POST['update_cat'])) {
    $id = $_POST['cat_id'];
    $name = $_POST['category_name'];
    $conn->query("UPDATE categories SET category_name = '$name' WHERE id = '$id'");
    header("Location: ../manage_categories.php?msg=updated");
}

// --- ACTION: UPDATE EXISTING SUBCATEGORY ---
if(isset($_POST['update_subcat'])) {
    $id = $_POST['sub_id'];
    $cat_id = $_POST['category_id'];
    $name = $_POST['subcategory_name'];
    $conn->query("UPDATE subcategories SET category_id = '$cat_id', subcategory_name = '$name' WHERE id = '$id'");
    header("Location: ../manage_categories.php?msg=updated");
}

// --- ACTION: DELETE CATEGORY ---
if(isset($_GET['delete_cat'])) {
    $id = $_GET['delete_cat'];
    
    // Safety Check: Double check if products exist
    $check = $conn->query("SELECT id FROM products WHERE category_id = '$id'");
    if($check->num_rows == 0) {
        // Safe to delete category and associated subcategories
        $conn->query("DELETE FROM subcategories WHERE category_id = '$id'");
        $conn->query("DELETE FROM categories WHERE id = '$id'");
        header("Location: ../manage_categories.php?msg=deleted");
    } else {
        header("Location: ../manage_categories.php?msg=error_has_products");
    }
}

// --- ACTION: DELETE SUBCATEGORY ---
if(isset($_GET['delete_subcat'])) {
    $id = $_GET['delete_subcat'];
    
    // Safety Check for Subcategory
    $check = $conn->query("SELECT id FROM products WHERE subcategory_id = '$id'");
    if($check->num_rows == 0) {
        $conn->query("DELETE FROM subcategories WHERE id = '$id'");
        header("Location: ../manage_categories.php?msg=deleted");
    } else {
        header("Location: ../manage_categories.php?msg=error_has_products");
    }
}
?>