<?php
// admin/order-detail.php
require_once '../config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, 
           u.full_name as customer_name,
           u.email as customer_email,
           s.store_name,
           s.phone_number as store_phone,
           f.full_name as farmer_name,
           f.email as farmer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN stores s ON o.store_id = s.id
    JOIN users f ON s.farmer_id = f.id
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: ' . BASE_URL . '/admin/orders.php');
    exit;
}

// Get order items
$stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$order_id]);
$order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $new_status = $_POST['status'];
        $admin_notes = trim($_POST['admin_notes'] ?? '');

        $stmt = $conn->prepare("
            UPDATE orders 
            SET status = ?, 
                admin_notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $admin_notes, $order_id]);

        // Log the status change
        $stmt = $conn->prepare("
            INSERT INTO order_status_logs (
                order_id, 
                status, 
                notes,
                updated_by
            ) VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $order_id,
            $new_status,
            $admin_notes,
            $_SESSION['user_id']
        ]);

        setFlashMessage('success', 'Order status updated successfully');
        header('Location: ' . BASE_URL . '/admin/order-detail.php?id=' . $order_id);
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get status history
$stmt = $conn->prepare("
    SELECT osl.*, u.full_name as updated_by_name
    FROM order_status_logs osl
    JOIN users u ON osl.updated_by = u.id
    WHERE osl.order_id = ?
    ORDER BY osl.created_at DESC
");
$stmt->execute([$order_id]);
$status_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Order Detail #<?php echo $order_id; ?> - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
<body class="bg-background">
    <?php require_once '../includes/admin-nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-semibold text-accesibel-text-color-2">
                    Order #<?php echo $order_id; ?>
                </h1>
                <p class="text-accesibel-text-color-3">
                    <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?>
                </p>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/orders.php" 
               class="text-primary hover:text-border-separator-3">
                ← Back to Orders
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Order Info -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Order Items -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-border-separator-1">
                        <h2 class="text-lg font-semibold text-accesibel-text-color-2">Order Items</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($order_items as $item): ?>
                                <div class="flex items-center">
                                    <img src="<?php echo getImageUrl($item['product_image'], 'product'); ?>"
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                         class="w-16 h-16 object-cover rounded-md mr-4">
                                    <div class="flex-1">
                                        <h3 class="text-accesibel-text-color-2 font-medium">
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        </h3>
                                        <p class="text-sm text-accesibel-text-color-3">
                                            <?php echo $item['quantity']; ?> × Rp <?php echo number_format($item['price_per_unit'], 0, ',', '.'); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium text-primary">
                                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-6 pt-6 border-t border-border-separator-1">
                            <div class="flex justify-end">
                                <div class="text-right">
                                    <p class="text-accesibel-text-color-3">Total Amount:</p>
                                    <p class="text-2xl font-bold text-primary">
                                        Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status History -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-border-separator-1">
                        <h2 class="text-lg font-semibold text-accesibel-text-color-2">Status History</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <?php foreach ($status_logs as $log): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center justify-center h-8 w-8 rounded-full
                                            <?php echo match($log['status']) {
                                                'Pending' => 'bg-yellow-100 text-yellow-800',
                                                'Processing' => 'bg-blue-100 text-blue-800',
                                                'Shipped' => 'bg-purple-100 text-purple-800',
                                                'Delivered' => 'bg-green-100 text-green-800',
                                                'Cancelled' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            }; ?>">
                                            <?php echo substr($log['status'], 0, 1); ?>
                                        </span>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <p class="text-sm font-medium text-accesibel-text-color-2">
                                            Status changed to "<?php echo $log['status']; ?>"
                                        </p>
                                        <?php if ($log['notes']): ?>
                                            <p class="mt-1 text-sm text-accesibel-text-color-3">
                                                Note: <?php echo htmlspecialchars($log['notes']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <p class="mt-1 text-xs text-accesibel-text-color-3">
                                            by <?php echo htmlspecialchars($log['updated_by_name']); ?> on 
                                            <?php echo date('d M Y H:i', strtotime($log['created_at'])); ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Status Update -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-border-separator-1">
                        <h2 class="text-lg font-semibold text-accesibel-text-color-2">Update Status</h2>
                    </div>
                    <div class="p-6">
                        <form method="POST">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-accesibel-text-color-2 mb-2">
                                        Current Status
                                    </label>
                                    <span class="inline-flex px-2 py-1 text-sm rounded-full
                                        <?php echo match($order['status']) {
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'Processing' => 'bg-blue-100 text-blue-800',
                                            'Shipped' => 'bg-purple-100 text-purple-800',
                                            'Delivered' => 'bg-green-100 text-green-800',
                                            'Cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        }; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-accesibel-text-color-2 mb-2">
                                        New Status
                                    </label>
                                    <select name="status" required
                                            class="w-full border border-border-separator-1 rounded-md px-3 py-2">
                                        <option value="">Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Processing">Processing</option>
                                        <option value="Shipped">Shipped</option>
                                        <option value="Delivered">Delivered</option>
                                        <option value="Cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-accesibel-text-color-2 mb-2">
                                        Notes
                                    </label>
                                    <textarea name="admin_notes" rows="3"
                                              class="w-full border border-border-separator-1 rounded-md px-3 py-2"
                                              placeholder="Add any notes about this status change"></textarea>
                                </div>

                                <button type="submit" name="update_status"
                                        class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-border-separator-3">
                                    Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Customer Info -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-border-separator-1">
                        <h2 class="text-lg font-semibold text-accesibel-text-color-2">Customer Information</h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Name</dt>
                                <dd class="text-accesibel-text-color-2 font-medium">
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Email</dt>
                                <dd class="text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['customer_email']); ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Phone</dt>
                                <dd class="text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['phone_number']); ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Shipping Address</dt>
                                <dd class="text-accesibel-text-color-2">
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Store Info -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-6 border-b border-border-separator-1">
                        <h2 class="text-lg font-semibold text-accesibel-text-color-2">Store Information</h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Store Name</dt>
                                <dd class="text-accesibel-text-color-2 font-medium">
                                    <?php echo htmlspecialchars($order['store_name']); ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Farmer Name</dt>
                                <dd class="text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['farmer_name']); ?>
                                </dd>
                            </div>
                            <div>
                            <dt class="text-sm text-accesibel-text-color-3">Contact Number</dt>
                                <dd class="text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['store_phone']); ?>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-accesibel-text-color-3">Email</dt>
                                <dd class="text-accesibel-text-color-2">
                                    <?php echo htmlspecialchars($order['farmer_email']); ?>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <?php if ($order['admin_notes']): ?>
                    <!-- Admin Notes -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6 border-b border-border-separator-1">
                            <h2 class="text-lg font-semibold text-accesibel-text-color-2">Admin Notes</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-accesibel-text-color-2">
                                <?php echo nl2br(htmlspecialchars($order['admin_notes'])); ?>
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Konfirmasi sebelum mengubah status
    document.querySelector('form').addEventListener('submit', function(e) {
        const newStatus = this.querySelector('[name="status"]').value;
        const currentStatus = '<?php echo $order['status']; ?>';
        
        if (newStatus === '') {
            e.preventDefault();
            alert('Please select a new status');
            return;
        }

        if (currentStatus === 'Delivered' || currentStatus === 'Cancelled') {
            if (!confirm('This order is already ' + currentStatus.toLowerCase() + '. Are you sure you want to change the status?')) {
                e.preventDefault();
                return;
            }
        }

        if (newStatus === 'Cancelled' && !confirm('Are you sure you want to cancel this order?')) {
            e.preventDefault();
            return;
        }
    });
    </script>
</body>
</html>