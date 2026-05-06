<?php
// Set correct timezone for accurate timestamps
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

// ==========================================
// 🛠️ AUTO-HEAL DATABASE ENGINE
// Automatically creates missing columns so it never crashes!
// ==========================================

// 1. Invoices Table: Check for discount and total_tax
$check_disc = $conn->query("SHOW COLUMNS FROM invoices LIKE 'discount'");
if($check_disc && $check_disc->num_rows == 0) {
    $conn->query("ALTER TABLE invoices ADD discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
}
$check_tax = $conn->query("SHOW COLUMNS FROM invoices LIKE 'total_tax'");
if($check_tax && $check_tax->num_rows == 0) {
    $conn->query("ALTER TABLE invoices ADD total_tax DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
}

// 2. Invoice Items Table: Check for item_note, gst_rate, and tax_amount
$check_note = $conn->query("SHOW COLUMNS FROM invoice_items LIKE 'item_note'");
if($check_note && $check_note->num_rows == 0) {
    $conn->query("ALTER TABLE invoice_items ADD item_note VARCHAR(255) NULL AFTER product_id");
}
$check_item_gst = $conn->query("SHOW COLUMNS FROM invoice_items LIKE 'gst_rate'");
if($check_item_gst && $check_item_gst->num_rows == 0) {
    $conn->query("ALTER TABLE invoice_items ADD gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER price");
}
$check_item_tax = $conn->query("SHOW COLUMNS FROM invoice_items LIKE 'tax_amount'");
if($check_item_tax && $check_item_tax->num_rows == 0) {
    $conn->query("ALTER TABLE invoice_items ADD tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gst_rate");
}
// ==========================================


if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // 1. Get Master Billing Info
    $customer_id = $conn->real_escape_string($_POST['customer_id']);
    $invoice_date = $conn->real_escape_string($_POST['invoice_date']);
    
    // Convert Customer ID to Name
    $cust_query = $conn->query("SELECT name FROM customers WHERE id = '$customer_id'");
    $customer_name = ($cust_query->num_rows > 0) ? $cust_query->fetch_assoc()['name'] : 'Unknown Customer';
    $customer_name = $conn->real_escape_string($customer_name);

    // 2. Capture Financials (including new GST fields)
    $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0.00;
    $total_tax = isset($_POST['total_tax']) ? (float)$_POST['total_tax'] : 0.00;
    $final_total = isset($_POST['final_total']) ? (float)$_POST['final_total'] : 0.00;

    // 3. Payment Info
    $payment_status = $conn->real_escape_string($_POST['payment_status']);
    
    if($payment_status == 'Unpaid') {
        $paid_amount = 0.00;
        $bank_id = "NULL"; 
    } else {
        $paid_amount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0.00;
        $bank_id = !empty($_POST['bank_id']) ? "'" . $conn->real_escape_string($_POST['bank_id']) . "'" : "NULL";
    }

    $time_now = date('H:i:s');
    $full_datetime = "$invoice_date $time_now";

    // 4. INSERT MASTER INVOICE
    $sql_invoice = "INSERT INTO invoices (customer_name, bank_id, total_amount, total_tax, discount, paid_amount, payment_status, created_at) 
                    VALUES ('$customer_name', $bank_id, '$final_total', '$total_tax', '$discount', '$paid_amount', '$payment_status', '$full_datetime')";
    
    if($conn->query($sql_invoice)) {
        $invoice_id = $conn->insert_id; 

        // 5. PROCESS CART ITEMS (Notes & GST)
        $product_ids = $_POST['product_id'];
        $qtys = $_POST['qty'];
        $prices = $_POST['price'];
        
        // Grab new arrays safely
        $notes = isset($_POST['item_note']) ? $_POST['item_note'] : []; 
        $gst_rates = isset($_POST['gst_rate']) ? $_POST['gst_rate'] : [];
        $tax_amounts = isset($_POST['tax_amount']) ? $_POST['tax_amount'] : [];

        for($i = 0; $i < count($product_ids); $i++) {
            $pid = $conn->real_escape_string($product_ids[$i]);
            $q = (float)$qtys[$i];
            $p = (float)$prices[$i];
            
            // Extract optional fields securely
            $note = isset($notes[$i]) ? $conn->real_escape_string($notes[$i]) : ''; 
            $gst_r = isset($gst_rates[$i]) ? (float)str_replace('%', '', $gst_rates[$i]) : 0.00;
            $tax_a = isset($tax_amounts[$i]) ? (float)$tax_amounts[$i] : 0.00;

            if(!empty($pid) && $q > 0) {
                // Insert Line Item WITH NOTE AND GST
                $conn->query("INSERT INTO invoice_items (invoice_id, product_id, item_note, qty, price, gst_rate, tax_amount) 
                              VALUES ('$invoice_id', '$pid', '$note', '$q', '$p', '$gst_r', '$tax_a')");
                
                // Deduct stock from inventory
                $conn->query("UPDATE products SET qty = qty - $q WHERE id = '$pid'");
            }
        }

        // 6. PROCESS INITIAL PAYMENT
        if($payment_status != 'Unpaid' && $bank_id != "NULL" && $paid_amount > 0) {
            $conn->query("UPDATE bank_accounts SET balance = balance + $paid_amount WHERE id = $bank_id");
            $conn->query("INSERT INTO invoice_payments (invoice_id, amount, bank_id, payment_date) VALUES ('$invoice_id', '$paid_amount', $bank_id, '$full_datetime')");
        }

        // Success!
        header("Location: ../generate_invoice.php?msg=success");
        exit;
    } else {
        die("Error generating invoice: " . $conn->error);
    }
} else {
    header("Location: ../generate_invoice.php");
    exit;
}
?>