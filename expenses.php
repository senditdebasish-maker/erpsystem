<?php 
include 'includes/db.php'; 
include 'includes/header.php'; 

// Check if we are in "Edit Mode"
$edit_mode = false;
if(isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = $conn->query("SELECT * FROM expenses WHERE id = '$edit_id'");
    if($edit_query->num_rows > 0) {
        $edit_data = $edit_query->fetch_assoc();
        $edit_mode = true;
    }
}
?>

<div class="container-fluid">
    <h2 class="fw-bold mb-4">Overhead Expenses</h2>

    <?php if(isset($_GET['msg'])): ?>
        <?php if($_GET['msg'] == 'updated'): ?>
            <div class="alert alert-success fw-bold"><i class="bi bi-check-circle me-2"></i> Expense updated successfully.</div>
        <?php elseif($_GET['msg'] == 'deleted'): ?>
            <div class="alert alert-danger fw-bold"><i class="bi bi-trash me-2"></i> Expense removed.</div>
        <?php endif; ?>
    <?php endif; ?>

    <form action="modules/<?php echo $edit_mode ? 'update_expense.php' : 'add_expense.php'; ?>" method="POST" class="card p-4 shadow-sm mb-4">
        <div class="d-flex gap-3 align-items-center">
            
            <?php if($edit_mode): ?>
                <input type="hidden" name="id" value="<?php echo $edit_data['id']; ?>">
            <?php endif; ?>

            <input type="text" name="desc" class="form-control" placeholder="Expense (e.g. Rent, Electricity)" value="<?php echo $edit_mode ? $edit_data['description'] : ''; ?>" required>
            <input type="number" name="amount" step="0.01" class="form-control" placeholder="Amount (₹)" value="<?php echo $edit_mode ? $edit_data['amount'] : ''; ?>" required>
            
            <?php if($edit_mode): ?>
                <button type="submit" class="btn btn-warning px-4 fw-bold shadow-sm">UPDATE</button>
                <a href="expenses.php" class="btn btn-secondary px-4 fw-bold">CANCEL</a>
            <?php else: ?>
                <button type="submit" class="btn btn-danger px-5 fw-bold shadow-sm">SAVE</button>
            <?php endif; ?>

        </div>
    </form>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $res = $conn->query("SELECT * FROM expenses ORDER BY id DESC"); 
                    if($res->num_rows > 0):
                        while($r = $res->fetch_assoc()): 
                    ?>
                    <tr>
                        <td class="ps-4"><?php echo date('d-M-Y', strtotime($r['date'])); ?></td>
                        <td class="fw-bold"><?php echo $r['description']; ?></td>
                        <td class="text-danger fw-bold">₹<?php echo number_format($r['amount'], 2); ?></td>
                        <td class="text-center">
                            <a href="expenses.php?edit=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-warning" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="modules/delete_expense.php?id=<?php echo $r['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this expense record?');" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endwhile; 
                    else: 
                    ?>
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No expenses recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>