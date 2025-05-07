<?php
// orders/index.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get orders based on user role
$query = isCustomer() ? 
    "SELECT o.*, s.store_name, 
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
     FROM orders o
     JOIN stores s ON o.store_id = s.id
     WHERE o.user_id = ?
     ORDER BY o.created_at DESC" :
    "SELECT o.*, u.full_name as customer_name, 
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
     FROM orders o
     JOIN users u ON o.user_id = u.id
     JOIN stores s ON o.store_id = s.id
     WHERE s.farmer_id = ?
     ORDER BY o.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group orders by status
$grouped_orders = [
    'Pending' => [],
    'Processing' => [],
    'Shipped' => [],
    'Delivered' => [],
    'Cancelled' => []
];

foreach ($orders as $order) {
    $grouped_orders[$order['status']][] = $order;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>My Orders - Local Farmer Connect</title>
</head>
<body class="bg-background">
    <?php require_once '../includes/header.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-semibold text-accesibel-text-color-2 mb-8">
            <?php echo isCustomer() ? 'My Orders' : 'Customer Orders'; ?>
        </h1>

        <?php if (empty($orders)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <h3 class="text-xl text-accesibel-text-color-2 mb-4">No orders found</h3>
                <?php if (isCustomer()): ?>
                    <p class="text-accesibel-text-color-3 mb-6">Start shopping to see your orders here.</p>
                    <a href="<?php echo BASE_URL; ?>/products.php" 
                       class="bg-primary text-white px-6 py-2 rounded-md hover:bg-border-separator-3">
                        Browse Products
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Order Status Tabs -->
            <div class="mb-8">
                <div class="border-b border-border-separator-1">
                    <nav class="-mb-px flex space-x-8">
                        <?php foreach ($grouped_orders as $status => $status_orders): ?>
                            <a href="#<?php echo strtolower($status); ?>"
                               class="border-b-2 py-4 px-1 <?php echo !empty($status_orders) ? 
                                   'border-primary text-primary' : 
                                   'border-transparent text-accesibel-text-color-3'; ?>">
                                <?php echo $status; ?> 
                                (<?php echo count($status_orders); ?>)
                            </a>
                        <?php endforeach; ?>
                    </nav>
                </div>
            </div>

            <!-- Orders List -->
            <?php foreach ($grouped_orders as $status => $status_orders): ?>
                <?php if (!empty($status_orders)): ?>
                    <div id="<?php echo strtolower($status); ?>" class="space-y-6">
                        <?php foreach ($status_orders as $order): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-accesibel-text-color-2">
                                                Order #<?php echo $order['id']; ?>
                                            </h3>
                                            <p class="text-accesibel-text-color-3">
                                                <?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex px-2 py-1 text-xs rounded-full
                                                <?php echo match($order['status']) {
                                                    'Pending' => 'bg-yellow-100 text-yellow-800',
                                                    'Processing' => 'bg-blue-100 text-blue-800',
                                                    'Shipped' => 'bg-purple-100 text-purple-800',
                                                    'Delivered' => 'bg-green-100 text-green-800',
                                                    'Cancelled' => 'bg-red-100 text-red-800'
                                                }; ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                            <p class="text-xl font-semibold text-primary mt-2">
                                                Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <div>
                                            <?php if (isCustomer()): ?>
                                                <p class="text-accesibel-text-color-3">
                                                    From: <?php echo htmlspecialchars($order['store_name']); ?>
                                                </p>
                                            <?php else: ?>
                                                <p class="text-accesibel-text-color-3">
                                                    Customer: <?php echo htmlspecialchars($order['customer_name']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="text-accesibel-text-color-3">
                                                <?php echo $order['item_count']; ?> items
                                            </p>
                                        </div>
                                        <div class="space-x-4">
                                            <a href="<?php echo BASE_URL; ?>/orders/detail.php?id=<?php echo $order['id']; ?>"
                                               class="text-primary hover:text-border-separator-3">
                                                View Details
                                            </a>
                                            <?php if ($order['status'] === 'Delivered' && isCustomer() && 
                                                      !isset($order['has_review'])): ?>
                                                <a href="<?php echo BASE_URL; ?>/reviews/create.php?order_id=<?php echo $order['id']; ?>"
                                                   class="text-primary hover:text-border-separator-3">
                                                    Write Review
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>