<?php
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

if(isset($_POST['id']) && isset($_POST['name'])) {
    $id = $conn->real_escape_string($_POST['id']);
    $name = $conn->real_escape_string($_POST['name']);
    
    // Fetch Customer Details
    $query = $conn->query("SELECT * FROM customers WHERE id = '$id'");
    if($query->num_rows == 0) die("<div class='alert alert-danger m-3'>Customer not found.</div>");
    
    $cust = $query->fetch_assoc();
    $cid = !empty($cust['customer_id']) ? $cust['customer_id'] : 'CUST-'.str_pad($cust['id'], 4, '0', STR_PAD_LEFT);
    $address = array_filter([$cust['village'], $cust['po'], $cust['dist'], $cust['pin'], $cust['state']]);
    $full_address = !empty($address) ? implode(", ", $address) : "Not Provided";

    // Fetch Invoice Ledger for this customer
    $ledger_query = $conn->query("SELECT i.*, 
        (SELECT GROUP_CONCAT(CONCAT('~ ₹', amount, ' on ', DATE_FORMAT(payment_date, '%d %b %y'))) FROM invoice_payments WHERE invoice_id = i.id) as payment_history
        FROM invoices i WHERE customer_name = '$name' ORDER BY i.id DESC");
?>

<div class="container-fluid p-4">
    <!-- Top Row: Profile & Banking -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body bg-white rounded-4 border-start border-primary border-4">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="bi bi-person-lines-fill me-2"></i>Profile Information</h6>
                    <h4 class="fw-bolder text-dark mb-1"><?php echo htmlspecialchars($cust['name']); ?></h4>
                    <span class="badge bg-primary bg-opacity-10 text-primary mb-3 px-3 py-1"><?php echo $cid; ?></span>
                    
                    <div class="d-flex mb-2"><i class="bi bi-telephone-fill text-muted me-3"></i><span class="fw-bold"><?php echo htmlspecialchars($cust['contact_no']); ?></span></div>
                    <div class="d-flex"><i class="bi bi-geo-alt-fill text-muted me-3"></i><span class="text-secondary"><?php echo htmlspecialchars($full_address); ?></span></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card h-100 border-0 shadow-sm rounded-4">
                <div class="card-body bg-white rounded-4 border-start border-success border-4">
                    <h6 class="fw-bold text-muted small text-uppercase mb-3"><i class="bi bi-bank me-2"></i>Banking Details</h6>
                    <?php if(!empty($cust['bank_name'])): ?>
                        <div class="row">
                            <div class="col-6 mb-2 text-muted small">Bank Name</div><div class="col-6 mb-2 fw-bold text-dark"><?php echo htmlspecialchars($cust['bank_name']); ?></div>
                            <div class="col-6 mb-2 text-muted small">Branch</div><div class="col-6 mb-2 fw-bold text-dark"><?php echo !empty($cust['branch_name']) ? htmlspecialchars($cust['branch_name']) : '-'; ?></div>
                            <div class="col-6 mb-2 text-muted small">Account No</div><div class="col-6 mb-2 fw-bolder text-success fs-5"><?php echo htmlspecialchars($cust['account_no']); ?></div>
                            <div class="col-6 mb-0 text-muted small">IFSC Code</div><div class="col-6 mb-0 fw-bold text-dark text-uppercase"><?php echo htmlspecialchars($cust['ifsc_code']); ?></div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted"><i class="bi bi-exclamation-circle fs-3 d-block mb-2 opacity-50"></i>No banking information provided.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Invoice Ledger Table -->
    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-receipt-cutoff text-primary me-2"></i>Invoice Ledger</h5>
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Invoice Ref</th>
                        <th class="text-end">Total Bill</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="text-end text-danger pe-4">Due Balance</th>
                        <th>Payment Logs</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_owed = 0;
                    if($ledger_query->num_rows > 0): 
                        while($row = $ledger_query->fetch_assoc()):
                            $paid = (float)$row['paid_amount'];
                            $total = (float)$row['total_amount'];
                            $due = $total - $paid;
                            $total_owed += $due;
                            $inv_no = "INV-" . str_pad($row['id'], 5, '0', STR_PAD_LEFT);
                    ?>
                    <tr>
                        <td class="ps-4 fw-bold text-secondary"><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                        <td><span class="badge bg-light text-dark border"><?php echo $inv_no; ?></span></td>
                        <td class="text-end fw-bold">₹<?php echo number_format($total, 2); ?></td>
                        <td class="text-end text-success fw-bold">₹<?php echo number_format($paid, 2); ?></td>
                        <td class="text-end text-danger fw-bolder pe-4">₹<?php echo number_format($due, 2); ?></td>
                        <td>
                            <div class="small text-muted" style="max-height: 60px; overflow-y: auto;">
                                <?php echo !empty($row['payment_history']) ? nl2br(htmlspecialchars($row['payment_history'])) : '<span class="fst-italic opacity-50">No payments yet</span>'; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No invoice records found for this customer.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if($total_owed > 0): ?>
                <tfoot class="table-danger">
                    <tr>
                        <td colspan="4" class="text-end fw-bold text-danger">TOTAL OUTSTANDING BALANCE:</td>
                        <td class="text-end fw-bolder text-danger fs-5 pe-4">₹<?php echo number_format($total_owed, 2); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php } ?>