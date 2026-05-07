<?php 
include 'includes/db.php'; 

// ==========================================
// 🛠️ AUTO-HEAL DATABASE 
// ==========================================
$check_gst = $conn->query("SHOW COLUMNS FROM products LIKE 'gst_rate'");
if($check_gst && $check_gst->num_rows == 0) {
    $conn->query("ALTER TABLE products ADD gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER selling_price");
}

include 'includes/header.php'; 

// --- FETCH DATA FOR DROPDOWNS & MODALS ---
$prod_options = "<option value=''>Search Item...</option>";

$prods = $conn->query("SELECT id, product_name, description, qty, selling_price, gst_rate FROM products WHERE qty > 0 ORDER BY product_name ASC");
while($p = $prods->fetch_assoc()) {
    $desc = htmlspecialchars(addslashes($p['description'] ?? ''), ENT_QUOTES);
    $pname = htmlspecialchars(addslashes($p['product_name']), ENT_QUOTES);
    $gst = isset($p['gst_rate']) ? (float)$p['gst_rate'] : 0;
    $prod_options .= "<option value='{$p['id']}' data-desc='{$desc}' data-stock='{$p['qty']}' data-price='{$p['selling_price']}' data-gst='{$gst}'>{$pname}</option>";
}

// Fetch Categories & Suppliers
$cat_options = "<option value=''>Select Category...</option>";
$cats = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
if($cats) { while($c = $cats->fetch_assoc()) $cat_options .= "<option value='{$c['id']}'>{$c['category_name']}</option>"; }

$sup_options = "<option value=''>Select Supplier...</option>";
$sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
if($sups) { while($s = $sups->fetch_assoc()) $sup_options .= "<option value='{$s['id']}'>{$s['name']}</option>"; }

// Fetch Subcategories for the Modal Logic
$subcats = [];
$res = $conn->query("SELECT * FROM subcategories");
if ($res) { while($r = $res->fetch_assoc()) { $subcats[] = $r; } }

// --- CALCULATE NEXT INVOICE NUMBER ---
$last_inv_res = $conn->query("SELECT id FROM invoices ORDER BY id DESC LIMIT 1");
$next_inv_id = ($last_inv_res && $last_inv_res->num_rows > 0) ? $last_inv_res->fetch_assoc()['id'] + 1 : 1;
$auto_inv_no = "INV-" . str_pad($next_inv_id, 5, '0', STR_PAD_LEFT);
?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    :root {
        --pos-bg: #f3f6f9;
        --pos-border: #e2e8f0;
        --pos-text: #1e293b;
    }
    body { background-color: var(--pos-bg); font-size: 0.9rem; color: var(--pos-text); }
    
    /* Input & Select Styling */
    .form-control, .form-select { border: 1px solid var(--pos-border); padding: 0.5rem 0.75rem; background-color: #fff; transition: all 0.2s ease; border-radius: 0.4rem; box-shadow: 0 1px 2px rgba(0,0,0,0.01); }
    .form-control:focus, .form-select:focus { border-color: #0d6efd; background-color: #fff; box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.1) !important; }
    
    /* Transparent Input for Table Math */
    .calc-text { background: transparent !important; border: 1px solid transparent; font-weight: 700; padding: 0.3rem 0.5rem; outline: none; width: 100%; border-radius: 4px; transition: border 0.2s, background 0.2s; }
    .calc-text:focus { border: 1px solid #0d6efd; background: #fff !important; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
    .readonly-text { border: none !important; pointer-events: none; }
    
    /* -------------------------------------
       ✨ ENTERPRISE TABLE STYLING ✨
    -------------------------------------- */
    .invoice-table-wrapper { border: 1px solid var(--pos-border); border-radius: 0.5rem; overflow: hidden; background: #fff; }
    .table-invoice { margin-bottom: 0; width: 100%; table-layout: fixed; } /* Fixed layout prevents stretching */
    .table-invoice thead th { 
        background-color: #f8fafc; color: #475569; font-size: 0.7rem; text-transform: uppercase; 
        letter-spacing: 0.5px; padding: 12px 10px; border-bottom: 1px solid var(--pos-border); font-weight: 700; 
    }
    .table-invoice tbody tr { transition: background-color 0.2s; border-bottom: 1px solid #f1f5f9; }
    .table-invoice tbody tr:hover { background-color: #f8fafc; }
    .table-invoice tbody td { padding: 10px; vertical-align: middle; }
    .table-invoice tbody tr:last-child { border-bottom: none; }
    
    .col-highlight { background-color: #f8fafc; border-left: 1px dashed var(--pos-border); border-right: 1px dashed var(--pos-border); }

    /* -------------------------------------
       ✨ SELECT2 WRAP & WIDE FIX ✨
    -------------------------------------- */
    /* 1. Allows the selected item text to wrap onto multiple lines without stretching the table */
    .select2-container--default .select2-selection--single { 
        border: 1px solid var(--pos-border); 
        border-radius: 0.4rem; 
        min-height: 38px; 
        height: auto !important; /* Auto height instead of fixed 38px */
        display: flex; 
        align-items: center; 
        background-color: #fff; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.01);
        padding: 4px 6px;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { 
        padding-left: 0; 
        width: 100%; 
        line-height: 1.3; 
        color: #1e293b; 
        font-weight: 500;
        white-space: normal !important; /* Force word-wrap inside the table cell */
        word-break: break-word;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow { 
        height: 100%; 
        right: 10px; 
        top: 50%;
        transform: translateY(-50%);
        position: absolute;
    }

    /* 2. Allows the dropdown menu to grow wider than the column when searching */
    .product-dropdown-wide {
        width: auto !important; /* Allows the menu to stretch to fit the one-line content */
        min-width: 100%; /* Ensures it's at least as wide as the input box */
        max-width: 90vw; /* Prevents it from going off the screen entirely */
        white-space: nowrap; /* Forces the content to stay on one line */
    }
    .product-dropdown-wide .select2-results__option {
        white-space: nowrap; /* Keeps items on one line inside the wide dropdown */
        padding: 8px 16px;
    }

    .select2-dropdown { border: 1px solid var(--pos-border); border-radius: 0.5rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); padding: 5px;}
    .select2-results__option { border-radius: 4px; margin-bottom: 2px; transition: background 0.1s;}
    .select2-container--default .select2-results__option--highlighted[aria-selected] { background-color: #f1f5f9; color: #0f172a; }
    
    .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .card-hover:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.05)!important; }
    
    /* ✨ MAGIC DATE PICKER FIX ✨ */
    .date-wrapper { position: relative; }
    .date-wrapper input[type="date"] { cursor: pointer; position: relative; z-index: 2; background: transparent; }
    .date-wrapper input[type="date"]::-webkit-calendar-picker-indicator { 
        position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; 
    }
    
    /* Modals & Utilities */
    .image-upload-box { border: 2px dashed #cbd5e1; background-color: #f8fafc; border-radius: 1rem; height: 160px; cursor: pointer; position: relative; overflow: hidden; }
    .image-upload-box:hover { border-color: #0d6efd; background-color: #eff6ff; }
    .image-upload-box .preview-img { object-fit: cover; border-radius: 0.8rem; position: absolute; top:0; left:0; width:100%; height:100%; }
    .tooltip-inner { background-color: #1e293b; font-weight: 500; font-size: 0.75rem; padding: 6px 10px; border-radius: 6px; }
    .hover-profile-link { cursor: pointer; text-decoration: underline dashed; transition: color 0.2s; }
    .hover-profile-link:hover { color: #0d6efd !important; }
    
    /* COMPACT SUMMARY SIDEBAR */
    .summary-card { font-size: 0.85rem; }
    .summary-card .form-control-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
    .summary-card .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.8rem; }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary"><i class="bi bi-receipt-cutoff fs-4"></i></div>
            <div>
                <h3 class="fw-bolder mb-0 text-dark tracking-tight">Create Bill</h3>
                <span class="text-muted small fw-bold">Invoice No: <span class="text-primary"><?php echo $auto_inv_no; ?></span></span>
            </div>
        </div>
        <a href="manage_invoices.php" class="btn btn-white border shadow-sm fw-bold text-secondary px-4 py-2 rounded-pill bg-white card-hover">
            <i class="bi bi-clock-history me-2 text-primary"></i> Billing History
        </a>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): 
        $print_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($print_id == 0) {
            $last_inv_check = $conn->query("SELECT id FROM invoices ORDER BY id DESC LIMIT 1");
            $print_id = ($last_inv_check && $last_inv_check->num_rows > 0) ? $last_inv_check->fetch_assoc()['id'] : 1;
        }
    ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-success border-4 py-3 rounded-3 d-flex justify-content-between align-items-center">
            <div><i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i> Invoice successfully generated and saved!</div>
            <div class="d-flex align-items-center gap-2">
                <a href="print_invoice.php?id=<?php echo $print_id; ?>" target="_blank" class="btn btn-dark btn-sm fw-bold shadow-sm px-4 rounded-pill">
                    <i class="bi bi-printer-fill me-1 text-success"></i> Print Bill
                </a>
                <a href="manage_invoices.php" class="btn btn-success btn-sm fw-bold shadow-sm px-4 rounded-pill">View in History</a>
                <button type="button" class="btn-close position-relative top-0 end-0 ms-2" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

    <form action="modules/save_invoice.php" method="POST">
        <div class="row g-3">
            
            <div class="col-xl-9 col-lg-8">
                
                <div class="card shadow-sm border-0 mb-3 rounded-4 card-hover">
                    <div class="card-body p-3 bg-white rounded-4">
                        <div class="row g-3 align-items-start">
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold text-secondary small text-uppercase tracking-wide mb-0"><i class="bi bi-person-fill me-1"></i> Billing To</label>
                                    <a href="#" class="text-decoration-none small fw-bold text-primary" data-bs-toggle="modal" data-bs-target="#quickCustomerModal" onclick="$('#ajaxCustomerForm')[0].reset(); $('#modal_update_id').val(''); $('#quickCustomerModal .modal-title').html('<i class=\'bi bi-person-plus me-2\'></i>Quick Register Customer'); $('#sameAsBilling').prop('checked', false);"><i class="bi bi-plus-circle me-1"></i>New Customer</a>
                                </div>
                                <select name="customer_id" id="mainCustomerSelect" class="form-select customer-select" required>
                                    <option value="">-- Search by Name, ID, or Phone --</option>
                                    <?php 
                                    $custs = $conn->query("SELECT * FROM customers ORDER BY name ASC");
                                    while($c = $custs->fetch_assoc()) {
                                        $cid = !empty($c['customer_id']) ? $c['customer_id'] : 'CUST-'.str_pad($c['id'], 4, '0', STR_PAD_LEFT);
                                        $data_json = htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8');
                                        echo "<option value='{$c['id']}' data-json='{$data_json}' data-cid='{$cid}' data-phone='{$c['contact_no']}'>{$c['name']}</option>";
                                    }
                                    ?>
                                </select>

                                <div id="customerDetailsBox" class="mt-2 p-2 bg-light border border-secondary border-opacity-25 rounded-3 d-none">
                                    <div class="row">
                                        <div class="col-6 border-end">
                                            <small class="fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.7rem;">Billing Address</small>
                                            <div id="displayBilling" class="small text-dark lh-sm fw-medium"></div>
                                        </div>
                                        <div class="col-6 ps-3">
                                            <small class="fw-bold text-muted text-uppercase d-block mb-1" style="font-size: 0.7rem;">Shipping Address</small>
                                            <div id="displayShipping" class="small text-dark lh-sm fw-medium"></div>
                                        </div>
                                    </div>
                                    <div class="mt-2 text-end">
                                        <button type="button" id="updateAddressBtn" class="btn btn-sm btn-outline-primary py-0 px-2 fw-bold" style="font-size: 0.75rem;">
                                            <i class="bi bi-pencil-square me-1"></i> Update Details
                                        </button>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-secondary small text-uppercase tracking-wide mb-1"><i class="bi bi-calendar-event me-1"></i> Invoice Date</label>
                                <div class="date-wrapper bg-white rounded-3">
                                    <input type="date" name="invoice_date" class="form-control fw-bold text-dark shadow-sm border-secondary border-opacity-25" required value="<?php echo date('Y-m-d'); ?>" onclick="this.showPicker();">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 mb-3 rounded-4 card-hover">
                    <div class="card-header bg-white py-3 border-bottom-0 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bolder text-dark"><i class="bi bi-cart-check-fill text-primary me-2"></i>Product Ledger</h6>
                        <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill shadow-sm small">GST Engine Active</span>
                    </div>
                    <div class="card-body p-0 px-3 pb-3">
                        <div class="invoice-table-wrapper shadow-sm">
                            <div class="table-responsive overflow-visible">
                                <table class="table-invoice align-middle" id="invoiceTable">
                                    <thead>
                                        <tr>
                                            <th width="40%" class="ps-3">Product / Service</th>
                                            <th width="9%" class="text-center">Qty</th>
                                            <th width="14%" class="text-end">Base Rate</th>
                                            <th width="9%" class="text-center">GST %</th>
                                            <th width="11%" class="text-end">Tax Amt</th>
                                            <th width="13%" class="text-end pe-3">Line Total</th>
                                            <th width="4%" class="text-center"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemRows">
                                        <tr>
                                            <td class="ps-3 py-2">
                                                <select name="product_id[]" class="form-select product-select" required>
                                                    <?php echo $prod_options; ?>
                                                </select>
                                                <input type="text" name="item_note[]" class="form-control form-control-sm mt-1 border-0 bg-light text-secondary shadow-none" style="font-size: 0.8rem;" placeholder="Serial No. / Description Note...">
                                            </td>
                                            <td class="py-2"><input type="number" name="qty[]" class="form-control form-control-sm sale-qty text-center fw-bold rounded-3 px-1 shadow-sm border-secondary border-opacity-25" value="1" min="1" required onclick="this.select();"></td>
                                            <td class="py-2"><input type="number" step="0.01" name="price[]" class="calc-text form-control-sm price-input text-end text-dark fw-bold w-100 px-1" required placeholder="0.00" onclick="this.select();"></td>
                                            <td class="py-2"><input type="text" name="gst_rate[]" class="calc-text form-control-sm readonly-text gst-input text-center text-info w-100 px-1" readonly placeholder="0%"></td>
                                            <td class="py-2"><input type="text" name="tax_amount[]" class="calc-text form-control-sm readonly-text tax-amount text-end text-info w-100 px-1" readonly placeholder="0.00"></td>
                                            <td class="py-2 pe-3 col-highlight"><input type="text" class="calc-text form-control-sm readonly-text row-total text-end text-dark fs-6 w-100 px-1" readonly placeholder="0.00"></td>
                                            <td class="text-center py-2"><button type="button" class="btn btn-sm btn-light text-danger remove-row rounded-circle shadow-sm border border-danger border-opacity-25 p-1" style="width:28px; height:28px;"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-2 bg-light border-top d-flex justify-content-between align-items-center">
                                <button type="button" id="addRow" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm">
                                    <i class="bi bi-plus-lg me-1"></i> Add Row
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
                    <div class="card-header bg-dark text-white py-2 border-0">
                        <h6 class="fw-bold mb-0 text-center tracking-wide text-uppercase small"><i class="bi bi-calculator me-2"></i>Checkout</h6>
                    </div>
                    <div class="card-body p-3 bg-white">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary border-opacity-10">
                            <span class="text-muted fw-bold">Subtotal</span>
                            <span class="text-dark fw-bold">₹ <span id="subTotal">0.00</span></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary border-opacity-10">
                            <span class="text-muted fw-bold">Total GST</span>
                            <span class="text-info fw-bold">+ ₹ <span id="totalGST">0.00</span></span>
                            <input type="hidden" name="total_tax" id="totalTaxInput">
                        </div>
                        
                        <div class="mb-3 pb-3 border-bottom border-secondary border-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-muted fw-bold">Discount</span>
                                <div class="btn-group" role="group">
                                    <input type="radio" class="btn-check discount-type" name="discount_type" id="disc_amount" value="amount" checked>
                                    <label class="btn btn-outline-secondary btn-sm px-2 py-0 fw-bold shadow-sm" for="disc_amount">₹</label>
                                    <input type="radio" class="btn-check discount-type" name="discount_type" id="disc_percent" value="percent">
                                    <label class="btn btn-outline-secondary btn-sm px-2 py-0 fw-bold shadow-sm" for="disc_percent">%</label>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <div class="input-group input-group-sm w-75 shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text bg-light border-end-0 border-secondary border-opacity-25 text-danger fw-bold" id="discount_symbol">-₹</span>
                                    <input type="number" step="0.01" id="discountInput" class="form-control text-end fw-bold text-danger border-start-0 border-secondary border-opacity-25" value="0" min="0" onclick="this.select();">
                                    <input type="hidden" name="discount" id="finalDiscountInput" value="0">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex flex-column align-items-center mb-3">
                            <span class="text-dark fw-bolder text-uppercase small mb-1 tracking-wide">Grand Total</span>
                            <h3 class="text-success fw-bolder mb-0 tracking-tight text-center w-100 bg-success bg-opacity-10 py-2 rounded-3 border border-success border-opacity-25 shadow-sm">
                                ₹ <span id="grandTotal">0.00</span>
                            </h3>
                            <input type="hidden" name="final_total" id="finalTotalInput">
                        </div>
                        
                        <div class="bg-light p-2 rounded-3 border border-secondary border-opacity-25 mb-3 shadow-sm">
                            <div class="mb-2">
                                <select name="payment_status" id="paymentStatus" class="form-select form-select-sm fw-bold shadow-none border-secondary rounded-2">
                                    <option value="Paid" selected>Fully Paid</option>
                                    <option value="Partial">Partial Deposit</option>
                                    <option value="Unpaid">Pay Later</option>
                                </select>
                            </div>
                            
                            <div class="mb-2" id="paidAmountContainer" style="display: none;">
                                <div class="input-group input-group-sm rounded-2 overflow-hidden shadow-sm">
                                    <span class="input-group-text bg-success text-white border-success">Rcvd ₹</span>
                                    <input type="number" step="0.01" name="paid_amount" id="paidAmount" class="form-control text-end fw-bold border-success text-success" value="0" min="0" required onclick="this.select();">
                                </div>
                            </div>
                            
                            <div class="mb-0" id="bankSelectContainer">
                                <select name="bank_id" id="bankSelect" class="form-select form-select-sm shadow-none rounded-2" required>
                                    <option value="">-- Dep. Bank --</option>
                                    <?php 
                                    $banks = $conn->query("SELECT id, bank_name FROM bank_accounts");
                                    while($b = $banks->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bank_name']}</option>";
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center p-2 bg-danger bg-opacity-10 border border-danger border-opacity-25 rounded-3 mb-3">
                            <span class="text-danger fw-bold small">Due Balance</span>
                            <h5 class="text-danger fw-bolder mb-0">₹ <span id="balanceDue">0.00</span></h5>
                        </div>
                        
                        <button type="submit" id="submitBtn" class="btn btn-dark w-100 fw-bold shadow-lg rounded-pill py-2 text-uppercase tracking-wide">
                            <i class="bi bi-check-circle-fill me-1 text-success"></i> Save Invoice
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white py-3 border-0">
                <h5 class="modal-title fw-bold" id="infoModalTitle">Customer Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="infoModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quickCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 bg-light">
            <form id="ajaxCustomerForm" enctype="multipart/form-data">
                <input type="hidden" name="customer_update_id" id="modal_update_id" value="">
                <div class="modal-header bg-primary text-white py-3 border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i>Quick Register Customer</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-xl-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-header bg-white py-3 border-0">
                                    <h6 class="fw-bold text-primary mb-0"><i class="bi bi-geo-alt-fill me-2"></i>Contact & Addresses</h6>
                                </div>
                                <div class="card-body bg-light rounded-bottom-4">
                                    <div class="row g-2 mb-3">
                                        <div class="col-6"><input type="text" name="name" id="modal_name" class="form-control border-secondary" placeholder="Customer Name *" required></div>
                                        <div class="col-6"><input type="text" name="contact_no" id="modal_contact" class="form-control border-secondary" placeholder="Contact No *" required></div>
                                    </div>
                                    
                                    <h6 class="small fw-bold text-muted mb-2 border-bottom pb-1">Billing Address</h6>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6"><input type="text" name="village" id="modal_vill" class="form-control border-secondary form-control-sm" placeholder="Village / Street"></div>
                                        <div class="col-6"><input type="text" name="po" id="modal_po" class="form-control border-secondary form-control-sm" placeholder="P.O"></div>
                                        <div class="col-4"><input type="text" name="dist" id="modal_dist" class="form-control border-secondary form-control-sm" placeholder="District"></div>
                                        <div class="col-4"><input type="text" name="pin" id="modal_pin" class="form-control border-secondary form-control-sm" placeholder="PIN"></div>
                                        <div class="col-4"><input type="text" name="state" id="modal_state" class="form-control border-secondary form-control-sm" placeholder="State"></div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-2">
                                        <h6 class="small fw-bold text-muted mb-0">Shipping Address</h6>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" id="sameAsBilling">
                                            <label class="form-check-label small" style="font-size:0.75rem;" for="sameAsBilling">Same as Billing</label>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6"><input type="text" name="shipping_village" id="ship_vill" class="form-control border-secondary form-control-sm" placeholder="Village / Street"></div>
                                        <div class="col-6"><input type="text" name="shipping_po" id="ship_po" class="form-control border-secondary form-control-sm" placeholder="P.O"></div>
                                        <div class="col-4"><input type="text" name="shipping_dist" id="ship_dist" class="form-control border-secondary form-control-sm" placeholder="District"></div>
                                        <div class="col-4"><input type="text" name="shipping_pin" id="ship_pin" class="form-control border-secondary form-control-sm" placeholder="PIN"></div>
                                        <div class="col-4"><input type="text" name="shipping_state" id="ship_state" class="form-control border-secondary form-control-sm" placeholder="State"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4">
                                <div class="card-header bg-white py-3 border-0">
                                    <h6 class="fw-bold text-success mb-0"><i class="bi bi-bank me-2"></i>Banking Information</h6>
                                </div>
                                <div class="card-body bg-light rounded-bottom-4">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <select name="bank_name" id="modal_bank" class="form-select border-secondary">
                                                <option value="">-- Select Bank --</option>
                                                <option value="SBI">State Bank of India</option>
                                                <option value="HDFC">HDFC Bank</option>
                                                <option value="ICICI">ICICI Bank</option>
                                                <option value="PNB">Punjab National Bank</option>
                                                <option value="Other">Other Bank</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="branch_name" id="modal_branch" class="form-control border-secondary" placeholder="Branch Name">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-6">
                                            <input type="text" name="account_no" id="modal_acc" class="form-control border-secondary fw-bold text-dark" placeholder="Account Number">
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" name="ifsc_code" id="modal_ifsc" class="form-control border-secondary text-uppercase" placeholder="IFSC Code">
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label fw-bold small text-muted">Upload Passbook/Cheque</label>
                                        <input type="file" name="passbook" class="form-control border-secondary" accept=".jpg,.jpeg,.png,.pdf">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-white rounded-bottom-4">
                    <button type="submit" class="btn btn-primary fw-bold px-5 rounded-pill shadow-sm">Save & Select</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="quickProductModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4 bg-light">
            <form id="ajaxProductForm" enctype="multipart/form-data">
                <div class="modal-header bg-dark text-white py-3 border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-box-seam me-2"></i>Register New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="card shadow-sm border-0 rounded-4 h-100">
                                <div class="card-body px-4 py-4">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Product Identification</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                                <span class="input-group-text border-end-0 bg-white"><i class="bi bi-box"></i></span>
                                                <input type="text" name="product_name" class="form-control border-start-0 ps-0" placeholder="Product Name *" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                                <span class="input-group-text border-end-0 bg-white"><i class="bi bi-truck"></i></span>
                                                <select name="supplier_id" class="form-select border-start-0 ps-0" required>
                                                    <?php echo $sup_options; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">Taxonomy & Classification</h6>
                                    <div class="row g-3 mb-4">
                                        <div class="col-md-6">
                                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                                <span class="input-group-text border-end-0 bg-white"><i class="bi bi-folder2-open text-primary"></i></span>
                                                <select name="category_id" id="modalCatSelect" class="form-select border-start-0 ps-0" required>
                                                    <?php echo $cat_options; ?>
                                                </select>
                                                <button type="button" class="btn btn-outline-primary px-3 bg-white" id="btnNewCat" title="Add New Category"><i class="bi bi-plus-lg"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                                <span class="input-group-text border-end-0 bg-white"><i class="bi bi-diagram-2 text-info"></i></span>
                                                <select name="subcategory_id" id="modalSubCatSelect" class="form-select border-start-0 ps-0" required>
                                                    <option value="">Select Category First...</option>
                                                </select>
                                                <button type="button" class="btn btn-outline-info px-3 bg-white" id="btnNewSubCat" title="Add New Subcategory"><i class="bi bi-plus-lg text-dark"></i></button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-7">
                                            <textarea name="description" class="form-control shadow-sm rounded-3" style="height: 120px; resize:none;" placeholder="Description / Specs..."></textarea>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="position-relative image-upload-box d-flex flex-column align-items-center justify-content-center w-100 overflow-hidden shadow-sm">
                                                <input type="file" name="product_image" id="modalProductImageInput" class="position-absolute w-100 h-100 top-0 start-0" accept=".jpg,.jpeg,.png,.webp" style="z-index: 2; opacity: 0;">
                                                <div id="modalUploadPlaceholder" class="text-center p-2" style="z-index: 1;">
                                                    <i class="bi bi-cloud-arrow-up-fill text-primary opacity-50" style="font-size: 2rem;"></i>
                                                    <h6 class="fw-bold mt-1 mb-0 text-dark opacity-50">Upload Image</h6>
                                                </div>
                                                <img id="modalImagePreview" src="" class="d-none position-absolute w-100 h-100 top-0 start-0 preview-img" style="z-index: 3;">
                                                <button type="button" id="modalRemoveImageBtn" class="d-none position-absolute top-0 end-0 m-2 btn btn-sm btn-danger rounded-circle shadow remove-img-btn p-1" style="width: 28px; height: 28px;"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card shadow-sm border-0 rounded-4 h-100 pricing-card">
                                <div class="card-body p-4">
                                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">Stock & Pricing</h6>
                                    
                                    <label class="form-label fw-bold text-dark small mb-1">Opening Stock <span class="text-danger">*</span></label>
                                    <input type="number" name="qty" class="form-control fw-bold mb-3 shadow-sm" value="1" min="1" required onclick="this.select();">

                                    <label class="form-label fw-bold text-dark small mb-1">Base Price <span class="text-danger">*</span></label>
                                    <div class="input-group shadow-sm overflow-hidden border border-success border-opacity-50 mb-3 rounded-3">
                                        <span class="input-group-text bg-success text-white border-0 fw-bold px-3">₹</span>
                                        <input type="number" step="0.01" name="selling_price" class="form-control border-0 text-success fw-bolder text-end" placeholder="0.00" required onclick="this.select();">
                                    </div>

                                    <label class="form-label fw-bold text-dark small mb-1">GST Rate (%) <span class="text-danger">*</span></label>
                                    <select name="gst_rate" class="form-select border-secondary shadow-sm rounded-3" required>
                                        <option value="0.00">0% (Exempt)</option>
                                        <option value="5.00">5% GST</option>
                                        <option value="12.00">12% GST</option>
                                        <option value="18.00">18% GST</option>
                                        <option value="28.00">28% GST</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-top-0 bg-white rounded-bottom-4">
                    <button type="submit" class="btn btn-dark fw-bold px-5 rounded-pill shadow-sm">Save & Add to Cart</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="innerCatModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white border-0 py-2">
                <h6 class="modal-title fw-bold">New Category</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <input type="text" id="innerCatName" class="form-control shadow-sm" placeholder="e.g. Electronics">
            </div>
            <div class="modal-footer border-0 p-3 pt-0">
                <button type="button" class="btn btn-primary btn-sm w-100 fw-bold rounded-pill" id="saveInnerCat">Save Category</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="innerSubCatModal" tabindex="-1" style="z-index: 1060;">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-info text-dark border-0 py-2">
                <h6 class="modal-title fw-bold">New Subcategory</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <select id="innerSubCatParent" class="form-select shadow-sm mb-2">
                    <?php echo $cat_options; ?>
                </select>
                <input type="text" id="innerSubCatName" class="form-control shadow-sm" placeholder="e.g. Mobile Phones">
            </div>
            <div class="modal-footer border-0 p-3 pt-0">
                <button type="button" class="btn btn-info btn-sm w-100 fw-bold text-dark rounded-pill" id="saveInnerSubCat">Save Subcategory</button>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    
    // ==========================================
    // 🎨 CUSTOMER FORMATTERS
    // ==========================================
    function formatCustomerResult (c) {
        if (!c.id) return c.text;
        var el = $(c.element);
        var cid = el.data('cid') ? `<span class="badge bg-secondary bg-opacity-10 text-dark border me-1">${el.data('cid')}</span>` : '';
        var phone = el.data('phone') ? `<i class="bi bi-telephone text-primary me-1"></i>${el.data('phone')}` : '';
        return $(`<div class="d-flex flex-column lh-sm"><span class="fw-bold text-dark fs-6">${c.text}</span><span class="small text-muted mt-1">${cid} ${phone}</span></div>`);
    }

    function formatCustomerSelection (c) {
        if (!c.id) return c.text;
        var el = $(c.element);
        return $(`<span class="hover-profile-link" data-name="${c.text}">${c.text} <i class="bi bi-info-circle text-primary ms-1"></i></span>`);
    }

    // ==========================================
    // 🎨 ADVANCED PRODUCT FORMATTERS
    // ==========================================
    // This creates the rich UI inside the dropdown list (Expands wide)
    function formatProductResult (p) {
        if (!p.id) return p.text; 
        
        var el = $(p.element);
        var stock = parseFloat(el.data('stock')) || 0;
        var price = parseFloat(el.data('price')) || 0;
        var desc = el.data('desc') || '';
        
        var stockBadgeClass = stock > 10 ? 'bg-success' : (stock > 0 ? 'bg-warning text-dark' : 'bg-danger');

        // Layout is completely horizontal to stretch the dropdown width
        return $(`
            <div class="d-flex align-items-center justify-content-between py-1 border-bottom border-light pe-3" style="min-width: max-content;">
                <div class="d-flex align-items-center gap-3 me-5">
                    <span class="fw-bold text-dark" style="font-size: 0.95rem;">${p.text}</span>
                    <span class="text-muted" style="font-size: 0.85rem;">${desc}</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="badge ${stockBadgeClass} rounded-pill border shadow-sm" style="font-size: 0.75rem;">Stock: ${stock}</span>
                    <span class="fw-bolder text-primary" style="font-size: 0.95rem;">₹${price.toFixed(2)}</span>
                </div>
            </div>
        `);
    }

    // This shows what happens inside the input box AFTER selecting (Wraps cleanly)
    function formatProductSelection (p) {
        if (!p.id) return p.text;
        var el = $(p.element);
        var stock = parseFloat(el.data('stock')) || 0;
        
        return $(`<div style="white-space: normal; word-wrap: break-word; line-height: 1.25;"><span class="fw-bold">${p.text}</span> <small class="text-muted ms-1 fst-italic">(Stock: ${stock})</small></div>`);
    }

    // ==========================================
    // 🚀 INITIALIZE SELECT2 WITH NEW FORMATTERS
    // ==========================================
    function initSelect2() {
        $('.customer-select').select2({ 
            templateResult: formatCustomerResult, 
            templateSelection: formatCustomerSelection, 
            width: '100%' 
        });
        
        $('.product-select').select2({ 
            templateResult: formatProductResult,     
            templateSelection: formatProductSelection, 
            width: '100%',
            dropdownAutoWidth: true, // 🔥 Allow dropdown to expand horizontally
            dropdownCssClass: 'product-dropdown-wide' 
        });
    }
    initSelect2();

    // ==========================================
    // 🖱️ HOVER / CLICK PROFILE LOGIC
    // ==========================================
    let hoverTimer;
    $(document).on('mouseenter', '.hover-profile-link', function() {
        let name = $(this).data('name');
        hoverTimer = setTimeout(function() {
            $('#infoModal').modal('show');
            $('#infoModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
            $.post('modules/ajax_get_customer.php', { name: name }, function(res) { $('#infoModalBody').html(res); });
        }, 800); 
    }).on('mouseleave', '.hover-profile-link', function() {
        clearTimeout(hoverTimer);
    });

    $(document).on('click', '.hover-profile-link', function(e) {
        e.preventDefault();
        let name = $(this).data('name');
        $('#infoModal').modal('show');
        $('#infoModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $.post('modules/ajax_get_customer.php', { name: name }, function(res) { $('#infoModalBody').html(res); });
    });

    // ==========================================
    // 📍 DYNAMIC ADDRESS DISPLAY & UPDATE LOGIC
    // ==========================================
    $('#mainCustomerSelect').on('change', function() {
        let el = $(this).find('option:selected');
        if(!el.val()) {
            $('#customerDetailsBox').addClass('d-none');
            return;
        }

        let data = el.data('json');
        if(data) {
            let bill = `${data.village || ''} ${data.po ? ', PO: '+data.po : ''}<br>${data.dist ? 'Dist: '+data.dist : ''} ${data.pin ? ' - '+data.pin : ''}`;
            if(bill.trim() === '' || bill.trim() === ', PO:<br>') bill = '<span class="text-danger fst-italic">Missing Data</span>';
            
            let ship_vill = data.shipping_village || data.village || '';
            let ship_po = data.shipping_po || data.po || '';
            let ship_dist = data.shipping_dist || data.dist || '';
            let ship_pin = data.shipping_pin || data.pin || '';
            
            let ship = `${ship_vill} ${ship_po ? ', PO: '+ship_po : ''}<br>${ship_dist ? 'Dist: '+ship_dist : ''} ${ship_pin ? ' - '+ship_pin : ''}`;
            if(ship.trim() === '' || ship.trim() === ', PO:<br>') ship = '<span class="text-danger fst-italic">Missing Data</span>';

            $('#displayBilling').html(bill);
            $('#displayShipping').html(ship);
            $('#customerDetailsBox').removeClass('d-none');

            $('#updateAddressBtn').data('json', data);
            
            if(bill.includes('Missing') || ship.includes('Missing')) {
                $('#updateAddressBtn').removeClass('btn-outline-primary').addClass('btn-danger').html('<i class="bi bi-exclamation-circle me-1"></i> Add Address Data');
            } else {
                $('#updateAddressBtn').removeClass('btn-danger').addClass('btn-outline-primary').html('<i class="bi bi-pencil-square me-1"></i> Update Details');
            }
        }
    });

    // OPEN UPDATE MODAL PRE-FILLED
    $('#updateAddressBtn').click(function() {
        let data = $(this).data('json');
        
        $('#quickCustomerModal .modal-title').html('<i class="bi bi-pencil-square me-2"></i>Update Customer Details');
        $('#modal_update_id').val(data.id);
        
        $('#modal_name').val(data.name);
        $('#modal_contact').val(data.contact_no);
        $('#modal_vill').val(data.village);
        $('#modal_po').val(data.po);
        $('#modal_dist').val(data.dist);
        $('#modal_pin').val(data.pin);
        $('#modal_state').val(data.state);
        
        $('#ship_vill').val(data.shipping_village);
        $('#ship_po').val(data.shipping_po);
        $('#ship_dist').val(data.shipping_dist);
        $('#ship_pin').val(data.shipping_pin);
        $('#ship_state').val(data.shipping_state);
        
        $('#modal_bank').val(data.bank_name);
        $('#modal_branch').val(data.branch_name);
        $('#modal_acc').val(data.account_no);
        $('#modal_ifsc').val(data.ifsc_code);

        $('#sameAsBilling').prop('checked', false);

        $('#quickCustomerModal').modal('show');
    });

    // SAME AS BILLING CHECKBOX
    $('#sameAsBilling').change(function() {
        if(this.checked) {
            $('#ship_vill').val($('#modal_vill').val());
            $('#ship_po').val($('#modal_po').val());
            $('#ship_dist').val($('#modal_dist').val());
            $('#ship_pin').val($('#modal_pin').val());
            $('#ship_state').val($('#modal_state').val());
        } else {
            $('#ship_vill, #ship_po, #ship_dist, #ship_pin, #ship_state').val('');
        }
    });

    // AJAX Form Submission - CUSTOMERS
    $('#ajaxCustomerForm').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: 'modules/ajax_quick_add.php?type=customer', 
            type: 'POST', 
            data: new FormData(this),
            contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.status == 'success') {
                    if(res.action === 'update') {
                        location.reload(); 
                    } else {
                        let newOption = new Option(res.name, res.id, true, true);
                        $(newOption).attr('data-json', JSON.stringify({
                            id: res.id, name: res.name, contact_no: res.phone,
                            village: res.village, shipping_village: res.shipping_village
                        }));
                        $('#mainCustomerSelect').append(newOption).trigger('change');
                        $('#quickCustomerModal').modal('hide');
                        $('#ajaxCustomerForm')[0].reset();
                    }
                } else alert("Error: " + res.message);
                btn.prop('disabled', false).html('Save & Select');
            }
        });
    });

    // --- MATH ENGINE & CART LOGIC ---
    const productOptionsTemplate = <?php echo json_encode($prod_options); ?>;

    $('#addRow').click(function() {
        let row = `<tr>
            <td class="ps-3 py-2">
                <select name="product_id[]" class="form-select product-select" required>
                    ${productOptionsTemplate}
                </select>
                <input type="text" name="item_note[]" class="form-control form-control-sm mt-1 border-0 bg-light text-secondary shadow-none" style="font-size:0.8rem;" placeholder="Serial No. / Description Note...">
            </td>
            <td class="py-2"><input type="number" name="qty[]" class="form-control form-control-sm sale-qty text-center fw-bold rounded-3 px-1 shadow-sm border-secondary border-opacity-25" value="1" min="1" required onclick="this.select();"></td>
            <td class="py-2"><input type="number" step="0.01" name="price[]" class="calc-text form-control-sm price-input text-end text-dark fw-bold w-100 px-1" required placeholder="0.00" onclick="this.select();"></td>
            <td class="py-2"><input type="text" name="gst_rate[]" class="calc-text form-control-sm readonly-text gst-input text-center text-info w-100 px-1" readonly placeholder="0%"></td>
            <td class="py-2"><input type="text" name="tax_amount[]" class="calc-text form-control-sm readonly-text tax-amount text-end text-info w-100 px-1" readonly placeholder="0.00"></td>
            <td class="py-2 pe-3 col-highlight"><input type="text" class="calc-text form-control-sm readonly-text row-total text-end text-dark w-100 fs-6 px-1" readonly placeholder="0.00"></td>
            <td class="text-center py-2"><button type="button" class="btn btn-sm btn-light text-danger remove-row rounded-circle shadow-sm border border-danger border-opacity-25 p-1" style="width:28px; height:28px;"><i class="bi bi-trash"></i></button></td>
        </tr>`;
        
        $('#itemRows').append(row);
        initSelect2();
    });

    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); calculateGrandTotal(); });

    $(document).on('change', '.product-select', function() {
        let row = $(this).closest('tr');
        let selectedOption = $(this).find('option:selected');
        
        if($(this).val() !== "") {
            let price = parseFloat(selectedOption.data('price') || 0).toFixed(2);
            let stock = selectedOption.data('stock') || 0;
            let gst = parseFloat(selectedOption.data('gst') || 0).toFixed(1);
            let desc = selectedOption.data('desc') || ""; 
            
            row.find('.price-input').val(price);
            row.find('.sale-qty').attr('max', stock); 
            row.find('.gst-input').val(gst + '%');
            row.find('input[name="item_note[]"]').val(desc); 
            calculateGrandTotal();
        } else {
            row.find('.price-input').val('');
            row.find('.gst-input').val('');
            row.find('.tax-amount').val('');
            row.find('.row-total').val('');
            row.find('input[name="item_note[]"]').val(''); 
            calculateGrandTotal();
        }
    });

    $(document).on('input', '.sale-qty, .price-input, #discountInput, #paidAmount', function() { calculateGrandTotal(); });

    $(document).on('change', '.discount-type', function() {
        if($(this).val() === 'percent') {
            $('#discount_symbol').text('-%');
        } else {
            $('#discount_symbol').text('-₹');
        }
        calculateGrandTotal();
    });

    $('#paymentStatus').change(function() {
        let status = $(this).val();
        let grand = parseFloat($('#finalTotalInput').val()) || 0;

        if (status === 'Unpaid') {
            $('#paidAmountContainer').slideUp();
            $('#bankSelectContainer').slideUp();
            $('#bankSelect').removeAttr('required');
            $('#paidAmount').val('0.00');
        } else if (status === 'Paid') {
            $('#paidAmountContainer').slideUp();
            $('#bankSelectContainer').slideDown();
            $('#bankSelect').attr('required', true);
            $('#paidAmount').val(grand.toFixed(2));
        } else if (status === 'Partial') {
            $('#paidAmountContainer').slideDown();
            $('#bankSelectContainer').slideDown();
            $('#bankSelect').attr('required', true);
            $('#paidAmount').val('').focus();
        }
        calculateGrandTotal();
    });

    function calculateGrandTotal() {
        let subtotal = 0; let totalGst = 0;
        $('#itemRows tr').each(function() {
            let q = parseFloat($(this).find('.sale-qty').val()) || 0;
            let p = parseFloat($(this).find('.price-input').val()) || 0;
            let gstStr = $(this).find('.gst-input').val() || "0";
            let gstRate = parseFloat(gstStr.replace('%', '')) || 0;
            
            let baseTotal = q * p;
            let taxAmt = baseTotal * (gstRate / 100);
            let rowTotal = baseTotal + taxAmt;
            
            if(rowTotal > 0) {
                $(this).find('.tax-amount').val(taxAmt.toFixed(2));
                $(this).find('.row-total').val(rowTotal.toFixed(2));
            } else {
                $(this).find('.tax-amount').val('');
                $(this).find('.row-total').val('');
            }
            subtotal += baseTotal; totalGst += taxAmt;
        });
        
        $('#subTotal').text(subtotal.toFixed(2));
        $('#totalGST').text(totalGst.toFixed(2));
        $('#totalTaxInput').val(totalGst.toFixed(2));
        
        let discountVal = parseFloat($('#discountInput').val()) || 0;
        let discountType = $('input[name="discount_type"]:checked').val();
        let calculatedDiscount = 0;
        
        if(discountType === 'percent') {
            calculatedDiscount = (subtotal + totalGst) * (discountVal / 100);
        } else {
            calculatedDiscount = discountVal;
        }
        
        let grand = subtotal + totalGst - calculatedDiscount;
        if(grand < 0) grand = 0; 
        
        $('#grandTotal').text(grand.toFixed(2));
        $('#finalTotalInput').val(grand.toFixed(2));
        $('#finalDiscountInput').val(calculatedDiscount.toFixed(2)); 
        
        let currentStatus = $('#paymentStatus').val();
        if (currentStatus === 'Paid') { $('#paidAmount').val(grand.toFixed(2)); } 
        else if (currentStatus === 'Unpaid') { $('#paidAmount').val('0.00'); }

        let paid = parseFloat($('#paidAmount').val()) || 0;
        if(paid > grand) { $('#paidAmount').val(grand.toFixed(2)); paid = grand; }
        
        let balance = grand - paid;
        $('#balanceDue').text(balance.toFixed(2));
    }

    // AJAX Form Submission - PRODUCTS
    $('#ajaxProductForm').submit(function(e) {
        e.preventDefault();
        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'modules/ajax_quick_add.php?type=product',
            type: 'POST',
            data: new FormData(this),
            contentType: false, processData: false, dataType: 'json',
            success: function(res) {
                if(res.status == 'success') {
                    let optionHTML = `<option value='${res.id}' data-desc='Quick Added...' data-stock='${res.qty}' data-price='${res.price}' data-gst='${res.gst}'>${res.name}</option>`;
                    $('.product-select').append(optionHTML);
                    
                    let emptyDropdownFound = false;
                    $('.product-select').each(function() {
                        if($(this).val() === '' && !emptyDropdownFound) {
                            $(this).val(res.id).trigger('change');
                            emptyDropdownFound = true;
                        }
                    });

                    if(!emptyDropdownFound) {
                        $('#addRow').click();
                        $('.product-select').last().val(res.id).trigger('change');
                    }

                    $('#quickProductModal').modal('hide');
                    $('#ajaxProductForm')[0].reset();
                    $('#modalRemoveImageBtn').click(); 
                } else alert("Error: " + res.message);
                btn.prop('disabled', false).html('Save & Add to Cart');
            }
        });
    });
});
</script>

