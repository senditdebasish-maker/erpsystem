<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// --- LOGIC: Check what we are editing (Category or Subcategory) ---
$edit_cat_mode = false;
$edit_sub_mode = false;

if(isset($_GET['edit_cat'])) {
    $id = $_GET['edit_cat'];
    $res = $conn->query("SELECT * FROM categories WHERE id = '$id'");
    if($res->num_rows > 0) { $cat_data = $res->fetch_assoc(); $edit_cat_mode = true; }
} elseif(isset($_GET['edit_subcat'])) {
    $id = $_GET['edit_subcat'];
    $res = $conn->query("SELECT * FROM subcategories WHERE id = '$id'");
    if($res->num_rows > 0) { $sub_data = $res->fetch_assoc(); $edit_sub_mode = true; }
}
?>

<style>
    .cursor-pointer { cursor: pointer; transition: background 0.2s; }
    .cursor-pointer:hover { background-color: #f8f9fa !important; }
    .nested-table { background-color: #f8f9fa; border-left: 4px solid #0d6efd; }
    .nested-product { background-color: #ffffff; border-left: 4px solid #198754; }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-diagram-3-fill text-primary me-2"></i>Master Hierarchy Manager</h2>
        <a href="inventory.php" class="btn btn-dark"><i class="bi bi-box-seam me-2"></i> Back to Inventory</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success fw-bold"><i class="bi bi-check-circle me-2"></i> Action completed successfully!</div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm border-0 border-top border-primary border-4 mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">
                        <?php 
                        if($edit_cat_mode) echo '<i class="bi bi-pencil me-2"></i>Update Category';
                        elseif($edit_sub_mode) echo '<i class="bi bi-pencil me-2"></i>Update Subcategory';
                        else echo '<i class="bi bi-plus-circle me-2"></i>Add New Category'; 
                        ?>
                    </h5>
                    
                    <form action="modules/master_cat_process.php" method="POST">
                        <?php if($edit_cat_mode): ?>
                            <input type="hidden" name="cat_id" value="<?php echo $cat_data['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" value="<?php echo $cat_data['category_name']; ?>" required>
                            </div>
                            <button type="submit" name="update_cat" class="btn btn-warning w-100 fw-bold">UPDATE CATEGORY</button>
                            
                        <?php elseif($edit_sub_mode): ?>
                            <input type="hidden" name="sub_id" value="<?php echo $sub_data['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Parent Category</label>
                                <select name="category_id" class="form-select" required>
                                    <?php 
                                    $cats = $conn->query("SELECT * FROM categories");
                                    while($c = $cats->fetch_assoc()) {
                                        $sel = ($c['id'] == $sub_data['category_id']) ? 'selected' : '';
                                        echo "<option value='{$c['id']}' $sel>{$c['category_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Subcategory Name</label>
                                <input type="text" name="subcategory_name" class="form-control" value="<?php echo $sub_data['subcategory_name']; ?>" required>
                            </div>
                            <button type="submit" name="update_subcat" class="btn btn-warning w-100 fw-bold">UPDATE SUBCATEGORY</button>
                            
                        <?php else: ?>
                            <div class="mb-3">
                                <label class="form-label">Category Name</label>
                                <input type="text" name="category_name" class="form-control" placeholder="e.g., Electronics" required>
                            </div>
                            <button type="submit" name="save_cat" class="btn btn-primary w-100 fw-bold">SAVE CATEGORY</button>
                        <?php endif; ?>

                        <?php if($edit_cat_mode || $edit_sub_mode): ?>
                            <a href="manage_categories.php" class="btn btn-light w-100 mt-2">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card bg-light border-0 p-3 shadow-sm">
                <small class="text-muted"><i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Tip:</strong> Click on any Category or Subcategory row in the table to expand it and view the items inside!</small>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th class="ps-3" width="5%">Sl</th>
                                    <th width="35%">Category Name</th>
                                    <th class="text-center" width="20%">Subcategories</th>
                                    <th class="text-center" width="20%">Total Products</th>
                                    <th class="text-center" width="20%">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $sql = "SELECT c.id, c.category_name, 
                                       (SELECT COUNT(*) FROM subcategories WHERE category_id = c.id) as sub_count,
                                       (SELECT COUNT(*) FROM products WHERE category_id = c.id) as prod_count
                                       FROM categories c ORDER BY c.category_name ASC";
                                $res = $conn->query($sql);
                                $sl = 1;
                                
                                while($row = $res->fetch_assoc()):
                                    $cat_id = $row['id'];
                                ?>
                                <tr class="cursor-pointer" data-bs-toggle="collapse" data-bs-target="#catRow<?php echo $cat_id; ?>">
                                    <td class="ps-3 fw-bold text-muted"><?php echo $sl++; ?></td>
                                    <td class="fw-bold fs-5 text-primary"><i class="bi bi-chevron-down me-2 fs-6 text-dark opacity-50"></i><?php echo $row['category_name']; ?></td>
                                    <td class="text-center"><span class="badge bg-secondary"><?php echo $row['sub_count']; ?></span></td>
                                    <td class="text-center"><span class="badge <?php echo $row['prod_count'] > 0 ? 'bg-info text-dark' : 'bg-light text-muted'; ?>"><?php echo $row['prod_count']; ?></span></td>
                                    <td class="text-center">
                                        <a href="manage_categories.php?edit_cat=<?php echo $cat_id; ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                        <?php if($row['prod_count'] == 0): ?>
                                            <a href="modules/master_cat_process.php?delete_cat=<?php echo $cat_id; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this category and all its subcategories?')"><i class="bi bi-trash"></i></a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled title="Empty products first"><i class="bi bi-trash"></i></button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <tr id="catRow<?php echo $cat_id; ?>" class="collapse nested-table">
                                    <td colspan="5" class="p-3">
                                        
                                        <?php 
                                        $sub_res = $conn->query("SELECT *, (SELECT COUNT(*) FROM products WHERE subcategory_id = subcategories.id) as p_count FROM subcategories WHERE category_id = '$cat_id'");
                                        if($sub_res->num_rows > 0): 
                                        ?>
                                            <table class="table table-sm mb-0">
                                                <thead class="text-muted small text-uppercase">
                                                    <tr>
                                                        <th width="40%"><i class="bi bi-arrow-return-right me-1"></i> Subcategory</th>
                                                        <th class="text-center" width="30%">Products Inside</th>
                                                        <th class="text-center" width="30%">Manage</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php while($sub = $sub_res->fetch_assoc()): $sub_id = $sub['id']; ?>
                                                    
                                                    <tr class="cursor-pointer" data-bs-toggle="collapse" data-bs-target="#subRow<?php echo $sub_id; ?>">
                                                        <td class="fw-bold"><i class="bi bi-chevron-down me-2 fs-6 text-dark opacity-50"></i><?php echo $sub['subcategory_name']; ?></td>
                                                        <td class="text-center"><span class="badge bg-dark"><?php echo $sub['p_count']; ?></span></td>
                                                        <td class="text-center">
                                                            <a href="manage_categories.php?edit_subcat=<?php echo $sub_id; ?>" class="btn btn-sm text-warning"><i class="bi bi-pencil-fill"></i></a>
                                                            <?php if($sub['p_count'] == 0): ?>
                                                                <a href="modules/master_cat_process.php?delete_subcat=<?php echo $sub_id; ?>" class="btn btn-sm text-danger" onclick="return confirm('Delete this subcategory?')"><i class="bi bi-trash-fill"></i></a>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>

                                                    <tr id="subRow<?php echo $sub_id; ?>" class="collapse nested-product">
                                                        <td colspan="3" class="p-3">
                                                            <?php 
                                                            $prod_res = $conn->query("SELECT * FROM products WHERE subcategory_id = '$sub_id'");
                                                            if($prod_res->num_rows > 0):
                                                            ?>
                                                                <table class="table table-bordered table-sm mb-0 bg-white">
                                                                    <thead class="bg-light text-muted small">
                                                                        <tr>
                                                                            <th>Product Name</th>
                                                                            <th class="text-center">Qty</th>
                                                                            <th class="text-end">Price</th>
                                                                            <th class="text-center">Edit</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php while($p = $prod_res->fetch_assoc()): ?>
                                                                        <tr>
                                                                            <td class="text-secondary fw-bold"><?php echo $p['product_name']; ?></td>
                                                                            <td class="text-center"><?php echo $p['qty']; ?></td>
                                                                            <td class="text-end">₹<?php echo $p['selling_price']; ?></td>
                                                                            <td class="text-center">
                                                                                <a href="edit_product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                                                                            </td>
                                                                        </tr>
                                                                        <?php endwhile; ?>
                                                                    </tbody>
                                                                </table>
                                                            <?php else: ?>
                                                                <span class="text-muted small fst-italic">No products in this subcategory.</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>

                                                    <?php endwhile; ?>
                                                </tbody>
                                            </table>
                                        <?php else: ?>
                                            <div class="text-muted small fst-italic p-2">No subcategories created yet.</div>
                                        <?php endif; ?>

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