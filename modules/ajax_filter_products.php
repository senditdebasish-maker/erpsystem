<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
include '../includes/db.php';

$where = "WHERE 1=1";

if(!empty($_POST['category_id'])) $where .= " AND p.category_id = '".$conn->real_escape_string($_POST['category_id'])."'";
if(!empty($_POST['supplier_id'])) $where .= " AND p.supplier_id = '".$conn->real_escape_string($_POST['supplier_id'])."'";
if(!empty($_POST['search'])) {
    $s = $conn->real_escape_string($_POST['search']);
    $where .= " AND (p.product_name LIKE '%$s%' OR p.description LIKE '%$s%')";
}
if(!empty($_POST['stock_status'])) {
    if($_POST['stock_status'] == 'in_stock') $where .= " AND p.qty >= 10";
    if($_POST['stock_status'] == 'low_stock') $where .= " AND p.qty > 0 AND p.qty < 10";
    if($_POST['stock_status'] == 'out_of_stock') $where .= " AND p.qty <= 0";
}

$order = "ORDER BY p.id DESC";
if(!empty($_POST['sort_by'])) {
    if($_POST['sort_by'] == 'price_high') $order = "ORDER BY p.selling_price DESC";
    if($_POST['sort_by'] == 'price_low') $order = "ORDER BY p.selling_price ASC";
    if($_POST['sort_by'] == 'name_asc') $order = "ORDER BY p.product_name ASC";
}

// Fetch KPIs
$stats = $conn->query("SELECT 
    COUNT(*) as total_products, 
    SUM(qty * selling_price) as stock_value,
    SUM(CASE WHEN qty > 0 AND qty < 10 THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN qty <= 0 THEN 1 ELSE 0 END) as out_of_stock
    FROM products p $where")->fetch_assoc();

// Fetch Data
$sql = "SELECT p.*, c.category_name, s.name as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        $where $order";
$res = $conn->query($sql);

$html = "";
if($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        
        $img_src = (!empty($row['product_image']) && file_exists("../uploads/products/".$row['product_image'])) ? "uploads/products/".$row['product_image'] : "";
        if($img_src) {
            $img_ui = "<img src='{$img_src}' class='product-thumb shadow-sm' alt='img'>";
        } else {
            $img_ui = "<div class='product-thumb-placeholder shadow-sm'><i class='bi bi-box'></i></div>";
        }

        $stock = (int)$row['qty'];
        if($stock <= 0) {
            $stock_badge = "<span class='badge bg-danger shadow-sm'>Out of Stock</span>";
        } elseif($stock < 10) {
            $stock_badge = "<span class='badge bg-warning text-dark shadow-sm'>Low: {$stock}</span>";
        } else {
            $stock_badge = "<span class='badge bg-success shadow-sm'>{$stock} Units</span>";
        }

        $cat = !empty($row['category_name']) ? $row['category_name'] : 'N/A';
        $sup = !empty($row['supplier_name']) ? $row['supplier_name'] : 'N/A';
        
        $desc = mb_strimwidth(strip_tags($row['description']), 0, 40, "...");
        $gst_rate = isset($row['gst_rate']) ? (float)$row['gst_rate'] : 0.00;

        // ACTIONS: View, Edit, and Delete Buttons
        $action_btns = "
            <div class='d-flex justify-content-center gap-1'>
                <button type='button' class='btn btn-sm btn-outline-primary view-product shadow-sm rounded-3' data-id='{$row['id']}' title='View Details'>
                    <i class='bi bi-eye'></i>
                </button>
                <a href='edit_product.php?id={$row['id']}' class='btn btn-sm btn-outline-secondary shadow-sm rounded-3' title='Edit Product'>
                    <i class='bi bi-pencil-square'></i>
                </a>
                <button type='button' class='btn btn-sm btn-outline-danger delete-product shadow-sm rounded-3' data-id='{$row['id']}' title='Delete Product'>
                    <i class='bi bi-trash'></i>
                </button>
            </div>
        ";

        $html .= "<tr>
            <td class='ps-4'>{$img_ui}</td>
            <td>
                <a href='#' class='view-product fw-bold text-dark d-block' data-id='{$row['id']}'>".htmlspecialchars($row['product_name'])."</a>
                <small class='text-muted'>{$desc}</small>
            </td>
            <td>
                <div class='small fw-bold text-secondary mb-1'><i class='bi bi-folder2 me-1'></i>{$cat}</div>
                <div class='small text-primary'><i class='bi bi-truck me-1'></i>{$sup}</div>
            </td>
            <td class='text-center'>{$stock_badge}</td>
            <td class='text-center'><span class='badge bg-info text-dark shadow-sm'>{$gst_rate}%</span></td>
            <td class='text-end fw-bolder text-success'>₹".number_format($row['selling_price'], 2)."</td>
            <td class='text-center pe-4'>{$action_btns}</td>
        </tr>";
    }
} else {
    $html = "<tr><td colspan='7' class='text-center py-5 text-muted'><i class='bi bi-box-seam fs-1 d-block mb-2 opacity-50'></i>No products found matching your filters.</td></tr>";
}

echo json_encode([
    'status' => 'success',
    'html' => $html,
    'total_products' => number_format($stats['total_products'] ?? 0),
    'stock_value' => number_format($stats['stock_value'] ?? 0, 2),
    'low_stock' => number_format($stats['low_stock'] ?? 0),
    'out_of_stock' => number_format($stats['out_of_stock'] ?? 0)
]);
exit;
?>