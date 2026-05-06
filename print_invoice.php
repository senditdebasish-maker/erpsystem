<?php
date_default_timezone_set('Asia/Kolkata');
include 'includes/db.php';

if(!isset($_GET['id'])) { die("Invoice ID missing."); }
$id = $conn->real_escape_string($_GET['id']);

// Fetch Invoice & Customer
$sql = "SELECT i.*, c.customer_id, c.contact_no, c.village, c.dist 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_name = c.name
        WHERE i.id = '$id'";
$inv = $conn->query($sql)->fetch_assoc();
if(!$inv) { die("Invoice not found."); }

// Fetch Company Settings
$settings = [];
$res = $conn->query("SELECT * FROM company_settings");
if($res) {
    while($row = $res->fetch_assoc()) { $settings[$row['setting_key']] = $row['setting_value']; }
}

// Convert Number to Words (Indian Format)
function getIndianCurrency(float $number) {
    $decimal = round($number - ($no = floor($number)), 2) * 100;
    $hundred = null;
    $digits_length = strlen($no);
    $i = 0;
    $str = array();
    $words = array(0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety');
    $digits = array('', 'Hundred','Thousand','Lakh', 'Crore');
    while( $i < $digits_length ) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += $divider == 10 ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 21) ? $words[$number].' '. $digits[$counter]. $plural.' '.$hundred:$words[floor($number / 10) * 10].' '.$words[$number % 10]. ' '.$digits[$counter].$plural.' '.$hundred;
        } else $str[] = null;
    }
    $Rupees = implode('', array_reverse($str));
    $paise = ($decimal > 0) ? " and " . ($words[$decimal / 10] . " " . $words[$decimal % 10]) . ' Paise' : '';
    return ($Rupees ? $Rupees . 'Rupees ' : '') . $paise . " Only";
}

// ==========================================
// 🚀 DYNAMIC UPI QR CODE GENERATOR (ALWAYS SHOWS)
// ==========================================
$due_amount = (float)$inv['total_amount'] - (float)$inv['paid_amount'];

$upi_id = "8759899124@upi"; // ⚠️ REPLACE THIS WITH YOUR REAL UPI ID!
$payee_name = urlencode($settings['company_name'] ?? 'Nimtita Gram Panchayat'); 
$invoice_ref = "INV-" . str_pad($inv['id'], 5, '0', STR_PAD_LEFT);
$qr_amount = (float)$inv['total_amount']; // Always uses the total bill amount

// Standard UPI URI format accepted by all Indian UPI apps
$upi_url = "upi://pay?pa={$upi_id}&pn={$payee_name}&tr={$invoice_ref}&am={$qr_amount}&cu=INR";
$qr_image_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($upi_url);
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice - <?php echo $id; ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 12px; color: #000; margin: 0; padding: 0; }
        .invoice-box { width: 800px; margin: auto; border: 1px solid #000; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        
        /* Tally Specific Borders */
        .border-bottom { border-bottom: 1px solid #000; }
        .border-right { border-right: 1px solid #000; }
        .border-top { border-top: 1px solid #000; }
        
        .p-2 { padding: 5px; }
        .header-title { font-size: 16px; font-weight: bold; text-align: center; border-bottom: 1px solid #000; padding: 5px; background: #f9f9f9; }
        
        .flex-container { display: flex; }
        .col-left { width: 50%; border-right: 1px solid #000; padding: 10px; }
        .col-right { width: 50%; padding: 0; display: flex; flex-direction: column; }
        .col-right-row { display: flex; border-bottom: 1px solid #000; }
        .col-right-cell { width: 50%; padding: 5px; border-right: 1px solid #000; }
        .col-right-cell:last-child { border-right: none; }

        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { border-right: 1px solid #000; padding: 5px; vertical-align: top; }
        table.items th { border-bottom: 1px solid #000; border-top: 1px solid #000; text-align: left; }
        table.items td:last-child, table.items th:last-child { border-right: none; }
        .item-empty-row td { color: transparent; height: 15px; } 

        .footer-summary { display: flex; border-top: 1px solid #000; }
        .footer-terms { width: 60%; border-right: 1px solid #000; padding: 10px; font-size: 10px; }
        .footer-totals { width: 40%; }
        .totals-row { display: flex; border-bottom: 1px solid #000; padding: 5px; }
        .totals-row div:first-child { width: 60%; font-weight: bold; text-align: right; padding-right: 10px; }
        .totals-row div:last-child { width: 40%; text-align: right; }
        .signature-box { height: 80px; text-align: right; padding: 10px; position: relative; }
        .signature-text { position: absolute; bottom: 10px; right: 10px; font-weight: bold; }

        /* Print Specific */
        @media print {
            body { padding: 0; margin: 0; }
            .no-print { display: none; }
            .invoice-box { width: 100%; border: 1px solid #000; }
        }

        /* Action Buttons */
        .print-btn { background: #0d6efd; color: white; padding: 10px 20px; border: none; cursor: pointer; font-weight: bold; border-radius: 5px; display: block; margin: 20px auto; width: 200px; text-align: center; text-decoration: none; }
        
        /* QR Box Styling */
        .qr-box { border: 1px dashed #666; padding: 8px; text-align: center; margin-top: 10px; background: #fafafa; }
    </style>
</head>
<body>

<button onclick="window.print()" class="no-print print-btn">🖨️ PRINT INVOICE</button>

<div class="invoice-box">
    <div class="header-title">TAX INVOICE</div>
    
    <!-- Top Details -->
    <div class="flex-container border-bottom">
        <div class="col-left">
            <div class="font-bold" style="font-size:16px;"><?php echo htmlspecialchars($settings['company_name'] ?? 'Your Company Name'); ?></div>
            <div><?php echo nl2br(htmlspecialchars($settings['company_address'] ?? '')); ?></div>
            <div><span class="font-bold">Contact:</span> <?php echo htmlspecialchars($settings['company_phone'] ?? ''); ?></div>
            <div><span class="font-bold">Email:</span> <?php echo htmlspecialchars($settings['company_email'] ?? ''); ?></div>
            <div style="margin-top:5px;"><span class="font-bold">GSTIN/UIN:</span> <?php echo htmlspecialchars($settings['company_gstin'] ?? ''); ?></div>
        </div>
        <div class="col-right">
            <div class="col-right-row" style="height: 50%;">
                <div class="col-right-cell">
                    <div class="font-bold">Invoice No.</div>
                    <div>INV-<?php echo str_pad($inv['id'], 5, '0', STR_PAD_LEFT); ?></div>
                </div>
                <div class="col-right-cell">
                    <div class="font-bold">Dated</div>
                    <div><?php echo date('d-M-Y', strtotime($inv['created_at'])); ?></div>
                </div>
            </div>
            <div class="col-right-row" style="height: 50%; border-bottom:none;">
                <div class="col-right-cell">
                    <div class="font-bold">Payment Status</div>
                    <div><?php echo htmlspecialchars($inv['payment_status']); ?></div>
                </div>
                <div class="col-right-cell">
                    <div class="font-bold">Supplier's Ref.</div>
                    <div>-</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Billed To -->
    <div class="p-2 border-bottom">
        <div class="font-bold">Billed To / Buyer:</div>
        <div class="font-bold" style="font-size: 14px;"><?php echo htmlspecialchars($inv['customer_name']); ?></div>
        <div>
            <?php 
            echo (!empty($inv['village']) ? htmlspecialchars($inv['village']) : '') . 
                 (!empty($inv['dist']) ? ', ' . htmlspecialchars($inv['dist']) : ''); 
            ?>
        </div>
        <div><span class="font-bold">Contact:</span> <?php echo htmlspecialchars($inv['contact_no'] ?? 'N/A'); ?></div>
    </div>

    <!-- Items Table -->
    <table class="items">
        <thead>
            <tr>
                <th width="5%">Sl No.</th>
                <th width="40%">Description of Goods</th>
                <th width="10%" class="text-center">Qty</th>
                <th width="15%" class="text-right">Rate</th>
                <th width="10%" class="text-center">GST %</th>
                <th width="20%" class="text-right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $items = $conn->query("SELECT ii.*, p.product_name FROM invoice_items ii JOIN products p ON ii.product_id = p.id WHERE ii.invoice_id = '$id'");
            $counter = 1;
            $subtotal = 0;
            $total_gst = 0;
            
            while($item = $items->fetch_assoc()) {
                $base_amt = $item['qty'] * $item['price'];
                $subtotal += $base_amt;
                $total_gst += $item['tax_amount'];
                
                $note = !empty($item['item_note']) ? "<br><span style='font-size:10px; font-style:italic;'>[{$item['item_note']}]</span>" : "";

                echo "<tr>
                    <td>{$counter}</td>
                    <td><span class='font-bold'>".htmlspecialchars($item['product_name'])."</span>{$note}</td>
                    <td class='text-center'>{$item['qty']}</td>
                    <td class='text-right'>".number_format($item['price'], 2)."</td>
                    <td class='text-center'>".(float)$item['gst_rate']."%</td>
                    <td class='text-right'>".number_format($base_amt, 2)."</td>
                </tr>";
                $counter++;
            }
            
            // Add blank rows to push footer down like Tally
            for($i = $counter; $i <= 10; $i++) {
                echo "<tr class='item-empty-row'><td>.</td><td>.</td><td>.</td><td>.</td><td>.</td><td>.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <!-- Footer Summary -->
    <div class="footer-summary">
        <div class="footer-terms">
            <div><span class="font-bold">Amount Chargeable (in words):</span></div>
            <div class="font-bold" style="margin-bottom: 10px; font-size:12px;">INR <?php echo getIndianCurrency((float)$inv['total_amount']); ?></div>
            
            <div class="font-bold text-decoration-underline">Declaration / Terms & Conditions:</div>
            <div><?php echo nl2br(htmlspecialchars($settings['terms_conditions'] ?? '')); ?></div>
            
            <!-- NEW: DYNAMIC QR CODE DISPLAY (ALWAYS SHOWS) -->
            <div class="qr-box">
                <div class="flex-container">
                    <div style="width: 130px; text-align: left;">
                        <img src="<?php echo $qr_image_url; ?>" alt="UPI QR Code" style="width: 120px; height: 120px;">
                    </div>
                    <div style="flex-grow: 1; text-align: left; padding-top: 25px;">
                        <div class="font-bold" style="font-size: 14px; margin-bottom: 5px;">Scan to Pay via UPI</div>
                        <div class="font-bold" style="font-size: 18px;">₹ <?php echo number_format($qr_amount, 2); ?></div>
                        <div style="font-size: 10px; margin-top: 5px;">GPay, PhonePe, Paytm, BHIM</div>
                    </div>
                </div>
            </div>
            
        </div>
        
        <div class="footer-totals">
            <div class="totals-row">
                <div>Taxable Value</div>
                <div><?php echo number_format($subtotal, 2); ?></div>
            </div>
            <div class="totals-row">
                <div>Total Tax (GST)</div>
                <div><?php echo number_format($total_gst, 2); ?></div>
            </div>
            <?php if((float)$inv['discount'] > 0): ?>
            <div class="totals-row">
                <div>Discount</div>
                <div>- <?php echo number_format($inv['discount'], 2); ?></div>
            </div>
            <?php endif; ?>
            <div class="totals-row" style="border-bottom: none; border-top: 1px solid #000; padding: 10px 5px;">
                <div style="font-size: 14px;">Total Invoice Value</div>
                <div style="font-size: 14px;" class="font-bold">₹ <?php echo number_format($inv['total_amount'], 2); ?></div>
            </div>
            
            <!-- Show what was paid if partial -->
            <?php if((float)$inv['paid_amount'] > 0 && $due_amount > 0): ?>
            <div class="totals-row" style="color: #666; font-size: 11px; border-bottom: none;">
                <div>Amount Paid</div>
                <div>- ₹ <?php echo number_format($inv['paid_amount'], 2); ?></div>
            </div>
            <div class="totals-row" style="border-bottom: 1px solid #000; padding-bottom: 10px;">
                <div class="font-bold">Balance Due</div>
                <div class="font-bold">₹ <?php echo number_format($due_amount, 2); ?></div>
            </div>
            <?php endif; ?>
            
            <div class="signature-box border-top">
                <div style="font-size: 10px;">for <?php echo htmlspecialchars($settings['company_name'] ?? 'Your Company'); ?></div>
                <div class="signature-text">Authorised Signatory</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>