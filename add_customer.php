<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// --- HANDLER: DELETE CUSTOMER ---
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Delete physical file safely
    $file_query = $conn->query("SELECT passbook_file FROM customers WHERE id = '$id'");
    if($file_query->num_rows > 0) {
        $file = $file_query->fetch_assoc()['passbook_file'];
        if(!empty($file) && file_exists("uploads/".$file)) {
            unlink("uploads/".$file);
        }
    }
    
    $conn->query("DELETE FROM customers WHERE id = '$id'");
    header("Location: add_customer.php?msg=deleted");
    exit;
}

// --- HANDLER: ADD CUSTOMER ---
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_customer'])) {
    $customer_id = $conn->real_escape_string($_POST['customer_id']);
    $name = $conn->real_escape_string($_POST['name']); 
    $contact = $conn->real_escape_string($_POST['contact']);
    
    // --- ADDED EMAIL LOGIC ---
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    // -------------------------

    // --- ADDED GSTIN LOGIC ---
    $has_gstin = isset($_POST['has_gstin']) ? $_POST['has_gstin'] : 'No';
    $gstin = ($has_gstin == 'Yes') ? $conn->real_escape_string($_POST['gstin']) : '';
    // -------------------------

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
        $passbook_file = "passbook_" . time() . "." . $ext;
        move_uploaded_file($_FILES['passbook']['tmp_name'], "uploads/" . $passbook_file);
    }

    // --- ADDED EMAIL TO INSERT QUERY ---
    $sql = "INSERT INTO customers (customer_id, name, contact_no, email, gstin, village, po, dist, pin, state, bank_name, branch_name, account_no, ifsc_code, passbook_file) 
            VALUES ('$customer_id', '$name', '$contact', '$email', '$gstin', '$village', '$po', '$dist', '$pin', '$state', '$bank_name', '$branch', '$account', '$ifsc', '$passbook_file')";
    
    if($conn->query($sql)) {
        header("Location: add_customer.php?msg=success");
        exit;
    } else {
        $error = "Database Error: " . $conn->error;
    }
}

// --- HANDLER: EDIT CUSTOMER ---
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_customer'])) {
    $id = $conn->real_escape_string($_POST['edit_id']);
    $name = $conn->real_escape_string($_POST['name']); 
    $contact = $conn->real_escape_string($_POST['contact']);
    
    // --- ADDED EMAIL LOGIC ---
    $email = isset($_POST['email']) ? $conn->real_escape_string($_POST['email']) : '';
    // -------------------------

    // --- ADDED GSTIN LOGIC ---
    $has_gstin = isset($_POST['edit_has_gstin']) ? $_POST['edit_has_gstin'] : 'No';
    $gstin = ($has_gstin == 'Yes') ? $conn->real_escape_string($_POST['edit_gstin']) : '';
    // -------------------------

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
        $old_file_q = $conn->query("SELECT passbook_file FROM customers WHERE id = '$id'");
        if($old_file_q->num_rows > 0) {
            $old_file = $old_file_q->fetch_assoc()['passbook_file'];
            if(!empty($old_file) && file_exists("uploads/".$old_file)) { unlink("uploads/".$old_file); }
        }

        $ext = pathinfo($_FILES['passbook']['name'], PATHINFO_EXTENSION);
        $passbook_file = "passbook_" . time() . "." . $ext;
        move_uploaded_file($_FILES['passbook']['tmp_name'], "uploads/" . $passbook_file);
        $file_update_sql = ", passbook_file = '$passbook_file'";
    }

    // --- ADDED EMAIL TO UPDATE QUERY ---
    $sql = "UPDATE customers SET name='$name', contact_no='$contact', email='$email', gstin='$gstin', village='$village', po='$po', dist='$dist', pin='$pin', state='$state', 
            bank_name='$bank_name', branch_name='$branch', account_no='$account', ifsc_code='$ifsc' $file_update_sql WHERE id='$id'";
    
    if($conn->query($sql)) {
        header("Location: add_customer.php?msg=updated");
        exit;
    } else {
        $error = "Database Error: " . $conn->error;
    }
}

// --- AUTO GENERATE CUSTOMER ID ---
$last_id_res = $conn->query("SELECT id FROM customers ORDER BY id DESC LIMIT 1");
$next_id = ($last_id_res->num_rows > 0) ? $last_id_res->fetch_assoc()['id'] + 1 : 1;
$auto_cust_id = "CUST-" . str_pad($next_id, 4, '0', STR_PAD_LEFT);
?>

<div class="container-fluid mb-5 mt-3">
    
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide text-uppercase">Directory</span>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-people-fill text-primary me-2"></i>Customer Management</h2>
        </div>
    </div>
    
    <!-- ALERTS -->
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-success border-4 py-3">
            <i class="bi bi-check-circle-fill me-2 text-success"></i> Customer profile created successfully!
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
        <div class="alert alert-info alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-info border-4 py-3">
            <i class="bi bi-info-circle-fill me-2 text-info"></i> Customer profile updated successfully!
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <div class="alert alert-danger alert-dismissible fade show fw-bold shadow-sm border-0 border-start border-danger border-4 py-3">
            <i class="bi bi-trash-fill me-2 text-danger"></i> Customer profile deleted.
            <button type="button" class="btn-close mt-1" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger fw-bold border-0 shadow-sm'><i class='bi bi-exclamation-triangle-fill me-2'></i> $error</div>"; ?>

    <!-- ADD CUSTOMER FORM -->
    <div class="card shadow-sm border-0 mb-5 rounded-4 bg-light">
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="row g-4">
                    <!-- LEFT COLUMN: PERSONAL DETAILS -->
                    <div class="col-xl-6">
                        <div class="card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white py-3 border-0">
                                <h5 class="fw-bold text-primary mb-0"><i class="bi bi-person-lines-fill me-2"></i>Personal Details</h5>
                            </div>
                            <div class="card-body bg-light rounded-bottom-4">
                                
                                <!-- --- REORDERED: ID & NAME NOW FIRST ROW --- -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Customer ID</label>
                                        <input type="text" name="customer_id" class="form-control bg-white text-primary fw-bold border-secondary" value="<?php echo $auto_cust_id; ?>" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Customer Name <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control border-secondary" required>
                                    </div>
                                </div>

                                <!-- --- REORDERED & ADDED EMAIL: CONTACT NO & EMAIL SECOND ROW --- -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Contact No <span class="text-danger">*</span></label>
                                        <input type="text" name="contact" class="form-control border-secondary" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Email ID <small class="fw-normal">(Optional)</small></label>
                                        <input type="email" name="email" class="form-control border-secondary">
                                    </div>
                                </div>
                                <!-- ----------------------------------------------------------- -->

                                <div class="row mb-3 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small text-muted">Having GSTIN No?</label>
                                        <div class="d-flex mt-1">
                                            <div class="form-check me-3">
                                                <input class="form-check-input" type="radio" name="has_gstin" id="gstin_yes" value="Yes">
                                                <label class="form-check-label fw-bold" for="gstin_yes">Yes</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="has_gstin" id="gstin_no" value="No" checked>
                                                <label class="form-check-label fw-bold" for="gstin_no">No</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-8" id="gstin_box" style="display: none;">
                                        <label class="form-label fw-bold small text-muted">GSTIN Number</label>
                                        <input type="text" name="gstin" class="form-control border-secondary text-uppercase" placeholder="Enter GSTIN" maxlength="15">
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label fw-bold small text-muted">Village</label>
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
                    <button type="submit" name="add_customer" class="btn btn-primary px-5 fw-bold shadow"><i class="bi bi-person-plus-fill me-2"></i> SAVE CUSTOMER</button>
                </div>
            </form>
        </div>
    </div>

    <!-- CUSTOMER LIST TABLE -->
    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-header bg-dark text-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Registered Customers</h6>
            <!-- INSTANT SEARCH BAR -->
            <div class="input-group input-group-sm w-25 shadow-none">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="searchCustomer" class="form-control border-start-0 ps-0" placeholder="Search Mobile, Name, Location...">
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="customerTable">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Cust ID</th>
                            <th>Profile Name</th>
                            <th>GSTIN</th>
                            <th>Contact Info</th>
                            <th width="20%">Location</th>
                            <th width="25%">Bank Info</th>
                            <th class="text-center">Passbook</th>
                            <th class="text-center pe-4">Action</th>
                        </tr>
                    </thead>
                    <tbody id="customerTableBody">
                        <?php 
                        $res = $conn->query("SELECT * FROM customers ORDER BY id DESC");
                        if($res->num_rows > 0):
                            while($row = $res->fetch_assoc()): 
                                $cid = !empty($row['customer_id']) ? $row['customer_id'] : 'CUST-'.str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold">
                                <a href="#" class="view-customer text-primary text-decoration-none" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>"><?php echo $cid; ?></a>
                            </td>
                            <td class="fw-bold">
                                <a href="#" class="view-customer text-dark text-decoration-none" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>"><?php echo $row['name']; ?></a>
                            </td>
                            
                            <td>
                                <?php if(!empty($row['gstin'])): ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary"><?php echo $row['gstin']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">N/A</span>
                                <?php endif; ?>
                            </td>

                            <!-- --- MODIFIED TO SHOW BOTH CONTACT AND EMAIL --- -->
                            <td>
                                <span class="badge bg-light text-dark border d-inline-block mb-1"><i class="bi bi-telephone-fill me-1 text-muted"></i> <?php echo $row['contact_no']; ?></span>
                                <?php if(!empty($row['email'])): ?>
                                    <div class="small text-muted"><i class="bi bi-envelope-fill me-1"></i> <?php echo htmlspecialchars($row['email']); ?></div>
                                <?php endif; ?>
                            </td>
                            <!-- ----------------------------------------------- -->

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
                                    <!-- --- ADDED data-email ATTRIBUTE HERE --- -->
                                    <button class="btn btn-sm btn-light text-warning border edit-btn" 
                                        data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                        data-contact="<?php echo $row['contact_no']; ?>" data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>" 
                                        data-gstin="<?php echo htmlspecialchars($row['gstin'] ?? ''); ?>" data-village="<?php echo htmlspecialchars($row['village']); ?>" 
                                        data-po="<?php echo htmlspecialchars($row['po']); ?>" data-dist="<?php echo htmlspecialchars($row['dist']); ?>" 
                                        data-pin="<?php echo htmlspecialchars($row['pin']); ?>" data-state="<?php echo htmlspecialchars($row['state']); ?>" 
                                        data-bank="<?php echo htmlspecialchars($row['bank_name']); ?>" data-branch="<?php echo htmlspecialchars($row['branch_name']); ?>" 
                                        data-acc="<?php echo htmlspecialchars($row['account_no']); ?>" data-ifsc="<?php echo htmlspecialchars($row['ifsc_code']); ?>" 
                                        title="Edit Customer">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <a href="add_customer.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-light text-danger border" onclick="return confirm('Are you sure you want to delete <?php echo $row['name']; ?>?');" title="Delete"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else:
                        ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-people fs-1 d-block mb-2 opacity-50"></i>
                                No customers registered yet.
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
     EDIT CUSTOMER MODAL
=========================================== -->
<div class="modal fade" id="editCustomerModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header bg-warning py-3 border-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square me-2"></i>Edit Customer Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4 bg-light">
                    <input type="hidden" name="edit_id" id="edit_id">
                    
                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">Personal Details</h6>
                    <div class="row mb-3 g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Customer Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Contact No</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" required>
                        </div>
                    </div>

                    <!-- --- ADDED EMAIL TO EDIT MODAL --- -->
                    <div class="row mb-3 g-3">
                        <div class="col-md-12">
                            <label class="form-label fw-bold small text-muted">Email ID <small class="fw-normal">(Optional)</small></label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                    </div>
                    <!-- --------------------------------- -->

                    <div class="row mb-3 g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Having GSTIN No?</label>
                            <div class="d-flex mt-1">
                                <div class="form-check me-3">
                                    <input class="form-check-input" type="radio" name="edit_has_gstin" id="edit_gstin_yes" value="Yes">
                                    <label class="form-check-label fw-bold" for="edit_gstin_yes">Yes</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="edit_has_gstin" id="edit_gstin_no" value="No" checked>
                                    <label class="form-check-label fw-bold" for="edit_gstin_no">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8" id="edit_gstin_box" style="display: none;">
                            <label class="form-label fw-bold small text-muted">GSTIN Number</label>
                            <input type="text" name="edit_gstin" id="edit_gstin" class="form-control text-uppercase" placeholder="Enter GSTIN" maxlength="15">
                        </div>
                    </div>

                    <div class="row mb-4 g-3">
                        <div class="col-md-3"><label class="form-label fw-bold small text-muted">Village</label><input type="text" name="village" id="edit_village" class="form-control"></div>
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
                    <button type="submit" name="edit_customer" class="btn btn-warning fw-bold px-5 text-dark shadow-sm"><i class="bi bi-save2 me-1"></i> Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     VIEW CUSTOMER PROFILE & LEDGER MODAL
=========================================== -->
<div class="modal fade" id="customerModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4 overflow-hidden border-0">
            <div class="modal-header bg-dark text-white py-3 border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Customer Ledger & Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-light" id="custModalBody">
                <div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading Ledger...</p></div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // INSTANT TABLE SEARCH 
    $("#searchCustomer").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#customerTableBody tr").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $('input[type=radio][name=has_gstin]').change(function() {
        if (this.value == 'Yes') {
            $('#gstin_box').slideDown('fast');
        } else {
            $('#gstin_box').slideUp('fast');
            $('input[name=gstin]').val(''); 
        }
    });

    $('input[type=radio][name=edit_has_gstin]').change(function() {
        if (this.value == 'Yes') {
            $('#edit_gstin_box').slideDown('fast');
        } else {
            $('#edit_gstin_box').slideUp('fast');
            $('#edit_gstin').val(''); 
        }
    });

    // OPEN EDIT MODAL & FILL DATA
    $('.edit-btn').on('click', function(){
        $('#edit_id').val($(this).data('id'));
        $('#edit_name').val($(this).data('name'));
        $('#edit_contact').val($(this).data('contact'));
        
        // --- ADDED EMAIL POPULATION ---
        $('#edit_email').val($(this).data('email'));
        // ------------------------------
        
        $('#edit_village').val($(this).data('village'));
        $('#edit_po').val($(this).data('po'));
        $('#edit_dist').val($(this).data('dist'));
        $('#edit_pin').val($(this).data('pin'));
        $('#edit_bank').val($(this).data('bank'));
        $('#edit_branch').val($(this).data('branch'));
        $('#edit_acc').val($(this).data('acc'));
        $('#edit_ifsc').val($(this).data('ifsc'));

        let gstin_val = $(this).data('gstin');
        if(gstin_val && gstin_val.trim() !== '') {
            $('#edit_gstin_yes').prop('checked', true);
            $('#edit_gstin_box').show();
            $('#edit_gstin').val(gstin_val);
        } else {
            $('#edit_gstin_no').prop('checked', true);
            $('#edit_gstin_box').hide();
            $('#edit_gstin').val('');
        }<?php 
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

        $('#editCustomerModal').modal('show');
    });

    // OPEN VIEW PROFILE MODAL VIA AJAX
    $('.view-customer').on('click', function(e){
        e.preventDefault();
        let cid = $(this).data('id');
        let cname = $(this).data('name');
        
        $('#custModalBody').html('<div class="text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-2 text-muted">Loading Ledger...</p></div>');
        $('#customerModal').modal('show');
        
        $.post('modules/ajax_get_customer_info.php', { id: cid, name: cname }, function(res) { 
            $('#custModalBody').html(res); 
        }).fail(function() {
            $('#custModalBody').html('<div class="alert alert-danger m-4">Failed to load customer details.</div>');
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>