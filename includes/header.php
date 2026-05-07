<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ERP System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { background: #f8fafc; font-family: sans-serif; }
        .sidebar { width: 250px; height: 100vh; position: fixed; background: #1e293b; padding-top: 20px; }
        .sidebar a { padding: 15px 25px; display: block; color: #94a3b8; text-decoration: none; }
        .sidebar a:hover { background: #334155; color: white; border-left: 4px solid #3b82f6; }
        .main { margin-left: 250px; padding: 40px; }
        .card { border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
  <div class="sidebar">
        <h4 class="text-white text-center fw-bold mb-4">CORE ERP</h4>
        
        <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
        <a href="generate_invoice.php"><i class="bi bi-receipt me-2"></i> Generate Bill</a>
        <a href="add_purchase.php"><i class="bi bi-receipt me-2"></i> Purchase Bill</a>
        <a href="manage_invoices.php"><i class="bi bi-folder2-open me-2"></i> Manage Bills</a>     
        <a href="manage_purchases.php"><i class="bi bi-cart-check me-2"></i> Manage Purchases</a>
        <a href="settings.php"><i class="bi bi-cart-check me-2"></i> settings</a>
        <a href="add_customer.php"><i class="bi bi-person-plus me-2"></i> Add Customer</a>
        <a href="add_supplier.php"><i class="bi bi-truck me-2"></i> Add Supplier</a>
        <a href="sales_report.php"><i class="bi bi-truck me-2"></i> Sales Report</a>
        <a href="inventory.php"><i class="bi bi-box-seam me-2"></i> Inventory</a>
        <a href="manage_categories.php"><i class="bi bi-tags me-2"></i> Category Manager</a>
        <a href="unpaid_invoices.php"><i class="bi bi-exclamation-octagon text-warning me-2"></i> Pending Payments</a>
        <a href="expenses.php"><i class="bi bi-wallet2 me-2"></i> Expenses</a>
        <a href="bank_management.php"><i class="bi bi-bank me-2"></i> Company Banks</a>
        <a href="login.php?logout=true" class="text-danger mt-5"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
    </div>
    <div class="main">