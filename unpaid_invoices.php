<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// Calculate Total Outstanding Money
$due_query = $conn->query("SELECT SUM(total_amount - paid_amount) as total_due FROM invoices WHERE payment_status != 'Paid'");
$total_outstanding = $due_query->fetch_assoc()['total_due'] ?? 0;
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-danger"><i class="bi bi-exclamation-octagon me-2"></i>Accounts Receivable</h2>
            <p class="text-muted">Track unpaid bills and collect pending payments.</p>
        </div>
        <div class="text-end">
            <h6 class="text-muted mb-1">Total Outstanding Dues</h6>
            <h3 class="fw-bold text-danger mb-0">₹ <?php echo number_format($total_outstanding, 2); ?></h3>
        </div>
    </div>

    <div class="card shadow-sm border-0 border-top border-danger border-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice #</th>
                            <th>Customer Name</th>
                            <th>Bill Total</th>
                            <th>Amount Paid</th>
                            <th>Amount Due</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // Fetch only invoices that are Unpaid or Partial
                        $sql = "SELECT * FROM invoices WHERE payment_status != 'Paid' ORDER BY id DESC";
                        $res = $conn->query($sql);
                        
                        if($res->num_rows > 0):
                            while($row = $res->fetch_assoc()): 
                                $due = $row['total_amount'] - $row['paid_amount'];
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold">
                                <a href="view_invoice.php?id=<?php echo $row['id']; ?>" class="text-decoration-none text-primary">
                                    #INV-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?>
                                </a>
                            </td>
                            <td class="fw-bold"><i class="bi bi-person-circle text-muted me-1"></i> <?php echo $row['customer_name']; ?></td>
                            <td class="text-muted">₹ <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td class="text-success">₹ <?php echo number_format($row['paid_amount'], 2); ?></td>
                            <td><h6 class="mb-0 fw-bold text-danger">₹ <?php echo number_format($due, 2); ?></h6></td>
                            <td>
                                <?php if($row['payment_status'] == 'Partial'): ?>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-pie-chart-fill me-1"></i> Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="bi bi-x-circle-fill me-1"></i> Unpaid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-success fw-bold pay-btn shadow-sm" 
                                        data-id="<?php echo $row['id']; ?>" 
                                        data-due="<?php echo $due; ?>"
                                        data-cust="<?php echo $row['customer_name']; ?>">
                                    <i class="bi bi-cash-coin me-1"></i> Collect Payment
                                </button>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-emoji-smile fs-1 d-block mb-2 text-success"></i>
                                Great job! All invoices are fully paid.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow">
            <form id="paymentForm">
                <div class="modal-header bg-success text-white py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2"></i>Record Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="invoice_id" id="pay_inv_id">
                    
                    <div class="mb-3 text-center">
                        <h6 class="text-muted mb-1" id="pay_cust_name">Customer Name</h6>
                        <h3 class="fw-bold text-danger mb-0">Due: ₹<span id="pay_due_amount">0.00</span></h3>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Amount Receiving (₹)</label>
                        <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control form-control-lg text-success fw-bold" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Deposit To Bank Account</label>
                        <select name="bank_id" class="form-select" required>
                            <option value="">Select Receiving Account...</option>
                            <?php 
                            $banks = $conn->query("SELECT id, bank_name FROM bank_accounts");
                            while($b = $banks->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bank_name']}</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4">Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Open the Modal and fill it with data when "Collect Payment" is clicked
    $('.pay-btn').click(function() {
        $('#pay_inv_id').val($(this).data('id'));
        $('#pay_cust_name').text($(this).data('cust'));
        
        let due = $(this).data('due');
        $('#pay_due_amount').text(due.toFixed(2));
        $('#pay_amount').val(due.toFixed(2)); // Auto-fill with full due amount
        $('#pay_amount').attr('max', due); // Prevent overpaying
        
        $('#paymentModal').modal('show');
    });

    // Handle the Form Submission via AJAX silently
    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        let submitBtn = $(this).find('button[type="submit"]');
        submitBtn.html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop('disabled', true);

        $.ajax({
            url: 'modules/process_payment.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.trim() === 'success') {
                    location.reload(); // Reload page to update the table and totals
                } else {
                    alert("Error: " + response);
                    submitBtn.html('Save Payment').prop('disabled', false);
                }
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>