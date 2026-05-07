<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    body { background-color: #f4f7f6; }
    .kpi-card { transition: transform 0.2s; border-radius: 0.75rem; }
    .kpi-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1)!important; }
    .kpi-label { font-size: 0.75rem; letter-spacing: 1px; }
    .filter-btn.active { background-color: #0d6efd; color: white; border-color: #0d6efd; }
    .chart-container { position: relative; height: 300px; width: 100%; }
</style>

<div class="container-fluid mb-5 mt-3">
    
    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom border-2 border-primary border-opacity-10">
        <div>
            <span class="badge bg-primary bg-opacity-10 text-primary mb-2 px-3 py-2 rounded-pill fw-bold tracking-wide text-uppercase">Analytics</span>
            <h2 class="fw-bolder mb-0 text-dark"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Sales Performance Report</h2>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-dark fw-bold shadow-sm rounded-pill px-4">
                <i class="bi bi-printer-fill me-2"></i> Print Report
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4 bg-white rounded-4">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-md-5">
                    <div class="btn-group w-100 shadow-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary fw-bold filter-btn" data-range="today">Today</button>
                        <button type="button" class="btn btn-outline-secondary fw-bold filter-btn" data-range="week">7 Days</button>
                        <button type="button" class="btn btn-outline-secondary fw-bold filter-btn active" data-range="month">This Month</button>
                        <button type="button" class="btn btn-outline-secondary fw-bold filter-btn" data-range="year">This Year</button>
                    </div>
                </div>
                <div class="col-md-3">
                    <input type="date" id="dateFrom" class="form-control fw-bold border-secondary text-muted" title="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" id="dateTo" class="form-control fw-bold border-secondary text-muted" title="To Date">
                </div>
                <div class="col-md-1">
                    <button id="customFilterBtn" class="btn btn-dark w-100 fw-bold shadow-sm"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-primary border-4 h-100 kpi-card">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Gross Sales (Total Value)</h6>
                <h3 class="fw-bolder text-dark mb-0">₹<span id="kpiGross">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-success border-4 h-100 kpi-card">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Net Revenue (After Disc)</h6>
                <h3 class="fw-bolder text-success mb-0">₹<span id="kpiNet">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-4">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-info border-4 h-100 kpi-card">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">GST Collected (Liability)</h6>
                <h3 class="fw-bolder text-info mb-0">₹<span id="kpiGst">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-warning border-4 h-100 kpi-card">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Total Discounts Given</h6>
                <h3 class="fw-bolder text-warning mb-0">₹<span id="kpiDiscount">0.00</span></h3>
            </div>
        </div>
        <div class="col-xl col-md-6">
            <div class="card border-0 shadow-sm bg-white p-3 border-start border-danger border-4 h-100 kpi-card">
                <h6 class="text-muted mb-1 kpi-label text-uppercase fw-bold">Outstanding Dues (Pending)</h6>
                <h3 class="fw-bolder text-danger mb-0">₹<span id="kpiPending">0.00</span></h3>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-8">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up text-primary me-2"></i>Revenue Trend</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill text-success me-2"></i>Collection Status</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-7">
            <div class="card shadow-sm border-0 rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-dark text-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-calendar3 me-2"></i>Daily Sales Breakdown</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 400px;">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light sticky-top">
                                <tr class="text-uppercase small text-muted">
                                    <th class="ps-4">Date</th>
                                    <th class="text-center">Invoices</th>
                                    <th class="text-end">Gross Sales</th>
                                    <th class="text-end">Discounts</th>
                                    <th class="text-end pe-4">Net Revenue</th>
                                </tr>
                            </thead>
                            <tbody id="dailyTableBody">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-5">
            <div class="card shadow-sm border-0 rounded-4 h-100 overflow-hidden">
                <div class="card-header bg-primary text-white py-3 border-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-star-fill text-warning me-2"></i>Top Selling Products</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-uppercase small text-muted">
                                    <th class="ps-4">Product Name</th>
                                    <th class="text-center">Qty Sold</th>
                                    <th class="text-end pe-4">Revenue Gen.</th>
                                </tr>
                            </thead>
                            <tbody id="topProductsBody">
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let trendChartInstance = null;
    let statusChartInstance = null;

    // Helper: Set dates based on quick buttons
    function setDateRange(range) {
        let today = new Date();
        let fromDate, toDate = today.toISOString().split('T')[0];

        if (range === 'today') {
            fromDate = toDate;
        } else if (range === 'week') {
            let lastWeek = new Date(today);
            lastWeek.setDate(today.getDate() - 6);
            fromDate = lastWeek.toISOString().split('T')[0];
        } else if (range === 'month') {
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        } else if (range === 'year') {
            fromDate = new Date(today.getFullYear(), 0, 1).toISOString().split('T')[0];
        }

        $('#dateFrom').val(fromDate);
        $('#dateTo').val(toDate);
        fetchReportData();
    }

    // Initialize with "This Month"
    setDateRange('month');

    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        setDateRange($(this).data('range'));
    });

    $('#customFilterBtn').click(function() {
        $('.filter-btn').removeClass('active');
        fetchReportData();
    });

    function fetchReportData() {
        let from = $('#dateFrom').val();
        let to = $('#dateTo').val();

        $.ajax({
            url: 'modules/ajax_sales_report.php',
            type: 'POST',
            data: { date_from: from, date_to: to },
            dataType: 'json',
            success: function(res) {
                // Update KPIs
                $('#kpiGross').text(res.kpis.gross);
                $('#kpiNet').text(res.kpis.net);
                $('#kpiGst').text(res.kpis.gst);
                $('#kpiDiscount').text(res.kpis.discount);
                $('#kpiPending').text(res.kpis.pending);

                // Update Tables
                $('#dailyTableBody').html(res.tables.daily);
                $('#topProductsBody').html(res.tables.products);

                // Update Trend Chart
                if(trendChartInstance) trendChartInstance.destroy();
                let ctxTrend = document.getElementById('trendChart').getContext('2d');
                trendChartInstance = new Chart(ctxTrend, {
                    type: 'line',
                    data: {
                        labels: res.charts.dates,
                        datasets: [{
                            label: 'Daily Net Revenue (₹)',
                            data: res.charts.revenues,
                            borderColor: '#0d6efd',
                            backgroundColor: 'rgba(13, 110, 253, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                            pointBackgroundColor: '#0d6efd'
                        }]
                    },
                    options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
                });

                // Update Status Chart
                if(statusChartInstance) statusChartInstance.destroy();
                let ctxStatus = document.getElementById('statusChart').getContext('2d');
                statusChartInstance = new Chart(ctxStatus, {
                    type: 'doughnut',
                    data: {
                        labels: ['Collected (Paid)', 'Outstanding (Due)'],
                        datasets: [{
                            data: [res.charts.status.paid, res.charts.status.due],
                            backgroundColor: ['#198754', '#dc3545'],
                            borderWidth: 0
                        }]
                    },
                    options: { maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom' } } }
                });
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>