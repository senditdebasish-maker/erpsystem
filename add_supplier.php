<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// --- HANDLER: DELETE SUPPLIER ---
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Delete physical file safely
    $file_query = $conn->query("SELECT passbook_file FROM suppliers WHERE id = '$id'");
    if($file_query->num_rows > 0) {
        $file = $file_query->fetch_assoc()['passbook_file'];
        if(!empty($file) && file_exists("uploads/".$file)) {
            unlink("uploads/".$file);
        }
    }
    
    $conn->query("DELETE FROM suppliers WHERE id = '$id'");
    header("Location: add_supplier.php?msg=deleted");
    exit;
}

// --- HANDLER: ADD SUPPLIER ---
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supplier'])) {
    $supplier_id = $conn->real_escape_string($_POST['supplier_id']);
    $name = $conn->real_escape_string($_POST['name']); 
    $contact = $conn->real_escape_string($_POST['contact']);
    $village = $conn->real_escape_string($_POST['village']); 
    $po = $conn->real_escape_string($_POST['po']);
    $dist = $conn->real_escape_string($_POST['dist']); 
    $pin = $conn->real_escape_string($_POST['pin']); 
    $state = $conn->real_escape_string($_POST['state']);
    
    $bank_name = $conn->real_escape_string($_POST['bank_name']); 
    $branch = $conn->real_escape_string($_POST['branch']);
    $account = $conn->real_escape_string($_POST['account']); 
    $ifsc = $conn->real_escape_string($_POST['ifsc']);
    
    // File Upload Logic
    $passbook_file = "";
    if(isset($_FILES['passbook']) && $_FILES['passbook']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); } 
        $ext = pathinfo($_FILES['passbook']['name'], PATHINFO_EXTENSION);
        $passbook_file = "supp_passbook_" . time() . "." . $ext;
        move_uploaded_file($_FILES['passbook']['tmp_name'], "uploads/" . $passbook_file);
    }

    $sql = "INSERT INTO suppliers (supplier_id, name, contact_no, village, po, dist, pin, state, bank_name, branch_name, account_no, ifsc_code, passbook_file) 
            VALUES ('$supplier_id', '$name', '$contact', '$village', '$po', '$dist', '$pin', '$state', '$bank_name', '$branch', '$account', '$ifsc', '$passbook_file')";
    
    if($conn->query($sql)) {
        header("Location: add_supplier.php?msg=success");
        exit;
    } else {
        $error = "Database Error: " . $conn->error;
    }
}

// --- HANDLER: EDIT SUPPLIER ---
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_supplier'])) {
    $id = $conn->real_escape_string($_POST['edit_id']);
    $name = $conn->real_escape_string($_POST['name']); 
    $contact = $conn->real_escape_string($_POST['contact']);
    $village = $conn->real_escape_string($_POST['village']); 
    $po = $conn->real_escape_string($_POST['po']);
    $dist = $conn->real_escape_string($_POST['dist']); 
    $pin = $conn->real_escape_string($_POST['pin']); 
    $state = $conn->real_escape_string($_POST['state']);
    $bank_name = $conn->real_escape_string($_POST['bank_name']); 
    $branch = $conn->real_escape_string($_POST['branch']);
    $account = $conn->real_escape_string($_POST['account']); 
    $ifsc = $conn->real_escape_string($_POST['ifsc']);

    // Check if new file uploaded
    $file_update_sql = "";
    if(isset($_FILES['passbook']) && $_FILES['passbook']['error'] == 0) {
        if (!file_exists('uploads')) { mkdir('uploads', 0777, true); } 
        
        // Remove old file
        $old_file_q = $conn->query("SELECT passbook_file FROM suppliers WHERE id = '$id'");
        if($old_file_q->num_rows > 0) {
            $old_file = $old_file_q->fetch_assoc()['passbook_file'];
            if(!empty($old_file) && file_exists("uploads/".$old_file)) { unlink("uploads/".$old_file); }
        }

        $ext = pathinfo($_FILES['passbook']['name'], PATHINFO_EXTENSION);
        $passbook_file = "supp_passbook_" . time() . "." . $ext;
        move_uploaded_file($_FILES['passbook']['tmp_name'], "uploads/" . $passbook_file);
        $file_update_sql = ", passbook_file = '$passbook_file'";
    }

    $sql = "UPDATE suppliers SET name='$name', contact_no='$contact', village='$village', po='$po', dist='$dist', pin='$pin', state='$state', 
            bank_name='$bank_name', branch_name='$branch', account_no='$account', ifsc_code='$ifsc' $file_update_sql WHERE id='$id'";
    
    if($conn->query($sql)) {
        header("Location: add_supplier.php?msg=updated");
        exit;
    } else {
        $error = "Database Error: " . $conn->error;
    }
}

// --- AUTO GENERATE SUPPLIER ID ---
$last_id_res = $conn->query("SELECT id FROM suppliers ORDER BY id DESC LIMIT 1");
$next_id = ($last_id_res->num_rows > 0) ? $last_id_res->fetch_assoc()['id'] + 1 : 1;
$auto_supp_id = "SUPP-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);
?>

<div class="container-fluid mb-5 mt-3">
    
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide text-uppercase">Directory</span>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-truck text-primary me-2"></i>Vendor & Supplier Management</h2>
        </div>
    </div>
    
    <!-- ALERTS -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-success border-4 py-3">
            <i class="bi bi-check-circle-fill me-2 text-success"></i> Supplier profile created successfully!
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-info border-4 py-3">
            <i class="bi bi-info-circle-fill me-2 text-info"></i> Supplier profile updated successfully!
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-danger border-4 py-3">
            <i class="bi bi-trash-fill me-2 text-danger"></i> Supplier profile deleted.
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger fw-bold border-0 shadow-sm'><i class='bi bi-exclamation-triangle-fill me-2'></i> $error</div>"; ?>

    <!-- ADD SUPPLIER FORM -->
    <div class="card shadow-sm border-0 mb-5 rounded-4 bg-light">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- LEFT COLUMN: SUPPLIER DETAILS -->
                    <div class="col-xl-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="fw-bold text-primary mb-0"><i class="bi bi-building me-2"></i>Company Details</h5>
                            </div>
                            <div class="card-body bg-light rounded-bottom-4">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Supplier ID</label>
                                        <input type="text" name="supplier_id" class="form-control bg-white text-primary fw-bold border-secondary" value="<?php echo $auto_supp_id; ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Contact No <span class="text-danger">*</span></label>
                                        <input type="text" name="contact" class="form-control border-secondary" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Supplier / Vendor Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control border-secondary" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold small text-muted">Village / Area</label>
                                        <input type="text" name="village" class="form-control border-secondary">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-bold small text-muted">Post Office (P.O)</label>
                                        <input type="text" name="po" class="form-control border-secondary">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-4">
                                        <label class="form-label fw-bold small text-muted">District</label>
                                        <input type="text" name="dist" class="form-control border-secondary">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fw-bold small text-muted">PIN</label>
                                        <input type="text" name="pin" class="form-control border-secondary">
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fw-bold small text-muted">State</label>
                                        <input type="text" name="state" class="form-control border-secondary">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN: BANK DETAILS -->
                    <div class="col-xl-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="fw-bold text-success mb-0"><i class="bi bi-bank me-2"></i>Banking Information</h5>
                            </div>
                            <div class="card-body bg-light rounded-bottom-4">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Bank Name</label>
                                        <select name="bank_name" class="form-select border-secondary">
                                            <option value="">-- Select Bank --</option>
                                            <option value="SBI">State Bank of India</option>
                                            <option value="HDFC">HDFC Bank</option>
                                            <option value="ICICI">ICICI Bank</option>
                                            <option value="PNB">Punjab National Bank</option>
                                            <option value="Other">Other Bank</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Branch Name</label>
                                        <input type="text" name="branch" class="form-control border-secondary">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Account Number</label>
                                        <input type="text" name="account" class="form-control border-secondary fw-bold text-dark">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">IFSC Code</label>
                                        <input type="text" name="ifsc" class="form-control border-secondary text-uppercase">
                                    </div>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label fw-bold small text-muted">Upload Passbook/Cheque (Image or PDF)</label>
                                    <input type="file" name="passbook" class="form-control border-secondary" accept=".jpg,.jpeg,.png,.pdf">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button type="reset" class="btn btn-light fw-bold shadow-sm border me-2 px-4">CLEAR</button>
                    <button type="submit" name="add_supplier" class="btn btn-primary px-5 fw-bold shadow"><i class="bi bi-truck me-2"></i> SAVE SUPPLIER</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUPPLIER LIST TABLE -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Registered Suppliers</h6>
            <!-- INSTANT SEARCH BAR -->
            <div class="input-group input-group-sm w-25 shadow-none">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="searchSupplier" class="form-control border-start-0 ps-0" placeholder="Search Mobile, Name, Location...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="supplierTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Supp ID</th>
                            <th>Company Name</th>
                            <th>Contact</th>
                            <th width="20%">Location</th>
                            <th width="25%">Bank Info</th>
                            <th class="text-center">Passbook</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="supplierTableBody">
                        <?php 
                        $res = $conn->query("SELECT * FROM suppliers ORDER BY id DESC");
                        if($res->num_rows > 0):
                            while($row = $res->fetch_assoc()): 
                                $sid = !empty($row['supplier_id']) ? $row['supplier_id'] : 'SUPP-'.str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold">
                                <a href="#" class="view-supplier text-primary text-decoration-none" data-id="<?php echo $row['id']; ?>"><?php echo $sid; ?></a>
                            </td>
                            <td class="fw-bold">
                                <a href="#" class="view-supplier text-dark text-decoration-none" data-id="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></a>
                            </td>
                            <td><span class="badge bg-light text-dark border"><i class="bi bi-telephone-fill me-1 text-muted"></i> <?php echo $row['contact_no']; ?></span></td>
                            <td>
                                <div class="small text-secondary">
                                    <?php echo !empty($row['village']) ? $row['village'].', ' : ''; ?>
                                    <?php echo !empty($row['dist']) ? $row['dist'] : 'N/A'; ?>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($row['bank_name'])): ?>
                                    <div class="fw-bold small text-success"><i class="bi bi-bank me-1"></i> <?php echo $row['bank_name']; ?></div>
                                    <div class="small text-muted">A/c: <span class="text-dark fw-bold"><?php echo $row['account_no']; ?></span></div>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Not Provided</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if(!empty($row['passbook_file'])): ?>
                                    <a href="uploads/<?php echo $row['passbook_file']; ?>" target="_blank" class="btn btn-sm btn-outline-info shadow-sm" title="View Document"><i class="bi bi-file-earmark-text"></i></a>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center pe-4">
                                <div class="btn-group shadow-sm">
                                    <!-- EDIT BUTTON WITH DATA ATTRIBUTES -->
                                    <button class="btn btn-sm btn-light text-warning border edit-btn" 
                                        data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                        data-contact="<?php echo $row['contact_no']; ?>" data-village="<?php echo htmlspecialchars($row['village']); ?>" 
                                        data-po="<?php echo htmlspecialchars($row['po']); ?>" data-dist="<?php echo htmlspecialchars($row['dist']); ?>" 
                                        data-pin="<?php echo htmlspecialchars($row['pin']); ?>" data-state="<?php echo htmlspecialchars($row['state']); ?>" 
                                        data-bank="<?php echo htmlspecialchars($row['bank_name']); ?>" data-branch="<?php echo htmlspecialchars($row['branch_name']); ?>" 
                                        data-acc="<?php echo htmlspecialchars($row['account_no']); ?>" data-ifsc="<?php echo htmlspecialchars($row['ifsc_code']); ?>" 
                                        title="Edit Supplier">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="add_supplier.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-light text-danger border" onclick="return confirm('Are you sure you want to delete <?php echo $row['name']; ?>?');" title="Delete"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-truck fs-1 d-block mb-2 opacity-50"></i>
                                No suppliers registered yet.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================
     EDIT SUPPLIER MODAL
=========================================== -->
<div class="modal fade" id="editSupplierModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-warning py-3 border-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>Edit Supplier Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="edit_id" id="edit_id">
                    
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Company Details</h6>
                    <div class="row mb-3 g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Supplier / Vendor Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Contact No</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" required>
                        </div>
                    </div>
                    <div class="row mb-4 g-3">
                        <div class="col-md-3"><label class="form-label fw-bold small text-muted">Village/Area</label><input type="text" name="village" id="edit_village" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label fw-bold small text-muted">P.O</label><input type="text" name="po" id="edit_po" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label fw-bold small text-muted">District</label><input type="text" name="dist" id="edit_dist" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label fw-bold small text-muted">PIN</label><input type="text" name="pin" id="edit_pin" class="form-control"></div>
                    </div>

                    <h6 class="fw-bold text-success border-bottom pb-2 mb-3">Banking Information</h6>
                    <div class="row mb-3 g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Bank Name</label>
                            <input type="text" name="bank_name" id="edit_bank" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Branch Name</label>
                            <input type="text" name="branch" id="edit_branch" class="form-control">
                        </div>
                    </div>
                    <div class="row mb-3 g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Account Number</label>
                            <input type="text" name="account" id="edit_acc" class="form-control fw-bold text-dark">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">IFSC Code</label>
                            <input type="text" name="ifsc" id="edit_ifsc" class="form-control text-uppercase">
                        </div>
                    </div>
                    <div>
                        <label class="form-label fw-bold small text-muted">Update Passbook Document (Leaves old file if empty)</label>
                        <input type="file" name="passbook" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 py-3">
                    <button type="button" class="btn btn-light border fw-bold px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="edit_supplier" class="btn btn-warning fw-bold px-5 text-dark shadow-sm"><i class="bi bi-save2 me-1"></i> Update Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     VIEW SUPPLIER PROFILE & LEDGER MODAL
=========================================== -->
<div class="modal fade" id="supplierModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 overflow-hidden border-0">
            <div class="modal-header bg-dark text-white py-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Supplier Ledger & Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light" id="supModalBody">
                <!-- AJAX Loads Content Here -->
                <div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading Ledger...</p></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // INSTANT TABLE SEARCH 
    $("#searchSupplier").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#supplierTableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // OPEN EDIT MODAL & FILL DATA
    $('.edit-btn').on('click', function(){
        $('#edit_id').val($(this).data('id'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_contact').val($(this).data('contact'));
        $('#edit_village').val($(this).data('village'));
        $('#edit_po').val($(this).data('po'));
        $('#edit_dist').val($(this).data('dist'));
        $('#edit_pin').val($(this).data('pin'));
        // If state exists in DB (might be undefined if missing)
        $('#edit_bank').val($(this).data('bank'));
        $('#edit_branch').val($(this).data('branch'));
        $('#edit_acc').val($(this).data('acc'));
        $('#edit_ifsc').val($(this).data('ifsc'));

        $('#editSupplierModal').modal('show');
    });

    // OPEN VIEW PROFILE MODAL VIA AJAX
    $('.view-supplier').on('click', function(e){
        e.preventDefault();
        let sid = $(this).data('id');
        
        $('#supModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading Ledger...</p></div>');
        $('#supplierModal').modal('show');
        
        $.post('modules/ajax_get_supplier_info.php', { id: sid }, function(res) { 
            $('#supModalBody').html(res); 
        }).fail(function() {
            $('#supModalBody').html('<div class="alert alert-danger m-4">Failed to load supplier details.</div>');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>