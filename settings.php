<?php
include 'includes/db.php';

// ==========================================
// 🛠️ AUTO-HEAL: Create Settings Table
// ==========================================
$check_table = $conn->query("SHOW TABLES LIKE 'company_settings'");
if($check_table->num_rows == 0) {
    $conn->query("CREATE TABLE company_settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT
    )");
    // Insert Default Values
    $conn->query("INSERT INTO company_settings (setting_key, setting_value) VALUES 
        ('company_name', 'Your Company Name Ltd.'),
        ('company_address', '123 Business Road, Market City, State - 100001'),
        ('company_phone', '+91 9876543210'),
        ('company_email', 'billing@yourcompany.com'),
        ('company_gstin', '22AAAAA0000A1Z5'),
        ('terms_conditions', '1. Goods once sold will not be taken back.\n2. Interest @ 18% p.a. will be charged if payment is delayed.\n3. Subject to local jurisdiction.')");
}
// ==========================================

// Handle Form Submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach($_POST as $key => $value) {
        $safe_val = $conn->real_escape_string($value);
        $conn->query("UPDATE company_settings SET setting_value = '$safe_val' WHERE setting_key = '$key'");
    }
    header("Location: settings.php?msg=success");
    exit;
}

// Fetch Current Settings
$settings = [];
$res = $conn->query("SELECT * FROM company_settings");
while($row = $res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

include 'includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-gear-fill text-primary me-2"></i>Company Settings</h2>
            <p class="text-muted small">Update your official billing details, GSTIN, and terms printed on invoices.</p>
        </div>
    </div>

    <?php if(isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
        <div class="alert alert-success fw-bold shadow-sm border-0 border-start border-success border-4"><i class="bi bi-check-circle me-2"></i>Settings updated successfully!</div>
    <?php endif; ?>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4 bg-light">
            <form action="" method="POST">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Company / Business Name</label>
                        <input type="text" name="company_name" class="form-control" value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Official GSTIN</label>
                        <input type="text" name="company_gstin" class="form-control text-uppercase" value="<?php echo htmlspecialchars($settings['company_gstin'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Contact Phone Number</label>
                        <input type="text" name="company_phone" class="form-control" value="<?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Support / Billing Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?php echo htmlspecialchars($settings['company_email'] ?? ''); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Full Registered Address</label>
                        <textarea name="company_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['company_address'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Invoice Terms & Conditions (Prints at bottom of bill)</label>
                        <textarea name="terms_conditions" class="form-control" rows="4"><?php echo htmlspecialchars($settings['terms_conditions'] ?? ''); ?></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary fw-bold px-5 py-2 rounded-pill shadow-sm"><i class="bi bi-save2 me-2"></i> Save Settings</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>