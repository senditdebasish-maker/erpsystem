<?php
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

if(!isset($_POST['id'])) die("Invalid request.");
$id = $conn->real_escape_string($_POST['id']);

// Get Purchase & Supplier Details
$sql = "SELECT p.*, s.supplier_id as supp_code, s.name as supplier_name, s.contact_no as contact, s.village as address 
        FROM purchases p 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        WHERE p.id = '$id'";
$pur_query = $conn->query($sql);

if($pur_query->num_rows == 0) die("<div class='alert alert-danger m-3'>Purchase record not found.</div>");
$pur = $pur_query->fetch_assoc();

$paid = (float)($pur['paid_amount'] ?? 0);
$total = (float)$pur['total_amount'];
$discount = (float)($pur['discount'] ?? 0);
$due = $total - $paid;

$sid = !empty($pur['supp_code']) ? htmlspecialchars($pur['supp_code']) : 'N/A';
$phone = !empty($pur['contact']) ? htmlspecialchars($pur['contact']) : 'N/A';
$addr = !empty($pur['address']) ? htmlspecialchars($pur['address']) : 'Address N/A';

echo "
<div class='p-4 bg-white'>
    <div class='row mb-4 border-bottom pb-4'>
        <div class='col-sm-7'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Vendor / Supplier</h6>
            <h5 class='fw-bold mb-2 text-primary'><i class='bi bi-truck me-2'></i>".htmlspecialchars($pur['supplier_name'])."</h5>
            <div class='small text-dark lh-sm'>
                <i class='bi bi-person-badge text-muted me-1'></i> {$sid}<br>
                <i class='bi bi-telephone text-muted me-1 mt-1'></i> {$phone}<br>
                <i class='bi bi-geo-alt text-muted me-1 mt-1'></i> {$addr}
            </div>
        </div>
        <div class='col-sm-5 text-sm-end'>
            <h6 class='text-muted text-uppercase small fw-bold mb-1'>Purchase Date</h6>
            <h6 class='fw-bold mb-0'>".date('d M Y', strtotime($pur['created_at']))."</h6>
            <span class='text-muted small'>".date('h:i A', strtotime($pur['created_at']))."</span>
        </div>
    </div>

    <div class='table-responsive border rounded-3 shadow-sm mb-4'>
        <table class='table table-borderless align-middle mb-0'>
            <thead class='table-light border-bottom'>
                <tr class='text-uppercase small text-muted'>
                    <th class='ps-3'>Product Name</th>
                    <th class='text-center'>Qty</th>
                    <th class='text-end'>Unit Cost</th>
                    <th class='text-end pe-3'>Line Total</th>
                </tr>
            </thead>
            <tbody>";

$items = $conn->query("SELECT pi.*, pr.product_name FROM purchase_items pi JOIN products pr ON pi.product_id = pr.id WHERE pi.purchase_id = '$id'");
while($item = $items->fetch_assoc()) {
    $line_total = $item['qty'] * $item['cost_price'];
    echo "<tr>
        <td class='ps-3 fw-bold text-dark'>".htmlspecialchars($item['product_name'])."</td>
        <td class='text-center'><span class='badge bg-light text-dark border'>{$item['qty']}</span></td>
        <td class='text-end text-muted'>₹".number_format($item['cost_price'], 2)."</td>
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
                    <td colspan='3' class='text-end fw-bold text-success small text-uppercase'>Discount Received:</td>
                    <td class='text-end text-success fw-bold pe-3'>- ₹".number_format($discount, 2)."</td>
                </tr>
                <tr class='border-top border-dark'>
                    <td colspan='3' class='text-end fw-bold fs-5 py-3'>Grand Total:</td>
                    <td class='text-end fw-bold text-primary fs-5 pe-3 py-3'>₹".number_format($total, 2)."</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- PAYMENT HISTORY TIMELINE -->
    <h6 class='fw-bold text-dark border-bottom pb-2 mb-3'><i class='bi bi-clock-history text-secondary me-2'></i>Payment Timeline</h6>
    <div class='table-responsive mb-4'>
        <table class='table table-sm table-borderless align-middle mb-0'>
            <thead class='border-bottom'>
                <tr class='text-muted small text-uppercase'>
                    <th>Date & Time</th>
                    <th>Deducted From</th>
                    <th class='text-end'>Amount Paid</th>
                </tr>
            </thead>
            <tbody>";

            // Fetch Payments
            $pay_query = $conn->query("SELECT p.*, b.bank_name, b.account_no FROM purchase_payments p LEFT JOIN bank_accounts b ON p.bank_id = b.id WHERE p.purchase_id = '$id' ORDER BY p.payment_date ASC");
            
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
                            <td class='small text-secondary'>Initial Payment</td>
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
                <span class='text-muted fw-bold'>Total Paid:</span>
                <span class='text-success fw-bold fs-5'>₹".number_format($paid, 2)."</span>
            </div>
        </div>
        <div class='col-md-6'>
            <div class='d-flex justify-content-between mb-1'>
                <span class='text-muted fw-bold'>Balance Owed:</span>
                <span class='text-danger fw-bold fs-5'>₹".number_format($due, 2)."</span>
            </div>
        </div>
    </div>
</div>";
?>