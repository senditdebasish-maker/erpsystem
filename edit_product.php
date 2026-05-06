<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// Check if ID is provided
if(!isset($_GET['id'])) {
    echo "<script>window.location.href = 'manage_inventory.php';</script>";
    exit;
}

$id = $conn->real_escape_string($_GET['id']);
$product_query = $conn->query("SELECT * FROM products WHERE id = '$id'");

if($product_query->num_rows == 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger fw-bold shadow-sm'><i class='bi bi-exclamation-triangle-fill me-2'></i> Product not found!</div></div>";
    include 'includes/footer.php';
    exit;
}

$p = $product_query->fetch_assoc();

// Fetch Subcategories into a JavaScript array for dynamic dropdowns
$subcats = [];
$res = $conn->query("SELECT * FROM subcategories");
if ($res) {
    while($r = $res->fetch_assoc()) { $subcats[] = $r; }
}
?>

<style>
    /* Next-Gen UI Styling */
    body { background-color: #f4f7f6; }
    .form-control, .form-select { 
        border: 1px solid #dce1e6; 
        padding: 0.75rem 1rem;
        background-color: #fdfdfd;
        transition: all 0.25s ease-in-out;
    }
    .form-control:focus, .form-select:focus { 
        border-color: #0d6efd; 
        background-color: #ffffff;
        box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1) !important; 
    }
    .input-group-text { border: 1px solid #dce1e6; background-color: #f8f9fa; color: #6c757d; }
    .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .card-hover:hover { transform: translateY(-3px); box-shadow: 0 .75rem 1.5rem rgba(0,0,0,.08)!important; }
    .form-text { font-size: 0.75rem; font-weight: 500; letter-spacing: 0.2px; }
    .pricing-card { background: linear-gradient(145deg, #ffffff 0%, #f4fbf7 100%); border: 1px solid #e1eee6; }
    
    /* Custom Stylish Image Upload Box */
    .image-upload-box {
        border: 2px dashed #cbd5e1;
        background-color: #f8fafc;
        border-radius: 1rem;
        height: 200px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .image-upload-box:hover { border-color: #0d6efd; background-color: #eff6ff; }
    .image-upload-box input[type="file"] { opacity: 0; cursor: pointer; }
    .image-upload-box .preview-img { object-fit: cover; border-radius: 0.8rem; }
    .remove-img-btn { z-index: 10; backdrop-filter: blur(4px); }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-warning border-opacity-25">
        <div>
            <span class="badge bg-warning bg-opacity-10 text-warning mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide"><i class="bi bi-pencil-fill me-1"></i> Edit Mode</span>
            <h2 class="fw-bolder mb-0 text-dark">Update Product</h2>
            <p class="text-muted small mb-0 mt-1">Modify details, replace imagery, or update stock and tax pricing.</p>
        </div>
        <a href="manage_inventory.php" class="btn btn-white border shadow-sm fw-bold text-secondary px-4 py-2 rounded-pill card-hover bg-white">
            <i class="bi bi-arrow-left me-2 text-primary"></i> Back to Inventory
        </a>
    </div>

    <!-- ALERTS -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-success border-4 py-3 rounded-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i> Product updated successfully!
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-danger border-4 py-3 rounded-3">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i> Error updating product: <?php echo htmlspecialchars($_GET['details']); ?>
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- MAIN FORM -->
    <form action="modules/process_edit_product.php" method="POST" enctype="multipart/form-data">
        
        <!-- HIDDEN FIELDS -->
        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
        <input type="hidden" name="old_image" value="<?php echo $p['product_image']; ?>">
        
        <div class="row g-4">
            <!-- LEFT COLUMN: Product Details -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 mb-4 card-hover">
                    <div class="card-header bg-white py-4 border-bottom-0">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-square-fill text-primary me-2 opacity-75"></i>Product Identification</h5>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Product Name <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0"><i class="bi bi-box"></i></span>
                                    <input type="text" name="product_name" class="form-control border-start-0 ps-0" value="<?php echo htmlspecialchars($p['product_name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Sourcing Vendor <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0"><i class="bi bi-truck"></i></span>
                                    <select name="supplier_id" class="form-select border-start-0 ps-0" required>
                                        <option value="">-- Select primary supplier --</option>
                                        <?php 
                                        $sups = $conn->query("SELECT id, name FROM suppliers ORDER BY name ASC");
                                        if($sups) {
                                            while($s = $sups->fetch_assoc()) {
                                                $selected = ($s['id'] == $p['supplier_id']) ? "selected" : "";
                                                echo "<option value='{$s['id']}' {$selected}>{$s['name']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr class="border-secondary opacity-10 my-4">

                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill text-secondary me-2 opacity-75"></i>Taxonomy & Classification</h6>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Master Category <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0 bg-white"><i class="bi bi-folder2-open text-primary"></i></span>
                                    <select name="category_id" id="catSelect" class="form-select border-start-0 ps-0" required>
                                        <option value="">Choose Category...</option>
                                        <?php 
                                        $cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
                                        if($cats) {
                                            while($c = $cats->fetch_assoc()) {
                                                $selected = ($c['id'] == $p['category_id']) ? "selected" : "";
                                                echo "<option value='{$c['id']}' {$selected}>{$c['category_name']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Subcategory <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0 bg-white"><i class="bi bi-diagram-2 text-info"></i></span>
                                    <select name="subcategory_id" id="subcatSelect" class="form-select border-start-0 ps-0" required>
                                        <option value="">Select Category First...</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark mb-1">Internal Notes / Description</label>
                                <textarea name="description" class="form-control shadow-sm rounded-3 h-100" style="min-height: 140px;"><?php echo htmlspecialchars($p['description']); ?></textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-image text-primary me-2"></i>Product Media</label>
                                
                                <!-- STYLISH IMAGE UPLOAD BOX -->
                                <div class="position-relative image-upload-box d-flex flex-column align-items-center justify-content-center w-100 overflow-hidden shadow-sm">
                                    
                                    <input type="file" name="product_image" id="productImageInput" class="position-absolute w-100 h-100 top-0 start-0" accept=".jpg,.jpeg,.png,.webp" style="z-index: 2;">
                                    
                                    <div id="uploadPlaceholder" class="text-center p-3" style="z-index: 1;">
                                        <i class="bi bi-cloud-arrow-up-fill text-primary opacity-75" style="font-size: 3rem;"></i>
                                        <h6 class="fw-bold mt-2 mb-1 text-dark">Replace Image</h6>
                                        <p class="small text-muted mb-0">JPG, PNG, WEBP</p>
                                    </div>

                                    <img id="imagePreview" src="" alt="Product Preview" class="d-none position-absolute w-100 h-100 top-0 start-0 preview-img" style="z-index: 3;">
                                    
                                    <button type="button" id="removeImageBtn" class="d-none position-absolute top-0 end-0 m-2 btn btn-sm btn-danger rounded-circle shadow remove-img-btn p-1" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="form-text text-muted mt-2">Leave blank to keep the current image.</div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Stock & Pricing -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 pricing-card card-hover sticky-top" style="top: 20px; z-index: 1;">
                    <div class="card-header bg-transparent py-4 border-bottom border-warning border-opacity-25">
                        <h5 class="mb-0 fw-bolder text-dark"><i class="bi bi-wallet2 text-warning me-2"></i>Stock & Pricing</h5>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">Current Stock Quantity <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text border-end-0 bg-white"><i class="bi bi-boxes text-secondary"></i></span>
                                <input type="number" name="qty" class="form-control border-start-0 ps-0 fw-bold" value="<?php echo $p['qty']; ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">Default Base Price <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border border-success border-opacity-50">
                                <span class="input-group-text bg-success text-white border-0 fw-bold fs-5 px-3">₹</span>
                                <input type="number" step="0.01" name="selling_price" class="form-control border-0 text-success fw-bolder fs-4 text-end" value="<?php echo $p['selling_price']; ?>" required>
                            </div>
                        </div>

                        <!-- NEW GST RATE SELECTION -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">GST Rate (%) <span class="text-danger">*</span></label>
                            <select name="gst_rate" class="form-select form-select-lg border-secondary shadow-sm rounded-3" required>
                                <?php 
                                $current_gst = isset($p['gst_rate']) ? (float)$p['gst_rate'] : 0.00;
                                $gst_options = [0.00, 5.00, 12.00, 18.00, 28.00];
                                
                                foreach($gst_options as $rate) {
                                    $selected = ($current_gst == $rate) ? "selected" : "";
                                    $label = ($rate == 0) ? "0% (Tax Exempt / Nil Rated)" : "{$rate}% GST";
                                    echo "<option value='{$rate}' {$selected}>{$label}</option>";
                                }
                                ?>
                            </select>
                            <div class="form-text text-muted mt-2"><i class="bi bi-percent me-1"></i>Tax rate applied during POS billing.</div>
                        </div>

                        <hr class="border-secondary opacity-10 my-4">

                        <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow rounded-pill d-flex align-items-center justify-content-center py-3 text-dark">
                            <i class="bi bi-save2-fill fs-5 me-2"></i> UPDATE PRODUCT
                        </button>
                        
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // --- PRE-LOAD EXISTING IMAGE LOGIC ---
    <?php if(!empty($p['product_image']) && file_exists("uploads/products/".$p['product_image'])): ?>
        $('#imagePreview').attr('src', 'uploads/products/<?php echo $p['product_image']; ?>').removeClass('d-none');
        $('#uploadPlaceholder').addClass('d-none');
        $('#removeImageBtn').removeClass('d-none');
        $('#productImageInput').css('z-index', '0');
    <?php endif; ?>

    // --- NEW IMAGE PREVIEW LOGIC ---
    $('#productImageInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result).removeClass('d-none');
                $('#uploadPlaceholder').addClass('d-none');
                $('#removeImageBtn').removeClass('d-none');
                $('#productImageInput').css('z-index', '0'); 
            }
            reader.readAsDataURL(file);
        }
    });

    $('#removeImageBtn').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); 
        $('#productImageInput').val('').css('z-index', '2'); 
        $('#imagePreview').attr('src', '').addClass('d-none');
        $('#uploadPlaceholder').removeClass('d-none');
        $(this).addClass('d-none');
    });

    // --- CATEGORY DYNAMIC PRE-LOAD LOGIC ---
    const subcategories = <?php echo json_encode($subcats); ?>;
    const existingSubCatId = "<?php echo $p['subcategory_id']; ?>";

    $('#catSelect').on('change', function() {
        let catId = $(this).val();
        let subSelect = $('#subcatSelect');
        subSelect.html('<option value="">Select Subcategory...</option>');
        
        subcategories.forEach(function(sub) {
            if(sub.category_id == catId) {
                let selected = (sub.id == existingSubCatId) ? "selected" : "";
                subSelect.append(`<option value="${sub.id}" ${selected}>${sub.subcategory_name}</option>`);
            }
        });
    });

    // Trigger change immediately to load existing subcategories
    $('#catSelect').trigger('change');
});
</script>

<?php include 'includes/footer.php'; ?>