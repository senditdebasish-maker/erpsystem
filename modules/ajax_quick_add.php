<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
include '../includes/db.php';

if(!isset($_GET['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$type = $_GET['type'];

if($type == 'customer') {
    $name = $conn->real_escape_string($_POST['name']);
    $contact = $conn->real_escape_string($_POST['contact_no']);
    $village = $conn->real_escape_string($_POST['village']);
    $po = $conn->real_escape_string($_POST['po']);
    $dist = $conn->real_escape_string($_POST['dist']);
    $pin = $conn->real_escape_string($_POST['pin']);
    $state = $conn->real_escape_string($_POST['state']);
    
    $bank_name = $conn->real_escape_string($_POST['bank_name']);
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $account_no = $conn->real_escape_string($_POST['account_no']);
    $ifsc_code = $conn->real_escape_string($_POST['ifsc_code']);
    
    // Auto generate Customer ID
    $last_id_query = $conn->query("SELECT id FROM customers ORDER BY id DESC LIMIT 1");
    $next_id = ($last_id_query->num_rows > 0) ? $last_id_query->fetch_assoc()['id'] + 1 : 1;
    $customer_id = 'CUST-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

    // File Upload Logic for Passbook
    $passbook_file = "";
    if(isset($_FILES['passbook']) && $_FILES['passbook']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $ext = pathinfo($_FILES['passbook']['name'], PATHINFO_EXTENSION);
        $passbook_file = "passbook_" . time() . "." . $ext;
        move_uploaded_file($_FILES['passbook']['tmp_name'], $target_dir . $passbook_file);
    }

    $sql = "INSERT INTO customers (customer_id, name, contact_no, village, po, dist, pin, state, bank_name, branch_name, account_no, ifsc_code, passbook_file) 
            VALUES ('$customer_id', '$name', '$contact', '$village', '$po', '$dist', '$pin', '$state', '$bank_name', '$branch_name', '$account_no', '$ifsc_code', '$passbook_file')";
            
    if($conn->query($sql)) {
        $insert_id = $conn->insert_id;
        echo json_encode([
            'status' => 'success', 
            'id' => $insert_id, 
            'name' => $name, 
            'cid' => $customer_id, 
            'phone' => $contact, 
            'village' => $village
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}

elseif($type == 'product') {
    $name = $conn->real_escape_string($_POST['product_name']);
    $supplier = $conn->real_escape_string($_POST['supplier_id']);
    $category = $conn->real_escape_string($_POST['category_id']);
    $subcategory = $conn->real_escape_string($_POST['subcategory_id']);
    $description = $conn->real_escape_string($_POST['description']);
    $qty = $conn->real_escape_string($_POST['qty']);
    $price = $conn->real_escape_string($_POST['selling_price']);
    $gst = isset($_POST['gst_rate']) ? $conn->real_escape_string($_POST['gst_rate']) : 0.00;

    // Handle Image Upload safely
    $product_image = ""; 
    if(isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if(!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $valid_ext = array("jpg", "jpeg", "png", "webp");
        
        if(in_array($ext, $valid_ext)) {
            $product_image = "prod_" . time() . "_" . rand(1000,9999) . "." . $ext;
            move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $product_image);
        }
    }

    $sql = "INSERT INTO products (product_name, supplier_id, category_id, subcategory_id, description, product_image, qty, selling_price, gst_rate) 
            VALUES ('$name', '$supplier', '$category', '$subcategory', '$description', '$product_image', '$qty', '$price', '$gst')";
            
    if($conn->query($sql)) {
        $insert_id = $conn->insert_id;
        echo json_encode(['status' => 'success', 'id' => $insert_id, 'name' => $name, 'qty' => $qty, 'price' => $price, 'gst' => $gst]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $conn->error]);
    }
}
?>