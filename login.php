<?php 
include 'includes/db.php';
if(isset($_GET['logout'])) { session_destroy(); header("Location: login.php"); exit; }
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $u = $_POST['username']; $p = $_POST['password'];
    $res = $conn->query("SELECT * FROM users WHERE username='$u' AND password='$p'");
    if($res->num_rows > 0) { $_SESSION['logged_in'] = true; header("Location: index.php"); }
    else { $error = "Invalid Credentials"; }
}
?>
<!DOCTYPE html>
<html>
<head><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-dark d-flex align-items-center justify-content-center" style="height: 100vh;">
    <div class="card p-5 shadow" style="width: 400px; border-radius: 15px;">
        <h2 class="text-center fw-bold mb-4">ERP LOGIN</h2>
        <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="username" class="form-control mb-3" placeholder="Username (admin)" required>
            <input type="password" name="password" class="form-control mb-4" placeholder="Password (admin123)" required>
            <button class="btn btn-primary w-100 fw-bold py-2">LOGIN</button>
        </form>
    </div>
</body>
</html>