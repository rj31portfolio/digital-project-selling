<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $functions->redirect('index.php');
}

$orderId = intval($_GET['id']);

// For logged-in users, verify they own this order
if ($auth->isLoggedIn()) {
    $order = $functions->getOrderDetails($orderId, $_SESSION['user_id']);
} else {
    // For admin access (you might want to add additional security here)
    $order = $functions->getOrderDetails($orderId);
}

if (!$order) {
    $functions->redirect('index.php');
}

// Generate invoice HTML
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $orderId; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .company-info {
            flex: 1;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-title {
            text-align: center;
            margin: 20px 0;
            font-size: 24px;
            font-weight: bold;
        }
        .customer-info {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .total-row {
            font-weight: bold;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .text-right {
            text-align: right;
        }
        .text-danger {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="company-info">
                <h2><?php echo $functions->getSetting('company_name'); ?></h2>
                <p><?php echo nl2br($functions->getSetting('company_address')); ?></p>
            </div>
            <div class="invoice-info">
                <h3>INVOICE</h3>
                <p><strong>Invoice #:</strong> <?php echo $functions->generateInvoiceNumber(); ?></p>
                <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
            </div>
        </div>
        
        <div class="invoice-title">
            INVOICE
        </div>
        
        <div class="customer-info">
            <h4>Bill To:</h4>
            <p><strong><?php echo $order['user_name']; ?></strong></p>
            <p><?php echo $order['user_email']; ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $order['title']; ?></td>
                    <td class="text-right">₹<?php echo number_format($order['price'], 2); ?></td>
                </tr>
                <?php if ($order['coupon_code']): ?>
                    <tr>
                        <td>Discount (<?php echo $order['coupon_discount']; ?>% - <?php echo $order['coupon_code']; ?>)</td>
                        <td class="text-right text-danger">-₹<?php echo number_format($order['price'] * ($order['coupon_discount'] / 100), 2); ?></td>
                    </tr>
                    <tr>
                        <td>Subtotal after discount</td>
                        <td class="text-right">₹<?php echo number_format($order['price'] - ($order['price'] * ($order['coupon_discount'] / 100)), 2); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td>GST (<?php echo $order['gst_percent']; ?>%)</td>
                    <td class="text-right">₹<?php echo number_format($order['gst_amount'], 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Total Amount</td>
                    <td class="text-right">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="payment-info">
            <h4>Payment Information:</h4>
            <p><strong>Payment ID:</strong> <?php echo $order['razorpay_payment_id'] ?: 'N/A'; ?></p>
            <p><strong>Payment Status:</strong> <?php echo ucfirst($order['status']); ?></p>
        </div>
        
        <div class="footer">
            <p>Thank you for your business!</p>
            <p>If you have any questions about this invoice, please contact our support team.</p>
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// Output the HTML (you could also use a PDF library here to generate a PDF)
echo $html;
?>