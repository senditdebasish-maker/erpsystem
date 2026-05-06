<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

if(!isset($_GET['id'])) {
    die("<div class='container mt-5'><h3>Error: No Invoice ID provided.</h3></div>");
}

$inv_id = $_GET['id'];

// 1. Fetch the main invoice details
$inv_query = $conn->query("SELECT * FROM invoices WHERE id = '$inv_id'");
if($inv_query->num_rows == 0) {
    die("<div class='container mt-5'><h3>Error: Invoice not found.</h3></div>");
}
$invoice = $inv_query->fetch_assoc();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-warning"><i class="bi bi-pencil-square me-2"></i>Edit Invoice #INV-<?php echo str_pad($inv_id, 5, '0', STR_PAD_LEFT); ?></h2>
        <a href="manage_invoices.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to History</a>
    </div>

    <form action="modules/update_invoice.php" method="POST">
        <input type="hidden" name="invoice_id" value="<?php echo $inv_id; ?>">

        <div class="card shadow-sm border-0 mb-4 border-top border-warning border-4">
            <div class="card-body bg-light">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-primary">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Choose Customer...</option>
                            <?php 
                            $custs = $conn->query("SELECT id, name, contact_no FROM customers ORDER BY name ASC");
                            while($c = $custs->fetch_assoc()) {
                                // Pre-select the customer that matches the saved name
                                $selected = ($c['name'] == $invoice['customer_name']) ? 'selected' : '';
                                echo "<option value='{$c['id']}' $selected>{$c['name']} ({$c['contact_no']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold text-success">Deposited Bank Account</label>
                        <select name="bank_id" class="form-select" required>
                            <option value="">Choose Receiving Account...</option>
                            <?php 
                            $banks = $conn->query("SELECT id, bank_name, account_no FROM bank_accounts");
                            while($b = $banks->fetch_assoc()) {
                                // Pre-select the bank
                                $selected = ($b['id'] == $invoice['bank_id']) ? 'selected' : '';
                                echo "<option value='{$b['id']}' $selected>{$b['bank_name']} ({$b['account_no']})</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-dark text-white py-3">
                <h6 class="mb-0 fw-bold"><i class="bi bi-cart3 me-2"></i>Cart Items</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0" id="invoiceTable">
                    <thead class="table-light">
                        <tr>
                            <th width="35%">Product / Item</th>
                            <th width="15%">Stock Availability</th>
                            <th width="15%">Qty Needed</th>
                            <th width="15%">Rate (₹)</th>
                            <th width="15%">Total (₹)</th>
                            <th width="5%" class="text-center">Del</th>
                        </tr>
                    </thead>
                    <tbody id="itemRows">
                        <?php 
                        // Fetch existing items for this invoice
                        $items_query = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = '$inv_id'");
                        while($item = $items_query->fetch_assoc()):
                            $row_total = $item['qty'] * $item['price'];
                        ?>
                        <tr>
                            <td>
                                <select name="product_id[]" class="form-select product-select" required>
                                    <option value="">Select Item...</option>
                                    <?php 
                                    // Fetch all products
                                    $prods = $conn->query("SELECT id, product_name FROM products ORDER BY product_name ASC");
                                    while($p = $prods->fetch_assoc()) {
                                        $selected = ($p['id'] == $item['product_id']) ? 'selected' : '';
                                        echo "<option value='{$p['id']}' $selected>{$p['product_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td><input type="text" class="form-control avail-qty bg-light" value="Pre-loaded" readonly></td>
                            <td><input type="number" name="qty[]" class="form-control sale-qty" value="<?php echo $item['qty']; ?>" min="1" required></td>
                            <td><input type="text" name="price[]" class="form-control price-input bg-light" value="<?php echo $item['price']; ?>" readonly></td>
                            <td><input type="text" class="form-control row-total bg-light fw-bold" value="<?php echo number_format($row_total, 2, '.', ''); ?>" readonly></td>
                            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="p-3 border-top">
                    <button type="button" id="addRow" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm"><i class="bi bi-plus-lg me-1"></i> Add Another Item</button>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-5 offset-md-7">
                <div class="card border-warning border-2 shadow-sm p-4 text-end rounded-4">
                    <h5 class="text-muted mb-2">Updated Grand Total</h5>
                    <h2 class="mb-4 text-warning fw-bold">₹ <span id="grandTotal"><?php echo number_format($invoice['total_amount'], 2, '.', ''); ?></span></h2>
                    <input type="hidden" name="final_total" id="finalTotalInput" value="<?php echo $invoice['total_amount']; ?>">
                    <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow">
                        <i class="bi bi-arrow-repeat me-2"></i> UPDATE INVOICE
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Add new row dynamically
    $('#addRow').click(function() {
        let row = `<tr>
            <td>
                <select name="product_id[]" class="form-select product-select" required>
                    <option value="">Select Item...</option>
                    <?php 
                    $prods = $conn->query("SELECT id, product_name FROM products WHERE qty > 0");
                    while($p = $prods->fetch_assoc()) echo "<option value='{$p['id']}'>{$p['product_name']}</option>";
                    ?>
                </select>
            </td>
            <td><input type="text" class="form-control avail-qty bg-light" readonly></td>
            <td><input type="number" name="qty[]" class="form-control sale-qty" value="1" min="1" required></td>
            <td><input type="text" name="price[]" class="form-control price-input bg-light" readonly></td>
            <td><input type="text" class="form-control row-total bg-light fw-bold" readonly></td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button></td>
        </tr>`;
        $('#itemRows').append(row);
    });

    // Remove row
    $(document).on('click', '.remove-row', function() { $(this).closest('tr').remove(); calculateGrandTotal(); });

    // Fetch Product Details via AJAX when selected
    $(document).on('change', '.product-select', function() {
        let row = $(this).closest('tr');
        let pid = $(this).val();
        
        if(pid !== "") {
            $.ajax({
                url: 'modules/ajax_get_product.php',
                type: 'POST',
                data: { id: pid },
                dataType: 'json',
                success: function(res) {
                    if(res.status === 'success') {
                        row.find('.avail-qty').val(res.qty);
                        row.find('.price-input').val(res.selling_price);
                        // Optional: remove the max constraint here since they are editing and restoring stock makes checking tricky for the UI
                        row.find('.sale-qty').removeAttr('max'); 
                        calculateGrandTotal();
                    } else {
                        alert("Database Error: " + res.message);
                    }
                }
            });
        } else {
            row.find('.avail-qty').val(''); row.find('.price-input').val(''); row.find('.row-total').val('');
            calculateGrandTotal();
        }
    });

    // Recalculate totals on quantity change
    $(document).on('input', '.sale-qty', function() { calculateGrandTotal(); });

    function calculateGrandTotal() {
        let grand = 0;
        $('#itemRows tr').each(function() {
            let q = parseFloat($(this).find('.sale-qty').val()) || 0;
            let p = parseFloat($(this).find('.price-input').val()) || 0;
            let total = q * p;
            
            $(this).find('.row-total').val(total.toFixed(2));
            grand += total;
        });
        $('#grandTotal').text(grand.toFixed(2));
        $('#finalTotalInput').val(grand.toFixed(2));
    }
});
</script>

<?php include 'includes/footer.php'; ?>