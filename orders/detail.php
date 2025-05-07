<?php
// orders/detail.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    header('Location: ' . BASE_URL . '/orders/');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get order details with authorization check
$stmt = $conn->prepare("
    SELECT o.*, 
           s.store_name, s.phone_number as store_phone,
           u.full_name as customer_name, u.phone_number as customer_phone
    FROM orders o
    JOIN stores s ON o.store_id = s.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND (o.user_id = ? OR s.farmer_id = ?)
");
$stmt->execute([$order_id, $_SESSION['user_id'], $_SESSION['user_id']]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ' . BASE_URL . '/orders/');
    exit;
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name, p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle order status updates (for farmers)
if (isFarmer() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $new_status = $_POST['status'];
        if (!in_array($new_status, ['Processing', 'Shipped', 'Delivered', 'Cancelled'])) {
            throw new Exception('Invalid status');
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        setFlashMessage('success', 'Order status updated successfully');
        header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $order_id);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Details - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-2xl font-semibold text-accesibel-text-color-2">
                Order #<?php echo $order['id']; ?>
            </h1>
            <a href="<?php echo BASE_URL; ?>/orders/" 
               class="text-primary hover:text-border-separator-3">
                Back to Orders
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Information -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-border-separator-1">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <span class="text-accesibel-text-color-3">Order Date:</span>
                                <p class="text-accesibel-text-color-2 font-medium">
                                    <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                </p>
                            </div>
                            <span class="inline-flex px-3 py-1 rounded-full text-sm
                                <?php echo match($order['status']) {
                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                    'Processing' => 'bg-blue-100 text-blue-800',
                                    'Shipped' => 'bg-purple-100 text-purple-800',
                                    'Delivered' => 'bg-green-100 text-green-800',
                                    'Cancelled' => 'bg-red-100 text-red-800'
                                }; ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </div>
                        
                        <?php if (isFarmer()): ?>
                            <div class="mt-4">
                                <h3 class="font-medium text-accesibel-text-color-2 mb-2">Update Order Status</h3>
                                <form method="POST" class="flex space-x-4">
                                    <select name="status" class="border border-border-separator-1 rounded-md px-3 py-2">
                                        <option value="Processing" <?php echo $order['status'] === 'Processing' ? 'selected' : ''; ?>>
                                            Processing
                                        </option>
                                        <option value="Shipped" <?php echo $order['status'] === 'Shipped' ? 'selected' : ''; ?>>
                                            Shipped
                                        </option>
                                        <option value="Delivered" <?php echo $order['status'] === 'Delivered' ? 'selected' : ''; ?>>
                                            Delivered
                                        </option>
                                        <option value="Cancelled" <?php echo $order['status'] === 'Cancelled' ? 'selected' : ''; ?>>
                                            Cancelled
                                        </option>
                                    </select>
                                    <button type="submit" 
                                            class="bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                                        Update Status
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-6 space-y-6">
                        <!-- Order Items -->
                        <div>
                            <h3 class="font-medium text-accesibel-text-color-2 mb-4">Order Items</h3>
                            <div class="space-y-4">
                                <?php foreach ($items as $item): ?>
                                    <div class="flex items-center space-x-4">
                                        <img src="<?php echo BASE_URL; ?>/uploads/products/<?php echo $item['image'] ?: 'default.jpg'; ?>"
                                             alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             class="w-16 h-16 object-cover rounded-md">
                                        <div class="flex-1">
                                            <h4 class="text-accesibel-text-color-2">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h4>
                                            <p class="text-sm text-accesibel-text-color-3">
                                                Quantity: <?php echo $item['quantity']; ?> × 
                                                Rp <?php echo number_format($item['price_per_unit'], 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                        <p class="text-accesibel-text-color-2 font-medium">
                                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Total Calculation -->
                        <div class="border-t border-border-separator-1 pt-4">
                            <div class="flex justify-end">
                                <div class="w-64">
                                    <div class="flex justify-between py-2">
                                        <span class="text-accesibel-text-color-3">Subtotal:</span>
                                        <span class="text-accesibel-text-color-2 font-medium">
                                            Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                        </span>
                                    </div>
                                    <div class="flex justify-between py-2 text-lg font-semibold">
                                        <span class="text-accesibel-text-color-2">Total:</span>
                                        <span class="text-primary">
                                            Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Details Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Shipping Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-medium text-accesibel-text-color-2 mb-4">Shipping Information</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="text-sm text-accesibel-text-color-3">Delivery Address:</label>
                            <p class="text-accesibel-text-color-2"><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                        </div>
                        <div>
                            <label class="text-sm text-accesibel-text-color-3">Phone Number:</label>
                            <p class="text-accesibel-text-color-2"><?php echo htmlspecialchars($order['phone_number']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Customer/Store Information -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <?php if (isFarmer()): ?>
                        <h3 class="font-medium text-accesibel-text-color-2 mb-4">Customer Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm text-accesibel-text-color-3">Name:</label>
                                <p class="text-accesibel-text-color-2"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-accesibel-text-color-3">Phone Number:</label>
                                <p class="text-accesibel-text-color-2"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <h3 class="font-medium text-accesibel-text-color-2 mb-4">Store Information</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm text-accesibel-text-color-3">Store Name:</label>
                                <p class="text-accesibel-text-color-2"><?php echo htmlspecialchars($order['store_name']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-accesibel-text-color-3">Contact Number:</label>
                                <p class="text-accesibel-text-color-2"><?php echo htmlspecialchars($order['store_phone']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Timeline -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="font-medium text-accesibel-text-color-2 mb-4">Order Status</h3>
                    <div class="space-y-4">
                        <div class="relative pb-8">
                            <div class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-border-separator-1"></div>
                            <div class="relative flex items-start group">
                                <div class="h-8 w-8 rounded-full bg-primary flex items-center justify-center ring-8 ring-white">
                                    <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <h4 class="text-accesibel-text-color-2 font-medium">Order Placed</h4>
                                    <p class="text-sm text-accesibel-text-color-3">
                                        <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <?php
                        $statuses = ['Processing', 'Shipped', 'Delivered'];
                        $currentFound = false;
                        foreach ($statuses as $status):
                            $isActive = $order['status'] === $status || 
                                      ($order['status'] === 'Delivered' && $status !== 'Delivered');
                            $isCurrent = $order['status'] === $status && !$currentFound;
                            if ($isCurrent) $currentFound = true;
                        ?>
                            <div class="relative pb-8 <?php echo end($statuses) === $status ? 'pb-0' : ''; ?>">
                                <?php if (!end($statuses) === $status): ?>
                                    <div class="absolute top-4 left-4 -ml-px h-full w-0.5 <?php echo $isActive ? 'bg-primary' : 'bg-border-separator-1'; ?>"></div>
                                <?php endif; ?>
                                <div class="relative flex items-start group">
                                    <div class="h-8 w-8 rounded-full <?php echo $isActive ? 'bg-primary' : 'bg-border-separator-1'; ?> 
                                                flex items-center justify-center ring-8 ring-white">
                                        <?php if ($isActive): ?>
                                            <svg class="h-4 w-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-accesibel-text-color-2 font-medium"><?php echo $status; ?></h4>
                                        <?php if ($isCurrent): ?>
                                            <p class="text-sm text-primary">Current Status</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>