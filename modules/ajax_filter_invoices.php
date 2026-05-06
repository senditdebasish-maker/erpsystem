<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

// ==========================================
// 🛠️ AUTO-HEAL DATABASE 
// ==========================================
$check_note = $conn->query("SHOW COLUMNS FROM invoice_items LIKE 'item_note'");
if($check_note && $check_note->num_rows == 0) {
    $conn->query("ALTER TABLE invoice_items ADD item_note VARCHAR(255) NULL AFTER product_id");
}

$check_disc = $conn->query("SHOW COLUMNS FROM invoices LIKE 'discount'");
if($check_disc && $check_disc->num_rows == 0) {
    $conn->query("ALTER TABLE invoices ADD discount DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
}

$check_tax = $conn->query("SHOW COLUMNS FROM invoices LIKE 'total_tax'");
if($check_tax && $check_tax->num_rows == 0) {
    $conn->query("ALTER TABLE invoices ADD total_tax DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
}
// ==========================================

$conn->query("SET SESSION group_concat_max_len = 10000;");

// Fetch company name for the payment link
$company_name = "Your Business Name"; 
$comp_res = $conn->query("SELECT setting_value FROM company_settings WHERE setting_key = 'company_name'");
if($comp_res && $comp_res->num_rows > 0) {
    $company_name = $comp_res->fetch_assoc()['setting_value'];
}

$where = "WHERE 1=1";
if(!empty($_POST['customer'])) $where .= " AND i.customer_name = '{$_POST['customer']}'";
if(!empty($_POST['bank_id'])) $where .= " AND i.bank_id = '{$_POST['bank_id']}'";
if(!empty($_POST['date_from'])) $where .= " AND DATE(i.created_at) >= '{$_POST['date_from']}'";
if(!empty($_POST['date_to'])) $where .= " AND DATE(i.created_at) <= '{$_POST['date_to']}'";

if(!empty($_POST['status'])) {
    if($_POST['status'] == 'Paid') $where .= " AND i.paid_amount >= i.total_amount";
    if($_POST['status'] == 'Partial') $where .= " AND i.paid_amount > 0 AND i.paid_amount < i.total_amount";
    if($_POST['status'] == 'Unpaid') $where .= " AND (i.paid_amount = 0 OR i.paid_amount IS NULL)";
}

if(!empty($_POST['search'])) {
    $s = $conn->real_escape_string(trim($_POST['search']));
    $where .= " AND (i.id LIKE '%$s%' OR i.customer_name LIKE '%$s%')";
}

// KPI MATH
$stats = $conn->query("SELECT COUNT(*) as c, SUM(total_amount) as t_spent, SUM(IFNULL(total_tax, 0)) as t_gst, SUM(IFNULL(discount, 0)) as t_disc, SUM(total_amount - IFNULL(paid_amount,0)) as t_pending FROM invoices i $where")->fetch_assoc();

// TABLE FETCH - NOW INCLUDES PAYMENT HISTORY
$sql = "SELECT i.*, b.bank_name, c.customer_id, c.contact_no, c.village, c.dist,
        (SELECT GROUP_CONCAT(
            CONCAT(pr.product_name, CASE WHEN ii.item_note != '' AND ii.item_note IS NOT NULL THEN CONCAT(' <span class=\"text-primary small fst-italic\">[', ii.item_note, ']</span>') ELSE '' END) 
            SEPARATOR '<br>• '
        ) FROM invoice_items ii JOIN products pr ON ii.product_id = pr.id WHERE ii.invoice_id = i.id) as product_names,
        (SELECT GROUP_CONCAT(
            CONCAT('~ ₹', amount, ' on ', DATE_FORMAT(payment_date, '%d-%b-%Y %h:%i %p')) 
            SEPARATOR '\n'
        ) FROM invoice_payments WHERE invoice_id = i.id) as payment_history
        FROM invoices i 
        LEFT JOIN bank_accounts b ON i.bank_id = b.id 
        LEFT JOIN customers c ON i.customer_name = c.name
        $where ORDER BY i.id DESC";
$res = $conn->query($sql);

$html = "";
if($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        
        $paid = (float)($row['paid_amount'] ?? 0);
        $total = (float)$row['total_amount'];
        $discount = (float)($row['discount'] ?? 0);
        $due = $total - $paid;
        
        $date = date('d M Y', strtotime($row['created_at'])) . "<br><small class='text-muted'>" . date('h:i A', strtotime($row['created_at'])) . "</small>";
        $bank_text = !empty($row['bank_name']) ? "<div class='small text-primary fw-bold mt-1'><i class='bi bi-bank me-1'></i>{$row['bank_name']}</div>" : "<div class='small text-muted mt-1'><i class='bi bi-cash me-1'></i>Pending/Cash</div>";

        // Collection Status / Progress
        $perc = ($total > 0) ? ($paid / $total) * 100 : 0;
        $bar_class = ($perc >= 100) ? 'bg-success' : (($perc > 0) ? 'bg-warning' : 'bg-danger');
        
        $status_badge = ($perc >= 100) ? "<span class='badge bg-success shadow-sm d-block mb-1'>PAID</span>" : 
                        (($perc > 0) ? "<span class='badge bg-warning text-dark shadow-sm d-block mb-1'>PARTIAL</span>" : 
                        "<span class='badge bg-danger shadow-sm d-block mb-1'>UNPAID</span>");
        
        $progress_ui = "
            {$status_badge}
            <div class='progress shadow-sm bg-light border' style='height: 6px; width: 100%;'>
                <div class='progress-bar {$bar_class}' style='width: {$perc}%'></div>
            </div>
            <div class='small text-muted mt-1' style='font-size:0.75rem;'>₹".number_format($paid, 2)." / ₹".number_format($total, 2)."</div>
        ";

        // HYPERLINKS & CUSTOMER DETAILS
        $inv_no_display = "INV-".str_pad($row['id'], 5, '0', STR_PAD_LEFT);
        $inv_link = "<a href='#' class='view-invoice text-primary fw-bold text-decoration-none' data-id='{$row['id']}'>{$inv_no_display}</a>";
        $cust_link = "<a href='#' class='view-customer text-dark fw-bold' style='font-size:1.05rem;' data-name='".htmlspecialchars($row['customer_name'], ENT_QUOTES)."'>".htmlspecialchars($row['customer_name'])."</a>";
        
        $cid = !empty($row['customer_id']) ? $row['customer_id'] : 'N/A';
        $phone = !empty($row['contact_no']) ? $row['contact_no'] : 'N/A';
        $addr = (!empty($row['village']) ? $row['village'] : '') . (!empty($row['dist']) ? ', '.$row['dist'] : '');
        if(empty(trim($addr)) || $addr == ', ') $addr = 'Address N/A';

        $customer_details = "
            {$cust_link}
            <div class='small text-muted mt-1'>
                <i class='bi bi-person-badge text-secondary me-1'></i>{$cid} <br>
                <i class='bi bi-telephone text-secondary me-1'></i>{$phone}
            </div>
        ";

        // ==========================================
        // 💬 WHATSAPP REMINDER LOGIC WITH PAYMENT LINK
        // ==========================================
        $wa_phone = preg_replace('/[^0-9]/', '', $row['contact_no']); 
        if(strlen($wa_phone) == 10) {
            $wa_phone = "91" . $wa_phone; 
        }

        // Clean up the product list for WhatsApp
        $wa_products = "";
        if (!empty($row['product_names'])) {
            $clean_products = strip_tags(str_replace('<br>• ', "\n- ", $row['product_names']));
            $wa_products = "- " . $clean_products;
        }

        // ⚠️ REPLACE THIS WITH YOUR REAL UPI ID (e.g., your_number@sbi, your_business@icici)
        $your_upi_id = "8759899124@upi"; 
        
        $encoded_payee = rawurlencode($company_name);
        $upi_payment_link = "upi://pay?pa={$your_upi_id}&pn={$encoded_payee}&tr={$inv_no_display}&am={$due}&cu=INR";

        // Build the message
        $wa_msg = "Hello *" . trim($row['customer_name']) . "*,\n\n";
        $wa_msg .= "This is a polite reminder regarding your recent purchase.\n\n";
        $wa_msg .= "Invoice No: *" . $inv_no_display . "*\n";
        
        if (!empty($wa_products)) {
            $wa_msg .= "Items Purchased:\n" . $wa_products . "\n\n";
        }
        
        $wa_msg .= "Total Bill: ₹" . number_format($total, 2) . "\n";
        
        // Include Payment History if partial payments exist
        if ($paid > 0 && !empty($row['payment_history'])) {
            $wa_msg .= "Total Paid: ₹" . number_format($paid, 2) . "\n";
            $wa_msg .= "Payment Log:\n" . $row['payment_history'] . "\n\n";
        }
        
        $wa_msg .= "Pending Due: *₹" . number_format($due, 2) . "*\n\n";
        
        // ADDED PAYMENT LINK SECTION
        $wa_msg .= "💳 *Pay Instantly via UPI:*\n";
        $wa_msg .= "Tap the link below to pay securely via GPay, PhonePe, or Paytm:\n";
        $wa_msg .= $upi_payment_link . "\n\n";
        $wa_msg .= "(Or pay manually to UPI ID: " . $your_upi_id . ")\n\n";

        $wa_msg .= "Kindly clear your pending dues at your earliest convenience. Thank you!";
        
        // URL Encode the final string
        $wa_link = "https://wa.me/{$wa_phone}?text=" . rawurlencode($wa_msg);
        // ==========================================

        // Actions
        $action_btns = "<div class='d-flex justify-content-center gap-1'>
            <button type='button' class='btn btn-sm btn-outline-primary view-invoice shadow-sm' data-id='{$row['id']}' title='View Details'><i class='bi bi-eye'></i></button>
            <a href='print_invoice.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-outline-secondary shadow-sm' title='Print'><i class='bi bi-printer'></i></a>";
        
        if($due > 0) {
            if(!empty($wa_phone) && strlen($wa_phone) >= 10) {
                $action_btns .= "<a href='{$wa_link}' target='_blank' class='btn btn-sm btn-success shadow-sm' title='Send WhatsApp Reminder'><i class='bi bi-whatsapp'></i></a>";
            }
            $action_btns .= "<button type='button' class='btn btn-sm btn-outline-success pay-btn shadow-sm' data-id='{$row['id']}' data-cust='".htmlspecialchars($row['customer_name'], ENT_QUOTES)."' data-due='{$due}' title='Record Payment'><i class='bi bi-cash-stack'></i></button>";
        }
        $action_btns .= "</div>";

        // HTML Row
        $html .= "<tr>
            <td class='ps-4'>{$inv_link}</td>
            <td>{$date}{$bank_text}</td>
            <td>{$customer_details}</td>
            <td><div class='small text-secondary' style='max-height: 80px; overflow-y: auto;'>• {$row['product_names']}</div></td>
            <td class='text-end text-danger fw-bold'>- ₹".number_format($discount, 2)."</td>
            <td class='text-end'><h6 class='mb-0 fw-bold text-dark'>₹".number_format($total, 2)."</h6></td>
            <td class='text-center align-middle' style='min-width: 130px;'>{$progress_ui}</td>
            <td class='text-center pe-4'>{$action_btns}</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='8' class='text-center py-5 text-muted'><i class='bi bi-search fs-1 d-block mb-2 opacity-50'></i>No invoices match your search.</td></tr>";
}

// JSON Payload
echo json_encode([
    'status' => 'success',
    'html' => $html,
    'count' => $stats['c'] ?? 0,
    'total' => number_format((float)($stats['t_spent'] ?? 0), 2, '.', ''),
    'total_gst' => number_format((float)($stats['t_gst'] ?? 0), 2, '.', ''),
    'discount' => number_format((float)($stats['t_disc'] ?? 0), 2, '.', ''),
    'pending' => number_format((float)($stats['t_pending'] ?? 0), 2, '.', '')
]);
exit;
?>