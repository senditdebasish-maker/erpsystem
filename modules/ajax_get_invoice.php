<?php
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';
if(!isset($_POST['id'])) die("Invalid request.");

$id = $conn->real_escape_string($_POST['id']);

// Get Invoice & Customer Details 
$sql = "SELECT i.*, c.customer_id, c.contact_no, c.gstin,
        c.village, c.po, c.dist, c.pin, c.state,
        c.shipping_village, c.shipping_po, c.shipping_dist, c.shipping_pin, c.shipping_state 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_name = c.name
        WHERE i.id = '$id'";
$inv = $conn->query($sql)->fetch_assoc();

$paid = (float)($inv['paid_amount'] ?? 0);
$total = (float)$inv['total_amount'];
$discount = (float)($inv['discount'] ?? 0);
$due = $total - $paid;

// --- LOGIC: FORMAT DISCOUNT AS 00.00(0%) ---
$subtotal = $total + $discount; 
$disc_pct = ($subtotal > 0) ? round(($discount / $subtotal) * 100, 1) : 0;
$formatted_discount = number_format($discount, 2) . " (" . $disc_pct . "%)";

// Customer Variables
$cid = !empty($inv['customer_id']) ? htmlspecialchars($inv['customer_id']) : 'N/A';
$phone = !empty($inv['contact_no']) ? htmlspecialchars($inv['contact_no']) : 'N/A';
$gstin_html = !empty($inv['gstin']) ? "<i class='bi bi-receipt text-muted me-1 mt-1'></i> GSTIN: ".htmlspecialchars($inv['gstin'])."<br>" : "";

// --- LOGIC: SEPARATE BILLING & SHIPPING ADDRESSES ---
$bill_addr = (!empty($inv['village']) ? htmlspecialchars($inv['village']) : '') . (!empty($inv['po']) ? ', PO: '.htmlspecialchars($inv['po']) : '') . '<br>';
$bill_addr .= (!empty($inv['dist']) ? htmlspecialchars($inv['dist']) : '') . (!empty($inv['pin']) ? ' - '.htmlspecialchars($inv['pin']) : '') . '<br>';
$bill_addr .= (!empty($inv['state']) ? htmlspecialchars($inv['state']) : '');
if(trim(strip_tags($bill_addr)) == '' || trim(strip_tags($bill_addr)) == '-' || trim(strip_tags($bill_addr)) == ', PO:') $bill_addr = 'Address N/A';

$ship_village = !empty($inv['shipping_village']) ? $inv['shipping_village'] : $inv['village'];
$ship_po = !empty($inv['shipping_po']) ? $inv['shipping_po'] : $inv['po'];
$ship_dist = !empty($inv['shipping_dist']) ? $inv['shipping_dist'] : $inv['dist'];
$ship_pin = !empty($inv['shipping_pin']) ? $inv['shipping_pin'] : $inv['pin'];
$ship_state = !empty($inv['shipping_state']) ? $inv['shipping_state'] : $inv['state'];

$ship_addr = (!empty($ship_village) ? htmlspecialchars($ship_village) : '') . (!empty($ship_po) ? ', PO: '.htmlspecialchars($ship_po) : '') . '<br>';
$ship_addr .= (!empty($ship_dist) ? htmlspecialchars($ship_dist) : '') . (!empty($ship_pin) ? ' - '.htmlspecialchars($ship_pin) : '') . '<br>';
$ship_addr .= (!empty($ship_state) ? htmlspecialchars($ship_state) : '');
if(trim(strip_tags($ship_addr)) == '' || trim(strip_tags($ship_addr)) == '-' || trim(strip_tags($ship_addr)) == ', PO:') $ship_addr = 'Address N/A';

echo "
<div class='p-2'>

    <!-- ==========================================
         NEW: BACK TO PROFILE NAVIGATION BUTTON 
    =========================================== -->
    <div class='mb-4 pb-3 border-bottom d-flex justify-content-between align-items-center'>
        <a href='#' class='btn btn-sm btn-dark fw-bold shadow-sm view-customer' data-name='".htmlspecialchars($inv['customer_name'])."'>
            <i class='bi bi-arrow-left me-1'></i> Back to Profile
        </a>
        <a href='print_invoice.php?id={$id}' target='_blank' class='btn btn-sm btn-outline-primary fw-bold shadow-sm'>
            <i class='bi bi-printer me-1'></i> Print Bill
        </a>
    </div>

    <div class='row mb-4 border-bottom pb-4'>
        <!-- BILLING INFO -->
        <div class='col-sm-5 border-end pe-3'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Billed To</h6>
            <h5 class='fw-bold mb-1 text-primary'><i class='bi bi-person-fill me-1'></i>".htmlspecialchars($inv['customer_name'])."</h5>
            <div class='small text-dark mb-2 lh-sm'>
                <i class='bi bi-person-badge text-muted me-1'></i> {$cid}<br>
                <i class='bi bi-telephone text-muted me-1 mt-1'></i> {$phone}<br>
                {$gstin_html}
                <i class='bi bi-geo-alt text-muted me-1 mt-1'></i> {$bill_addr}
            </div>
        </div>
        
        <!-- SHIPPING INFO -->
        <div class='col-sm-4 ps-3'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Shipped To</h6>
            <div class='small text-dark mt-2 lh-sm'>
                <i class='bi bi-truck text-muted me-1'></i> {$ship_addr}
            </div>
        </div>

        <!-- INVOICE META -->
        <div class='col-sm-3 text-sm-end'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Invoice Date</h6>
            <h6 class='fw-bold mb-0'>".date('d M Y', strtotime($inv['created_at']))."</h6>
            <span class='text-muted small'>".date('h:i A', strtotime($inv['created_at']))."</span>
        </div>
    </div>

    <!-- ITEM TABLE -->
    <div class='table-responsive border rounded-3 shadow-sm mb-4'>
        <table class='table table-borderless align-middle mb-0'>
            <thead class='table-light border-bottom'>
                <tr class='text-uppercase small text-muted'>
                    <th class='ps-3' width='50%'>Product Details</th>
                    <th class='text-center'>Qty</th>
                    <th class='text-end'>Rate</th>
                    <th class='text-end pe-3'>Total</th>
                </tr>
            </thead>
            <tbody>";

$items = $conn->query("SELECT ii.*, p.product_name FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = '$id'");

while($item = $items->fetch_assoc()) {
    $line_total = $item['qty'] * $item['price'];
    
    $note_html = "";
    if(!empty($item['item_note'])) {
        $note_html = "<div class='small text-muted fst-italic mt-1'><i class='bi bi-info-circle me-1'></i>" . htmlspecialchars($item['item_note']) . "</div>";
    }

    echo "<tr>
        <td class='ps-3 text-dark'>
            <div class='fw-bolder fs-6'>".htmlspecialchars($item['product_name'])."</div>
            {$note_html}
        </td>
        <td class='text-center'><span class='badge bg-light text-dark border'>{$item['qty']}</span></td>
        <td class='text-end text-muted'>₹".number_format($item['price'], 2)."</td>
        <td class='text-end fw-bold text-dark pe-3'>₹".number_format($line_total, 2)."</td>
    </tr>";
}

echo "      </tbody>
            <tfoot class='bg-light border-top'>
                <tr>
                    <td colspan='3' class='text-end fw-bold pt-3 small text-uppercase'>Subtotal:</td>
                    <td class='text-end fw-bold pe-3 pt-3'>₹".number_format($subtotal, 2)."</td>
                </tr>
                <tr>
                    <td colspan='3' class='text-end fw-bold text-danger small text-uppercase'>Discount Applied:</td>
                    <td class='text-end text-warning fw-bold pe-3'>- ₹{$formatted_discount}</td>
                </tr>
                <tr class='border-top border-dark'>
                    <td colspan='3' class='text-end fw-bold fs-5 py-3'>Grand Total:</td>
                    <td class='text-end fw-bold text-primary fs-5 pe-3 py-3'>₹".number_format($total, 2)."</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- PAYMENT HISTORY TIMELINE -->
    <h6 class='fw-bold text-dark border-bottom pb-2 mb-3'><i class='bi bi-clock-history text-secondary me-2'></i>Collection Timeline</h6>
    <div class='table-responsive mb-4'>
        <table class='table table-sm table-borderless align-middle mb-0'>
            <thead class='border-bottom'>
                <tr class='text-muted small text-uppercase'>
                    <th>Date & Time</th>
                    <th>Deposited To</th>
                    <th class='text-end'>Amount Collected</th>
                </tr>
            </thead>
            <tbody>";

            $pay_query = $conn->query("SELECT p.*, b.bank_name, b.account_no FROM invoice_payments p LEFT JOIN bank_accounts b ON p.bank_id = b.id WHERE p.invoice_id = '$id' ORDER BY p.payment_date ASC");
            
            if($pay_query && $pay_query->num_rows > 0) {
                while($pay = $pay_query->fetch_assoc()) {
                    $bank_display = !empty($pay['bank_name']) ? "{$pay['bank_name']} (*".substr($pay['account_no'], -4).")" : "Cash / General";
                    echo "<tr>
                            <td class='fw-bold text-dark small'>".date('d M Y, h:i A', strtotime($pay['payment_date']))."</td>
                            <td class='small text-secondary'>{$bank_display}</td>
                            <td class='text-end fw-bold text-success'>₹".number_format($pay['amount'], 2)."</td>
                          </tr>";
                }
            } else {
                if($paid > 0) {
                    echo "<tr>
                            <td class='small text-muted fst-italic'>Legacy Record</td>
                            <td class='small text-secondary'>Initial Deposit</td>
                            <td class='text-end fw-bold text-success'>₹".number_format($paid, 2)."</td>
                          </tr>";
                } else {
                    echo "<tr><td colspan='3' class='text-center py-3 text-muted small fst-italic'>No payments recorded yet.</td></tr>";
                }
            }

echo "      </tbody>
        </table>
    </div>

    <!-- SUMMARY PANEL -->
    <div class='row align-items-center bg-light p-3 rounded-3 border'>
        <div class='col-md-6 border-end'>
            <div class='d-flex justify-content-between mb-1'>
                <span class='text-muted fw-bold'>Total Collected:</span>
                <span class='text-success fw-bold fs-5'>₹".number_format($paid, 2)."</span>
            </div>
        </div>
        <div class='col-md-6'>
            <div class='d-flex justify-content-between mb-1'>
                <span class='text-muted fw-bold'>Balance Remaining:</span>
                <span class='text-danger fw-bold fs-5'>₹".number_format($due, 2)."</span>
            </div>
        </div>
    </div>
</div>";
?>