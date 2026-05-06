<?php
include '../includes/db.php';

if(!isset($_POST['id'])) {
    die("<div class='alert alert-danger m-4'>Invalid Request. No product ID provided.</div>");
}

$id = $conn->real_escape_string($_POST['id']);

$sql = "SELECT p.*, c.category_name, sc.subcategory_name, s.name as supplier_name, s.contact_no 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        WHERE p.id = '$id'";
$res = $conn->query($sql);

if($res->num_rows == 0) {
    die("<div class='alert alert-warning m-4'><i class='bi bi-exclamation-triangle me-2'></i> Product not found in the database.</div>");
}

$p = $res->fetch_assoc();

$img_src = (!empty($p['product_image']) && file_exists("../uploads/products/".$p['product_image'])) ? "uploads/products/".$p['product_image'] : "";
if($img_src) {
    $img_html = "<img src='{$img_src}' class='img-fluid rounded-4 shadow-sm w-100' style='object-fit: cover; max-height: 350px;' alt='Product'>";
} else {
    $img_html = "<div class='bg-white rounded-4 shadow-sm d-flex flex-column align-items-center justify-content-center border border-secondary border-opacity-25' style='height: 300px;'><i class='bi bi-box-seam text-muted' style='font-size: 5rem;'></i><p class='text-muted fw-bold mt-2'>No Image Available</p></div>";
}

$qty = (int)$p['qty'];
if($qty == 0) {
    $stock_color = 'danger'; $stock_status = 'Out of Stock';
} elseif($qty < 10) {
    $stock_color = 'warning text-dark'; $stock_status = 'Low Stock';
} else {
    $stock_color = 'success'; $stock_status = 'In Stock';
}

$base_price = (float)$p['selling_price'];
$gst_rate = isset($p['gst_rate']) ? (float)$p['gst_rate'] : 0.00;
$tax_amount = $base_price * ($gst_rate / 100);
$final_price = $base_price + $tax_amount;
$total_inventory_value = $base_price * $qty;

echo "
<div class='row g-0'>
    <!-- LEFT COLUMN: Image -->
    <div class='col-md-5 p-4 bg-light border-end d-flex align-items-center justify-content-center'>
        {$img_html}
    </div>
    
    <!-- RIGHT COLUMN: Details -->
    <div class='col-md-7 p-4 bg-white'>
        
        <div class='mb-4'>
            <span class='badge bg-{$stock_color} mb-2 px-3 py-2 shadow-sm rounded-pill fw-bold'>{$stock_status}: {$qty} Units</span>
            <h3 class='fw-bolder text-dark mb-1'>".htmlspecialchars($p['product_name'])."</h3>
            <p class='text-muted small mb-0'>Added: ".date('d M Y', strtotime($p['created_at']))."</p>
        </div>

        <h6 class='fw-bold border-bottom pb-2 mb-3'><i class='bi bi-tag-fill me-2 text-secondary'></i>Pricing Breakdown</h6>
        
        <div class='row g-3 mb-4'>
            <div class='col-6'>
                <div class='p-3 bg-light rounded-4 border border-secondary border-opacity-25'>
                    <small class='text-muted fw-bold text-uppercase d-block mb-1'>Base Rate</small>
                    <h5 class='text-dark fw-bolder mb-0'>₹".number_format($base_price, 2)."</h5>
                </div>
            </div>
            <div class='col-6'>
                <div class='p-3 bg-light rounded-4 border border-info border-opacity-50'>
                    <small class='text-info fw-bold text-uppercase d-block mb-1'>GST ({$gst_rate}%)</small>
                    <h5 class='text-info fw-bolder mb-0'>+ ₹".number_format($tax_amount, 2)."</h5>
                </div>
            </div>
            <div class='col-12 mt-2'>
                <div class='p-3 bg-success bg-opacity-10 rounded-4 border border-success border-opacity-50 d-flex justify-content-between align-items-center'>
                    <span class='text-success fw-bold text-uppercase'>Final Price (Incl. Tax)</span>
                    <h4 class='text-success fw-bolder mb-0'>₹".number_format($final_price, 2)."</h4>
                </div>
            </div>
        </div>

        <h6 class='fw-bold border-bottom pb-2 mb-3'><i class='bi bi-diagram-3 me-2 text-secondary'></i>Taxonomy & Sourcing</h6>
        <div class='mb-2'>
            <span class='text-muted small fw-bold text-uppercase d-inline-block' style='width: 110px;'>Category:</span>
            <span class='fw-bold text-dark'>".(!empty($p['category_name']) ? htmlspecialchars($p['category_name']) : '-')."</span>
        </div>
        <div class='mb-4'>
            <span class='text-muted small fw-bold text-uppercase d-inline-block' style='width: 110px;'>Supplier:</span>
            <span class='fw-bold text-primary'><i class='bi bi-truck me-1'></i>".(!empty($p['supplier_name']) ? htmlspecialchars($p['supplier_name']) : 'Unknown Vendor')."</span> 
        </div>

        <h6 class='fw-bold border-bottom pb-2 mb-2'><i class='bi bi-card-text me-2 text-secondary'></i>Description / Notes</h6>
        <p class='text-secondary small mb-0' style='line-height: 1.6;'>
            ".(!empty($p['description']) ? nl2br(htmlspecialchars($p['description'])) : '<em>No internal notes or description provided for this product.</em>')."
        </p>

    </div>
</div>
";
?>