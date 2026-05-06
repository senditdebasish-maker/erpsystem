<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 
?>

<style>
    body { background-color: #f4f7f6; }
    .filter-input { border: 1px solid #dce1e6; box-shadow: none !important; }
    .filter-input:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1) !important; }
    .view-purchase-link { cursor: pointer; text-decoration: none; font-weight: 700; color: #1e293b; }
    .view-purchase-link:hover { text-decoration: underline; color: #0d6efd !important; }
    .view-supplier-link { cursor: pointer; text-decoration: none; border-bottom: 1px dashed #6c757d; }
    .view-supplier-link:hover { color: #0d6efd !important; border-bottom-color: #0d6efd; }
    .kpi-label { font-size: 0.7rem; letter-spacing: 1px; }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide text-uppercase">Procurement Ledger</span>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Purchase History</h2>
        </div>
        <a href="add_purchase.php" class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-pill">
            <i class="bi bi-plus-lg me-2"></i> New Purchase Entry
        </a>
    </div>

    <!-- 5-COLUMN KPI DASHBOARD (Including GST) -->
    <div class="row g-3 mb-4">
        <div class="col-xl col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Total Orders</h6>
                <h3 class="fw-bolder text-dark mb-0" id="totalCount"><div class="spinner-border spinner-border-sm"></div></h3>
            </div>
        </div>
        <div class="col-xl col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Gross Spent</h6>
                <h3 class="fw-bolder text-success mb-0">₹<span id="totalAmount">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-info border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">GST Paid (Input)</h6>
                <h3 class="fw-bolder text-info mb-0">₹<span id="totalGST">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-warning border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Total Discounts</h6>
                <h3 class="fw-bolder text-warning mb-0">₹<span id="totalDiscount">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-danger border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Balance Owed</h6>
                <h3 class="fw-bolder text-danger mb-0">₹<span id="totalPending">0.00</span></h3>
            </div>
        </div>
    </div>

    <!-- FILTERS -->
    <div class="card shadow-sm border-0 mb-4 bg-white rounded-4">
        <div class="card-body p-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <select id="filterSupplier" class="form-select filter-input shadow-sm">
                        <option value="">All Suppliers</option>
                        <?php 
                        $sup_query = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
                        while($s = $sup_query->fetch_assoc()) echo "<option value='{$s['id']}'>{$s['name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select id="filterStatus" class="form-select filter-input shadow-sm">
                        <option value="">All Status</option>
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial</option>
                        <option value="Unpaid">Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" id="filterFrom" class="form-control filter-input shadow-sm" placeholder="From Date">
                </div>
                <div class="col-md-2">
                    <input type="date" id="filterTo" class="form-control filter-input shadow-sm" placeholder="To Date">
                </div>
                <div class="col-md-3">
                    <input type="text" id="searchBox" class="form-control filter-input shadow-sm" placeholder="Search Ref # or Supplier...">
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TABLE -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Purchase Entry Logs</h6>
            <button id="resetFilters" class="btn btn-sm btn-outline-light py-0">Reset</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-uppercase small text-muted fw-bold">
                            <th class="ps-4">Ref #</th>
                            <th>Purchase Date</th>
                            <th>Supplier</th>
                            <th class="text-end">Base Amount</th>
                            <th class="text-end text-info">GST Amt</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end pe-4">Final Bill</th>
                            <th class="text-center">Status</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="purchaseTableBody">
                        <!-- AJAX content -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODALS (INFO / PAYMENT) remain same structure as your code -->
<div class="modal fade" id="infoModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden"><div class="modal-header bg-white border-bottom py-3"><h5 class="modal-title fw-bolder text-dark" id="infoModalTitle">Purchase Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body p-0 bg-light" id="infoModalBody"></div></div></div></div>

<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 overflow-hidden">
            <form id="paymentForm">
                <div class="modal-header bg-success text-white py-3 border-0"><h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2"></i>Pay Supplier</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="purchase_id" id="pay_pur_id">
                    <div class="mb-4 text-center p-3 bg-white border border-danger rounded-3 shadow-sm">
                        <h6 class="text-muted mb-1 text-uppercase small" id="pay_sup_name">Supplier</h6>
                        <h3 class="fw-bold text-danger mb-0">Due: ₹<span id="pay_due_amount">0.00</span></h3>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold text-success small">Amount Paying Now</label><input type="number" step="0.01" name="amount" id="pay_amount" class="form-control form-control-lg fw-bold" required></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-primary small">Deduct From Bank Account</label>
                        <select name="bank_id" class="form-select form-select-lg" required>
                            <option value="">-- Select Bank --</option>
                            <?php $banks = $conn->query("SELECT id, bank_name, balance FROM bank_accounts");
                            while($b = $banks->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bank_name']} (₹{$b['balance']})</option>"; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0"><button type="submit" class="btn btn-success fw-bold px-5 rounded-pill">Confirm Payment</button></div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function fetchPurchases() {
        let formData = {
            supplier_id: $('#filterSupplier').val(),
            status: $('#filterStatus').val(),
            search: $('#searchBox').val(),
            date_from: $('#filterFrom').val(),
            date_to: $('#filterTo').val()
        };

        $.ajax({
            url: 'modules/ajax_filter_purchases.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    $('#purchaseTableBody').html(res.html);
                    $('#totalCount').text(res.count);
                    $('#totalAmount').text(res.total);
                    $('#totalGST').text(res.total_gst); // NEW: Display total GST in KPI
                    $('#totalDiscount').text(res.discount);
                    $('#totalPending').text(res.pending);
                }
            }
        });
    }

    fetchPurchases();
    $('.filter-input').on('input change', fetchPurchases);
    $('#resetFilters').click(function() { $('.filter-input').val(''); fetchPurchases(); });

    $(document).on('click', '.view-purchase-link', function() {
        let id = $(this).data('id');
        $('#infoModal').modal('show');
        $.post('modules/ajax_get_purchase.php', { id: id }, function(res) { $('#infoModalBody').html(res); });
    });

    $(document).on('click', '.pay-btn', function() {
        $('#pay_pur_id').val($(this).data('id'));
        $('#pay_sup_name').text($(this).data('sup'));
        $('#pay_due_amount').text(parseFloat($(this).data('due')).toFixed(2));
        $('#pay_amount').val(parseFloat($(this).data('due')).toFixed(2));
        $('#paymentModal').modal('show');
    });

    $('#paymentForm').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Processing...');
        $.ajax({
            url: 'modules/process_purchase_payment.php',
            type: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if(response.trim() === 'success') {
                    $('#paymentModal').modal('hide');
                    fetchPurchases();
                } else alert(response);
                btn.prop('disabled', false).text('Confirm Payment');
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>