<?php
error_reporting(0);
ob_clean();
header('Content-Type: application/json');
include '../includes/db.php';

// Auto-heal database to ensure shipping columns exist
$check_shipping = $conn->query("SHOW COLUMNS FROM customers LIKE 'shipping_village'");
if($check_shipping && $check_shipping->num_rows == 0) {
    $conn->query("ALTER TABLE customers 
        ADD COLUMN shipping_village VARCHAR(100) AFTER state,
        ADD COLUMN shipping_po VARCHAR(100) AFTER shipping_village,
        ADD COLUMN shipping_dist VARCHAR(100) AFTER shipping_po,
        ADD COLUMN shipping_pin VARCHAR(20) AFTER shipping_dist,
        ADD COLUMN shipping_state VARCHAR(50) AFTER shipping_pin");
}

if(!isset($_GET['type'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$type = $_GET['type'];

if($type == 'customer') {
    $update_id = isset($_POST['customer_update_id']) ? $conn->real_escape_string($_POST['customer_update_id']) : '';
    
    $name = $conn->real_escape_string($_POST['name']);
    $contact = $conn->real_escape_string($_POST['contact_no']);
    
    // Billing Address
    $village = $conn->real_escape_string($_POST['village']);
    $po = $conn->real_escape_string($_POST['po']);
    $dist = $conn->real_escape_string($_POST['dist']);
    $pin = $conn->real_escape_string($_POST['pin']);
    $state = $conn->real_escape_string($_POST['state']);
    
    // Shipping Address
    $ship_village = $conn->real_escape_string($_POST['shipping_village'] ?? '');
    $ship_po = $conn->real_escape_string($_POST['shipping_po'] ?? '');
    $ship_dist = $conn->real_escape_string($_POST['shipping_dist'] ?? '');
    $ship_pin = $conn->real_escape_string($_POST['shipping_pin'] ?? '');
    $ship_state = $conn->real_escape_string($_POST['shipping_state'] ?? '');
    
    $bank_name = $conn->real_escape_string($_POST['bank_name']);
    $branch_name = $conn->real_escape_string($_POST['branch_name']);
    $account_no = $conn->real_escape_string($_POST['account_no']);
    $ifsc_code = $conn->real_escape_string($_POST['ifsc_code']);
    
    // File Upload Logic for Passbook
    $passbook_sql = "";
    if(isset($_FILES['passbook']) && $_FILES['passbook']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $ext = pathinfo($_FILES['passbook']['name'], PATHINFO_EXTENSION);
        $passbook_file = "passbook_" . time() . "." . $ext;
        move_uploaded_file($_FILES['passbook']['tmp_name'], $target_dir . $passbook_file);
        $passbook_sql = ", passbook_file = '$passbook_file'";
    }

    if(!empty($update_id)) {
        // UPDATE EXISTING CUSTOMER
        $sql = "UPDATE customers SET 
                name='$name', contact_no='$contact', 
                village='$village', po='$po', dist='$dist', pin='$pin', state='$state',
                shipping_village='$ship_village', shipping_po='$ship_po', shipping_dist='$ship_dist', shipping_pin='$ship_pin', shipping_state='$ship_state',
                bank_name='$bank_name', branch_name='$branch_name', account_no='$account_no', ifsc_code='$ifsc_code'
                $passbook_sql 
                WHERE id='$update_id'";
                
        if($conn->query($sql)) {
            echo json_encode([
                'status' => 'success', 
                'action' => 'update',
                'id' => $update_id, 
                'name' => $name, 
                'phone' => $contact, 
                'village' => $village,
                'shipping_village' => $ship_village
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    } else {
        // INSERT NEW CUSTOMER
        $last_id_query = $conn->query("SELECT id FROM customers ORDER BY id DESC LIMIT 1");
        $next_id = ($last_id_query->num_rows > 0) ? $last_id_query->fetch_assoc()['id'] + 1 : 1;
        $customer_id = 'CUST-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO customers (customer_id, name, contact_no, village, po, dist, pin, state, shipping_village, shipping_po, shipping_dist, shipping_pin, shipping_state, bank_name, branch_name, account_no, ifsc_code $passbook_sql) 
                VALUES ('$customer_id', '$name', '$contact', '$village', '$po', '$dist', '$pin', '$state', '$ship_village', '$ship_po', '$ship_dist', '$ship_pin', '$ship_state', '$bank_name', '$branch_name', '$account_no', '$ifsc_code')";
                
        if($conn->query($sql)) {
            $insert_id = $conn->insert_id;
            echo json_encode([
                'status' => 'success', 
                'action' => 'insert',
                'id' => $insert_id, 
                'name' => $name, 
                'cid' => $customer_id, 
                'phone' => $contact, 
                'village' => $village,
                'shipping_village' => $ship_village
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $conn->error]);
        }
    }
}

elseif($type == 'product') {
    // ... [Keep your existing product logic exactly the same] ...
    $name = $conn->real_escape_string($_POST['product_name']);
    $supplier = $conn->real_escape_string($_POST['supplier_id']);
    $category = $conn->real_escape_string($_POST['category_id']);
    $subcategory = $conn->real_escape_string($_POST['subcategory_id']);
    $description = $conn->real_escape_string($_POST['description']);
    $qty = $conn->real_escape_string($_POST['qty']);
    $price = $conn->real_escape_string($_POST['selling_price']);
    $gst = isset($_POST['gst_rate']) ? $conn->real_escape_string($_POST['gst_rate']) : 0.00;

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