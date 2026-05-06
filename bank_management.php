<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// Handle deletion of a bank account (Only if balance is 0 to prevent accounting errors)
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $check = $conn->query("SELECT balance FROM bank_accounts WHERE id = '$id'")->fetch_assoc();
    if($check['balance'] == 0) {
        $conn->query("DELETE FROM bank_accounts WHERE id = '$id'");
        $success = "Bank account removed.";
    } else {
        $error = "Cannot delete an account that still has a balance!";
    }
}
?>

<div class="container-fluid">
    <h2 class="fw-bold mb-4">Company Bank Accounts</h2>

    <?php if(isset($success)) echo "<div class='alert alert-success'>$success</div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-plus-circle me-2"></i>Add New Bank</h5>
                </div>
                <div class="card-body">
                    <form action="modules/add_bank.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g. State Bank of India" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" name="account_no" class="form-control" placeholder="XXXXXXXXX123" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label">IFSC Code</label>
                                <input type="text" name="ifsc" class="form-control" placeholder="SBIN000XXXX">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Branch Name</label>
                                <input type="text" name="branch" class="form-control">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Opening Balance (₹)</label>
                            <input type="number" name="balance" step="0.01" class="form-control" value="0.00" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">SAVE ACCOUNT</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-success"><i class="bi bi-wallet2 me-2"></i>Active Accounts</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bank Details</th>
                                    <th>Account No.</th>
                                    <th>Current Balance</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $res = $conn->query("SELECT * FROM bank_accounts ORDER BY id DESC");
                                while($row = $res->fetch_assoc()): 
                                ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold"><?php echo $row['bank_name']; ?></span><br>
                                        <small class="text-muted">IFSC: <?php echo $row['ifsc_code'] ?: 'N/A'; ?></small>
                                    </td>
                                    <td>
                                        <code><?php echo $row['account_no'] ?: 'Not Provided'; ?></code><br>
                                        <small class="text-muted"><?php echo $row['branch_name']; ?></small>
                                    </td>
                                    <td>
                                        <h5 class="text-success fw-bold mb-0">₹ <?php echo number_format($row['balance'], 2); ?></h5>
                                    </td>
                                    <td class="text-center">
                                        <a href="bank_management.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this bank account?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>