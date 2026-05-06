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
        }

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