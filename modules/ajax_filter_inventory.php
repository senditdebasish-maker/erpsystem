<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
include '../includes/db.php';

$where = "WHERE 1=1";
if(!empty($_POST['category_id'])) $where .= " AND p.category_id = '{$_POST['category_id']}'";
if(!empty($_POST['supplier_id'])) $where .= " AND p.supplier_id = '{$_POST['supplier_id']}'";

if(!empty($_POST['stock_status'])) {
    if($_POST['stock_status'] == 'in_stock') $where .= " AND p.qty >= 10";
    if($_POST['stock_status'] == 'low_stock') $where .= " AND p.qty > 0 AND p.qty < 10";
    if($_POST['stock_status'] == 'out_of_stock') $where .= " AND p.qty = 0";
}

if(!empty($_POST['search'])) {
    $s = $conn->real_escape_string($_POST['search']);
    $where .= " AND (p.product_name LIKE '%$s%' OR s.name LIKE '%$s%' OR p.description LIKE '%$s%')";
}

// Sorting logic
$order_by = "ORDER BY p.id DESC";
if($_POST['sort_by'] == 'price_high') $order_by = "ORDER BY p.selling_price DESC";
if($_POST['sort_by'] == 'price_low') $order_by = "ORDER BY p.selling_price ASC";
if($_POST['sort_by'] == 'name_asc') $order_by = "ORDER BY p.product_name ASC";

// 1. KPI MATH
$stats = $conn->query("
    SELECT 
        COUNT(*) as total_prod, 
        SUM(qty * selling_price) as stock_val,
        SUM(CASE WHEN qty > 0 AND qty < 10 THEN 1 ELSE 0 END) as low_stk,
        SUM(CASE WHEN qty = 0 THEN 1 ELSE 0 END) as out_stk
    FROM products p LEFT JOIN suppliers s ON p.supplier_id = s.id 
    $where
")->fetch_assoc();

// 2. FETCH TABLE ROWS
$sql = "SELECT p.*, c.category_name, sc.subcategory_name, s.name as supplier_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id 
        LEFT JOIN suppliers s ON p.supplier_id = s.id 
        $where $order_by";
$res = $conn->query($sql);

$html = "";
if($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        
        // Handle Image Thumbnail
        $img_src = (!empty($row['product_image']) && file_exists("../uploads/products/".$row['product_image'])) ? "uploads/products/".$row['product_image'] : "";
        if($img_src) {
            $img_ui = "<img src='{$img_src}' class='product-thumb shadow-sm' alt='Product'>";
        } else {
            $img_ui = "<div class='product-thumb-placeholder'><i class='bi bi-box'></i></div>";
        }

        // Handle Stock Status Badges
        $qty = $row['qty'];
        if($qty == 0) {
            $stock_badge = "<span class='badge bg-danger shadow-sm px-3 py-2'>Out of Stock (0)</span>";
        } elseif($qty < 10) {
            $stock_badge = "<span class='badge bg-warning text-dark shadow-sm px-3 py-2'>Low Stock ({$qty})</span>";
        } else {
            $stock_badge = "<span class='badge bg-success shadow-sm px-3 py-2'>In Stock ({$qty})</span>";
        }

        // Product Name Link
        $prod_link = "<a href='#' class='view-product fw-bolder text-dark fs-6' data-id='{$row['id']}'>{$row['product_name']}</a>";
        
        // Taxonomy formatting
        $taxonomy = "<span class='badge bg-light border text-primary fw-bold'><i class='bi bi-folder2 me-1'></i>{$row['category_name']}</span>";
        if(!empty($row['subcategory_name'])) {
            $taxonomy .= " <i class='bi bi-chevron-right text-muted small'></i> <span class='text-muted small'>{$row['subcategory_name']}</span>";
        }

        // Actions
        $action_btns = "
        <div class='d-flex justify-content-center gap-1'>
            <button type='button' class='btn btn-sm btn-outline-primary view-product shadow-sm' data-id='{$row['id']}' title='View Details'><i class='bi bi-eye'></i></button>
            <a href='edit_product.php?id={$row['id']}' class='btn btn-sm btn-outline-warning shadow-sm' title='Edit'><i class='bi bi-pencil'></i></a>
            <a href='modules/delete_product.php?id={$row['id']}' class='btn btn-sm btn-outline-danger shadow-sm' onclick=\"return confirm('WARNING: Are you sure you want to delete this product?');\" title='Delete'><i class='bi bi-trash'></i></a>
        </div>";

        $html .= "<tr>
            <td class='ps-4'>{$img_ui}</td>
            <td>
                {$prod_link}
                <div class='small text-muted mt-1 text-truncate' style='max-width: 250px;'>{$row['description']}</div>
            </td>
            <td>
                <div class='mb-1'>{$taxonomy}</div>
                <div class='small text-dark fw-bold'><i class='bi bi-truck text-muted me-1'></i>{$row['supplier_name']}</div>
            </td>
            <td class='text-center'>{$stock_badge}</td>
            <td class='text-end'><h5 class='mb-0 fw-bolder text-success'>₹ ".number_format($row['selling_price'], 2)."</h5></td>
            <td class='text-center pe-4'>{$action_btns}</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='6' class='text-center py-5 text-muted'><i class='bi bi-search fs-1 d-block mb-2 opacity-50'></i>No products found matching your filters.</td></tr>";
}

echo json_encode([
    'status' => 'success', 
    'html' => $html, 
    'total_products' => $stats['total_prod'] ?? 0, 
    'stock_value' => number_format($stats['stock_val'] ?? 0, 2),
    'low_stock' => $stats['low_stk'] ?? 0,
    'out_of_stock' => $stats['out_stk'] ?? 0
]);
exit;
?>