<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 
?>

<style>
    body { background-color: #f4f7f6; }
    .filter-input { border: 1px solid #dce1e6; box-shadow: none !important; }
    .filter-input:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1) !important; }
    .product-thumb { width: 45px; height: 45px; object-fit: cover; border-radius: 8px; border: 1px solid #dee2e6; }
    .product-thumb-placeholder { width: 45px; height: 45px; border-radius: 8px; background-color: #f8f9fa; border: 1px dashed #ced4da; display: flex; align-items: center; justify-content: center; color: #adb5bd; }
    .view-product { cursor: pointer; text-decoration: none; }
    .view-product:hover { text-decoration: underline; color: #0d6efd !important; }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide">Inventory Module (GST Enabled)</span>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-boxes text-primary me-2"></i>Master Inventory</h2>
            <p class="text-muted small mb-0 mt-1">Track stock levels, monitor pricing, and manage your product catalog.</p>
        </div>
        <a href="inventory.php" class="btn btn-primary fw-bold shadow-sm px-4 py-2 rounded-pill">
            <i class="bi bi-plus-lg me-2"></i> Add New Product
        </a>
    </div>

    <!-- 4-COLUMN KPI DASHBOARD -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Products</h6>
                <h3 class="fw-bolder text-dark mb-0" id="kpiTotalProducts"><div class="spinner-border spinner-border-sm text-primary"></div></h3>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Total Stock Value (Base)</h6>
                <h3 class="fw-bolder text-success mb-0">₹ <span id="kpiStockValue"><div class="spinner-border spinner-border-sm text-success"></div></span></h3>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-warning border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Low Stock Alerts</h6>
                <h3 class="fw-bolder text-warning mb-0" id="kpiLowStock"><div class="spinner-border spinner-border-sm text-warning"></div></h3>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-danger border-4 h-100 rounded-4">
                <h6 class="text-muted mb-1 small text-uppercase fw-bold">Out of Stock</h6>
                <h3 class="fw-bolder text-danger mb-0" id="kpiOutOfStock"><div class="spinner-border spinner-border-sm text-danger"></div></h3>
            </div>
        </div>
    </div>

    <!-- ADVANCED FILTER BAR -->
    <div class="card shadow-sm border-0 mb-4 bg-white rounded-4">
        <div class="card-body p-4">
            <div class="row g-3 align-items-end mb-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Category</label>
                    <select id="filterCategory" class="form-select filter-input rounded-3 shadow-sm">
                        <option value="">All Categories</option>
                        <?php 
                        $cats = $conn->query("SELECT id, category_name FROM categories ORDER BY category_name ASC");
                        while($c = $cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['category_name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Supplier</label>
                    <select id="filterSupplier" class="form-select filter-input rounded-3 shadow-sm">
                        <option value="">All Suppliers</option>
                        <?php 
                        $sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
                        while($s = $sups->fetch_assoc()) echo "<option value='{$s['id']}'>{$s['name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Stock Status</label>
                    <select id="filterStock" class="form-select filter-input rounded-3 shadow-sm">
                        <option value="">All Items</option>
                        <option value="in_stock">In Stock (Adequate)</option>
                        <option value="low_stock">Low Stock (< 10 units)</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Sort By</label>
                    <select id="filterSort" class="form-select filter-input rounded-3 shadow-sm">
                        <option value="newest">Newest Added</option>
                        <option value="price_high">Price: High to Low</option>
                        <option value="price_low">Price: Low to High</option>
                        <option value="name_asc">Name: A to Z</option>
                    </select>
                </div>
            </div>
            
            <div class="row g-3 align-items-end">
                <div class="col-md-10">
                    <div class="input-group shadow-sm rounded-3 overflow-hidden">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="searchBox" class="form-control border-start-0 filter-input ps-0" placeholder="Search by Product Name, Description, or Supplier...">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="button" id="resetFilters" class="btn btn-dark w-100 fw-bold shadow-sm rounded-3">Reset Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN PRODUCT TABLE -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3 border-0">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Product Catalog</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4" width="60px">Image</th>
                            <th>Product Details</th>
                            <th>Category & Supplier</th>
                            <th class="text-center">In Stock</th>
                            <!-- NEW GST COLUMN -->
                            <th class="text-center">GST %</th>
                            <th class="text-end">Base Price</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="productTableBody">
                        <!-- AJAX loads here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- VIEW PRODUCT INFO MODAL -->
<div class="modal fade" id="infoModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 overflow-hidden border-0">
            <div class="modal-header bg-white border-bottom py-3">
                <h5 class="modal-title fw-bolder text-dark" id="infoModalTitle">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light" id="infoModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    function fetchProducts() {
        let formData = {
            category_id: $('#filterCategory').val(),
            supplier_id: $('#filterSupplier').val(),
            stock_status: $('#filterStock').val(),
            sort_by: $('#filterSort').val(),
            search: $('#searchBox').val()
        };

        $('#productTableBody').css('opacity', '0.5');

        $.ajax({
            url: 'modules/ajax_filter_products.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    $('#productTableBody').html(res.html).css('opacity', '1');
                    $('#kpiTotalProducts').text(res.total_products);
                    $('#kpiStockValue').text(res.stock_value);
                    $('#kpiLowStock').text(res.low_stock);
                    $('#kpiOutOfStock').text(res.out_of_stock);
                }
            }
        });
    }

    fetchProducts();
    $('.filter-input').on('input change', function() { fetchProducts(); });

    $('#resetFilters').click(function() {
        $('.filter-input').val('');
        $('#filterSort').val('newest');
        fetchProducts(); 
    });

    $(document).on('click', '.view-product', function(e) {
        e.preventDefault();
        let id = $(this).data('id');
        $('#infoModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>');
        $('#infoModal').modal('show');
        $.post('modules/ajax_get_product.php', { id: id }, function(res) { $('#infoModalBody').html(res); });
    });
});
</script>
<?php include 'includes/footer.php'; ?>