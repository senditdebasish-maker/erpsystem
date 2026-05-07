<?php
include '../includes/db.php';

if(!isset($_POST['name'])) die("<div class='alert alert-danger'>No customer specified.</div>");
$name = $conn->real_escape_string($_POST['name']);

// Fetch Customer
$sql = "SELECT * FROM customers WHERE name = '$name' LIMIT 1";
$res = $conn->query($sql);

if($res->num_rows == 0) {
    echo "<div class='text-center py-4'>
            <i class='bi bi-exclamation-circle text-warning fs-1 d-block mb-3'></i>
            <h5 class='fw-bold text-dark'>Profile Not Found</h5>
            <p class='text-muted'>This customer name exists in an invoice, but their full profile was never saved in the Customer Directory.</p>
          </div>";
    exit;
}

$c = $res->fetch_assoc();
$cid = !empty($c['customer_id']) ? $c['customer_id'] : 'CUST-'.str_pad($c['id'], 4, '0', STR_PAD_LEFT);

// --- LOGIC: SEPARATE BILLING & SHIPPING ADDRESSES ---
// Billing Address
$bill_addr = (!empty($c['village']) ? htmlspecialchars($c['village']) : '') . (!empty($c['po']) ? ', PO: '.htmlspecialchars($c['po']) : '') . '<br>';
$bill_addr .= (!empty($c['dist']) ? 'Dist: '.htmlspecialchars($c['dist']) : '') . (!empty($c['pin']) ? ' - '.htmlspecialchars($c['pin']) : '');
if(trim(strip_tags($bill_addr)) == '' || trim(strip_tags($bill_addr)) == '-' || trim(strip_tags($bill_addr)) == ', PO:') $bill_addr = 'Not Provided';

// Shipping Address (Falls back to billing if shipping columns are empty/don't exist)
$ship_village = $c['shipping_village'] ?? $c['village'] ?? '';
$ship_po = $c['shipping_po'] ?? $c['po'] ?? '';
$ship_dist = $c['shipping_dist'] ?? $c['dist'] ?? '';
$ship_pin = $c['shipping_pin'] ?? $c['pin'] ?? '';

$ship_addr = (!empty($ship_village) ? htmlspecialchars($ship_village) : '') . (!empty($ship_po) ? ', PO: '.htmlspecialchars($ship_po) : '') . '<br>';
$ship_addr .= (!empty($ship_dist) ? 'Dist: '.htmlspecialchars($ship_dist) : '') . (!empty($ship_pin) ? ' - '.htmlspecialchars($ship_pin) : '');
if(trim(strip_tags($ship_addr)) == '' || trim(strip_tags($ship_addr)) == '-' || trim(strip_tags($ship_addr)) == ', PO:') $ship_addr = 'Same as Billing';

// Fetch Lifetime Stats
$stats = $conn->query("SELECT COUNT(*) as total_orders, SUM(total_amount) as lifetime_spent, SUM(total_amount - IFNULL(paid_amount,0)) as lifetime_owed FROM invoices WHERE customer_name = '$name'")->fetch_assoc();

?>

<div class='text-center mb-4'>
    <div class='bg-primary text-white d-inline-block rounded-circle mb-2 d-flex align-items-center justify-content-center mx-auto shadow' style='width: 60px; height: 60px; font-size: 24px;'>
        <i class='bi bi-person'></i>
    </div>
    <h4 class='fw-bold text-dark mb-0'><?php echo htmlspecialchars($c['name']); ?></h4>
    <span class='badge bg-light text-dark border'><?php echo $cid; ?></span>
    <?php if(!empty($c['gstin'])): ?>
        <span class='badge bg-warning text-dark border ms-1'><i class="bi bi-receipt me-1"></i> GSTIN: <?php echo htmlspecialchars($c['gstin']); ?></span>
    <?php endif; ?>
</div>

<!-- KPI DASHBOARD -->
<div class='row g-3 mb-4'>
    <div class='col-md-4'>
        <div class='card bg-white border-0 shadow-sm h-100 p-3 text-center border-bottom border-primary border-3'>
            <h6 class='text-muted small text-uppercase fw-bold mb-2'>Total Invoices</h6>
            <h4 class='fw-bold text-primary mb-0'><?php echo ($stats['total_orders'] ?? 0); ?></h4>
        </div>
    </div>
    <div class='col-md-4'>
        <div class='card bg-white border-0 shadow-sm h-100 p-3 text-center border-bottom border-success border-3'>
            <h6 class='text-muted small text-uppercase fw-bold mb-2'>Lifetime Billed</h6>
            <h5 class='fw-bold text-success mb-0'>₹ <?php echo number_format($stats['lifetime_spent'] ?? 0, 2); ?></h5>
        </div>
    </div>
    <div class='col-md-4'>
        <div class='card bg-white border-0 shadow-sm h-100 p-3 text-center border-bottom border-danger border-3'>
            <h6 class='text-muted small text-uppercase fw-bold mb-2'>Total Owed</h6>
            <h5 class='fw-bold text-danger mb-0'>₹ <?php echo number_format($stats['lifetime_owed'] ?? 0, 2); ?></h5>
        </div>
    </div>
</div>

<!-- DETAILS CARDS -->
<div class='row g-4 mb-4'>
    <!-- Contact & Addresses -->
    <div class='col-md-6'>
        <div class='card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100'>
            <div class='card-header bg-light py-2 border-0'>
                <h6 class='mb-0 fw-bold text-dark'><i class='bi bi-geo-alt-fill me-2 text-danger'></i> Contact Details</h6>
            </div>
            <div class='card-body p-3'>
                <div class='mb-3'>
                    <small class='text-muted fw-bold text-uppercase d-block'>Phone Number</small>
                    <span class='fw-bold text-dark'><?php echo !empty($c['contact_no']) ? htmlspecialchars($c['contact_no']) : 'N/A'; ?></span>
                </div>
                <div class='row'>
                    <div class='col-6 border-end'>
                        <small class='text-muted fw-bold text-uppercase d-block'>Billing Address</small>
                        <span class='text-dark small lh-sm d-block mt-1'><?php echo $bill_addr; ?></span>
                    </div>
                    <div class='col-6 ps-3'>
                        <small class='text-muted fw-bold text-uppercase d-block'>Shipping Address</small>
                        <span class='text-dark small lh-sm d-block mt-1'><?php echo $ship_addr; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Banking Details -->
    <div class='col-md-6'>
        <div class='card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100'>
            <div class='card-header bg-light py-2 border-0'>
                <h6 class='mb-0 fw-bold text-dark'><i class='bi bi-bank2 me-2 text-success'></i> Banking Details</h6>
            </div>
            <div class='card-body p-3'>
                <?php if(!empty($c['bank_name'])): ?>
                    <div class='mb-2'>
                        <small class='text-muted fw-bold text-uppercase d-block'>Bank & Branch</small>
                        <span class='fw-bold text-dark'><?php echo htmlspecialchars($c['bank_name']); ?> (<?php echo htmlspecialchars($c['branch_name']); ?>)</span>
                    </div>
                    <div>
                        <small class='text-muted fw-bold text-uppercase d-block'>Account & IFSC</small>
                        <span class='text-dark'>A/c: <strong><?php echo htmlspecialchars($c['account_no']); ?></strong> <br>IFSC: <?php echo htmlspecialchars($c['ifsc_code']); ?></span>
                    </div>
                <?php else: ?>
                    <div class='text-center py-4 text-muted fst-italic'>No bank details provided.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- PAGINATED INVOICE HISTORY -->
<h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2"></i>Invoice History</h6>
<div class="table-responsive border border-secondary border-opacity-25 rounded-3 shadow-sm">
    <table class="table table-hover align-middle mb-0 w-100" id="customerInvoicesTable">
        <thead class="table-light">
            <tr class="small text-uppercase fw-bold text-muted">
                <th class="ps-3">Inv #</th>
                <th>Date</th>
                <th class="text-end">Discount</th>
                <th class="text-end">Net Payable</th>
                <th class="text-center pe-3">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $inv_query = $conn->query("SELECT * FROM invoices WHERE customer_name = '$name' ORDER BY id DESC");
            if($inv_query && $inv_query->num_rows > 0):
                while($inv = $inv_query->fetch_assoc()): 
                    
                    // Format Discount 00.00(0%)
                    $d_amt = (float)($inv['discount'] ?? 0);
                    $total = (float)$inv['total_amount'];
                    $subtotal = $total + $d_amt;
                    $d_pct = ($subtotal > 0) ? round(($d_amt / $subtotal) * 100, 1) : 0;
                    $modal_discount = ($d_amt > 0) ? number_format($d_amt, 2) . " (" . $d_pct . "%)" : "0.00 (0%)";
                    
                    // Dynamic Status Calculation
                    $paid = (float)($inv['paid_amount'] ?? 0);
                    $perc = ($total > 0) ? ($paid / $total) * 100 : 0;
                    
                    if ($perc >= 100) {
                        $status_text = 'Paid';
                        $status_badge = 'success';
                    } elseif ($perc > 0) {
                        $status_text = 'Partial';
                        $status_badge = 'warning text-dark';
                    } else {
                        $status_text = 'Unpaid';
                        $status_badge = 'danger';
                    }
            ?>
            <tr>
                <!-- NOTE: The 'view-invoice' class here hooks into your parent page's JS script automatically! -->
                <td class="ps-3">
                    <a href="#" class="view-invoice fw-bold text-primary text-decoration-none" data-id="<?php echo $inv['id']; ?>">
                        INV-<?php echo str_pad($inv['id'], 5, '0', STR_PAD_LEFT); ?>
                    </a>
                </td>
                <td class="small fw-bold text-dark"><?php echo date('d-M-Y', strtotime($inv['created_at'])); ?></td>
                <td class="text-warning text-end fw-bold"><?php echo $modal_discount; ?></td>
                <td class="fw-bolder text-end text-dark">₹<?php echo number_format($total, 2); ?></td>
                <td class="text-center pe-3">
                    <span class="badge bg-<?php echo $status_badge; ?>"><?php echo $status_text; ?></span>
                </td>
            </tr>
            <?php 
                endwhile; 
            else:
            ?>
                <tr><td colspan="5" class="text-center py-4 text-muted small fst-italic">No purchase history found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Initialize DataTables -->
<script>
    $(document).ready(function() {
        if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#customerInvoicesTable')) {
            $('#customerInvoicesTable').DataTable({
                "pageLength": 5,          // Keep modal clean with 5 rows
                "lengthChange": false,    
                "info": false,             
                "ordering": false,        
                "language": {
                    "search": "Filter:"
                }
            });
        }
    });
</script>