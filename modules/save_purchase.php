<?php
// Set correct timezone for India
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

// ==========================================
// 🛠️ AUTO-HEAL DATABASE ENGINE
// Automatically creates missing columns so it never crashes!
// ==========================================

// 1. Purchases Table: Check for total_tax
$check_tax = $conn->query("SHOW COLUMNS FROM purchases LIKE 'total_tax'");
if($check_tax && $check_tax->num_rows == 0) {
    $conn->query("ALTER TABLE purchases ADD total_tax DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
}

// 2. Purchase Items Table: Check for item_note, gst_rate, and tax_amount
$check_note = $conn->query("SHOW COLUMNS FROM purchase_items LIKE 'item_note'");
if($check_note && $check_note->num_rows == 0) {
    $conn->query("ALTER TABLE purchase_items ADD item_note VARCHAR(255) NULL AFTER product_id");
}
$check_item_gst = $conn->query("SHOW COLUMNS FROM purchase_items LIKE 'gst_rate'");
if($check_item_gst && $check_item_gst->num_rows == 0) {
    $conn->query("ALTER TABLE purchase_items ADD gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER cost_price");
}
$check_item_tax = $conn->query("SHOW COLUMNS FROM purchase_items LIKE 'tax_amount'");
if($check_item_tax && $check_item_tax->num_rows == 0) {
    $conn->query("ALTER TABLE purchase_items ADD tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER gst_rate");
}
// ==========================================

if($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. Capture Master Form Data safely
    $purchase_no = $conn->real_escape_string($_POST['purchase_no']);
    $supplier_id = $conn->real_escape_string($_POST['supplier_id']);
    
    // Combine the user-selected date with the current actual time
    $purchase_date = $conn->real_escape_string($_POST['purchase_date']) . " " . date('H:i:s'); 
    
    // Capture Financials including new GST fields
    $total_amount = isset($_POST['final_total']) ? (float)$_POST['final_total'] : 0.00;
    $total_tax = isset($_POST['total_tax']) ? (float)$_POST['total_tax'] : 0.00;
    $discount = isset($_POST['discount']) ? (float)$_POST['discount'] : 0.00;
    $payment_status = $conn->real_escape_string($_POST['payment_status']);
    
    // Handle the bank ID properly and zero out payment if unpaid
    if($payment_status == 'Unpaid') {
        $paid_amount = 0.00;
        $bank_id = "NULL"; 
    } else {
        $paid_amount = isset($_POST['paid_amount']) ? (float)$_POST['paid_amount'] : 0.00;
        $bank_id = !empty($_POST['bank_id']) ? "'" . $conn->real_escape_string($_POST['bank_id']) . "'" : "NULL";
    }

    // Start Transaction (If any step fails, the whole process reverses to protect data)
    $conn->begin_transaction();

    try {
        // 2. Save the Main Purchase Record into the `purchases` table (Now with total_tax)
        $sql = "INSERT INTO purchases (purchase_no, supplier_id, total_amount, total_tax, discount, paid_amount, payment_status, created_at, bank_id) 
                VALUES ('$purchase_no', '$supplier_id', '$total_amount', '$total_tax', '$discount', '$paid_amount', '$payment_status', '$purchase_date', $bank_id)";
        $conn->query($sql);
        
        // Grab the ID of the purchase we just created
        $purchase_id = $conn->insert_id;

        // 3. Save the Items & Update Inventory Stock
        $product_ids = $_POST['product_id'];
        $qtys = $_POST['qty'];
        $costs = $_POST['cost'];
        
        // Grab new GST and Note arrays safely
        $notes = isset($_POST['item_note']) ? $_POST['item_note'] : []; 
        $gst_rates = isset($_POST['gst_rate']) ? $_POST['gst_rate'] : [];
        $tax_amounts = isset($_POST['tax_amount']) ? $_POST['tax_amount'] : [];

        foreach($product_ids as $key => $pid) {
            if(empty($pid)) continue; // Skip empty rows just in case
            
            $pid = $conn->real_escape_string($pid);
            $qty = (float)$qtys[$key];
            $cost = (float)$costs[$key];

            // Extract optional fields securely
            $note = isset($notes[$key]) ? $conn->real_escape_string($notes[$key]) : ''; 
            $gst_r = isset($gst_rates[$key]) ? (float)str_replace('%', '', $gst_rates[$key]) : 0.00;
            $tax_a = isset($tax_amounts[$key]) ? (float)$tax_amounts[$key] : 0.00;

            if($qty > 0) {
                // Save individual line item into `purchase_items` (Now with GST & Notes)
                $conn->query("INSERT INTO purchase_items (purchase_id, product_id, item_note, qty, cost_price, gst_rate, tax_amount) 
                              VALUES ('$purchase_id', '$pid', '$note', '$qty', '$cost', '$gst_r', '$tax_a')");
                
                // MAGIC: Add the new incoming quantity to the existing stock levels
                $conn->query("UPDATE products SET qty = qty + $qty WHERE id = '$pid'");
            }
        }

        // 4. Deduct Money from Bank Account (Only if money actually changed hands)
        if ($paid_amount > 0 && $bank_id != "NULL") {
            $conn->query("UPDATE bank_accounts SET balance = balance - $paid_amount WHERE id = $bank_id");
            
            // Optional: You could log the payment in `purchase_payments` here if you use that table for history!
            $conn->query("INSERT INTO purchase_payments (purchase_id, amount, bank_id, payment_date) VALUES ('$purchase_id', '$paid_amount', $bank_id, '$purchase_date')");
        }

        // 5. Commit all changes to the database & Redirect with success message and ID (for printing)
        $conn->commit();
        
        // Note: Redirecting to purchase_entry.php (update this if your UI page is named differently)
        header("Location: ../add_purchase.php?msg=success&id=" . $purchase_id);
        exit;

    } catch(Exception $e) {
        // If anything crashed, undo everything and show the error
        $conn->rollback();
        die("Database Error: " . $e->getMessage());
    }
} else {
    // Kick out anyone trying to load this page directly without submitting a form
    header("Location: ../add_purchase.php");
    exit;
}
?>