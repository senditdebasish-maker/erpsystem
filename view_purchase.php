<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

$pid = $_GET['id'];
$p_res = $conn->query("SELECT p.*, s.name as supplier_name, b.bank_name FROM purchases p JOIN suppliers s ON p.supplier_id = s.id JOIN bank_accounts b ON p.bank_id = b.id WHERE p.id = '$pid'")->fetch_assoc();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Purchase Details: <?php echo $p_res['purchase_no']; ?></h2>
        <a href="manage_purchases.php" class="btn btn-outline-dark"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h6 class="text-muted fw-bold small text-uppercase mb-3">Order Information</h6>
                    <p class="mb-1"><strong>Supplier:</strong> <?php echo $p_res['supplier_name']; ?></p>
                    <p class="mb-1"><strong>Date:</strong> <?php echo date('d-M-Y H:i', strtotime($p_res['created_at'])); ?></p>
                    <p class="mb-1"><strong>Paid Via:</strong> <?php echo $p_res['bank_name']; ?></p>
                    <hr>
                    <h4 class="text-danger fw-bold">Total: ₹ <?php echo number_format($p_res['total_amount'], 2); ?></h4>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3"><h6 class="mb-0 fw-bold">Itemized List</h6></div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Unit Cost</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $items = $conn->query("SELECT pi.*, p.product_name FROM purchase_items pi JOIN products p ON pi.product_id = p.id WHERE pi.purchase_id = '$pid'");
                            while($i = $items->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $i['product_name']; ?></td>
                                <td class="text-center"><?php echo $i['qty']; ?></td>
                                <td class="text-end">₹ <?php echo number_format($i['cost_price'], 2); ?></td>
                                <td class="text-end fw-bold">₹ <?php echo number_format($i['total_price'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>