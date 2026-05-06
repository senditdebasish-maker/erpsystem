<?php
include '../includes/db.php';

if(!isset($_POST['name'])) die("<div class='alert alert-danger'>No customer specified.</div>");
$name = $conn->real_escape_string($_POST['name']);

// Fetch Customer
$sql = "SELECT * FROM customers WHERE name = '$name' LIMIT 1";
$res = $conn->query($sql);

if($res->num_rows == 0) {
    echo "<div class='text-center py-4'>
            <i class='bi bi-exclamation-circle text-warning fs-1 d-block mb-3'></i>
            <h5 class='fw-bold text-dark'>Profile Not Found</h5>
            <p class='text-muted'>This customer name exists in an invoice, but their full profile was never saved in the Customer Directory.</p>
          </div>";
    exit;
}

$c = $res->fetch_assoc();
$cid = !empty($c['customer_id']) ? $c['customer_id'] : 'CUST-'.str_pad($c['id'], 4, '0', STR_PAD_LEFT);
$addr = (!empty($c['village']) ? $c['village'] : '') . (!empty($c['po']) ? ', PO: '.$c['po'] : '') . (!empty($c['dist']) ? '<br>Dist: '.$c['dist'] : '') . (!empty($c['pin']) ? ' - '.$c['pin'] : '');
if(empty(trim(strip_tags($addr)))) $addr = 'Not Provided';

// Fetch Lifetime Stats
$stats = $conn->query("SELECT COUNT(*) as total_orders, SUM(total_amount) as lifetime_spent, SUM(total_amount - IFNULL(paid_amount,0)) as lifetime_owed FROM invoices WHERE customer_name = '$name'")->fetch_assoc();

echo "
<div class='text-center mb-4'>
    <div class='bg-primary text-white d-inline-block rounded-circle mb-2 d-flex align-items-center justify-content-center mx-auto shadow' style='width: 60px; height: 60px; font-size: 24px;'>
        <i class='bi bi-person'></i>
    </div>
    <h4 class='fw-bold text-dark mb-0'>{$c['name']}</h4>
    <span class='badge bg-light text-dark border'>{$cid}</span>
</div>

<div class='row g-3 mb-4'>
    <div class='col-md-4'>
        <div class='card bg-white border-0 shadow-sm h-100 p-3 text-center border-bottom border-primary border-3'>
            <h6 class='text-muted small text-uppercase fw-bold mb-2'>Total Invoices</h6>
            <h4 class='fw-bold text-primary mb-0'>".($stats['total_orders'] ?? 0)."</h4>
        </div>
    </div>
    <div class='col-md-4'>
        <div class='card bg-white border-0 shadow-sm h-100 p-3 text-center border-bottom border-success border-3'>
            <h6 class='text-muted small text-uppercase fw-bold mb-2'>Lifetime Billed</h6>
            <h5 class='fw-bold text-success mb-0'>₹ ".number_format($stats['lifetime_spent'] ?? 0, 2)."</h5>
        </div>
    </div>
    <div class='col-md-4'>
        <div class='card bg-white border-0 shadow-sm h-100 p-3 text-center border-bottom border-danger border-3'>
            <h6 class='text-muted small text-uppercase fw-bold mb-2'>Total Owed</h6>
            <h5 class='fw-bold text-danger mb-0'>₹ ".number_format($stats['lifetime_owed'] ?? 0, 2)."</h5>
        </div>
    </div>
</div>

<div class='row g-4'>
    <div class='col-md-6'>
        <div class='card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100'>
            <div class='card-header bg-light py-2 border-0'>
                <h6 class='mb-0 fw-bold text-dark'><i class='bi bi-geo-alt-fill me-2 text-danger'></i> Contact Details</h6>
            </div>
            <div class='card-body p-3'>
                <div class='mb-2'>
                    <small class='text-muted fw-bold text-uppercase d-block'>Phone Number</small>
                    <span class='fw-bold text-dark'>{$c['contact_no']}</span>
                </div>
                <div>
                    <small class='text-muted fw-bold text-uppercase d-block'>Address</small>
                    <span class='text-dark'>{$addr}</span>
                </div>
            </div>
        </div>
    </div>
    <div class='col-md-6'>
        <div class='card border border-secondary border-opacity-25 shadow-sm rounded-4 h-100'>
            <div class='card-header bg-light py-2 border-0'>
                <h6 class='mb-0 fw-bold text-dark'><i class='bi bi-bank2 me-2 text-success'></i> Banking Details</h6>
            </div>
            <div class='card-body p-3'>
                ";
                if(!empty($c['bank_name'])) {
                    echo "
                    <div class='mb-2'>
                        <small class='text-muted fw-bold text-uppercase d-block'>Bank & Branch</small>
                        <span class='fw-bold text-dark'>{$c['bank_name']} ({$c['branch_name']})</span>
                    </div>
                    <div>
                        <small class='text-muted fw-bold text-uppercase d-block'>Account & IFSC</small>
                        <span class='text-dark'>A/c: <strong>{$c['account_no']}</strong> <br>IFSC: {$c['ifsc_code']}</span>
                    </div>";
                } else {
                    echo "<div class='text-center py-3 text-muted fst-italic'>No bank details provided.</div>";
                }
echo "      </div>
        </div>
    </div>
</div>
";
?>