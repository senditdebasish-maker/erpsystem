<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// Generate Auto Purchase Number
$last_id_res = $conn->query("SELECT id FROM purchases ORDER BY id DESC LIMIT 1");
$next_id = ($last_id_res && $last_id_res->num_rows > 0) ? $last_id_res->fetch_assoc()['id'] + 1 : 1;
$purchase_no = "PUR-" . str_pad($next_id, 5, '0', STR_PAD_LEFT);

// Fetch Products
$prod_options = "<option value=''>Search Item...</option>";
$prods = $conn->query("SELECT id, product_name, qty, purchase_price, gst_rate FROM products ORDER BY product_name ASC");
while($p = $prods->fetch_assoc()) {
    $prod_options .= "<option value='{$p['id']}' data-stock='{$p['qty']}' data-cost='{$p['purchase_price']}' data-gst='{$p['gst_rate']}'>{$p['product_name']}</option>";
}

// Fetch Categories & Suppliers for Modals
$cat_options = "<option value=''>Select Category...</option>";
$cats = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
if($cats) { while($c = $cats->fetch_assoc()) $cat_options .= "<option value='{$c['id']}'>{$c['category_name']}</option>"; }

$sup_options = "<option value=''>Select Supplier...</option>";
$sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
if($sups) { while($s = $sups->fetch_assoc()) $sup_options .= "<option value='{$s['id']}'>{$s['name']}</option>"; }

$subcats = [];
$res = $conn->query("SELECT * FROM subcategories");
if ($res) { while($r = $res->fetch_assoc()) { $subcats[] = $r; } }
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    :root { --pos-bg: #f3f6f9; --pos-border: #e2e8f0; --pos-text: #1e293b; }
    body { background-color: var(--pos-bg); font-size: 0.9rem; color: var(--pos-text); }
    .form-control, .form-select { border: 1px solid var(--pos-border); padding: 0.5rem 0.75rem; background-color: #fff; transition: all 0.2s ease; border-radius: 0.4rem; }
    .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important; }
    .calc-text { background: transparent !important; border: 1px solid transparent; font-weight: 700; padding: 0.3rem 0.5rem; outline: none; width: 100%; border-radius: 4px; }
    .calc-text:focus { border: 1px solid #0d6efd; background: #fff !important; }
    .readonly-text { border: none !important; pointer-events: none; }
    .invoice-table-wrapper { border: 1px solid var(--pos-border); border-radius: 0.5rem; overflow: hidden; background: #fff; }
    .table-invoice { margin-bottom: 0; width: 100%; }
    .table-invoice thead th { background-color: #f8fafc; color: #475569; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 10px; border-bottom: 1px solid var(--pos-border); font-weight: 700; }
    .table-invoice tbody tr { border-bottom: 1px solid #f1f5f9; }
    .col-highlight { background-color: #f8fafc; border-left: 1px dashed var(--pos-border); border-right: 1px dashed var(--pos-border); }
    .date-wrapper { position: relative; }
    .date-wrapper input[type="date"]::-webkit-calendar-picker-indicator { position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .select2-container--default .select2-selection--single { border: 1px solid var(--pos-border); border-radius: 0.4rem; height: 38px; display: flex; align-items: center; }
    .tooltip-inner { background-color: #1e293b; font-weight: 500; font-size: 0.75rem; padding: 6px 10px; border-radius: 6px; }
    .summary-card { font-size: 0.85rem; }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-dark p-2 rounded-3 text-white"><i class="bi bi-cart-plus fs-4"></i></div>
            <div>
                <h3 class="fw-bolder mb-0 text-dark tracking-tight">Purchase Entry <span class="badge bg-info text-dark ms-2 fs-6">GST</span></h3>
                <span class="text-muted small fw-bold">Ref No: <span class="text-primary"><?php echo $purchase_no; ?></span></span>
            </div>
        </div>
        <a href="manage_purchases.php" class="btn btn-white border shadow-sm fw-bold text-secondary px-4 py-2 rounded-pill bg-white">
            <i class="bi bi-clock-history me-2 text-primary"></i> Purchase History
        </a>
    </div>

    <!-- SUCCESS ALERT WITH PRINT AND VIEW HISTORY BUTTONS -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): 
        $print_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($print_id == 0) {
            $last_inv_check = $conn->query("SELECT id FROM purchases ORDER BY id DESC LIMIT 1");
            $print_id = ($last_inv_check && $last_inv_check->num_rows > 0) ? $last_inv_check->fetch_assoc()['id'] : 1;
        }
    ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-success border-4 py-3 rounded-3 d-flex justify-content-between align-items-center">
            <div><i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i> Purchase entry successfully recorded!</div>
            <div class="d-flex align-items-center gap-2">
                <a href="print_purchase.php?id=<?php echo $print_id; ?>" target="_blank" class="btn btn-dark btn-sm fw-bold shadow-sm px-4 rounded-pill">
                    <i class="bi bi-printer-fill me-1 text-success"></i> Print Record
                </a>
                <a href="manage_purchases.php" class="btn btn-success btn-sm fw-bold shadow-sm px-4 rounded-pill">View in History</a>
                <button type="button" class="btn-close position-relative top-0 end-0 ms-2" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <form action="modules/save_purchase.php" method="POST">
        <input type="hidden" name="purchase_no" value="<?php echo $purchase_no; ?>">

        <div class="row g-3">
            <div class="col-xl-9 col-lg-8">
                
                <div class="card shadow-sm border-0 mb-3 rounded-4">
                    <div class="card-body p-3 bg-white rounded-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold text-secondary small text-uppercase tracking-wide mb-0"><i class="bi bi-truck me-1 text-primary"></i> Source Supplier</label>
                                    <a href="#" class="text-decoration-none small fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#quickCustomerModal"><i class="bi bi-plus-circle me-1"></i>New Supplier</a>
                                </div>
                                <select name="supplier_id" class="form-select searchable-select" required>
                                    <option value="">-- Search Supplier --</option>
                                    <?php 
                                    $sups = $conn->query("SELECT id, supplier_id, name, contact_no, village FROM suppliers ORDER BY name ASC");
                                    while($s = $sups->fetch_assoc()) {
                                        $sid = !empty($s['supplier_id']) ? $s['supplier_id'] : 'SUP-'.str_pad($s['id'], 4, '0', STR_PAD_LEFT);
                                        $village = !empty($s['village']) ? $s['village'] : 'Address N/A';
                                        echo "<option value='{$s['id']}' data-sid='{$sid}' data-phone='{$s['contact_no']}' data-village='{$village}'>{$s['name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase mb-1"><i class="bi bi-calendar-event me-1 text-primary"></i> Entry Date</label>
                                <div class="date-wrapper bg-white rounded-3">
                                    <input type="date" name="purchase_date" class="form-control fw-bold text-dark shadow-sm border-secondary border-opacity-25" required value="<?php echo date('Y-m-d'); ?>" onclick="this.showPicker();">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-3 rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white py-3">
                        <h6 class="mb-0 fw-bold small tracking-wide"><i class="bi bi-box-seam me-2"></i>Product Sourcing Details</h6>
                    </div>
                    <div class="card-body p-0 px-3 pb-3 bg-white">
                        <div class="invoice-table-wrapper shadow-sm mt-3">
                            <div class="table-responsive overflow-visible">
                                <table class="table-invoice align-middle" id="purchaseTable">
                                    <thead>
                                        <tr>
                                            <th width="35%" class="ps-3">Item Name</th>
                                            <th width="8%" class="text-center">Qty</th>
                                            <th width="12%" class="text-end">Unit Cost</th>
                                            <th width="10%" class="text-center">GST %</th>
                                            <th width="12%" class="text-end">Tax Amt</th>
                                            <th width="13%" class="text-end pe-3 col-highlight">Net Total</th>
                                            <th width="4%" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemRows">
                                        <tr>
                                            <td class="ps-3 py-2">
                                                <select name="product_id[]" class="form-select searchable-select product-select" required>
                                                    <?php echo $prod_options; ?>
                                                </select>
                                                <input type="text" name="item_note[]" class="form-control form-control-sm mt-1 border-0 bg-light text-secondary shadow-none" style="font-size: 0.8rem;" placeholder="Serial No. / Note...">
                                            </td>
                                            <td><input type="number" name="qty[]" class="form-control form-control-sm buy-qty text-center fw-bold rounded-3 px-1" value="1" min="1" required onclick="this.select();"></td>
                                            <td><input type="number" step="0.01" name="cost[]" class="calc-text cost-input text-end text-dark" required placeholder="0.00" onclick="this.select();"></td>
                                            <td><input type="number" step="0.01" name="gst_rate[]" list="gst_presets" class="calc-text gst-input text-center text-info" value="0" required onclick="this.select();"></td>
                                            <td><input type="text" class="calc-text readonly-text tax-amount text-end text-info" readonly placeholder="0.00"></td>
                                            <td class="pe-3 col-highlight"><input type="text" class="calc-text readonly-text row-total text-end text-dark fs-6" readonly placeholder="0.00"></td>
                                            <td class="text-center"><button type="button" class="btn btn-sm btn-light text-danger remove-row rounded-circle shadow-sm border border-danger border-opacity-25" style="width:28px; height:28px;"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
                                <button type="button" id="addRow" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                                    <i class="bi bi-plus-lg me-1"></i> Add Item Row
                                </button>
                                <button type="button" class="btn btn-sm btn-dark rounded-pill px-3 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#quickProductModal">
                                    <i class="bi bi-box-seam me-1"></i> Create New Product
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-4">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden sticky-top summary-card" style="top: 20px; z-index: 1;">
                    <div class="card-header bg-primary text-white py-2 border-0">
                        <h6 class="fw-bold mb-0 text-center tracking-wide text-uppercase small">Order Summary</h6>
                    </div>
                    <div class="card-body p-3 bg-white">
                        <div class="d-flex justify-content-between mb-2"><span>Subtotal</span> <span class="fw-bold">₹ <span id="subTotal">0.00</span></span></div>
                        <div class="d-flex justify-content-between mb-2"><span>Total GST</span> <span class="text-info fw-bold">+ ₹ <span id="totalTax">0.00</span></span><input type="hidden" name="total_tax" id="totalTaxInput"></div>
                        
                        <div class="mb-3 pb-3 border-bottom border-secondary border-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-bold">Discount</span>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check discount-type" name="discount_type" id="disc_amount" value="amount" checked>
                                    <label class="btn btn-outline-secondary btn-sm px-2 py-0 fw-bold" for="disc_amount">₹</label>
                                    <input type="radio" class="btn-check discount-type" name="discount_type" id="disc_percent" value="percent">
                                    <label class="btn btn-outline-secondary btn-sm px-2 py-0 fw-bold" for="disc_percent">%</label>
                                </div>
                            </div>
                            <div class="input-group input-group-sm shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text bg-light border-0 text-danger fw-bold" id="discount_symbol">-₹</span>
                                <input type="number" step="0.01" id="discountInput" class="form-control text-end fw-bold text-danger border-0" value="0" min="0" onclick="this.select();">
                                <input type="hidden" name="discount" id="finalDiscountInput" value="0">
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column align-items-center mb-3">
                            <span class="text-dark fw-bolder text-uppercase small mb-1">Net Payable</span>
                            <h3 class="text-primary fw-bolder mb-0 tracking-tight text-center w-100 bg-primary bg-opacity-10 py-2 rounded-3 border border-primary border-opacity-25 shadow-sm">
                                ₹ <span id="grandTotal">0.00</span>
                            </h3>
                            <input type="hidden" name="final_total" id="finalTotalInput">
                        </div>
                        
                        <div class="bg-light p-2 rounded-3 border border-secondary border-opacity-25 mb-3 shadow-sm">
                            <select name="payment_status" id="paymentStatus" class="form-select form-select-sm fw-bold mb-2 rounded-2">
                                <option value="Paid" selected>Fully Paid</option>
                                <option value="Partial">Partial/Advance</option>
                                <option value="Unpaid">Unpaid / Credit</option>
                            </select>
                            
                            <div class="mb-2" id="paidAmountContainer" style="display: none;">
                                <div class="input-group input-group-sm rounded-2 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-danger text-white border-danger small">Paying ₹</span>
                                    <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control text-end fw-bold border-danger text-danger" value="0" min="0" required onclick="this.select();">
                                </div>
                            </div>
                            
                            <div class="mb-0" id="bankSelectContainer">
                                <select name="bank_id" id="bankSelect" class="form-select form-select-sm shadow-none rounded-2" required>
                                    <option value="">-- Paid From --</option>
                                    <?php $banks = $conn->query("SELECT id, bank_name, balance FROM bank_accounts");
                                    while($b = $banks->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bank_name']} (₹{$b['balance']})</option>"; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center p-2 bg-warning bg-opacity-10 border border-warning border-opacity-25 rounded-3 mb-3">
                            <span class="text-dark fw-bold small">Balance Credit</span>
                            <h5 class="text-dark fw-bolder mb-0">₹ <span id="balanceDue">0.00</span></h5>
                        </div>
                        
                        <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-bold shadow-lg rounded-pill py-2">RECORD ENTRY</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<datalist id="gst_presets"><option value="0"><option value="5"><option value="12"><option value="18"><option value="28"></datalist>

<!-- Includes Modals (Customer/Supplier and Product) -->
<!-- (Keeping the standard Quick Add Modals hidden here to save space, but they function the same) -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    
    // FORMATTERS FOR TOOLTIPS & LISTS
    function formatSupplierResult (s) {
        if (!s.id) return s.text;
        var el = $(s.element);
        var sid = el.data('sid') ? `<span class="badge bg-secondary bg-opacity-10 text-dark border me-1">${el.data('sid')}</span>` : '';
        var phone = el.data('phone') ? `<i class="bi bi-telephone text-primary me-1"></i>${el.data('phone')}` : '';
        var village = el.data('village') ? `<span class="ms-2 border-start ps-2"><i class="bi bi-geo-alt text-danger me-1"></i>${el.data('village')}</span>` : '';
        return $(`<div class="d-flex flex-column lh-sm"><span class="fw-bold text-dark fs-6">${s.text}</span><span class="small text-muted mt-1">${sid} ${phone} ${village}</span></div>`);
    }

    function formatSupplierSelection (s) {
        if (!s.id) return s.text;
        var el = $(s.element);
        var sid = el.data('sid') || 'N/A';
        var phone = el.data('phone') || 'N/A';
        var village = el.data('village') || 'N/A';
        let details = `ID: ${sid} | Ph: ${phone} | Loc: ${village}`;
        return $(`<span data-bs-toggle="tooltip" data-bs-placement="bottom" title="${details}" style="cursor:help;">${s.text} <i class="bi bi-info-circle-fill text-primary ms-1"></i></span>`);
    }

    function initSelect2() { 
        $('.searchable-select').not('.customer-select').select2({ width: '100%' });
        
        // Apply tooltip formatter to supplier dropdown
        $('select[name="supplier_id"]').select2({ templateResult: formatSupplierResult, templateSelection: formatSupplierSelection, width: '100%' });
        
        $('[data-bs-toggle="tooltip"]').tooltip();
    }
    initSelect2();

    $(document).on('select2:select', 'select[name="supplier_id"]', function() {
        setTimeout(() => $('[data-bs-toggle="tooltip"]').tooltip(), 100);
    });

    const productOptionsTemplate = <?php echo json_encode($prod_options); ?>;

    $('#addRow').click(function() {
        let row = `<tr>
            <td class="ps-3 py-2">
                <select name="product_id[]" class="form-select searchable-select product-select" required>
                    ${productOptionsTemplate}
                </select>
                <input type="text" name="item_note[]" class="form-control form-control-sm mt-1 border-0 bg-light text-secondary shadow-none" style="font-size:0.8rem;" placeholder="Serial No. / Note...">
            </td>
            <td><input type="number" name="qty[]" class="form-control form-control-sm buy-qty text-center fw-bold rounded-3 px-1" value="1" min="1" required onclick="this.select();"></td>
            <td><input type="number" step="0.01" name="cost[]" class="calc-text cost-input text-end text-dark" required onclick="this.select();"></td>
            <td><input type="number" step="0.01" name="gst_rate[]" list="gst_presets" class="calc-text gst-input text-center text-info" value="0" onclick="this.select();"></td>
            <td><input type="text" class="calc-text readonly-text tax-amount text-end text-info" readonly></td>
            <td class="pe-3 col-highlight"><input type="text" class="calc-text readonly-text row-total text-end text-dark fs-6" readonly></td>
            <td class="text-center py-2"><button type="button" class="btn btn-sm btn-light text-danger remove-row rounded-circle"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        $('#itemRows').append(row);
        initSelect2();
    });

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); calculateTotal(); });

    $(document).on('change', '.product-select', function() {
        let opt = $(this).find('option:selected');
        let row = $(this).closest('tr');
        if($(this).val()) {
            row.find('.cost-input').val(opt.data('cost'));
            row.find('.gst-input').val(opt.data('gst'));
        }
        calculateTotal();
    });

    $(document).on('input', '.buy-qty, .cost-input, .gst-input, #discountInput, #paidAmount', calculateTotal);
    $(document).on('change', '.discount-type', function() {
        $('#discount_symbol').text($(this).val() === 'percent' ? '-%' : '-₹');
        calculateTotal();
    });

    // =====================================
    // STRICT PAYMENT STATUS FIX
    // =====================================
    $('#paymentStatus').change(function() {
        let status = $(this).val();
        let grand = parseFloat($('#finalTotalInput').val()) || 0;

        if (status === 'Unpaid') {
            $('#paidAmountContainer').slideUp();
            $('#bankSelectContainer').slideUp();
            $('#bankSelect').removeAttr('required'); // FIX: Removes required tag so form submits
            $('#paidAmount').val('0.00'); // FIX: Forces payment to zero
        } else if (status === 'Paid') {
            $('#paidAmountContainer').slideUp();
            $('#bankSelectContainer').slideDown();
            $('#bankSelect').attr('required', true); // Re-adds required tag
            $('#paidAmount').val(grand.toFixed(2));
        } else if (status === 'Partial') {
            $('#paidAmountContainer').slideDown();
            $('#bankSelectContainer').slideDown();
            $('#bankSelect').attr('required', true); // Re-adds required tag
            $('#paidAmount').val('').focus();
        }
        calculateTotal();
    });

    function calculateTotal() {
        let subtotal = 0, totalTax = 0;
        $('#itemRows tr').each(function() {
            let q = parseFloat($(this).find('.buy-qty').val()) || 0;
            let c = parseFloat($(this).find('.cost-input').val()) || 0;
            let g = parseFloat($(this).find('.gst-input').val()) || 0;
            
            let base = q * c;
            let tax = base * (g/100);
            let total = base + tax;
            
            $(this).find('.tax-amount').val(tax.toFixed(2));
            $(this).find('.row-total').val(total.toFixed(2));
            
            subtotal += base; totalTax += tax;
        });
        
        $('#subTotal').text(subtotal.toFixed(2));
        $('#totalTax').text(totalTax.toFixed(2));
        $('#totalTaxInput').val(totalTax.toFixed(2));
        
        let discVal = parseFloat($('#discountInput').val()) || 0;
        let discType = $('input[name="discount_type"]:checked').val();
        let flatDisc = (discType === 'percent') ? ((subtotal + totalTax) * (discVal / 100)) : discVal;
        
        let grand = subtotal + totalTax - flatDisc;
        if(grand < 0) grand = 0;
        
        $('#grandTotal').text(grand.toFixed(2));
        $('#finalTotalInput').val(grand.toFixed(2));
        $('#finalDiscountInput').val(flatDisc.toFixed(2));
        
        // Force Paid Amount Logic
        let currentStatus = $('#paymentStatus').val();
        if (currentStatus === 'Paid') { $('#paidAmount').val(grand.toFixed(2)); } 
        else if (currentStatus === 'Unpaid') { $('#paidAmount').val('0.00'); }

        let paid = parseFloat($('#paidAmount').val()) || 0;
        if(paid > grand) { $('#paidAmount').val(grand.toFixed(2)); paid = grand; }
        
        let balance = grand - paid;
        $('#balanceDue').text(balance.toFixed(2));
    }
});
</script>

<?php include 'includes/footer.php'; ?>