<?php
include '../includes/db.php';

if(isset($_GET['id'])) {
    $inv_id = $_GET['id'];
    
    // We use a transaction so if anything fails, it rolls back and prevents math errors
    $conn->begin_transaction();
    try {
        // 1. Get the invoice details so we can reverse the bank balance
        $inv_query = $conn->query("SELECT total_amount, bank_id FROM invoices WHERE id = '$inv_id'");
        if($inv_query->num_rows > 0) {
            $inv_data = $inv_query->fetch_assoc();
            $total = $inv_data['total_amount'];
            $bank_id = $inv_data['bank_id'];
            
            // Deduct the money back out of the bank account
            $conn->query("UPDATE bank_accounts SET balance = balance - $total WHERE id = '$bank_id'");
            
            // 2. Get all the items on this invoice to restock them
            $items = $conn->query("SELECT product_id, qty FROM invoice_items WHERE invoice_id = '$inv_id'");
            while($item = $items->fetch_assoc()) {
                $pid = $item['product_id'];
                $qty = $item['qty'];
                
                // Add the items back into inventory
                $conn->query("UPDATE products SET qty = qty + $qty WHERE id = '$pid'");
            }
            
            // 3. Finally, delete the items and the invoice itself
            $conn->query("DELETE FROM invoice_items WHERE invoice_id = '$inv_id'");
            $conn->query("DELETE FROM invoices WHERE id = '$inv_id'");
            
            $conn->commit();
            
            // Redirect back to the manage page with a success message
            header("Location: ../manage_invoices.php?msg=deleted");
            exit;
        }
    } catch(Exception $e) {
        $conn->rollback();
        die("Error deleting invoice: " . $e->getMessage());
    }
} else {
    header("Location: ../manage_invoices.php");
}
?>