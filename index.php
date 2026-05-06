<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// --- ADVANCED SQL LOGIC: AGGREGATING BUSINESS METRICS ---

// 1. Calculate Total Liquid Revenue (Money actually received)
$rev_query = $conn->query("SELECT SUM(paid_amount) as total_rev FROM invoices");
$total_revenue = $rev_query->fetch_assoc()['total_rev'] ?? 0;

// 2. Calculate Total Money Stuck in Market (Accounts Receivable)
$due_query = $conn->query("SELECT SUM(total_amount - paid_amount) as total_due FROM invoices WHERE payment_status != 'Paid'");
$total_due = $due_query->fetch_assoc()['total_due'] ?? 0;

// 3. Low Stock Warning Logic (Items with 5 or fewer in stock)
$stock_query = $conn->query("SELECT COUNT(*) as low_stock FROM products WHERE qty <= 5");
$low_stock_count = $stock_query->fetch_assoc()['low_stock'] ?? 0;

// 4. Total Active Customers
$cust_query = $conn->query("SELECT COUNT(*) as total_cust FROM customers");
$total_customers = $cust_query->fetch_assoc()['total_cust'] ?? 0;

// --- CHART DATA PREPARATION (Last 7 Days Revenue) ---
$chart_labels = [];
$chart_data = [];
$sales_trend = $conn->query("SELECT DATE(created_at) as date, SUM(total_amount) as daily_total 
                             FROM invoices 
                             GROUP BY DATE(created_at) 
                             ORDER BY DATE(created_at) DESC LIMIT 7");

// We fetch descending to get the latest 7, but we want to display them chronologically (ascending) on the chart
$trend_rows = [];
while($row = $sales_trend->fetch_assoc()) { $trend_rows[] = $row; }
$trend_rows = array_reverse($trend_rows);

foreach($trend_rows as $row) {
    $chart_labels[] = date('d M', strtotime($row['date']));
    $chart_data[] = $row['daily_total'];
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-speedometer2 me-2"></i>Business Dashboard</h2>
            <p class="text-muted">Welcome back. Here is your real-time business overview.</p>
        </div>
        <div>
            <a href="generate_invoice.php" class="btn btn-primary fw-bold shadow-sm me-2"><i class="bi bi-receipt-cutoff me-1"></i> New Bill</a>
            <a href="inventory.php" class="btn btn-dark fw-bold shadow-sm"><i class="bi bi-box-seam me-1"></i> Add Stock</a>
        </div>
    </div>

    <!-- TOP METRICS ROW -->
    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-bottom border-success border-4 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Total Revenue Collected</h6>
                        <h3 class="fw-bold text-success mb-0">₹ <?php echo number_format($total_revenue, 2); ?></h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success fs-3">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-bottom border-danger border-4 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Market Dues (Pending)</h6>
                        <h3 class="fw-bold text-danger mb-0">₹ <?php echo number_format($total_due, 2); ?></h3>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger fs-3">
                        <i class="bi bi-exclamation-octagon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-bottom border-warning border-4 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Low Stock Alerts</h6>
                        <h3 class="fw-bold text-warning mb-0"><?php echo $low_stock_count; ?> Items</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning fs-3">
                        <i class="bi bi-boxes"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-bottom border-primary border-4 rounded-4 h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted small text-uppercase fw-bold mb-1">Active Customers</h6>
                        <h3 class="fw-bold text-primary mb-0"><?php echo $total_customers; ?></h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary fs-3">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MIDDLE ROW: CHART & LOW STOCK -->
    <div class="row g-4 mb-4">
        <!-- Revenue Chart -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Sales Trend (Last 7 Days)</h6>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Smart Low Stock Engine -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-top border-warning border-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-exclamation-triangle text-warning me-2"></i>Critical Stock Levels</h6>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php 
                        $crit_stock = $conn->query("SELECT product_name, qty FROM products WHERE qty <= 5 ORDER BY qty ASC LIMIT 6");
                        if($crit_stock->num_rows > 0):
                            while($item = $crit_stock->fetch_assoc()):
                                $badge_color = $item['qty'] == 0 ? 'bg-danger' : 'bg-warning text-dark';
                                $alert_text = $item['qty'] == 0 ? 'Out of Stock!' : 'Low Stock';
                        ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <h6 class="mb-0 fw-bold text-secondary"><?php echo $item['product_name']; ?></h6>
                                <small class="text-muted"><?php echo $alert_text; ?></small>
                            </div>
                            <span class="badge <?php echo $badge_color; ?> rounded-pill fs-6"><?php echo $item['qty']; ?></span>
                        </li>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                        <li class="list-group-item text-center py-5 text-muted">
                            <i class="bi bi-check-circle text-success fs-1 d-block mb-2"></i>
                            All inventory levels are healthy!
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="card-footer bg-white border-0 text-center py-3">
                    <a href="manage_inventory.php" class="text-decoration-none fw-bold text-primary">Manage Inventory <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- BOTTOM ROW: RECENT TRANSACTIONS -->
    <div class="card border-0 shadow-sm rounded-4 mb-5">
        <div class="card-header bg-dark text-white py-3 rounded-top-4">
            <h6 class="fw-bold mb-0"><i class="bi bi-clock-history me-2"></i>Recent Billing Activity</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recent = $conn->query("SELECT id, customer_name, created_at, total_amount, payment_status FROM invoices ORDER BY id DESC LIMIT 5");
                        while($r = $recent->fetch_assoc()):
                            $inv_pad = str_pad($r['id'], 5, '0', STR_PAD_LEFT);
                            $date = date('d M Y, h:i A', strtotime($r['created_at']));
                            
                            $p_stat = $r['payment_status'] ?? 'Unpaid';
                            if($p_stat == 'Paid') $badge = "<span class='badge bg-success'>Paid</span>";
                            elseif($p_stat == 'Partial') $badge = "<span class='badge bg-warning text-dark'>Partial</span>";
                            else $badge = "<span class='badge bg-danger'>Pending</span>";
                        ?>
                        <tr>
                            <td class="ps-4 fw-bold text-primary">#INV-<?php echo $inv_pad; ?></td>
                            <td class="fw-bold text-dark"><?php echo $r['customer_name']; ?></td>
                            <td class="text-muted"><?php echo $date; ?></td>
                            <td class="fw-bold text-dark">₹ <?php echo number_format($r['total_amount'], 2); ?></td>
                            <td><?php echo $badge; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 text-center py-3 rounded-bottom-4">
            <a href="manage_invoices.php" class="text-decoration-none fw-bold text-dark">View All History <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>

<!-- Chart.js CDN for the Graph -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    // Injecting the PHP array data into Javascript
    const labels = <?php echo json_encode($chart_labels); ?>;
    const dataPoints = <?php echo json_encode($chart_data); ?>;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Daily Sales (₹)',
                data: dataPoints,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                borderWidth: 3,
                pointBackgroundColor: '#0d6efd',
                pointRadius: 4,
                fill: true,
                tension: 0.4 // Makes the line smooth and curvy
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false } // Hides the legend for a cleaner look
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>