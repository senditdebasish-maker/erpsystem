<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');
include '../includes/db.php';

$where = "WHERE 1=1";
$date_from = trim($_POST['date_from'] ?? '');
$date_to = trim($_POST['date_to'] ?? '');

if(!empty($date_from)) { $where .= " AND DATE(created_at) >= '$date_from'"; }
if(!empty($date_to)) { $where .= " AND DATE(created_at) <= '$date_to'"; }

// 1. KPI MATH (Gross, Net, Tax, Discount, Pending)
$kpi_query = $conn->query("SELECT 
    COUNT(id) as total_inv, 
    SUM(total_amount + IFNULL(discount, 0)) as gross_sales, 
    SUM(total_amount) as net_sales,
    SUM(IFNULL(total_tax, 0)) as total_gst, 
    SUM(IFNULL(discount, 0)) as total_disc, 
    SUM(IFNULL(paid_amount, 0)) as total_paid,
    SUM(total_amount - IFNULL(paid_amount, 0)) as total_pending 
    FROM invoices $where");

$kpis = $kpi_query->fetch_assoc();

// Format KPIs safely
$gross = (float)($kpis['gross_sales'] ?? 0);
$net = (float)($kpis['net_sales'] ?? 0);
$gst = (float)($kpis['total_gst'] ?? 0);
$disc = (float)($kpis['total_disc'] ?? 0);
$paid = (float)($kpis['total_paid'] ?? 0);
$pending = (float)($kpis['total_pending'] ?? 0);

// 2. DAILY TREND (Line Chart & Table Data)
$daily_sql = "SELECT DATE(created_at) as sale_date, COUNT(id) as inv_count, 
              SUM(total_amount + IFNULL(discount, 0)) as gross,
              SUM(IFNULL(discount, 0)) as disc,
              SUM(total_amount) as net 
              FROM invoices $where GROUP BY DATE(created_at) ORDER BY sale_date ASC";
$daily_res = $conn->query($daily_sql);

$dates = [];
$revenues = [];
$daily_html = "";

if($daily_res && $daily_res->num_rows > 0) {
    while($d = $daily_res->fetch_assoc()) {
        // Data for Chart
        $dates[] = date('d M', strtotime($d['sale_date']));
        $revenues[] = (float)$d['net'];
        
        // Data for Table
        $daily_html .= "<tr>
            <td class='ps-4 fw-bold text-secondary'>" . date('d M Y', strtotime($d['sale_date'])) . "</td>
            <td class='text-center'><span class='badge bg-light text-dark border'>{$d['inv_count']}</span></td>
            <td class='text-end'>₹" . number_format($d['gross'], 2) . "</td>
            <td class='text-end text-danger'>-₹" . number_format($d['disc'], 2) . "</td>
            <td class='text-end fw-bold text-primary pe-4'>₹" . number_format($d['net'], 2) . "</td>
        </tr>";
    }
} else {
    $daily_html = "<tr><td colspan='5' class='text-center py-4 text-muted'>No sales data found for this period.</td></tr>";
}

// 3. TOP SELLING PRODUCTS
$top_sql = "SELECT p.product_name, SUM(ii.qty) as total_qty, SUM(ii.qty * ii.price) as total_revenue 
            FROM invoice_items ii 
            JOIN products p ON ii.product_id = p.id 
            JOIN invoices i ON ii.invoice_id = i.id 
            $where 
            GROUP BY ii.product_id 
            ORDER BY total_revenue DESC LIMIT 5";
$top_res = $conn->query($top_sql);

$top_html = "";
if($top_res && $top_res->num_rows > 0) {
    while($p = $top_res->fetch_assoc()) {
        $top_html .= "<tr>
            <td class='ps-4 fw-bold text-dark'>{$p['product_name']}</td>
            <td class='text-center'><span class='badge bg-warning text-dark'>{$p['total_qty']} Sold</span></td>
            <td class='text-end fw-bolder text-success pe-4'>₹" . number_format($p['total_revenue'], 2) . "</td>
        </tr>";
    }
} else {
    $top_html = "<tr><td colspan='3' class='text-center py-4 text-muted'>No product data available.</td></tr>";
}

// OUTPUT JSON
echo json_encode([
    'kpis' => [
        'gross' => number_format($gross, 2, '.', ''),
        'net' => number_format($net, 2, '.', ''),
        'gst' => number_format($gst, 2, '.', ''),
        'discount' => number_format($disc, 2, '.', ''),
        'pending' => number_format($pending, 2, '.', '')
    ],
    'charts' => [
        'dates' => $dates,
        'revenues' => $revenues,
        'status' => [
            'paid' => $paid,
            'due' => $pending
        ]
    ],
    'tables' => [
        'daily' => $daily_html,
        'products' => $top_html
    ]
]);
exit;
?>