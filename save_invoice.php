<?php
include '../includes/db.php';

$conn->begin_transaction();
try {
    $cust_id = $_POST['customer_id'];
    $bank_id = $_POST['bank_id'];
    $total = $_POST['final_total'];

    // Get the exact customer name for the record
    $c_query = $conn->query("SELECT name FROM customers WHERE id = '$cust_id'");
    $cust_name = $c_query->fetch_assoc()['name'];

    // 1. Save Main Invoice
    $conn->query("INSERT INTO invoices (customer_name, total_amount, bank_id) VALUES ('$cust_name', '$total', '$bank_id')");
    $invoice_id = $conn->insert_id;

    // 2. Save Items & Deduct Inventory
    foreach($_POST['product_id'] as $key => $pid) {
        if(empty($pid)) continue;
        $qty = $_POST['qty'][$key]; 
        $price = $_POST['price'][$key];

        $conn->query("INSERT INTO invoice_items (invoice_id, product_id, qty, price) VALUES ('$invoice_id', '$pid', '$qty', '$price')");
        $conn->query("UPDATE products SET qty = qty - $qty WHERE id = '$pid'");
    }

    // 3. Add Money to Company Bank Account
    $conn->query("UPDATE bank_accounts SET balance = balance + $total WHERE id = '$bank_id'");

    $conn->commit();
    
    // Redirect instantly to the printable receipt!
    header("Location: ../print_invoice.php?id=$invoice_id");

} catch(Exception $e) {
    $conn->rollback();
    echo "Error processing invoice: " . $e->getMessage();
}
?>