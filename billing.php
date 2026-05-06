<?php include 'includes/db.php'; include 'includes/header.php'; ?>
<h2 class="fw-bold mb-4">Create Invoice</h2>
<form action="modules/process_invoice.php" method="POST">
    <div class="card p-3 mb-3">
        <div class="row">
            <div class="col-md-6"><input type="text" name="customer" class="form-control" placeholder="Customer Name" required></div>
            <div class="col-md-6">
                <select name="bank_id" class="form-select">
                    <?php $b = $conn->query("SELECT * FROM bank_accounts"); while($r = $b->fetch_assoc()) echo "<option value='{$r['id']}'>{$r['bank_name']}</option>"; ?>
                </select>
            </div>
        </div>
    </div>
    <table class="table card shadow-sm" id="billTable">
        <thead class="table-dark"><tr><th>Barcode</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead>
        <tbody id="items">
            <tr>
                <td><input type="text" name="barcode[]" class="form-control bc"> <input type="hidden" name="product_id[]" class="pid"></td>
                <td><input type="number" name="qty[]" class="form-control qt" value="1"></td>
                <td><input type="text" name="price[]" class="form-control pr" readonly></td>
                <td><span class="row-total">0.00</span></td>
            </tr>
        </tbody>
    </table>
    <button type="button" id="addRow" class="btn btn-outline-primary btn-sm mb-3">+ Add Item Row</button>
    
    <div class="text-end">
        <h3>Grand Total: ₹ <span id="grand">0.00</span></h3>
        <input type="hidden" name="total" id="total_input">
        <button type="submit" class="btn btn-success btn-lg px-5">FINALIZE BILL</button>
    </div>
</form>

<script>
$('#addRow').click(function() {
    $('#items').append(`<tr><td><input type="text" name="barcode[]" class="form-control bc"> <input type="hidden" name="product_id[]" class="pid"></td><td><input type="number" name="qty[]" class="form-control qt" value="1"></td><td><input type="text" name="price[]" class="form-control pr" readonly></td><td><span class="row-total">0.00</span></td></tr>`);
});
$(document).on('change', '.bc', function() {
    let row = $(this).closest('tr');
    $.post('modules/ajax_fetch_product.php', {barcode: $(this).val()}, function(data) {
        let res = JSON.parse(data);
        if(res.status == 'success') { row.find('.pr').val(res.price); row.find('.pid').val(res.id); calc(); }
    });
});
$(document).on('input', '.qt', calc);
function calc() {
    let g = 0;
    $('#items tr').each(function() {
        let q = $(this).find('.qt').val() || 0;
        let p = $(this).find('.pr').val() || 0;
        let t = q * p;
        $(this).find('.row-total').text(t.toFixed(2));
        g += t;
    });
    $('#grand').text(g.toFixed(2)); $('#total_input').val(g);
}
</script>
<?php include 'includes/footer.php'; ?>