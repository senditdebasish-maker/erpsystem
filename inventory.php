<?php 
include 'includes/db.php'; 

// ==========================================
// 🛠️ AUTO-HEAL DATABASE 
// Automatically adds GST column to products if missing
// ==========================================
$check_gst = $conn->query("SHOW COLUMNS FROM products LIKE 'gst_rate'");
if($check_gst && $check_gst->num_rows == 0) {
    $conn->query("ALTER TABLE products ADD gst_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER selling_price");
}
// ==========================================

include 'includes/header.php'; 

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
    .image-upload-box:hover {
        border-color: #0d6efd;
        background-color: #eff6ff;
    }
    .image-upload-box input[type="file"] {
        opacity: 0;
        cursor: pointer;
    }
    .image-upload-box .preview-img {
        object-fit: cover;
        border-radius: 0.8rem;
    }
    .remove-img-btn {
        z-index: 10;
        backdrop-filter: blur(4px);
    }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide">Inventory Module</span>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-box-seam text-primary me-2"></i>Register New Product</h2>
            <p class="text-muted small mb-0 mt-1">Add items, upload images, and set base pricing and taxes.</p>
        </div>
        <a href="manage_inventory.php" class="btn btn-white border shadow-sm fw-bold text-secondary px-4 py-2 rounded-pill card-hover bg-white">
            <i class="bi bi-table me-2 text-primary"></i> View Master Inventory
        </a>
    </div>

    <!-- SUCCESS / ERROR ALERTS -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-success border-4 py-3 rounded-3">
            <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i> Product successfully cataloged in inventory!
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'error'): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-danger border-4 py-3 rounded-3">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-danger"></i> Error saving product: <?php echo htmlspecialchars($_GET['details']); ?>
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- MAIN FORM -->
    <form action="modules/process_inventory.php" method="POST" enctype="multipart/form-data">
        
        <div class="row g-4">
            <!-- LEFT COLUMN: Product & Category Details -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 rounded-4 mb-4 card-hover">
                    <div class="card-header bg-white py-4 border-bottom-0">
                        <h5 class="mb-0 fw-bold text-dark"><i class="bi bi-info-square-fill text-primary me-2 opacity-75"></i>Product Identification</h5>
                        <p class="small text-muted mb-0 mt-1">Core details used for searching and billing.</p>
                    </div>
                    <div class="card-body px-4 pb-4 pt-0">
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Product Name <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0"><i class="bi bi-box"></i></span>
                                    <input type="text" name="product_name" class="form-control border-start-0 ps-0" placeholder="e.g. Samsung Galaxy S23 Ultra" required>
                                </div>
                                <div class="form-text text-muted mt-1"><i class="bi bi-lightbulb me-1 text-warning"></i>Include brand and model for clarity.</div>
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
                                            while($s = $sups->fetch_assoc()) echo "<option value='{$s['id']}'>{$s['name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-text text-muted mt-1"><i class="bi bi-link-45deg me-1"></i>Links this product to vendor analytics.</div>
                            </div>
                        </div>

                        <hr class="border-secondary opacity-10 my-4">

                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill text-secondary me-2 opacity-75"></i>Taxonomy & Classification</h6>
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Master Category <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0 bg-white"><i class="bi bi-folder2-open text-primary"></i></span>
                                    <select name="category_id" id="catSelect" class="form-select border-start-0 ps-0 border-end-0" required>
                                        <option value="">Choose Category...</option>
                                        <?php 
                                        $cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
                                        if($cats) {
                                            while($c = $cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['category_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary border border-start-0 fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addCatModal" title="Create New Category"><i class="bi bi-plus-lg"></i></button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark mb-1">Subcategory <span class="text-danger">*</span></label>
                                <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                    <span class="input-group-text border-end-0 bg-white"><i class="bi bi-diagram-2 text-info"></i></span>
                                    <select name="subcategory_id" id="subcatSelect" class="form-select border-start-0 ps-0 border-end-0" required>
                                        <option value="">Select Category First...</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-info border border-start-0 fw-bold px-3" data-bs-toggle="modal" data-bs-target="#addSubCatModal" title="Create New Subcategory"><i class="bi bi-plus-lg text-dark"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark mb-1">Internal Notes / Description</label>
                                <textarea name="description" class="form-control shadow-sm rounded-3 h-100" style="min-height: 140px;" placeholder="Enter specifications, internal notes, or warranty details..."></textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark mb-1"><i class="bi bi-image text-primary me-2"></i>Product Media</label>
                                
                                <!-- STYLISH IMAGE UPLOAD BOX -->
                                <div class="position-relative image-upload-box d-flex flex-column align-items-center justify-content-center w-100 overflow-hidden shadow-sm">
                                    
                                    <!-- The actual file input covering the box -->
                                    <input type="file" name="product_image" id="productImageInput" class="position-absolute w-100 h-100 top-0 start-0" accept=".jpg,.jpeg,.png,.webp" style="z-index: 2;">
                                    
                                    <!-- Placeholder UI (Visible when empty) -->
                                    <div id="uploadPlaceholder" class="text-center p-3" style="z-index: 1;">
                                        <i class="bi bi-cloud-arrow-up-fill text-primary opacity-75" style="font-size: 3rem;"></i>
                                        <h6 class="fw-bold mt-2 mb-1 text-dark">Click or Drag Image</h6>
                                        <p class="small text-muted mb-0">JPG, PNG, WEBP</p>
                                    </div>

                                    <!-- Image Preview (Hidden by default) -->
                                    <img id="imagePreview" src="" alt="Product Preview" class="d-none position-absolute w-100 h-100 top-0 start-0 preview-img" style="z-index: 3;">
                                    
                                    <!-- Remove Button (Hidden by default) -->
                                    <button type="button" id="removeImageBtn" class="d-none position-absolute top-0 end-0 m-2 btn btn-sm btn-danger rounded-circle shadow remove-img-btn p-1" style="width: 28px; height: 28px;">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Stock & Pricing -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 rounded-4 pricing-card card-hover sticky-top" style="top: 20px; z-index: 1;">
                    <div class="card-header bg-transparent py-4 border-bottom border-success border-opacity-25">
                        <h5 class="mb-0 fw-bolder text-dark"><i class="bi bi-wallet2 text-success me-2"></i>Stock & Pricing</h5>
                        <p class="small text-muted mb-0 mt-1">Define initial values.</p>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">Opening Stock Quantity <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden">
                                <span class="input-group-text border-end-0 bg-white"><i class="bi bi-boxes text-secondary"></i></span>
                                <!-- SELECT ON CLICK ADDED -->
                                <input type="number" name="qty" class="form-control border-start-0 ps-0 fw-bold" placeholder="0" min="0" value="0" required onclick="this.select();">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">Default Base Price <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border border-success border-opacity-50">
                                <span class="input-group-text bg-success text-white border-0 fw-bold fs-5 px-3">₹</span>
                                <!-- SELECT ON CLICK ADDED -->
                                <input type="number" step="0.01" name="selling_price" class="form-control border-0 text-success fw-bolder fs-4 text-end" value="0.00" required onclick="this.select();">
                            </div>
                        </div>

                        <!-- MANUAL + PRESET GST RATE SELECTION -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark mb-1">GST Rate (%) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg shadow-sm rounded-3 overflow-hidden border border-secondary border-opacity-50">
                                <!-- DATALIST FOR HYBRID DROPDOWN/MANUAL ENTRY -->
                                <input type="number" step="0.01" name="gst_rate" list="gst_presets" class="form-control border-0 text-dark fw-bold text-center" placeholder="e.g. 18" value="0" required onclick="this.select();">
                                <span class="input-group-text bg-light text-secondary border-0 fw-bold fs-6 px-3">%</span>
                            </div>
                            <!-- PRESETS -->
                            <datalist id="gst_presets">
                                <option value="0"></option>
                                <option value="5"></option>
                                <option value="12"></option>
                                <option value="18"></option>
                                <option value="28"></option>
                            </datalist>
                            <div class="form-text text-muted mt-2"><i class="bi bi-keyboard me-1"></i>Select a preset or type a custom tax rate manually.</div>
                        </div>

                        <hr class="border-secondary opacity-10 my-4">

                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold shadow rounded-pill d-flex align-items-center justify-content-center py-3">
                            <i class="bi bi-cloud-arrow-up-fill fs-5 me-2"></i> SUBMIT TO INVENTORY
                        </button>
                        <p class="text-center text-muted small mt-3 mb-0"><i class="bi bi-shield-check me-1"></i>Data is securely encrypted.</p>
                        
                    </div>
                </div>
            </div>
        </div>

    </form>
</div>

<!-- CATEGORY MODAL -->
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 overflow-hidden border-0">
            <form id="catForm">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i>Create Master Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <label class="form-label fw-bold text-dark small text-uppercase">New Category Name</label>
                    <input type="text" name="category_name" id="newCatName" class="form-control form-control-lg shadow-sm rounded-3" placeholder="e.g. Electronics, Furniture" required>
                </div>
                <div class="modal-footer bg-white border-top-0 py-3">
                    <button type="button" class="btn btn-light fw-bold border shadow-sm rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4 shadow-sm rounded-pill"><i class="bi bi-check2-circle me-1"></i> Save Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SUBCATEGORY MODAL -->
<div class="modal fade" id="addSubCatModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 overflow-hidden border-0">
            <form id="subCatForm">
                <div class="modal-header bg-info text-dark border-0 py-3">
                    <h5 class="modal-title fw-bolder"><i class="bi bi-diagram-3 me-2"></i>Create Subcategory</h5>
                    <button type="button" class="btn-close text-dark" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark small text-uppercase">Parent Category</label>
                        <select name="category_id" id="parentCatId" class="form-select form-select-lg shadow-sm rounded-3" required>
                            <option value="">Select Parent Link...</option>
                            <?php 
                            $cats = $conn->query("SELECT * FROM categories ORDER BY category_name ASC");
                            if($cats) {
                                while($c = $cats->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['category_name']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label fw-bold text-dark small text-uppercase">New Subcategory Name</label>
                        <input type="text" name="subcategory_name" id="newSubCatName" class="form-control form-control-lg shadow-sm rounded-3" placeholder="e.g. Mobile Phones, Chairs" required>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top-0 py-3">
                    <button type="button" class="btn btn-light fw-bold border shadow-sm rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info fw-bolder px-4 shadow-sm rounded-pill text-dark"><i class="bi bi-check2-circle me-1"></i> Save Subcategory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// --- IMAGE PREVIEW LOGIC ---
$(document).ready(function() {
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
});


// --- CATEGORY DYNAMIC LOGIC ---
const subcategories = <?php echo json_encode($subcats); ?>;

$('#catSelect').on('change', function() {
    let catId = $(this).val();
    let subSelect = $('#subcatSelect');
    subSelect.html('<option value="">Select Subcategory...</option>');
    
    subcategories.forEach(function(sub) {
        if(sub.category_id == catId) {
            subSelect.append(`<option value="${sub.id}">${sub.subcategory_name}</option>`);
        }
    });
});

// --- AJAX FOR CATEGORY MODAL ---
$('#catForm').on('submit', function(e) {
    e.preventDefault(); 
    let submitBtn = $(this).find('button[type="submit"]');
    submitBtn.html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop('disabled', true);
    
    let catName = $('#newCatName').val();
    
    $.ajax({
        url: 'modules/ajax_add_category.php',
        type: 'POST',
        data: { category_name: catName },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                $('#catSelect').append(`<option value="${res.id}" selected>${res.name}</option>`);
                $('#parentCatId').append(`<option value="${res.id}">${res.name}</option>`);
                $('#catSelect').trigger('change'); 
                
                $('#addCatModal').modal('hide');
                $('#catForm')[0].reset();
            } else {
                alert("Error saving category!");
            }
            submitBtn.html('<i class="bi bi-check2-circle me-1"></i> Save Category').prop('disabled', false);
        }
    });
});

// --- AJAX FOR SUBCATEGORY MODAL ---
$('#subCatForm').on('submit', function(e) {
    e.preventDefault(); 
    let submitBtn = $(this).find('button[type="submit"]');
    submitBtn.html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop('disabled', true);
    
    let parentCatId = $('#parentCatId').val();
    let subCatName = $('#newSubCatName').val();
    
    $.ajax({
        url: 'modules/ajax_add_subcategory.php',
        type: 'POST',
        data: { category_id: parentCatId, subcategory_name: subCatName },
        dataType: 'json',
        success: function(res) {
            if(res.status === 'success') {
                subcategories.push({id: res.id, category_id: res.cat_id, subcategory_name: res.name});
                
                if($('#catSelect').val() == res.cat_id) {
                    $('#subcatSelect').append(`<option value="${res.id}" selected>${res.name}</option>`);
                }
                
                $('#addSubCatModal').modal('hide');
                $('#subCatForm')[0].reset();
            } else {
                alert("Error saving subcategory!");
            }
            submitBtn.html('<i class="bi bi-check2-circle me-1"></i> Save Subcategory').prop('disabled', false);
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>