<?php
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';
if(!isset($_POST['id'])) die("Invalid request.");

$id = $conn->real_escape_string($_POST['id']);

// Get Invoice & Customer Details
$sql = "SELECT i.*, c.customer_id, c.contact_no, c.village, c.dist 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_name = c.name
        WHERE i.id = '$id'";
$inv = $conn->query($sql)->fetch_assoc();

$paid = (float)($inv['paid_amount'] ?? 0);
$total = (float)$inv['total_amount'];
$discount = (float)($inv['discount'] ?? 0);
$due = $total - $paid;

$cid = !empty($inv['customer_id']) ? htmlspecialchars($inv['customer_id']) : 'N/A';
$phone = !empty($inv['contact_no']) ? htmlspecialchars($inv['contact_no']) : 'N/A';
$addr = (!empty($inv['village']) ? htmlspecialchars($inv['village']) : '') . (!empty($inv['dist']) ? ', '.htmlspecialchars($inv['dist']) : '');
if(empty(trim($addr)) || $addr == ', ') $addr = 'Address N/A';

echo "
<div class='p-2'>
    <div class='row mb-4 border-bottom pb-4'>
        <div class='col-sm-7'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Billed To</h6>
            <h5 class='fw-bold mb-1 text-primary'><i class='bi bi-person-fill me-1'></i>".htmlspecialchars($inv['customer_name'])."</h5>
            <div class='small text-dark mb-2 lh-sm'>
                <i class='bi bi-person-badge text-muted me-1'></i> {$cid}<br>
                <i class='bi bi-telephone text-muted me-1 mt-1'></i> {$phone}<br>
                <i class='bi bi-geo-alt text-muted me-1 mt-1'></i> {$addr}
            </div>
        </div>
        <div class='col-sm-5 text-sm-end'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Invoice Date</h6>
            <h6 class='fw-bold mb-0'>".date('d M Y', strtotime($inv['created_at']))."</h6>
            <span class='text-muted small'>".date('h:i A', strtotime($inv['created_at']))."</span>
        </div>
    </div>

    <div class='table-responsive border rounded-3 shadow-sm mb-4'>
        <table class='table table-borderless align-middle mb-0'>
            <thead class='table-light border-bottom'>
                <tr class='text-uppercase small text-muted'>
                    <th class='ps-3'>Product Name</th>
                    <th class='text-center'>Qty</th>
                    <th class='text-end'>Selling Price</th>
                    <th class='text-end pe-3'>Total</th>
                </tr>
            </thead>
            <tbody>";

$items = $conn->query("SELECT ii.*, p.product_name FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = '$id'");
while($item = $items->fetch_assoc()) {
    $line_total = $item['qty'] * $item['price'];
    
    // FORMAT THE LINE ITEM NOTE
    $note_html = "";
    if(!empty($item['item_note'])) {
        $note_html = "<br><small class='text-muted fst-italic'><i class='bi bi-info-circle me-1'></i>" . htmlspecialchars($item['item_note']) . "</small>";
    }

    echo "<tr>
        <td class='ps-3 text-dark'>
            <span class='fw-bold'>".htmlspecialchars($item['product_name'])."</span>
            {$note_html}
        </td>
        <td class='text-center'><span class='badge bg-light text-dark border'>{$item['qty']}</span></td>
        <td class='text-end text-muted'>₹".number_format($item['price'], 2)."</td>
        <td class='text-end fw-bold text-dark pe-3'>₹".number_format($line_total, 2)."</td>
    </tr>";
}

$subtotal = $total + $discount; 

echo "      </tbody>
            <tfoot class='bg-light border-top'>
                <tr>
                    <td colspan='3' class='text-end fw-bold pt-3 small text-uppercase'>Subtotal:</td>
                    <td class='text-end fw-bold pe-3 pt-3'>₹".number_format($subtotal, 2)."</td>
                </tr>
                <tr>
                    <td colspan='3' class='text-end fw-bold text-danger small text-uppercase'>Discount Applied:</td>
                    <td class='text-end text-danger fw-bold pe-3'>- ₹".number_format($discount, 2)."</td>
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