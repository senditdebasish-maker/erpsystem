<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata'); 
include '../includes/db.php';

// ==========================================
// 🛠️ AUTO-HEAL PURCHASE TABLE
// Ensure total_tax column exists to prevent crash
// ==========================================
$check_col = $conn->query("SHOW COLUMNS FROM purchases LIKE 'total_tax'");
if($check_col && $check_col->num_rows == 0) {
    $conn->query("ALTER TABLE purchases ADD total_tax DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount");
}
// ==========================================

$conn->query("SET SESSION group_concat_max_len = 10000;");

$where = "WHERE 1=1";
$supplier_id = trim($_POST['supplier_id'] ?? '');
$bank_id = trim($_POST['bank_id'] ?? '');
$status = trim($_POST['status'] ?? '');
$search = trim($_POST['search'] ?? '');
$date_from = trim($_POST['date_from'] ?? '');
$date_to = trim($_POST['date_to'] ?? '');

if(!empty($supplier_id)) { $where .= " AND p.supplier_id = '$supplier_id'"; }
if(!empty($bank_id)) { $where .= " AND p.bank_id = '$bank_id'"; }
if(!empty($date_from)) { $where .= " AND DATE(p.created_at) >= '$date_from'"; }
if(!empty($date_to)) { $where .= " AND DATE(p.created_at) <= '$date_to'"; }

if(!empty($status)) {
    if($status == 'Paid') $where .= " AND p.paid_amount >= p.total_amount";
    if($status == 'Partial') $where .= " AND p.paid_amount > 0 AND p.paid_amount < p.total_amount";
    if($status == 'Unpaid') $where .= " AND (p.paid_amount = 0 OR p.paid_amount IS NULL)";
}

if(!empty($search)) {
    $search = $conn->real_escape_string($search);
    $where .= " AND (p.purchase_no LIKE '%$search%' OR s.name LIKE '%$search%')";
}

// --- KPI MATH ---
$kpi_sql = "SELECT COUNT(*) as c, 
            SUM(total_amount) as t_spent, 
            SUM(total_tax) as t_gst, 
            SUM(discount) as t_disc, 
            SUM(total_amount - IFNULL(paid_amount,0)) as t_pending 
            FROM purchases p 
            LEFT JOIN suppliers s ON p.supplier_id = s.id $where";

$kpi_res = $conn->query($kpi_sql);
$kpis = $kpi_res->fetch_assoc();

// --- FETCH ROWS ---
$sql = "SELECT p.*, s.id as sup_id, s.name as supplier_name, s.contact_no as contact, s.supplier_id as supp_code, b.bank_name, 
        (SELECT GROUP_CONCAT(pr.product_name SEPARATOR '<br>• ') FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = p.id) as product_names
        FROM purchases p 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        LEFT JOIN bank_accounts b ON p.bank_id = b.id 
        $where ORDER BY p.id DESC";

$res = $conn->query($sql);

$html = "";
if($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $paid = (float)($row['paid_amount'] ?? 0);
        $total = (float)$row['total_amount'];
        $gst_amt = (float)($row['total_tax'] ?? 0);
        $discount = (float)($row['discount'] ?? 0);
        
        // Base Amount calculation
        $base_amt = $total + $discount - $gst_amt; 
        $due = $total - $paid;
        
        $date = date('d M Y', strtotime($row['created_at'])) . "<br><small class='text-muted'>" . date('h:i A', strtotime($row['created_at'])) . "</small>";
        $bank_text = !empty($row['bank_name']) ? "<div class='small text-primary fw-bold mt-1'><i class='bi bi-bank me-1'></i>{$row['bank_name']}</div>" : "<div class='small text-muted mt-1'><i class='bi bi-cash me-1'></i>Pending/Cash</div>";

        $perc = ($total > 0) ? ($paid / $total) * 100 : 0;
        $bar_class = ($perc >= 100) ? 'bg-success' : (($perc > 0) ? 'bg-warning' : 'bg-danger');
        $status_badge = ($perc >= 100) ? "<span class='badge bg-success d-block mb-1'>PAID</span>" : (($perc > 0) ? "<span class='badge bg-warning text-dark d-block mb-1'>PARTIAL</span>" : "<span class='badge bg-danger d-block mb-1'>UNPAID</span>");
        
        $progress_ui = "
            {$status_badge}
            <div class='progress' style='height: 5px; width: 100%;'>
                <div class='progress-bar {$bar_class}' style='width: {$perc}%'></div>
            </div>
            <div class='small text-muted mt-1'>₹".number_format($paid)." / ₹".number_format($total)."</div>
        ";

        $ref = htmlspecialchars($row['purchase_no']);
        $ref_link = "<a href='#' class='view-purchase-link text-primary fw-bold' data-id='{$row['id']}' data-ref='{$ref}'>{$ref}</a>";
        
        $action_menu = "
            <div class='d-flex justify-content-center gap-1'>
                <button type='button' class='btn btn-sm btn-outline-primary view-purchase' data-id='{$row['id']}' data-ref='{$ref}' title='View'><i class='bi bi-eye'></i></button>
                <a href='print_purchase.php?id={$row['id']}' target='_blank' class='btn btn-sm btn-outline-secondary' title='Print'><i class='bi bi-printer'></i></a>";
        
        if($due > 0) {
            $action_menu .= "<button type='button' class='btn btn-sm btn-outline-success pay-btn' data-id='{$row['id']}' data-sup='".htmlspecialchars($row['supplier_name'], ENT_QUOTES)."' data-due='{$due}' title='Pay'><i class='bi bi-wallet2'></i></button>";
        }
        
        $action_menu .= "</div>";

        $html .= "<tr>
            <td class='ps-4'>{$ref_link}</td>
            <td>{$date}{$bank_text}</td>
            <td><span class='fw-bold'>".htmlspecialchars($row['supplier_name'])."</span><br><small class='text-muted'>".htmlspecialchars($row['supp_code'])."</small></td>
            <td class='text-end'>₹ ".number_format($base_amt, 2)."</td>
            <td class='text-end text-info fw-bold'>₹ ".number_format($gst_amt, 2)."</td>
            <td class='text-end text-success'>₹ ".number_format($discount, 2)."</td>
            <td class='text-end fw-bold'>₹ ".number_format($total, 2)."</td>
            <td class='text-center'>{$progress_ui}</td>
            <td class='text-center pe-4'>{$action_menu}</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='9' class='text-center py-5 text-muted'>No purchase records found.</td></tr>";
}

echo json_encode([
    'status' => 'success', 
    'html' => $html, 
    'count' => $kpis['c'] ?? 0, 
    'total' => number_format((float)($kpis['t_spent'] ?? 0), 2, '.', ''),
    'total_gst' => number_format((float)($kpis['t_gst'] ?? 0), 2, '.', ''),
    'discount' => number_format((float)($kpis['t_disc'] ?? 0), 2, '.', ''),
    'pending' => number_format((float)($kpis['t_pending'] ?? 0), 2, '.', '')
]);
exit;