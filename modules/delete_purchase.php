<?php
include '../includes/db.php';

if(isset($_GET['id'])) {
    $pid = $_GET['id'];
    $conn->begin_transaction();
    
    try {
        $p_data = $conn->query("SELECT total_amount, bank_id FROM purchases WHERE id = '$pid'")->fetch_assoc();
        $amount = $p_data['total_amount'];
        $bank_id = $p_data['bank_id'];

        // 1. Restore Money to Bank
        $conn->query("UPDATE bank_accounts SET balance = balance + $amount WHERE id = '$bank_id'");

        // 2. Reduce Stock
        $items = $conn->query("SELECT product_id, qty FROM purchase_items WHERE purchase_id = '$pid'");
        while($i = $items->fetch_assoc()) {
            $prod_id = $i['product_id'];
            $qty = $i['qty'];
            $conn->query("UPDATE products SET qty = qty - $qty WHERE id = '$prod_id'");
        }

        // 3. Delete Records
        $conn->query("DELETE FROM purchase_items WHERE purchase_id = '$pid'");
        $conn->query("DELETE FROM purchases WHERE id = '$pid'");

        $conn->commit();
        header("Location: ../manage_purchases.php?msg=deleted");
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
    }
}
?>