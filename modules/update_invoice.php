<?php
include '../includes/db.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inv_id = $_POST['invoice_id'];
    $new_cust_id = $_POST['customer_id'];
    $new_bank_id = $_POST['bank_id'];
    $new_total = $_POST['final_total'];

    $conn->begin_transaction();
    
    try {
        // --- PHASE 1: REVERSE THE OLD MATH ---
        
        // 1A. Get old invoice data
        $old_inv = $conn->query("SELECT total_amount, bank_id FROM invoices WHERE id = '$inv_id'")->fetch_assoc();
        $old_total = $old_inv['total_amount'];
        $old_bank_id = $old_inv['bank_id'];

        // 1B. Deduct old total from old bank account
        $conn->query("UPDATE bank_accounts SET balance = balance - $old_total WHERE id = '$old_bank_id'");

        // 1C. Restore old items back to inventory
        $old_items = $conn->query("SELECT product_id, qty FROM invoice_items WHERE invoice_id = '$inv_id'");
        while($item = $old_items->fetch_assoc()) {
            $pid = $item['product_id'];
            $qty = $item['qty'];
            $conn->query("UPDATE products SET qty = qty + $qty WHERE id = '$pid'");
        }

        // 1D. Wipe the old cart clean
        $conn->query("DELETE FROM invoice_items WHERE invoice_id = '$inv_id'");


        // --- PHASE 2: APPLY THE NEW MATH ---

        // 2A. Get the new customer name for the record
        $c_query = $conn->query("SELECT name FROM customers WHERE id = '$new_cust_id'");
        $cust_name = $c_query->fetch_assoc()['name'];

        // 2B. Update the main invoice header
        $conn->query("UPDATE invoices SET customer_name = '$cust_name', total_amount = '$new_total', bank_id = '$new_bank_id' WHERE id = '$inv_id'");

        // 2C. Save the new items and deduct from inventory
        foreach($_POST['product_id'] as $key => $pid) {
            if(empty($pid)) continue;
            
            $qty = $_POST['qty'][$key]; 
            $price = $_POST['price'][$key];

            $conn->query("INSERT INTO invoice_items (invoice_id, product_id, qty, price) VALUES ('$inv_id', '$pid', '$qty', '$price')");
            $conn->query("UPDATE products SET qty = qty - $qty WHERE id = '$pid'");
        }

        // 2D. Add new total to the newly selected bank account
        $conn->query("UPDATE bank_accounts SET balance = balance + $new_total WHERE id = '$new_bank_id'");

        // Commit all changes
        $conn->commit();
        
        // Success screen
        echo "<div style='text-align:center; padding: 50px; font-family: sans-serif;'>";
        echo "<h1 style='color: #ffc107;'>Invoice #$inv_id Updated!</h1>";
        echo "<p>Inventory and Bank Balances have been automatically adjusted.</p>";
        echo "<br><br>";
        echo "<a href='../manage_invoices.php' style='padding: 15px 30px; background: #212529; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;'>Return to Billing History</a>";
        echo "</div>";

    } catch(Exception $e) {
        $conn->rollback();
        echo "Error updating invoice: " . $e->getMessage();
    }
}
?>