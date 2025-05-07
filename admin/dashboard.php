<?php
// admin/dashboard.php
require_once $_SERVER['DOCUMENT_ROOT'] . '/local-farmer-connect/config/database.php';

if (!isLoggedIn() || !isAdmin()) {
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Dashboard Statistics
$stats = [
    'users' => $conn->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'farmers' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'Farmer'")->fetchColumn(),
    'products' => $conn->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders' => $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue' => $conn->query("SELECT SUM(total_amount) FROM orders WHERE status != 'Cancelled'")->fetchColumn(),
    'pending_orders' => $conn->query("SELECT COUNT(*) FROM orders WHERE status = 'Pending'")->fetchColumn()
];

// Recent Orders
$stmt = $conn->query("
    SELECT o.*, u.full_name, s.store_name 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN stores s ON o.store_id = s.id
    ORDER BY o.created_at DESC LIMIT 5
");
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top Products
$stmt = $conn->query("
    SELECT p.*, s.store_name, COUNT(oi.id) as order_count
    FROM products p
    JOIN stores s ON p.store_id = s.id
    LEFT JOIN order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY order_count DESC
    LIMIT 5
");
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Local Farmer Connect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/dist/output.css">
</head>
<body class="bg-background font-sans min-h-screen">
    <?php include '../includes/admin-nav.php'; ?>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-accesibel-text-color-2 mb-8">Dashboard Overview</h1>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Total Users</h3>
                <p class="text-3xl font-bold text-accesibel-text-color-2"><?php echo number_format($stats['users']); ?></p>
                <p class="text-sm text-accesibel-text-color-3 mt-2">Farmers: <?php echo number_format($stats['farmers']); ?></p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Total Revenue</h3>
                <p class="text-3xl font-bold text-primary">
                    Rp <?php echo number_format($stats['revenue'] ?? 0, 0, ',', '.'); ?>
                </p>
                <p class="text-sm text-accesibel-text-color-3 mt-2">
                    Orders: <?php echo number_format($stats['orders']); ?>
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-accesibel-text-color-3 text-sm mb-2">Pending Orders</h3>
                <p class="text-3xl font-bold text-yellow-600">
                    <?php echo number_format($stats['pending_orders']); ?>
                </p>
                <p class="text-sm text-accesibel-text-color-3 mt-2">Need attention</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-border-separator-1 p-4">
                    <h2 class="text-xl font-semibold text-accesibel-text-color-2">Recent Orders</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="flex justify-between items-center border-b border-border-separator-1 pb-4 last:border-b-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-accesibel-text-color-2">
                                        Order #<?php echo $order['id']; ?>
                                    </p>
                                    <p class="text-sm text-accesibel-text-color-3">
                                        <?php echo htmlspecialchars($order['full_name']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-primary font-medium">
                                        Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                                    </p>
                                    <span class="inline-block px-2 py-1 text-xs rounded-full 
                                        <?php echo match($order['status']) {
                                            'Pending' => 'bg-yellow-100 text-yellow-800',
                                            'Processing' => 'bg-blue-100 text-blue-800',
                                            'Delivered' => 'bg-green-100 text-green-800',
                                            'Cancelled' => 'bg-red-100 text-red-800',
                                            default => 'bg-gray-100 text-gray-800'
                                        }; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Top Products -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="border-b border-border-separator-1 p-4">
                    <h2 class="text-xl font-semibold text-accesibel-text-color-2">Top Products</h2>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        <?php foreach ($top_products as $product): ?>
                            <div class="flex justify-between items-center border-b border-border-separator-1 pb-4 last:border-b-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-accesibel-text-color-2">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </p>
                                    <p class="text-sm text-accesibel-text-color-3">
                                        <?php echo htmlspecialchars($product['store_name']); ?>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-primary font-medium">
                                        <?php echo number_format($product['order_count']); ?> orders
                                    </p>
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