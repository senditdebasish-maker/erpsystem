<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 
?>
<!-- ADD THESE 3 LINES FOR PAGINATION -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<style>
    /* Professional Hyperlinks */
    /* ... your existing styles ... */
<style>
    /* Professional Hyperlinks */
    .view-invoice { cursor: pointer; text-decoration: none; }
    .view-invoice:hover { text-decoration: underline; }
    .view-customer { cursor: pointer; text-decoration: none; border-bottom: 1px dashed #6c757d; }
    .view-customer:hover { color: #0d6efd !important; border-bottom-color: #0d6efd; }
</style>

<div class="container-fluid mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text text-primary me-2"></i>Invoice History</h2>
            <p class="text-muted small">Real-time ledger, tracking, and management.</p>
        </div>
        <a href="generate_invoice.php" class="btn btn-primary fw-bold shadow-sm">
            <i class="bi bi-plus-circle-fill me-2"></i> Create New Bill
        </a>
    </div>

    <!-- 4-COLUMN KPI DASHBOARD -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4 h-100">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Invoices</h6>
                <h3 class="fw-bold text-dark mb-0" id="totalCount"><div class="spinner-border spinner-border-sm text-primary"></div></h3>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4 h-100">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Gross Amount Billed</h6>
                <h3 class="fw-bold text-success mb-0">₹ <span id="totalAmount"><div class="spinner-border spinner-border-sm text-success"></div></span></h3>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-warning border-4 h-100">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Discounts Given</h6>
                <h3 class="fw-bold text-warning mb-0">₹ <span id="totalDiscount"><div class="spinner-border spinner-border-sm text-warning"></div></span></h3>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-danger border-4 h-100">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Pending Dues (Market)</h6>
                <h3 class="fw-bold text-danger mb-0">₹ <span id="totalPending"><div class="spinner-border spinner-border-sm text-danger"></div></span></h3>
            </div>
        </div>
    </div>

    <!-- ADVANCED FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4 bg-light rounded-4">
        <div class="card-body">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Customer</label>
                    <select id="filterCustomer" class="form-select filter-input border-secondary">
                        <option value="">All Customers</option>
                        <?php 
                        $cust_query = $conn->query("SELECT DISTINCT customer_name FROM invoices ORDER BY customer_name ASC");
                        while($c = $cust_query->fetch_assoc()) echo "<option value='{$c['customer_name']}'>{$c['customer_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted">Bank</label>
                    <select id="filterBank" class="form-select filter-input border-secondary">
                        <option value="">All Banks</option>
                        <?php 
                        $bank_query = $conn->query("SELECT id, bank_name FROM bank_accounts");
                        while($b = $bank_query->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bank_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">Status</label>
                    <select id="filterStatus" class="form-select filter-input border-secondary">
                        <option value="">All Statuses</option>
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial Paid</option>
                        <option value="Unpaid">Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">From Date</label>
                    <input type="date" id="filterFrom" class="form-control filter-input border-secondary">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold small text-muted">To Date</label>
                    <input type="date" id="filterTo" class="form-control filter-input border-secondary">
                </div>
            </div>
            
            <div class="row g-3 align-items-end">
                <div class="col-md-10">
                    <div class="input-group shadow-sm">
                        <span class="input-group-text bg-white border-secondary"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchBox" class="form-control border-start-0 filter-input border-secondary" placeholder="Search by Invoice #, Amount, Customer, Product...">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" id="resetFilters" class="btn btn-dark w-100 fw-bold shadow-sm">Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN INVOICE TABLE -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3 border-0">
            <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2"></i> Ledger Entries</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice #</th>
                            <th>Date & Account</th> 
                            <th>Customer Details</th> 
                            <th width="20%">Products</th>
                            <!-- NEW DISCOUNT COLUMN -->
                            <th class="text-end">Discount</th> 
                            <th class="text-end">Grand Total</th>
                            <th class="text-center" width="15%">Collection Status</th> 
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="invoiceTableBody">
                        <!-- AJAX loads here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- INFO MODAL -->
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 border-0">
                <h5 class="modal-title fw-bold" id="infoModalTitle">Loading...</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="infoModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<!-- PAYMENT MODAL -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow rounded-4 overflow-hidden">
            <form id="paymentForm">
                <div class="modal-header bg-success text-white py-3 border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2"></i>Record Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="invoice_id" id="pay_inv_id">
                    
                    <div class="mb-4 text-center p-3 bg-white border border-danger rounded-3 shadow-sm">
                        <h6 class="text-muted mb-1 text-uppercase small" id="pay_cust_name">Customer Name</h6>
                        <h3 class="fw-bold text-danger mb-0">Remaining Due: ₹<span id="pay_due_amount">0.00</span></h3>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success small text-uppercase">Amount Receiving Now (Partial or Full)</label>
                        <input type="number" step="0.01" name="amount" id="pay_amount" class="form-control form-control-lg border-success text-success fw-bold shadow-sm" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary small text-uppercase">Deposit To Bank Account</label>
                        <select name="bank_id" class="form-select shadow-sm" required>
                            <option value="">-- Select Receiving Account --</option>
                            <?php 
                            $banks = $conn->query("SELECT id, bank_name, account_no FROM bank_accounts");
                            while($b = $banks->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bank_name']} ({$b['account_no']})</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0">
                    <button type="button" class="btn btn-light fw-bold shadow-sm border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm"><i class="bi bi-save2-fill me-2"></i> Save Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    function fetchInvoices() {
        let customer = $('#filterCustomer').val();
        let bank = $('#filterBank').val();
        let status = $('#filterStatus').val();
        let search = $('#searchBox').val();
        let date_from = $('#filterFrom').val();
        let date_to = $('#filterTo').val();

        $('#invoiceTableBody').css('opacity', '0.5');

        $.ajax({
            url: 'modules/ajax_filter_invoices.php',
            type: 'POST',
            data: { customer: customer, bank_id: bank, status: status, search: search, date_from: date_from, date_to: date_to },
            dataType: 'json',
            success: function(response) {
                if(response.status === 'success') {
                    $('#invoiceTableBody').html(response.html).css('opacity', '1');
                    $('#totalCount').text(response.count);
                    $('#totalAmount').text(response.total);
                    $('#totalDiscount').text(response.discount);
                    $('#totalPending').text(response.pending);
                }
            }
        });
    }

    fetchInvoices();
    $('.filter-input').on('input change', function() { fetchInvoices(); });

    $('#resetFilters').click(function() {
        $('#filterCustomer').val('');
        $('#filterBank').val('');
        $('#filterStatus').val('');
        $('#searchBox').val('');
        $('#filterFrom').val('');
        $('#filterTo').val('');
        fetchInvoices(); 
    });

    $(document).on('click', '.view-invoice', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        $('#infoModalTitle').html('<i class="bi bi-receipt me-2"></i> Invoice #INV-' + String(id).padStart(5, '0'));
        $('#infoModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#infoModal').modal('show');
        $.post('modules/ajax_get_invoice.php', { id: id }, function(res) { $('#infoModalBody').html(res); });
    });

    $(document).on('click', '.view-customer', function(e) {
        e.preventDefault();
        let name = $(this).data('name');
        $('#infoModalTitle').html('<i class="bi bi-person-badge me-2"></i> Customer Profile');
        $('#infoModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#infoModal').modal('show');
        $.post('modules/ajax_get_customer.php', { name: name }, function(res) { $('#infoModalBody').html(res); });
    });

    $(document).on('click', '.pay-btn', function() {
        let id = $(this).data('id');
        let cust = $(this).data('cust');
        let due = parseFloat($(this).data('due'));

        $('#pay_inv_id').val(id);
        $('#pay_cust_name').text(cust);
        $('#pay_due_amount').text(due.toFixed(2));
        $('#pay_amount').val(due.toFixed(2));
        $('#pay_amount').attr('max', due); 
        
        $('#paymentModal').modal('show');
    });

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
                    $('#paymentModal').modal('hide');
                    fetchInvoices();
                    $('#paymentForm')[0].reset();
                    submitBtn.html('<i class="bi bi-save2-fill me-2"></i> Save Payment').prop('disabled', false);
                } else {
                    alert("Error: " + response);
                    submitBtn.html('<i class="bi bi-save2-fill me-2"></i> Save Payment').prop('disabled', false);
                }
            }
        });
    });
});
</script>
<?php include 'includes/footer.php'; ?>